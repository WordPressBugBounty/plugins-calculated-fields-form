<?php

if ( !is_admin() )
{
    print 'Direct access not allowed.';
    exit;
}

wp_enqueue_style('cff-ai-assistant-css', plugins_url( '/css/style.ai.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION);

?>
<script data-category="functional" type="module" src="<?php print esc_attr( plugins_url( '/js/ai-assistant.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ) ); ?>"></script>
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
	];

	print 'cff_ai_texts=' . json_encode( $ai_config_obj );

?></script>
<div id="cff-ai-assistant-container" style="display:none;">
	<div id="cff-ai-assistan-title" class="cff-ai-assistan-title">
		<span>
		<?php esc_attr_e( 'AI Assistant (Experimental)', 'calculated-fields-form' ); ?>
		</span>
		<button id="cff-ai-assistant-unmount" class="button-secondary" style="display:none;"><?php esc_html_e('unload model', 'calculated-fields-form'); ?></button>
		<button id="cff-ai-assistant-close" class="button-secondary"><?php esc_html_e('close', 'calculated-fields-form'); ?></button>
	</div>
	<div id="cff-ai-assistant-answer-row" class="cff-ai-assistant-answer-row">
		<div class="cff-ai-assistance-message cff-ai-assistance-bot-message">
			<?php
				print '<b>' . esc_html__( 'Hi! I\'m your Code Assistant.', 'calculated-fields-form') . '</b>&nbsp;';
				esc_html_e('Please wait while the AI downloads. This may take a moment depending on your network speed.', 'calculated-fields-form' ); ?>
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
	<div id="cff-ai-assistant-question-row"class="cff-ai-assistant-question-row">
		<div id="cff-ai-assistant-stats"></div>
		<div class="cff-ai-assistant-question-controls">
			<textarea id="cff-ai-assistant-question" name="cff-ai-assistant-question" row="3" placeholder="<?php esc_html_e( 'Please, enter your question...', 'calculated-fields-form' ); ?>"></textarea>
			<button type="button" id="cff-ai-assistan-send-btn" name="cff-ai-assistan-send-btn" class="button-primary" disabled><?php esc_html_e( 'Send', 'calculated-fields-form' ); ?></button>
		</div>
	</div>
</div>