<?php
if ( !is_admin() ) {
    print 'Direct access not allowed.';
    exit;
}

if ( ! class_exists( 'CPCFF_AI_FORM_GENERATOR' ) ) {
	class CPCFF_AI_FORM_GENERATOR {
		static private $model = "models/gemini-2.0-flash-lite";
		static private $base_url = "https://generativelanguage.googleapis.com/v1beta/";
		static private $cached_content_name = null;
		static private $cache_ttl = 3600; // 1 hour cache TTL
		static private $cache_key = 'cff_ai_form_schema';
		/**
		 * Create or retrieve cached content for the schema
		 */
		static private function get_or_create_cached_content($api_key) {
			// Load the JSON schema
			$schema = file_get_contents(plugin_dir_path(__FILE__) . '../js/schema.min.json');

			// Check if we have a cached content name stored
			$cached_name = get_transient(self::$cache_key);

			if ($cached_name) {
				// Verify the cached content still exists
				if (self::verify_cached_content($cached_name, $api_key)) {
					return $cached_name;
				}
			}

			// Create new cached content
			$cached_name = self::create_cached_content($schema, $api_key);

			if ($cached_name) {
				// Store the cached content name with TTL
				set_transient(self::$cache_key, $cached_name, self::$cache_ttl);
			}

			return $cached_name;
		}

		/**
		 * Create cached content on Gemini servers
		 */
		static private function create_cached_content($schema, $api_key) {
			$cache_url = self::$base_url . "cachedContents?key=" . $api_key;

			$system_instruction = "You are an expert in coding and form generation. Generate valid JSON that conforms to the provided schema. Always return clean, valid JSON without markdown formatting.";

			$cache_data = [
				"model" => self::$model,
				"systemInstruction" => [
					"parts" => [
						["text" => $system_instruction]
					]
				],
				"contents" => [
					[
						"role" => "user",
						"parts" => [
							[
								"text" => "JSON Schema for form generation:\n\n" . $schema . "\n\nThis schema defines the structure for generating form JSON objects."
							]
						]
					],
					[
						"role" => "model",
						"parts" => [
							[
								"text" => "I understand. I will generate JSON form structures that strictly conform to the provided schema. I'll ensure the output is valid JSON without markdown formatting and exclude properties with null values."
							]
						]
					]
				],
				"ttl" => self::$cache_ttl . "s"
			];

			$args = [
				"headers" => ["Content-Type" => "application/json"],
				"body" => json_encode($cache_data),
				"timeout" => 30
			];

			$response = wp_remote_post($cache_url, $args);

			if (is_wp_error($response)) {
				// error_log("Failed to create cached content: " . $response->get_error_message());
				return null;
			}

			$data = json_decode(wp_remote_retrieve_body($response), true);

			if (!empty($data['error'])) {
				// error_log("Gemini cache creation error: " . $data['error']['message']);
				return null;
			}

			return isset($data['name']) ? $data['name'] : null;
		}

		/**
		 * Verify that cached content still exists
		 */
		static private function verify_cached_content($cached_name, $api_key) {
			$verify_url = self::$base_url . $cached_name . "?key=" . $api_key;

			$args = [
				"method" => "GET",
				"timeout" => 10
			];

			$response = wp_remote_get($verify_url, $args);

			if (is_wp_error($response)) {
				return false;
			}

			$status_code = wp_remote_retrieve_response_code($response);
			return $status_code === 200;
		}

		/**
		 * Main inference method with caching support
		 */
		static public function model_inference($form_description, $api_key) {
			$inference_url = self::$base_url . self::$model . ":generateContent?key=" . $api_key;

			// Try to use cached content
			$cached_content_name = self::get_or_create_cached_content($api_key);

			if ($cached_content_name) {
				// Use cached content approach
				$prompt = "Generate a JSON form structure based on the provided schema for the following form description:\n\n";
				$prompt .= "Form description: " . $form_description . "\n\n";
				$prompt .= "Return only the valid JSON object without markdown formatting or null properties:";

				$data = [
					"cachedContent" => $cached_content_name,
					"contents" => [
						[
							"parts" => [
								["text" => $prompt]
							]
						]
					],
					"generationConfig" => [
						"temperature" => 0.0,
						"topP" => 0.9
					]
				];
			} else {
				// Fallback to original approach if caching fails

				$schema = file_get_contents(plugin_dir_path(__FILE__) . '../js/schema.min.json');
				$prompt = "Generate a JSON form structure that strictly conforms to the following JSON schema specifications. The output must be valid JSON syntax that passes schema validation:\n\n";
				$prompt .= $schema . "\n\n";
				$prompt .= "The output must be in JSON format. Do NOT use Markdown or enclose the response in triple backticks. Do not include properties with value null.\n";
				$prompt .= "New form description: " . $form_description . "\nReturn only the JSON object:";

				$data = [
					"contents" => [
						[
							"parts" => [
								["text" => $prompt]
							]
						]
					],
					"systemInstruction" => [
						"parts" => [
							["text" => "You are an expert in coding and form generation. Generate valid JSON that conforms to the provided schema. Always return clean, valid JSON without markdown formatting."]
						]
					],
					"generationConfig" => [
						"temperature" => 0.0,
						"topP" => 0.9
					]
				];
			}

			$args = [
				"headers" => ["Content-Type" => "application/json"],
				"body" => json_encode($data),
				"timeout" => 45
			];

			$response = wp_remote_post($inference_url, $args);

			if (is_wp_error($response)) {
				throw new Exception($response->get_error_message());
			}

			$data = json_decode(wp_remote_retrieve_body($response), true);
			$exception = new Exception(__('Empty AI model answer', 'calculated-field-form'));

			if (empty($data)) throw $exception;

			if (!empty($data['error'])) {
				throw new Exception($data['error']['message']);
			}

			try {
				$output = $data['candidates'][0]['content']['parts'][0]['text'];
				// Remove markdown characters from the beginning and end and minify the JSON:
				$output = preg_replace('/^```json\s*(.*?)\s*```$/s', '$1', $output);
				$output = json_decode($output, true);
				$output = json_encode($output);
			} catch (Exception $err) {
				throw $exception;
			}

			return $output;
		}

		/**
		 * Manually clear cached content (useful for debugging or schema updates)
		 */
		static public function clear_cache($api_key = null) {
			// Clear WordPress transient
			delete_transient(self::$cache_key);

			// Optionally delete from Gemini servers if API key provided
			if ($api_key && self::$cached_content_name) {
				$delete_url = self::$base_url . self::$cached_content_name . "?key=" . $api_key;
				wp_remote_request($delete_url, ["method" => "DELETE"]);
			}
		}
	} // End class CPCFF_AI_FORM_GENERATOR.
}

