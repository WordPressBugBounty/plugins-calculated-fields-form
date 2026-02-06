<?php

if ( ! is_admin() || ! current_user_can( 'manage_options' ) )
{
    print 'Unauthorized access.';
    exit;
}

$models = [
    'openai' => [
        'gpt-5-nano'    => esc_html__( 'GPT-5 Nano (Fast and Cheap)', 'calculated-fields-form' ),
        'gpt-5-mini'    => esc_html__( 'GPT-5 Mini (Balanced)', 'calculated-fields-form' ),
        'gpt-5.2'       => esc_html__( 'GPT-5.2 (Most Capable)', 'calculated-fields-form' )
    ],
    'claude' => [
        'claude-sonnet-4-20250514'      => esc_html__( 'Claude Sonnet 4.5 (Recommended)', 'calculated-fields-form' ),
        'claude-haiku-4-5-20251001'     => esc_html__( 'Claude Haiku 4.5 (Fast)', 'calculated-fields-form' ),
        'claude-opus-4-6'        		=> esc_html__( 'Claude Opus 4.6 (Most Capable)', 'calculated-fields-form' ),
        'claude-opus-4-5-20251101'      => esc_html__( 'Claude Opus 4.5', 'calculated-fields-form' ),
        'claude-opus-4-20250514'        => esc_html__( 'Claude Opus 4', 'calculated-fields-form' ),
        'claude-3-5-sonnet-20241022'    => esc_html__( 'Claude 3.5 Sonnet (Previous)', 'calculated-fields-form' ),
        'claude-3-haiku-20240307'       => esc_html__( 'Claude 3 Haiku (Budget)', 'calculated-fields-form' )
    ],
    'gemini' => [
        'gemini-2.5-flash-lite'         => esc_html__( 'Gemini 2.5 Flash-Lite', 'calculated-fields-form' ),
        'gemini-2.5-flash'              => esc_html__( 'Gemini 2.5 Flash', 'calculated-fields-form' ),
        'gemini-2.5-pro'                => esc_html__( 'Gemini 2.5 Pro', 'calculated-fields-form' )
    ]
];

