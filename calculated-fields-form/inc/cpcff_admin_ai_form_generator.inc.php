<?php
if ( !is_admin() ) {
    print 'Direct access not allowed.';
    exit;
}

if ( ! class_exists( 'CPCFF_AI_FORM_GENERATOR' ) ) {
	class CPCFF_AI_FORM_GENERATOR {

		static private $model 			= "models/gemini-2.0-flash-lite"; // models/gemini-2.0-flash-001
		static private $base_url		= "https://generativelanguage.googleapis.com/v1beta/";

		static public function model_inference( $form_description, $api_key ) {

			$caching_url 	= self::$base_url . "cachedContents?key=" . $api_key;
			$inference_url 	= self::$base_url . self::$model . ":generateContent?key=" . $api_key;

			// Caching the JSON schema.
			$cache_id = get_transient( 'cff_ai_form_schema' );
			if ( empty( $cache_id ) ) {
				$schema = file_get_contents( plugin_dir_path( __FILE__ ) . '../js/schema.min.json' );
				$data = [
					"displayName" 	=> "CFFSchemaCache",
					"model"		  	=> self::$model,
					"contents" 		=> [
						[
							"role" 	=> "user",
							"parts" => [
								["text" => "This is the JSON schema for generating web forms:"],
								["text" => $schema]
							]
						]
					],
					"systemInstruction" => [
						"parts" 	=> [
							"text" 	=> "You are an expert in coding"
						]
					],
					"ttl" 			=> "86400s" // Cache for 24 hours
				];

				$args = [
					"headers" => [ "Content-Type" => "application/json" ],
					"body"    => json_encode( $data )
				];

				$response = wp_remote_post( $caching_url, $args );

				if ( ! is_wp_error( $response ) ) {
					$cache_result = json_decode( wp_remote_retrieve_body( $response ), true );
					if ( ! empty( $cache_result )  ) {
						// if ( ! empty( $cache_result[ 'error' ] ) ) error_log( $cache_result['error']['message'] );
						if ( empty( $cache_result["error"] ) && isset( $cache_result["name"] ) ) {
							$cache_id = $cache_result["name"];
							set_transient( 'cff_ai_form_schema',  $cache_id, intval( $cache_result["expireTime"] ) );
						}
					} else {
						error_log( __( 'Empty AI model answer', 'calculated-field-form' ) );
					}
				} else {
					error_log( $response->get_error_message() );
				}

			} // End caching process.

			// Generate the prompt.
			$prompt = "";
			if ( empty( $cache_id ) ) {
				$prompt .= "Generate a JSON form structure that strictly conforms to the following JSON schema specifications. The output must be valid JSON syntax that passes schema validation:\n\n$schema\n\n";
			}
			$prompt .= "The output must be in JSON format. Do NOT use Markdown or enclose the response in triple backticks. Do not include porperties with value null.\n";
			$prompt .= "New form description: $form_description\nReturn only the JSON object:";

			$data = [ 'contents' => [[ 'parts' => [[ 'text' => $prompt ]] ]], "generationConfig" => [ "temperature" => 0.0, "topP" => 0.9 ] ];

			if ( ! empty( $cache_id ) ) {
				$data['generationConfig'] = [
					"explicitContextCaching" => true,
					"cachedContent" => $cache_id // Reference cached schema
				];
			}

			// Inferring the model to generate the form.
			$body = json_encode( $data );
			$response = wp_remote_post( $inference_url, [
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body' => $body,
				'timeout' => 20,
			]);

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			$data = json_decode(wp_remote_retrieve_body($response), true);
			$exception = new Exception( __( 'Empty AI model answer', 'calculated-field-form' ) );

			if( empty( $data ) ) throw $exception;
			if ( ! empty( $data[ 'error' ] ) ) throw new Exception( $data['error']['message'] );
			try {
				$output = $data['candidates'][0]['content']['parts'][0]['text'];
				// Remove markdown characters from the beginning and end and minifiy the JSON:
				$output = preg_replace('/^```json\s*(.*?)\s*```$/s', '$1', $output);
				$output = json_decode( $output, true );
				$output = json_encode( $output );
			} catch ( Exception $err ) {
				throw $exception;
			}

			return $output;

		} // End model_inference.

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