// Main code

/** CALL THE AI FORM GENERATOR **/
if (
	! empty( $_POST['cff_ai_form_generator_description'] ) &&
	! empty( $_POST['cff_ai_form_generator_api_key'] )
) {

	$output = [];

	remove_all_actions( 'shutdown' );
	check_admin_referer( 'cff-ai-form-generator', '_cpcff_nonce' );

	$form_description = sanitize_textarea_field( wp_unslash( $_POST['cff_ai_form_generator_description'] ) );
	$api_key		  = sanitize_text_field( wp_unslash( $_POST['cff_ai_form_generator_api_key'] ) );

	// Re-check they are not empty.
	if ( ! empty( $form_description ) && ! empty( $api_key ) ) {
		try {
			$transient_name_form_structure = 'cff_ai_form_structure_' . get_current_user_id();
			$transient_name_form_preview   = 'cff_ai_form_preview_' . get_current_user_id();

			delete_transient( $transient_name_form_structure );
			delete_transient( $transient_name_form_preview );

			$form_structure = CPCFF_AI_FORM_GENERATOR::model_inference( $form_description, $api_key );
			$form_preview = CPCFF_MAIN::instance()->no_form_preview( $form_structure );

			$transient_form_structure_expiration = 24 * 60 *60; // 224 hours.
			$transient_form_preview_expiration = 5 * 60; // 5 minutes.

			set_transient( $transient_name_form_structure, $form_structure, $transient_form_structure_expiration );
			set_transient( $transient_name_form_preview, $form_preview, $transient_form_preview_expiration );

			$output['success'] 	= 'ok';

		} catch ( Exception $err ) {
			$output['error'] = $err->getMessage();
		}

	} else {
		$output['error'] = __( 'Empty API Key or form description', 'calculated-field-form' );
	}
	print json_encode( $output );
	exit;

 } elseif (
	! empty( $_GET['cff_ai_form_preview'] )
 ) {

	remove_all_actions( 'shutdown' );
	check_admin_referer( 'cff-ai-form-generator', '_cpcff_nonce' );

	$transient_name_form_preview   = 'cff_ai_form_preview_' . get_current_user_id();

	$form_preview = get_transient( $transient_name_form_preview );
	delete_transient( $transient_name_form_preview );

	if ( ! empty( $form_preview ) ) print $form_preview;
	else print esc_html_e( 'No form preview available.', 'claculated-fields-form' );
	exit;

 }