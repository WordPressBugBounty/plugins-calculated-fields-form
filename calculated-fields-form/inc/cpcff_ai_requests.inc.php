<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'CPCFF_AI_REQUESTS' ) ) {
    class CPCFF_AI_REQUESTS {
        // Static properties.
        private static $models;
        private static $default_provider = 'gemini';
        private static $default_model;

        // Instance properties.
        private $api_key;
        private $api_provider;
        private $model;
        private $temperature;
        private $max_tokens = 8000;
        private $timeout = 180;

        private $retry_flag = true;
        private $min_useful_output = 10; // Minimum characters to consider useful output for "fieldnameX"

        private $error_mssgs;

        // Constructor.
        public function __construct($api_key, $provider, $model, $temperature = null, $max_tokens = null) {

            // Initialize error messages
            $this->error_mssgs = [
                'insufficientTokens' => 'Request too complex for current token limit (%s tokens). The AI exhausted tokens during processing. Please try: 1) Simplify your request, or 2) Break the task into smaller parts.'
            ];

            self::init_models();
            if ( ! isset(self::$models[$provider]) ) {
                $this->error_mssgs['invalidProvider'] = 'Invalid provider: ' . $provider;
                $provider = self::$default_provider;
            }
            if ( ! isset(self::$models[$provider]['models'][$model]) ) $model = self::$models[$provider]['default_model'];

            // Intialize with API key, provider, model, and optional parameters
            $this->api_key = $api_key;
            $this->api_provider = $provider;
            $this->model = $model;
            $this->temperature = $temperature;
            $this->max_tokens = max($this->max_tokens, $max_tokens, self::$models[$provider]['models'][$model]['max_tokens']);
        }

        // Static methods.

        private static function init_models() {
            if (self::$models === null) {
                self::$models = [
                    'openai' => [
                        'title' => esc_html__('OpenAI', 'calculated-fields-form'),
                        'default_model' => 'gpt-5.4-mini',
                        'api_key_url' => 'https://platform.openai.com/api-keys',
                        'models' => [
                            'gpt-5.5-pro' => [
                                'title' => esc_html__('GPT-5.5 Pro (Most Capable)', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 120000
                            ],
                            'gpt-5.5' => [
                                'title' => esc_html__('GPT-5.5', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 120000
                            ],
                            'gpt-5.4-pro' => [
                                'title' => esc_html__('GPT-5.4 Pro', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 120000
                            ],
                            'gpt-5.4' => [
                                'title' => esc_html__('GPT-5.4', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 120000
                            ],
                            'gpt-5.4-mini' => [
                                'title' => esc_html__('GPT-5.4 Mini (Balanced)', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 120000
                            ],
							'gpt-5.4-nano' => [
								'title' => esc_html__('GPT-5.4 Nano (Fast and Cheap)', 'calculated-fields-form'),
								'form-generation' => false,
								'ai-assistant' => true,
								'max_tokens' => 120000
							]
                        ]
                    ],
                    'claude' => [
                        'title'         => esc_html__('Anthropic (Claude)', 'calculated-fields-form'),
                        'default_model' => 'claude-haiku-4-5-20251001',
                        'api_key_url'   => 'https://console.anthropic.com/settings/keys',
                        'models'        => [
//							'claude-fable-5' => [
//								'title'           => esc_html__('Claude Fable 5 (Most Powerful)', 'calculated-fields-form'),
//								'form-generation' => true,
//								'ai-assistant'    => true,
//								'max_tokens'      => 96000  // soporta hasta 128k output
//							],
                            'claude-opus-4-8' => [
                                'title' => esc_html__('Claude Opus 4.8 (Most Capable)', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'claude-opus-4-7' => [
                                'title' => esc_html__('Claude Opus 4.7', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'claude-opus-4-6' => [
                                'title' => esc_html__('Claude Opus 4.6', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'claude-opus-4-5-20251101' => [
                                'title' => esc_html__('Claude Opus 4.5', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'claude-sonnet-4-6' => [
                                'title' => esc_html__('Claude Sonnet 4.6 (Recommended)', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
							'claude-sonnet-4-5-20250929' => [
								'title'           => esc_html__('Claude Sonnet 4.5', 'calculated-fields-form'),
								'form-generation' => true,
								'ai-assistant'    => true,
								'max_tokens'      => 64000
							],
                            'claude-haiku-4-5-20251001' => [
                                'title' => esc_html__('Claude Haiku 4.5 (Fast)', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ]
                        ]
                    ],
                    'gemini' => [
                        'title'         => esc_html__('Google (Gemini)', 'calculated-fields-form'),
                        'default_model' => 'gemini-2.5-flash',
                        'api_key_url'   => 'https://aistudio.google.com/app/api-keys',
                        'models'        => [
                            'gemini-3.1-pro-preview' => [
                                'title' => esc_html__('Gemini 3.1 Pro', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'gemini-3.5-flash' => [
                                'title' => esc_html__('Gemini 3.5 Flash', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'gemini-3.1-flash-lite' => [
                                'title' => esc_html__('Gemini 3.1 Flash-Lite', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'gemini-2.5-pro' => [
                                'title' => esc_html__('Gemini 2.5 Pro', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'gemini-2.5-flash' => [
                                'title' => esc_html__('Gemini 2.5 Flash', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'gemini-2.5-flash-lite' => [
                                'title' => esc_html__('Gemini 2.5 Flash-Lite', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ]
                        ]
                    ],
                    'minimax' => [
                        'title'         => esc_html__('MiniMax', 'calculated-fields-form'),
                        'default_model' => 'MiniMax-M2.7',
                        'api_key_url'   => 'https://platform.minimax.io/user-center/basic-information',
                        'models'        => [
							'MiniMax-M3' => [
								'title'           => esc_html__('MiniMax M3 (Latest)', 'calculated-fields-form'),
								'form-generation' => true,
								'ai-assistant'    => true,
								'max_tokens'      => 64000
							],
                            'MiniMax-M2.7-highspeed' => [
                                'title' => esc_html__('MiniMax M2.7 Highspeed', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'MiniMax-M2.7' => [
                                'title' => esc_html__('MiniMax M2.7', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'MiniMax-M2.5-highspeed' => [
                                'title' => esc_html__('MiniMax M2.5 Highspeed', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'MiniMax-M2.5' => [
                                'title' => esc_html__('MiniMax M2.5', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'MiniMax-M2.1-highspeed' => [
                                'title' => esc_html__('MiniMax M2.1 Highspeed', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'MiniMax-M2.1' => [
                                'title' => esc_html__('MiniMax M2.1', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'MiniMax-M2' => [
                                'title' => esc_html__('MiniMax M2', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ]
                        ]
                    ],
                    'kimi' => [
                        'title'         => esc_html__('Kimi (Moonshot AI)', 'calculated-fields-form'),
                        'default_model' => 'kimi-k2.5',
                        'api_key_url'   => 'https://platform.moonshot.ai/console/api-keys',
                        'models'        => [
                            'kimi-k2.6' => [
                                'title' => esc_html__('Kimi K2.6', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'kimi-k2.5' => [
                                'title' => esc_html__('Kimi K2.5', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'moonshot-v1-128k' => [
                                'title' => esc_html__('Moonshot V1 128K', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'moonshot-v1-32k' => [
                                'title' => esc_html__('Moonshot V1 32K', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 32768
                            ],
                            'moonshot-v1-8k' => [
                                'title' => esc_html__('Moonshot V1 8K', 'calculated-fields-form'),
                                'form-generation' => false,
                                'ai-assistant' => true,
                                'max_tokens' => 8192
                            ]
                        ]
                    ],
                    'deepseek' => [
                        'title'         => esc_html__('DeepSeek', 'calculated-fields-form'),
                        'default_model' => 'deepseek-v4-flash',
                        'api_key_url'   => 'https://platform.deepseek.com/api_keys',
                        'models'        => [
                            'deepseek-v4-pro' => [
                                'title' => esc_html__('DeepSeek V4 Pro', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ],
                            'deepseek-v4-flash' => [
                                'title' => esc_html__('DeepSeek V4 Flash', 'calculated-fields-form'),
                                'form-generation' => true,
                                'ai-assistant' => true,
                                'max_tokens' => 64000
                            ]
                        ]
                    ]
                ];
                self::$default_model = self::$models[self::$default_provider]['default_model'];

                // Check if there are models registered using the WordPress Connector API.
                if ( function_exists('wp_ai_client_prompt') ) {
                    $builder = wp_ai_client_prompt('test');
                    if ($builder->is_supported_for_text_generation()) {
                        self::$default_provider = 'wordpress-ai';
                        self::$models['wordpress-ai'] = [
                            'title'         => esc_html__('WordPress AI Connector', 'calculated-fields-form'),
                            'default_model' => '*',
                            'api_key_url'   => admin_url('options-connectors.php'),
                            'models'        => [
                                '*' => [
                                    'form-generation' => false,
                                    'ai-assistant' => true,
                                    'max_tokens' => 64000
                                ]
                            ]
                        ];
                    }
                }
            }
        }

        public static function get_models() {
            self::init_models();
            return self::$models;
        }

        public static function get_provider_from_model($model) {
            self::init_models();
            foreach (self::$models as $provider => $data) {
                if (array_key_exists($model, $data['models'])) {
                    return $provider;
                }
            }
            return null; // Model not found
        }

        public static function get_models_for_provider($provider) {
            self::init_models();
            return isset( self::$models[$provider] ) ? self::$models[$provider]['models'] : [];
        }

        public static function get_default_provider() {
            self::init_models();
            return self::$default_provider;
        }

        public static function get_default_model() {
            self::init_models();
            return self::$default_model;
        }

        // Instance methods.

        /**
         * Convert natural language description to JavaScript formula
         * This runs ONLY in admin when creating/editing the form
         */
        public function request($prompt, $context) {
            // Reset continuation state for new requests.
            if ( ! empty( $this->error_mssgs['invalidProvider'] ) ) {
                return ['error' => $this->error_mssgs['invalidProvider']];
            }
            switch($this->api_provider) {
                case 'openai':
                    return $this->call_openai($prompt, $context);
                case 'gemini':
                    return $this->call_gemini($prompt, $context);
                case 'claude':
                    return $this->call_claude($prompt, $context);
                case 'minimax':
                    return $this->call_minimax($prompt, $context);
                case 'kimi':
                    return $this->call_kimi($prompt, $context);
                case 'deepseek':
                    return $this->call_deepseek($prompt, $context);
                case 'wordpress-ai':
                    return $this->call_wordpress_ai($prompt, $context);
                default:
                    return ['error' => 'Unknown provider'];
            }
        }

        /***************************** PRIVATE METHODS *****************************/

        /**
         * Check if we got substantial output before attempting continuation
         */
        private function has_substantial_output($content) {
            return strlen(trim($content)) >= $this->min_useful_output;
        }

        /**
         * Call WordPress AI connector
         */
        private function call_wordpress_ai($prompt, $context) {
			$_set_timeout = function($timeout){ return 120; };
			add_filter('wp_ai_client_default_request_timeout', $_set_timeout, 999);

			try {
				$response = wp_ai_client_prompt($prompt)
					->using_system_instruction($context)
					->generate_text();
			} finally {
				remove_filter('wp_ai_client_default_request_timeout', $_set_timeout, 999);
			}

            if (is_wp_error($response)) {
                return ['error' => $response->get_error_message()];
            }

            return [
                'response' => $response
            ];
        }

        /**
         * Call OpenAI API with automatic continuation handling
         */
        private function call_openai($prompt, $context) {
            $url = 'https://api.openai.com/v1/chat/completions';

            // Use specified model or default
            $model = $this->model ?: self::$models['openai']['default_model'];

            // Build messages array
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

            $data = [
                'model' => $model,
                'messages' => $messages,
                'max_completion_tokens' => $this->max_tokens,
                'response_format' => ['type' => 'json_object']
            ];

            // Send request to OpenAI API.
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
                    if ($this->retry_flag) {
                        $this->retry_flag = false;
                        $this->max_tokens *= 2;
                        return $this->call_openai($prompt, $context);
                    } else {
                        // Token limit hit but no useful output - likely spent on reasoning/processing
                        return [
                            'error' => sprintf($this->error_mssgs['insufficientTokens'], $this->max_tokens)
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
        private function call_gemini($prompt, $context) {
            // Use specified model or default
            $model = $this->model ?: self::$models['gemini']['default_model'];
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $this->api_key;

            $data = [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $context]
                    ]
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => $this->max_tokens,
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
                                'error' => sprintf($this->error_mssgs['insufficientTokens'], $this->max_tokens)
                            ];
                        }
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
         * Call MiniMax API with automatic continuation handling
         */
        private function call_minimax($prompt, $context) {
            $url = 'https://api.minimax.io/v1/chat/completions';

            $model = $this->model ?: self::$models['minimax']['default_model'];
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

            $data = [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $this->max_tokens
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

                if ($finish_reason === 'length') {
                    if ($this->retry_flag) {
                        $this->retry_flag = false;
                        $this->max_tokens *= 2;
                        return $this->call_minimax($prompt, $context);
                    } else {
                        return [
                            'error' => sprintf($this->error_mssgs['insufficientTokens'], $this->max_tokens)
                        ];
                    }
                }

                return ['response' => $response_content];
            }

            if (isset($body['error']['message'])) {
                return ['error' => $body['error']['message']];
            }

            return ['error' => 'Failed MiniMax request'];
        }

        /**
         * Call Claude (Anthropic) API with automatic continuation handling
         */
        private function call_claude($prompt, $context) {
            $url = 'https://api.anthropic.com/v1/messages';

            // Use specified model or default
            $model = $this->model ?: self::$models['claude']['default_model'];

            $data = [
                'model'      => $model,
                'max_tokens' => $this->max_tokens,
                'system'     => $context,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => $prompt
                    ]
                ]
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
                                'error' => sprintf($this->error_mssgs['insufficientTokens'], $this->max_tokens)
                            ];
                        }
                    }
                }

                return ['response' => $response_content];
            }

            if (isset($body['error']['message'])) {
                return ['error' => $body['error']['message']];
            }

            return ['error' => 'Failed Claude request'];
        }

        /**
         * Call Kimi (Moonshot AI) API with automatic continuation handling
         */
        private function call_kimi($prompt, $context) {
            $url = 'https://api.moonshot.ai/v1/chat/completions';

            $model = $this->model ?: self::$models['kimi']['default_model'];
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

            $data = [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $this->max_tokens
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

                if ($finish_reason === 'length') {
                    if ($this->retry_flag) {
                        $this->retry_flag = false;
                        $this->max_tokens *= 2;
                        return $this->call_kimi($prompt, $context);
                    } else {
                        return [
                            'error' => sprintf($this->error_mssgs['insufficientTokens'], $this->max_tokens)
                        ];
                    }
                }

                return ['response' => $response_content];
            }

            if (isset($body['error']['message'])) {
                return ['error' => $body['error']['message']];
            }

            return ['error' => 'Failed Kimi request'];
        }

        /**
         * Call DeepSeek API with automatic continuation handling
         */
        private function call_deepseek($prompt, $context) {
            $url = 'https://api.deepseek.com/v1/chat/completions';

            $model = $this->model ?: self::$models['deepseek']['default_model'];
            $messages = [
                [
                    'role'    => 'system',
                    'content' => $context
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt
                ]
            ];

            $data = [
                'model'      => $model,
                'messages'   => $messages,
                'max_tokens' => $this->max_tokens
            ];

            $response = wp_remote_post($url, [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key
                ],
                'body'    => json_encode($data),
                'timeout' => $this->timeout
            ]);

            if (is_wp_error($response)) {
                return ['error' => $response->get_error_message()];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['choices'][0]['message']['content'])) {
                $response_content = $body['choices'][0]['message']['content'];
                $finish_reason    = $body['choices'][0]['finish_reason'] ?? 'unknown';

                if ($finish_reason === 'length') {
                    if ($this->retry_flag) {
                        $this->retry_flag = false;
                        $this->max_tokens *= 2;
                        return $this->call_deepseek($prompt, $context);
                    } else {
                        return [
                            'error' => sprintf($this->error_mssgs['insufficientTokens'], $this->max_tokens)
                        ];
                    }
                }

                return ['response' => $response_content];
            }

            if (isset($body['error']['message'])) {
                return ['error' => $body['error']['message']];
            }

            return ['error' => 'Failed DeepSeek request'];
        }
    }
}