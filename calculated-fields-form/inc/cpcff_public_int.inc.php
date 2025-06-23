<?php

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeEnd
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTagSquiz.Commenting.FunctionComment.MissingParamTag

if ( ! defined( 'CP_AUTH_INCLUDE' ) ) {
	print 'Direct access not allowed.';
	exit;
}

// Required scripts.
require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_templates.inc.php';

// Corrects a conflict with W3 Total Cache.
if ( function_exists( 'w3_instance' ) ) {
	try {
		$w3_config = w3_instance( 'W3_Config' );
		$w3_config->set( 'minify.html.enable', false );
	} catch ( Exception $err ) {
		error_log( $err->getMessage() );
	}
}

add_filter( 'style_loader_tag', array( 'CPCFF_AUXILIARY', 'complete_link_tag' ) );

wp_enqueue_style( 'cpcff_stylepublic', plugins_url( '/css/stylepublic.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );
wp_enqueue_style( 'cpcff_jquery_ui', plugins_url( '/vendors/jquery-ui/jquery-ui.min.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );
wp_enqueue_style( 'cpcff_jquery_ui_font', plugins_url( '/vendors/jquery-ui/jquery-ui-1.12.icon-font.min.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );

$cpcff_main = CPCFF_MAIN::instance();
$form_obj   = $cpcff_main->get_form( $id );

$form_data            = $form_obj->get_option( 'form_structure', CP_CALCULATEDFIELDSF_DEFAULT_form_structure );
$form_data_serialized = serialize( $form_data );

if ( ( stripos( $form_data_serialized, 'select2' ) || stripos( $form_data_serialized, 'fphone' ) ) && ! wp_script_is( 'select2' ) && ! wp_script_is( 'select-2-js' ) ) {
	wp_enqueue_style( 'cpcff_select2_css', plugins_url( '/vendors/select2/select2.min.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );
	wp_enqueue_script( 'cpcff_select2_js', plugins_url( '/vendors/select2/select2.min.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION, true );
}

if ( strpos( $form_data_serialized, 'fqrcode' ) && ! wp_script_is( 'qrcode' ) ) {
	wp_enqueue_script( 'cpcff_qrcode_js', plugins_url( '/vendors/qrcode/html5-qrcode.min.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION, true );
}

if ( preg_match( '/PDFPAGESNUMBER/i', $form_data_serialized ) && ! wp_script_is( 'cpcff_pdf_js' ) ) {
	wp_enqueue_script( 'cpcff_pdf_js', plugins_url( '/vendors/pdf-js/pdf.min.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION, true );
	wp_add_inline_script( 'cpcff_pdf_js', 'pdfjsLib.GlobalWorkerOptions.workerSrc="' . esc_js( plugins_url( '/vendors/pdf-js/pdf.worker.min.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ) ) . '";', 'after' );
}

if ( ! empty( $form_data ) ) {
	if ( isset( $form_data[1] ) && is_object( $form_data[1] ) ) {
		$form_data[1] = (array) $form_data[1];
	}
	if ( isset( $form_data[1] ) && isset( $form_data[1][0] ) ) {
		if( !empty( $form_template ) ) {
			$form_data[ 1 ][ 0 ]->formtemplate = $form_template;
		}

		if ( ! empty( $form_data[1][0]->formtemplate ) ) {
			CPCFF_TEMPLATES::enqueue_template_resources( $form_data[1][0]->formtemplate );
		}

		if ( ! empty( $form_data[1][0]->customstyles ) ) {
			print '<style>' . wp_strip_all_tags( $form_data[1][0]->customstyles ) . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
	$form_data[1]['formid'] = 'cp_calculatedfieldsf_pform_' . CPCFF_MAIN::$form_counter;

	if ( ! defined( 'CFF_AUXILIARY_NONCE' ) ) define( 'CFF_AUXILIARY_NONCE',  wp_create_nonce( 'cff-client-side-auxilary-nonce' ) );

	// Form Heights
	print $form_obj->get_height( '#' . $form_data[1]['formid'] );
	?>
<form name="<?php echo esc_attr( $form_data[1]['formid'] ); ?>" id="<?php echo esc_attr( $form_data[1]['formid'] ); ?>" action="<?php
echo esc_attr( ( false !== ( $permalink = get_permalink() ) ) ? $permalink : '?' );
?>" method="post" enctype="multipart/form-data" onsubmit="return fbuilderjQuery.fbuilder.doValidate(this);" class="cff-form no-prefetch <?php
if ( ! empty( $form_data[1][0] ) && ! empty( $form_data[1][0]->persistence ) ) {
	echo ' persist-form';
}
if ( ! empty( $form_data[1][0] ) && property_exists( $form_data[1][0], 'formtemplate' ) && ! empty( $form_data[1][0]->formtemplate ) ) {
	echo ' ' . esc_attr( $form_data[ 1 ][ 0 ]->formtemplate );
}
if ( ! empty( $atts ) && ! empty( $atts['class'] ) ) {
	echo ' ' . esc_attr( $atts['class'] );
}
?>" <?php
// Direction.
if ( property_exists( $form_data[1][0], 'direction' ) ) {
	print ' dir="' . esc_attr( $form_data[1][0]->direction ) . '"';
}
?> data-nonce="<?php print esc_attr( CFF_AUXILIARY_NONCE ); ?>">
<?php
	// Submit form via AJAX.
	if ( $form_obj->get_option('fp_ajax', 0) ) {
		print '<iframe name="cff_iframe_for_submission_' . CPCFF_MAIN::$form_counter . '" id="cff_iframe_for_submission_' . CPCFF_MAIN::$form_counter . '" style="display:none;" data-cff-reset="' . ( $form_obj->get_option('fp_ajax_reset_form', 0) ? 1 : 0 ) . '"></iframe>';
		if ( ! empty( $form_obj->get_option('fp_thanks_mssg', '') ) ) {
			print '<div class="cff-thanks-message" style="display:none;">' . esc_html( $form_obj->get_option('fp_thanks_mssg', '') ) . '</div>';
		}
	}
?>
<input type="hidden" name="cp_calculatedfieldsf_pform_psequence" value="_<?php echo esc_attr( CPCFF_MAIN::$form_counter ); ?>" />
<input type="hidden" name="cp_calculatedfieldsf_id" value="<?php echo esc_attr( $id ); ?>" />
<input type="hidden" name="cp_ref_page" value="<?php echo esc_attr( CPCFF_AUXILIARY::site_url() ); ?>" />
<pre style="display:none !important;"><script type="text/javascript">form_structure_<?php echo esc_js( CPCFF_MAIN::$form_counter ); ?>=<?php print str_replace( array( "\n", "\r" ), ' ', ( ( version_compare( CP_CFF_PHPVERSION, '5.3.0' ) >= 0 ) ? json_encode( $form_data, JSON_HEX_QUOT | JSON_HEX_TAG ) : json_encode( $form_data ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>;</script></pre>
<div id="fbuilder">
	<?php
	if (
			! empty( $form_data ) &&
			! empty( $form_data[1] ) &&
			! empty( $form_data[1][0] ) &&
			! empty( $form_data[1][0]->loading_animation )
		) {
		print '<div class="cff-processing-form"></div>';
	}
	?>
	<div id="fbuilder_<?php echo esc_attr( CPCFF_MAIN::$form_counter ); ?>">
		<div id="formheader_<?php echo esc_attr( CPCFF_MAIN::$form_counter ); ?>"></div>
		<div id="fieldlist_<?php echo esc_attr( CPCFF_MAIN::$form_counter ); ?>"></div>
		<div class="clearer"></div>
	</div>
</div>
	<?php
	if ( $form_obj->get_option( 'enable_submit', '' ) == '' ) {
		print '<div id="cp_subbtn_' . esc_attr( CPCFF_MAIN::$form_counter ) . '" class="cp_subbtn" style="display:none;">' . esc_html( $form_obj->get_option( 'vs_text_submitbtn', 'Submit' ) ) . '</div>';
	}
	?>
<div class="clearer"></div>
	<?php
	wp_nonce_field( 'cpcff_form_' . $id . '_' . CPCFF_MAIN::$form_counter, '_cpcff_public_nonce' );
	?>
</form>
	<?php
	// If the form shortcode was configured to be opened into an iframe adjust iframe size.
	if ( isset( $_REQUEST['cff_iframe'] ) ):
		?>
	<style>.cff-form{width:100%;overflow-x:auto;box-sizing: border-box;}</style>
	<?php
	endif;
}