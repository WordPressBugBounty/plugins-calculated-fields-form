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
		 * Main inference method with caching support
		 */
		static public function model_inference($provider, $model, $form_description, $api_key) {
			$schema = file_get_contents(plugin_dir_path(__FILE__) . '../js/schema.min.json');
            $models = CPCFF_AI_REQUESTS::get_models();

            if ( ! isset( $models[ $provider ] ) ) {
                throw new Exception( __('Invalid AI provider selected.', 'calculated-fields-form') );
            }

            if ( ! isset( $models[ $provider ]['models'][$model] ) ) {
                $model = $models[ $provider ]['default_model'];
            }

            $prompt = "";
            $prompt .= "Schema:\n" . $schema . "\n\n";
            $prompt .= "Task: Generate form structure for: " . $form_description . "\n\n";
            $prompt .= "Rules:\n";
            $prompt .= "- Output valid JSON only\n";
            $prompt .= "- Match schema exactly\n";
            $prompt .= "- Omit null values\n";
            $prompt .= "- No markdown, no explanations\n\n";
            $prompt .= "JSON:";

            $context = "You generate valid JSON matching provided schemas. Output only JSON.";

            $aiRequest = new CPCFF_AI_REQUESTS($api_key, $provider, $model, 0.0, 8000);

            $response  = $aiRequest->request($prompt, $context);
			if ( empty( $response ) || ! empty( $response['error'] ) ) {
                $error_message = ! empty( $response['error'] ) ? $response['error'] : __('Unknown error from AI model.', 'calculated-fields-form');
                throw new Exception( __('AI Model Error: ', 'calculated-fields-form') . $error_message );
            }
            $output = preg_replace('/^```json\s*(.*?)\s*```$/s', '$1', $response['response']);
            $output = json_decode($output, true);
            $output = json_encode($output);

            return $output;
		}
	} // End class CPCFF_AI_FORM_GENERATOR.
}

// Main code

/** CALL THE AI FORM GENERATOR **/
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

        $output = [];

        $model_selected     = isset($_POST['cff_ai_form_generator_model']) ? sanitize_text_field(wp_unslash($_POST['cff_ai_form_generator_model'])) : get_option('cff_ai_form_generator_model', '');
        $provider_selected = isset($_POST['cff_ai_form_generator_provider']) ? sanitize_text_field(wp_unslash($_POST['cff_ai_form_generator_provider'])) : get_option('cff_ai_form_generator_provider', '');
        $api_key            = isset($_POST['cff_ai_form_generator_api_key']) ? sanitize_text_field(wp_unslash($_POST['cff_ai_form_generator_api_key'])) : get_option('cff_ai_form_generator_api_key', '');

        if (! empty($model_selected) && ! empty($provider_selected) ) {
            if ( isset($_POST['cff_ai_form_generator_description']) ) {

                $form_description = sanitize_textarea_field(wp_unslash($_POST['cff_ai_form_generator_description']));

                if (! empty($form_description) && ! empty($api_key)) {
                    try {
                        $transient_name_form_structure = 'cff_ai_form_structure_' . get_current_user_id();
                        $transient_name_form_preview   = 'cff_ai_form_preview_' . get_current_user_id();

                        delete_transient($transient_name_form_structure);
                        delete_transient($transient_name_form_preview);

                        $form_structure = CPCFF_AI_FORM_GENERATOR::model_inference($provider_selected, $model_selected, $form_description, $api_key);
                        $form_preview = CPCFF_MAIN::instance()->no_form_preview($form_structure);

                        $transient_form_structure_expiration = 24 * 60 * 60; // 224 hours.
                        $transient_form_preview_expiration = 5 * 60; // 5 minutes.

                        set_transient($transient_name_form_structure, $form_structure, $transient_form_structure_expiration);
                        set_transient($transient_name_form_preview, $form_preview, $transient_form_preview_expiration);

                        $output['success']     = 'ok';
                    } catch (Exception $err) {
                        $output['error'] = $err->getMessage();
                    }
                } else {
                    $output['error'] = __('Empty API Key or form description', 'calculated-field-form');
                }
            } else { // Save settings case.
                update_option('cff_ai_form_generator_model', $model_selected);
                update_option('cff_ai_form_generator_provider', $provider_selected);
                update_option('cff_ai_form_generator_api_key', $api_key);
                $output['success']     = __('Settings saved successfully', 'calculated-fields-form');
            }
        } else {
            $output['error'] = __('Model or provider not selected', 'calculated-fields-form');
        }
        print json_encode($output);
        exit;
    }
}