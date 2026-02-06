<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'CPCFF_AI_REQUESTS' ) ) {
    class CPCFF_AI_REQUESTS {

        private $api_key;
        private $api_provider = 'openai'; // or 'gemini', 'claude'
        private $model;
        private $temperature;
        private $max_tokens = 1500;
        private $timeout = 30;

        private $retry_flag = true;
        private $continuation_attempts = 0;
        private $max_continuation_attempts = 3;
        private $min_useful_output = 10; // Minimum characters to consider useful output for "fieldnameX"

        private $error_mssgs;

        public function __construct($api_key, $provider, $model, $temperature = null, $max_tokens = null) {
            $this->api_key = $api_key;
            $this->api_provider = $provider;
            $this->model = $model;
            $this->temperature = $temperature;
            if ($max_tokens !== null) {
                $this->max_tokens = $max_tokens;
            }

            // Initialize erro messages
            $this->error_mssgs = [
                'insufficientTokens'    => 'Request too complex for current token limit (%s tokens). The AI exhausted tokens during processing. Please try: 1) Simplify your request, or 2) Break the task into smaller parts.',
                'mayBeIncomplete'       => 'Response may be incomplete. Continuation failed: %s',
                'incomplete'            => 'Response is incomplete. Maximum continuation attempts (%s) reached.',
            ];
        }

        /**
         * Convert natural language description to JavaScript formula
         * This runs ONLY in admin when creating/editing the form
         */
        public function request($prompt, $context) {
            // Reset continuation state for new requests
            $this->continuation_attempts = 0;

            switch($this->api_provider) {
                case 'openai':
                    return $this->call_openai($prompt, $context);
                case 'gemini':
                    return $this->call_gemini($prompt, $context);
                case 'claude':
                    return $this->call_claude($prompt, $context);
                default:
                    return ['error' => 'Unknown provider'];
            }
        }

        /**
         * Check if we got substantial output before attempting continuation
         */
        private function has_substantial_output($content) {
            return strlen(trim($content)) >= $this->min_useful_output;
        }

        /**
         * Call OpenAI API with automatic continuation handling
         */
        private function call_openai($prompt, $context, $conversation_history = []) {
            $url = 'https://api.openai.com/v1/chat/completions';

            // Use specified model or default
            $model = $this->model ?: 'gpt-5-nano';

            // Build messages array
            $messages = [];

            if (empty($conversation_history)) {
                // First request
                $messages = [
                    [
                        'role' => 'system',
                        'content' => $context
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ];
            } else {
                // Continuation request - use full conversation history
                $messages = $conversation_history;
                $messages[] = [
                    'role' => 'user',
                    'content' => 'Please continue from where you left off and complete the code.'
                ];
            }

            $data = [
                'model' => $model,
                'messages' => $messages,
                'max_completion_tokens' => $this->max_tokens
            ];

            $response = wp_remote_post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key
                ],
                'body' => json_encode($data),
                'timeout' => $this->timeout
            ]);

            if (is_wp_error($response)) {
                return ['error' => $response->get_error_message()];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['choices'][0]['message']['content'])) {
                $response_content = $body['choices'][0]['message']['content'];
                $finish_reason = $body['choices'][0]['finish_reason'] ?? 'unknown';

                // Check if response was cut off due to token limit
                if ($finish_reason === 'length') {
                    // First, check if we got substantial output
                    if (!$this->has_substantial_output($response_content)) {
                        if ($this->retry_flag) {
                            $this->retry_flag = false;
                            $this->max_tokens *= 2;
                            return $this->call_openai($prompt, $context);
                        } else {
                            // Token limit hit but no useful output - likely spent on reasoning/processing
                            return [
                                'error' => printf($this->error_mssgs['insufficientTokens'], $this->max_tokens)
                            ];
                        }
                    }

                    // We have substantial output, attempt continuation
                    if ($this->continuation_attempts < $this->max_continuation_attempts) {
                        $this->continuation_attempts++;

                        // Add this response to conversation history
                        $messages[] = [
                            'role' => 'assistant',
                            'content' => $response_content
                        ];

                        // Recursively call with conversation history to get continuation
                        $continuation_result = $this->call_openai($prompt, $context, $messages);

                        if (isset($continuation_result['response'])) {
                            // Combine the responses
                            return ['response' => $response_content . $continuation_result['response']];
                        } else if (isset($continuation_result['error'])) {
                            // If continuation fails, return what we have with a warning
                            return [
                                'response' => $response_content,
                                'warning' => printf($this->error_mssgs['mayBeIncomplete'], $continuation_result['error'])
                            ];
                        }
                    } else {
                        // Max continuation attempts reached
                        return [
                            'response' => $response_content,
                            'warning' => printf($this->error_mssgs['incomplete'], $this->max_continuation_attempts)
                        ];
                    }
                }

                return ['response' => $response_content];
            }

            if (isset($body['error']['message'])) {
                return ['error' => $body['error']['message']];
            }

            return ['error' => 'Failed OpenAI request'];
        }

        /**
         * Call Google Gemini API with automatic continuation handling
         */
        private function call_gemini($prompt, $context, $previous_response = '') {
            // Use specified model or default
            $model = $this->model ?: 'gemini-pro';
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $this->api_key;

            // Build the prompt
            if (empty($previous_response)) {
                // First request
                $combined_prompt = $context . "\n\n" . $prompt;
            } else {
                // Continuation request
                $combined_prompt = "You previously provided this incomplete response:\n\n" . $previous_response . "\n\nPlease continue from where you left off and complete the code.";
            }

            $data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $combined_prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $this->temperature ?: 0.3,
                    'maxOutputTokens' => $this->max_tokens
                ]
            ];

            $response = wp_remote_post($url, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($data),
                'timeout' => $this->timeout
            ]);

            if (is_wp_error($response)) {
                return ['error' => $response->get_error_message()];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
                $response_content = $body['candidates'][0]['content']['parts'][0]['text'];
                $finish_reason = $body['candidates'][0]['finishReason'] ?? 'UNKNOWN';

                // Check if response was cut off due to token limit (MAX_TOKENS in Gemini)
                if ($finish_reason === 'MAX_TOKENS') {
                    // First, check if we got substantial output
                    if (!$this->has_substantial_output($response_content)) {
                        if ($this->retry_flag) {
                            $this->retry_flag = false;
                            $this->max_tokens *= 2;
                            return $this->call_gemini($prompt, $context);
                        } else {
                            // Token limit hit but no useful output - likely spent on reasoning/processing
                            return [
                                'error' => printf($this->error_mssgs['insufficientTokens'], $this->max_tokens)
                            ];
                        }
                    }

                    // We have substantial output, attempt continuation
                    if ($this->continuation_attempts < $this->max_continuation_attempts) {
                        $this->continuation_attempts++;

                        // Get continuation
                        $accumulated = empty($previous_response) ? $response_content : $previous_response . $response_content;
                        $continuation_result = $this->call_gemini($prompt, $context, $accumulated);

                        if (isset($continuation_result['response'])) {
                            // Combine the responses
                            return ['response' => $response_content . $continuation_result['response']];
                        } else if (isset($continuation_result['error'])) {
                            // If continuation fails, return what we have with a warning
                            return [
                                'response' => $response_content,
                                'warning' => printf($this->error_mssgs['mayBeIncomplete'], $continuation_result['error'])
                            ];
                        }
                    } else {
                        // Max continuation attempts reached
                        return [
                            'response' => $response_content,
                            'warning' => printf($this->error_mssgs['incomplete'], $this->max_continuation_attempts)
                        ];
                    }
                }

                return ['response' => $response_content];
            }

            if (isset($body['error']['message'])) {
                return ['error' => $body['error']['message']];
            }

            return ['error' => 'Failed Gemini request'];
        }

        /**
         * Call Claude (Anthropic) API with automatic continuation handling
         */
        private function call_claude($prompt, $context, $conversation_history = []) {
            $url = 'https://api.anthropic.com/v1/messages';

            // Use specified model or default
            $model = $this->model ?: 'claude-sonnet-4-20250514';

            // Build messages array
            if (empty($conversation_history)) {
                // First request - combine context into the user message
                $messages = [
                    [
                        'role' => 'user',
                        'content' => $context . "\n\n" . $prompt
                    ]
                ];
            } else {
                // Continuation request - use full conversation history
                $messages = $conversation_history;
                $messages[] = [
                    'role' => 'user',
                    'content' => 'Please continue from where you left off and complete the code.'
                ];
            }

            $data = [
                'model' => $model,
                'max_tokens' => $this->max_tokens,
                'messages' => $messages,
                'temperature' => $this->temperature ?: 0.3
            ];

            $response = wp_remote_post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->api_key,
                    'anthropic-version' => '2023-06-01'
                ],
                'body' => json_encode($data),
                'timeout' => $this->timeout
            ]);

            if (is_wp_error($response)) {
                return ['error' => $response->get_error_message()];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['content'][0]['text'])) {
                $response_content = $body['content'][0]['text'];
                $stop_reason = $body['stop_reason'] ?? 'unknown';

                // Check if response was cut off due to token limit
                if ($stop_reason === 'max_tokens') {
                    // First, check if we got substantial output
                    if (!$this->has_substantial_output($response_content)) {
                        if ($this->retry_flag) {
                            $this->retry_flag = false;
                            $this->max_tokens *= 2;
                            return $this->call_claude($prompt, $context);
                        } else {
                            // Token limit hit but no useful output - likely spent on reasoning/processing
                            return [
                                'error' => printf($this->error_mssgs['insufficientTokens'], $this->max_tokens)
                            ];
                        }
                    }

                    // We have substantial output, attempt continuation
                    if ($this->continuation_attempts < $this->max_continuation_attempts) {
                        $this->continuation_attempts++;

                        // Add this response to conversation history
                        $messages[] = [
                            'role' => 'assistant',
                            'content' => $response_content
                        ];

                        // Recursively call with conversation history to get continuation
                        $continuation_result = $this->call_claude($prompt, $context, $messages);

                        if (isset($continuation_result['response'])) {
                            // Combine the responses
                            return ['response' => $response_content . $continuation_result['response']];
                        } else if (isset($continuation_result['error'])) {
                            // If continuation fails, return what we have with a warning
                            return [
                                'response' => $response_content,
                                'warning' => printf($this->error_mssgs['mayBeIncomplete'], $continuation_result['error'])
                            ];
                        }
                    } else {
                        // Max continuation attempts reached
                        return [
                            'response' => $response_content,
                            'warning' => printf($this->error_mssgs['incomplete'], $this->max_continuation_attempts)
                        ];
                    }
                }

                return ['response' => $response_content];
            }

            if (isset($body['error']['message'])) {
                return ['error' => $body['error']['message']];
            }

            return ['error' => 'Failed Claude request'];
        }
    }
}