if ( isset( $_POST['_cpcff_ai_assistant_nonce'] ))
{
    $_cpcff_ai_assistant_nonce = sanitize_text_field( wp_unslash( $_POST['_cpcff_ai_assistant_nonce'] ) );
    if ( wp_verify_nonce( $_cpcff_ai_assistant_nonce, 'cff_ai_save_settings_nonce' ) ) { // Save AI Assistant Settings
        $api_key = isset( $_POST['_cpcff_ai_assistant_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['_cpcff_ai_assistant_api_key'] ) ) : '';
        update_option( 'cff_ai_assistant_api_key', $api_key);
        if ( empty( $api_key ) ) {
            // If API key is empty, reset provider and model to local
            update_option( 'cff_ai_assistant_provider', 'local');
            update_option( 'cff_ai_assistant_model', 'local');
            exit;
        }
        $provider = isset( $_POST['_cpcff_ai_assistant_provider'] ) ? sanitize_text_field( wp_unslash( $_POST['_cpcff_ai_assistant_provider'] ) ) : 'local';
        $provider =  array_key_exists( $provider, $models ) ? $provider : 'local';
        update_option( 'cff_ai_assistant_provider', $provider);

        $model = isset( $_POST['_cpcff_ai_assistant_model'] ) ? sanitize_text_field( wp_unslash( $_POST['_cpcff_ai_assistant_model'] ) ) : '';
        $model = ( $provider !== 'local' && array_key_exists( $model, $models[ $provider ] ) ) ? $model : ( $provider === 'local' ? 'local' :  array_keys( $models[ $provider ] )[0] );
        update_option( 'cff_ai_assistant_model', $model);
    } elseif ( wp_verify_nonce( $_cpcff_ai_assistant_nonce, 'cff_ai_request_nonce' ) ) { // Handle AI Assistant Request
        require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_ai_requests.inc.php';

        $api_key = get_option( 'cff_ai_assistant_api_key', '' );
        $provider = get_option( 'cff_ai_assistant_provider', '' );
        $model = get_option( 'cff_ai_assistant_model', '' );

        if ( empty( $api_key ) || empty( $provider ) || empty( $model ) ) {
            wp_send_json( [ 'error' => __( 'API Key, Provider, and Model are required.', 'calculated-fields-form' ) ] );
        }

        $prompt = sanitize_text_field( wp_unslash( $_POST['_cpcff_ai_assistant_message'] ) );
        $context = sanitize_text_field( wp_unslash( $_POST['_cpcff_ai_assistant_context'] ) );

        if ( empty( $prompt ) || empty( $context ) ) {
            wp_send_json( [ 'error' => __( 'Prompt and Context are required.', 'calculated-fields-form' ) ] );
        }

        $ai_requests = new CPCFF_AI_REQUESTS( $api_key, $provider, $model );

        $response = $ai_requests->request( $prompt, $context );
        wp_send_json( $response );
    }
    exit;
}

wp_enqueue_style('cff-ai-assistant-css', plugins_url( '/css/style.ai.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION);

?>
<script data-category="functional"><?php
	$ai_config_obj = [
        'typing' 		 	=> __( 'Typing...', 'calculated-fields-form' ),
		'generating' 	 	=> __( 'Generating...', 'calculated-fields-form' ),
		'placeholder'		=> __( 'Please, enter your question ...', 'calculated-fields-form' ),
		'placeholder_css'	=> __( 'Please, enter your CSS related question ...', 'calculated-fields-form' ),
		'placeholder_js' 	=> __( 'Create an equation that ...', 'calculated-fields-form' ),
		'placeholder_html' 	=> __( 'Generate a summary of the fields ...', 'calculated-fields-form' ),
		'copy_btn'		 	=> __( 'Copy', 'calculated-fields-form' ),
		'copied_btn'	 	=> __( 'Copied !!!', 'calculated-fields-form' ),
		'unload'			=> __( 'This action will completely unload the model and remove it from your browser cache. Would you like to proceed?', 'calculated-fields-form' ),
        'api_key_required'  => __( 'API Key is required for the selected provider.', 'calculated-fields-form' )
	];

	print 'var cff_ai_texts=' . json_encode( $ai_config_obj ) . ';';

    print 'var cff_ai_models=' . json_encode( $models ) . ';';
    print 'var cff_ai_provider="' . esc_js( get_option( 'cff_ai_assistant_provider', 'local' ) ) . '";';
    print 'var cff_ai_model="' . esc_js( get_option( 'cff_ai_assistant_model', '' ) ) . '";';
    print 'var cff_ai_api_key="' . esc_js( get_option( 'cff_ai_assistant_api_key', '' ) ) . '";';
    print 'var cff_ai_save_settings_nonce="' . esc_js( wp_create_nonce( 'cff_ai_save_settings_nonce' ) ) . '";';
    print 'var cff_ai_request_nonce="' . esc_js( wp_create_nonce( 'cff_ai_request_nonce' ) ) . '";';

?></script>
<script data-category="functional" type="module" src="<?php print esc_attr( plugins_url( '/js/ai-assistant.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ) ); ?>"></script>
<div id="cff-ai-assistant-container" style="display:none;">
	<div id="cff-ai-assistan-header" class="cff-ai-assistan-header">
        <div id="cff-ai-assistan-title" class="cff-ai-assistan-title">
            <span>
            <?php esc_attr_e( 'AI Assistant (Experimental)', 'calculated-fields-form' ); ?>
            </span>
            <button id="cff-ai-assistant-unmount" class="button-secondary" style="display:none;"><?php esc_html_e('unload model', 'calculated-fields-form'); ?></button>
            <button id="cff-ai-assistant-settings" class="button-secondary" title="<?php esc_attr_e( 'Settings', 'calculated-fields-form' ); ?>">&#9881;</button>
            <button id="cff-ai-assistant-close" class="button-secondary"><?php esc_html_e('close', 'calculated-fields-form'); ?></button>
        </div>
        <div id="cff-ai-assistant-settings-container" style="display:none;cursor:initial;">
            <div style="display:flex; justify-content:space-between;">
                <span style="font-weight:bold;"><?php esc_html_e( 'AI Assistant Settings', 'calculated-fields-form' ); ?></span>
                <button id="cff-ai-assistant-settings-close" class="button-secondary"><?php esc_html_e('close', 'calculated-fields-form'); ?></button>
            </div>
            <label for="cff-ai-assistant-provider"><?php esc_html_e( 'Select your preferred AI provider:', 'calculated-fields-form' ); ?></label>
            <select id="cff-ai-assistant-provider" name="cff-ai-assistant-provider">
                <option value="local"><?php esc_html_e( 'Locally in the browser', 'calculated-fields-form' ); ?></option>
                <option value="openai"><?php esc_html_e( 'OpenAI (GPT)', 'calculated-fields-form' ); ?></option>
                <option value="claude"><?php esc_html_e( 'Anthropic (Claude)', 'calculated-fields-form' ); ?></option>
                <option value="gemini"><?php esc_html_e( 'Google (Gemini)', 'calculated-fields-form' ); ?></option>
            </select>

            <div id="cff-ai-assistant-model-container" style="display:none;">
                <label for="cff-ai-assistant-model"><?php esc_html_e( 'Select your preferred AI provider:', 'calculated-fields-form' ); ?></label>
                <select id="cff-ai-assistant-model" name="cff-ai-assistant-model">
                </select>
            </div>

            <div id="cff-ai-assitance-api-key-container" style="display:none;">
                <label for="cff-ai-assistant-api-key"><?php esc_html_e( 'Provider API Key (required):', 'calculated-fields-form' ); ?></label>
                <input type="password" id="cff-ai-assistant-api-key" name="cff-ai-assistant-api-key" placeholder="<?php esc_html_e( 'Enter your API key here...', 'calculated-fields-form' ); ?>">
            </div>

            <div style="text-align:right; margin-top:10px;">
                <button type="button" id="cff-ai-assistant-save-settings-btn" name="cff-ai-assistant-save-settings-btn" class="button-primary"><?php esc_html_e( 'Save Settings', 'calculated-fields-form' ); ?></button>
            </div>

        </div>
    </div>
	<div id="cff-ai-assistant-answer-row" class="cff-ai-assistant-answer-row">
        <div class="cff-ai-assistance-first-message-local cff-ai-assistance-message cff-ai-assistance-bot-message cff-ai-assistance-loading-message">
			<?php
				print '<b>' . esc_html__( 'Hi! I\'m your Code Assistant.', 'calculated-fields-form') . '</b>';
            ?>
            <div id="cff-ai-assistant-loading-message">
                <?php
                    esc_html_e('Please wait while the AI downloads. This may take a moment depending on your network speed.', 'calculated-fields-form' );
                ?>
                <div class="cff-ai-assistant-progress-container">
                    <div class="cff-ai-assistant-progress-bar" id="cff-ai-assistant-progress-bar"></div>
                </div>
                <div class="cff-ai-assistant-status" id="cff-ai-assistant-status"></div>
                <!-- GPU Error -->
                <div id="cff-ai-gpu-error" style="display:none;">
                    <h3><?php esc_html_e( 'WebGPU is not supported in your browser', 'calculated-fields-form' ); ?></h3>
                    <p><?php esc_html_e( 'WebGPU is a modern graphics API for the web. To use this application, you\'ll need a browser with WebGPU support.', 'calculated-fields-form' ); ?></p>

                    <h4><?php esc_html_e( 'Recommended browsers with WebGPU support:', 'calculated-fields-form' ); ?></h4>
                    <ul>
                        <li><?php esc_html_e( 'Chrome 113 or later', 'calculated-fields-form' ); ?></li>
                        <li><?php esc_html_e( 'Edge 113 or later', 'calculated-fields-form' ); ?></li>
                        <li><?php esc_html_e( 'Firefox 121 or later (with the \'dom.webgpu.enabled\' flag enabled)', 'calculated-fields-form' ); ?></li>
                        <li><?php esc_html_e( 'Safari 17.4 or later', 'calculated-fields-form' ); ?></li>
                    </ul>

                    <h4><?php esc_html_e( 'How to enable WebGPU:', 'calculated-fields-form' ); ?></h4>
                    <h5><?php esc_html_e( 'In Chrome/Edge:', 'calculated-fields-form' ); ?></h5>
                    <ol>
                        <li><?php esc_html_e( 'Ensure your browser is updated to version 113 or later', 'calculated-fields-form' ); ?></li>
                        <li><?php esc_html_e( 'WebGPU should be enabled by default', 'calculated-fields-form' ); ?></li>
                    </ol>

                    <h5><?php esc_html_e( 'In Firefox:', 'calculated-fields-form' ); ?></h5>
                    <ol>
                        <li><?php esc_html_e( 'Ensure you have Firefox 121 or later', 'calculated-fields-form' ); ?></li>
                        <li><?php esc_html_e( 'Type "about:config" in the URL bar', 'calculated-fields-form' ); ?></li>
                        <li><?php esc_html_e( 'Search for "dom.webgpu.enabled"', 'calculated-fields-form' ); ?></li>
                        <li><?php esc_html_e( 'Set it to "true" by clicking the toggle button', 'calculated-fields-form' ); ?></li>
                        <li><?php esc_html_e( 'Restart the browser', 'calculated-fields-form' ); ?></li>
                    </ol>

                    <h5><?php esc_html_e( 'In Safari:', 'calculated-fields-form' ); ?></h5>
                    <ol>
                        <li><?php esc_html_e( 'Ensure you have Safari 17.4 or later', 'calculated-fields-form' ); ?></li>
                        <li><?php esc_html_e( 'WebGPU should be enabled by default', 'calculated-fields-form' ); ?></li>
                    </ol>
                </div>

                <!-- Caches Error -->
                <div id="cff-ai-caches-error" style="display:none;">
                    <h3><?php esc_html_e( 'Cache API not available!', 'calculated-fields-form' ); ?></h3>
                    <p><?php esc_html_e( 'Your browser may be using HTTP instead of HTTPS, or it may not support the Cache API.', 'calculated-fields-form' ); ?></p>
                    <p><?php esc_html_e( 'For full functionality, please:', 'calculated-fields-form' ); ?></p>
                    <ul>
                        <li><?php esc_html_e( 'Open this website using HTTPS (e.g., https://example.com)', 'calculated-fields-form' ); ?></li>
                        <li><?php esc_html_e( 'Use a modern browser that supports the Cache API', 'calculated-fields-form' ); ?></li>
                    </ul>
                </div>
            </div>
		</div>
    </div>
	<div id="cff-ai-assistant-question-row"class="cff-ai-assistant-question-row">
		<div id="cff-ai-assistant-stats"></div>
		<div class="cff-ai-assistant-question-controls">
			<textarea id="cff-ai-assistant-question" name="cff-ai-assistant-question" row="3" placeholder="<?php esc_html_e( 'Please, enter your question...', 'calculated-fields-form' ); ?>"></textarea>
			<button type="button" id="cff-ai-assistan-send-btn" name="cff-ai-assistan-send-btn" class="button-primary" disabled><?php esc_html_e( 'Send', 'calculated-fields-form' ); ?></button>
		</div>
	</div>
</div>