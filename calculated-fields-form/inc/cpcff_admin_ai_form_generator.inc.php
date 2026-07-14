<?php
if ( !is_admin() ) {
	print 'Direct access not allowed.';
	exit;
}

require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_ai_requests.inc.php';

if ( ! class_exists( 'CPCFF_AI_FORM_GENERATOR' ) ) {
	class CPCFF_AI_FORM_GENERATOR {
		static private $model = "gemini-2.5-flash-lite";

		/**
		 * Load and pretty-print the JSON Schema so the model can navigate it
		 * field-by-field rather than facing 60KB on a single line.
		 */
		static private function load_schema_pretty() {
			$raw = file_get_contents( plugin_dir_path( __FILE__ ) . '../js/schema.min.json' );
			$decoded = json_decode( $raw, true );
			if ( ! is_array( $decoded ) ) {
				throw new Exception( __('Could not parse schema.min.json. The schema file may be malformed.', 'calculated-fields-form') );
			}
			return json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		}

		/**
		 * Main inference method with caching support.
		 */
		static public function model_inference($provider, $model, $api_key, $form_description, $base_form_structure = null) {
			$schema_pretty = self::load_schema_pretty();

            $models = CPCFF_AI_REQUESTS::get_models();

            if ( ! isset( $models[ $provider ] ) ) {
                throw new Exception( __('Invalid AI provider selected.', 'calculated-fields-form') );
            }

            if ( ! isset( $models[ $provider ]['models'][$model] ) ) {
                $model = $models[ $provider ]['default_model'];
            }

            $template = '';
            if ( ! empty( $base_form_structure ) ) {
                try {
                    $form_structure_obj = json_decode($base_form_structure, true);
                    if (isset($form_structure_obj[1][0]['formtemplate'])) {
                        $template = $form_structure_obj[1][0]['formtemplate'];
                    }
                } catch (Exception $err) {}
            } else {
                if ( defined( 'CP_CALCULATEDFIELDSF_DEFAULT_template' ) ) {
                    $template = CP_CALCULATEDFIELDSF_DEFAULT_template;
                }
            }

            // Build the prompt: description FIRST, then (if modifying) the
            // current structure and preservation rules, then schema complete,
            // then critical rules + few-shot example, then final instruction.
            $prompt  = "=== USER DESCRIPTION ===\n";
            $prompt .= $form_description;
            $prompt .= "\n=== END ===\n\n";

            if ( ! empty( $base_form_structure ) ) {
                $prompt .= "=== CURRENT FORM STRUCTURE ===\n";
                $prompt .= $base_form_structure;
                $prompt .= "\n=== END ===\n\n";
                $prompt .= "The USER DESCRIPTION above lists modifications to apply to the CURRENT FORM STRUCTURE. Apply ONLY those modifications. Every field, value, or structure not mentioned must remain exactly as it is in the current form structure. Do not add, remove, or change anything that isn't explicitly referenced.\n\n";
            } else {
                $prompt .= "Generate a form structure that satisfies the description above.\n\n";
            }

            $prompt .= "=== SCHEMA ===\n";
            $prompt .= $schema_pretty;
            $prompt .= "\n=== END ===\n\n";

            $prompt .= "CRITICAL RULES (any violation will break the result):\n";
            $prompt .= "1. Wrap your entire output in <json>...</json> delimiters. NOTHING else may appear outside these delimiters.\n";
            $prompt .= "2. Inside the <json> delimiters, output a valid JSON array with EXACTLY two top-level elements: [fields_array, settings_array]. fields_array is a plain JSON array of field objects (NOT wrapped in any object). settings_array is a plain JSON array containing EXACTLY one settings object.\n";
            $prompt .= "3. Use ONLY property names that exist in the schema above. NEVER invent property names. If you see a property like 'currencySymbol', 'thousandSeparator', 'top_aligned', or 'cp_cff_natural' in your training data but it is NOT in the schema, OMIT it.\n";
            $prompt .= "4. Every value MUST match the type and enum declared in the schema. Numbers for number properties. Booleans (true/false) for boolean properties. Strings for string properties. Arrays of the right shape for array properties.\n";
            $prompt .= "5. Omit null values and empty defaults. Only include properties you actually need to set.\n";
            $prompt .= "6. NO thinking out loud. NO explanations. NO markdown code fences. NO comments. The <json> delimiters are the ONLY thing in your response.\n\n";
            /*
			$prompt .= "Example of the EXACT output format expected (do not deviate):\n";
            $prompt .= "<json>\n";
            $prompt .= "[\n";
            $prompt .= "  [{\"name\":\"fieldname1\",\"ftype\":\"ftext\",\"title\":\"Name\",\"required\":true},{\"name\":\"fieldname2\",\"ftype\":\"femail\",\"title\":\"Email\",\"required\":true},{\"name\":\"fieldname_btn\",\"ftype\":\"fButton\",\"sType\":\"submit\",\"sValue\":\"Send\"}],\n";
            $prompt .= "  [{\"title\":\"My Form\",\"formlayout\":\"0\",\"formtemplate\":\"default\"}]\n";
            $prompt .= "]\n";
            $prompt .= "</json>\n\n";
			*/
            $prompt .= "Now generate the form described above. Output ONLY <json>...</json>:\n";

            $context = "You are a strict JSON form generator. You ALWAYS output structured data wrapped in <json>...</json> delimiters. You NEVER think out loud, never explain, never use markdown code fences. You NEVER invent property names \xe2\x80\x94 you only use properties that appear in the schema provided in the user message. You NEVER omit required properties. You NEVER include null values or empty defaults. Your entire response is exactly one <json>...</json> block.";

            $aiRequest = new CPCFF_AI_REQUESTS($api_key, $provider, $model, 0.0, 8000);

            // Retry loop (max 2 attempts) for transient malformed output.
            $decoded = null;
            $last_raw = '';
            for ( $attempt = 1; $attempt <= 2; $attempt++ ) {
                $response = $aiRequest->request( $prompt, $context );
                if ( empty( $response ) || ! empty( $response['error'] ) ) {
                    $error_message = ! empty( $response['error'] ) ? $response['error'] : __('Unknown error from AI model.', 'calculated-fields-form');
                    if ( $attempt == 2 ) {
                        throw new Exception( __('AI Model Error: ', 'calculated-fields-form') . $error_message );
                    }
                    continue;
                }

                $last_raw = isset( $response['response'] ) ? $response['response'] : '';

                // Extract JSON robustly: handle markdown fences (anywhere), the
                // <json> delimiters we asked for, and raw JSON as a last resort.
                $candidate = $last_raw;
                if ( preg_match_all( '/```json\s*(.*?)\s*```/s', $last_raw, $fence_matches ) ) {
                    $candidate = end( $fence_matches[1] );
                } elseif ( preg_match_all( '/<json>(.*?)<\/json>/s', $last_raw, $delim_matches ) ) {
                    $candidate = end( $delim_matches[1] );
                } else {
                    $candidate = $last_raw;
                }
                $candidate = trim( $candidate );

                $decoded = json_decode( $candidate, true );
                if ( is_array( $decoded ) && count( $decoded ) === 2 && is_array( $decoded[0] ) && is_array( $decoded[1] ) && count( $decoded[1] ) === 1 ) {
                    break;
                }
                $decoded = null;
            }

            if ( $decoded === null ) {
                throw new Exception(
                    __('AI model output is not a valid [fields_array, settings_array] structure after retries. Raw response (first 500 chars): ', 'calculated-fields-form')
                    . substr( $last_raw, 0, 500 )
                );
            }

            if ( ! empty( $template ) && stripos( $form_description, 'template' ) === false ) {
                try {
                    if ( isset( $decoded[1][0]['formtemplate'] ) ) {
                        $decoded[1][0]['formtemplate'] = $template;
                    }
                } catch ( Exception $err ) {}
            }

            $output = json_encode( $decoded );
            return $output;
		}

        static public function dispatch() {
            $make_output = function ($value, $type) {
                wp_send_json([$type => $value]);
            };

            if(current_user_can(apply_filters('cpcff_forms_edition_capability', 'manage_options'))) {
                if (
                    isset($_GET['cff_ai_form_preview']) ||
                    isset($_POST['cff_ai_form_generator_description']) ||
                    isset($_POST['cff_ai_form_save_settings'])
                ) {

                    remove_all_actions('shutdown');
                    check_admin_referer('cff-ai-form-generator', '_cpcff_nonce');

                    if ( isset($_GET['cff_ai_form_preview']) ) {
                        $transient_name_form_preview   = 'cff_ai_form_preview_' . get_current_user_id();
                        $form_preview = get_transient($transient_name_form_preview);
                        delete_transient($transient_name_form_preview);
                        if (! empty($form_preview)) print $form_preview;
                        else print esc_html_e('No form preview available.', 'calculated-fields-form');
                        exit;
                    }

                    $model_selected     = isset($_POST['cff_ai_form_generator_model']) ? sanitize_text_field(wp_unslash($_POST['cff_ai_form_generator_model'])) : CPCFF_AI_REQUESTS::get_selected_model_from_context('form-generation');
                    $provider_selected = isset($_POST['cff_ai_form_generator_provider']) ? sanitize_text_field(wp_unslash($_POST['cff_ai_form_generator_provider'])) : CPCFF_AI_REQUESTS::get_selected_provider_from_context('form-generation');
                    $api_key            = isset($_POST['cff_ai_form_generator_api_key']) ? sanitize_text_field(wp_unslash($_POST['cff_ai_form_generator_api_key'])) : CPCFF_AI_REQUESTS::get_selected_api_key_from_context('form-generation');

                    if (empty($provider_selected)) {
                        $make_output(esc_html__('Please select a provider.', 'calculated-fields-form'), 'error');
                    }

                    if (empty($model_selected) && $provider_selected != 'wordpress-ai') {
                        $make_output(esc_html__('Please select a model.', 'calculated-fields-form'), 'error');
                    }

                    if ( isset($_POST['cff_ai_form_generator_description']) ) {

                        if (empty($api_key) && $provider_selected != 'wordpress-ai') {
                            $make_output(esc_html__('Please enter your API key.', 'calculated-fields-form'), 'error');
                        }

                        $form_description = sanitize_textarea_field(wp_unslash($_POST['cff_ai_form_generator_description']));
                        if (empty($form_description)) {
                            $make_output(esc_html__('Form description is required.', 'calculated-fields-form'), 'error');
                        }

                        try {
                            $transient_name_form_structure = 'cff_ai_form_structure_' . get_current_user_id();
                            $transient_name_form_preview   = 'cff_ai_form_preview_' . get_current_user_id();
                            $current_form_structure = null;
                            if ( ! empty( $_POST['modify_form'] ) ) {
                                $current_form_structure = get_transient($transient_name_form_structure);
                            } else {
                                delete_transient($transient_name_form_structure);
                                delete_transient($transient_name_form_preview);
                            }

                            $form_structure = self::model_inference($provider_selected, $model_selected, $api_key, $form_description, $current_form_structure);

                            // Defense against prompt injection: AI responses are treated as untrusted.
                            // Sanitize the decoded structure before storing in transient so the same
                            // protections applied to manual saves (PR 4 change 1) also apply here.
                            $decoded_structure = json_decode($form_structure, true);
                            if (is_array($decoded_structure)) {
                                $sanitized = CPCFF_FORM::sanitize_structure($decoded_structure);
                                $form_structure = json_encode($sanitized);
                            }

                            $form_preview = CPCFF_MAIN::instance()->no_form_preview($form_structure);

                            $transient_form_structure_expiration = 24 * 60 * 60; // 24 hours.
                            $transient_form_preview_expiration = 5 * 60; // 5 minutes.

                            // Enforce deletion.
                            delete_transient($transient_name_form_structure);
                            delete_transient($transient_name_form_preview);

                            set_transient($transient_name_form_structure, $form_structure, $transient_form_structure_expiration);
                            set_transient($transient_name_form_preview, $form_preview, $transient_form_preview_expiration);

                            $make_output('ok', 'success');
                        } catch (Exception $err) {
                            $make_output($err->getMessage(), 'error');
                        }

                    } else { // Save settings case.
                        CPCFF_AI_REQUESTS::set_selected_model_from_context('form-generation', $model_selected);
                        CPCFF_AI_REQUESTS::set_selected_provider_from_context('form-generation', $provider_selected);
                        CPCFF_AI_REQUESTS::set_selected_api_key_from_context('form-generation', $api_key);
                        $make_output(esc_html__('Settings saved successfully', 'calculated-fields-form'), 'success');
                    }
                }
            }
        } // End dispatch
	} // End class CPCFF_AI_FORM_GENERATOR.
}