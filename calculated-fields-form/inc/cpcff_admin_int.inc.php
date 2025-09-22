<?php

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd
if ( ! is_admin() ) {
	print 'Direct access not allowed.';
	exit;
}

// Required scripts.
require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_templates.inc.php';

check_admin_referer( 'cff-form-settings', '_cpcff_nonce' );

$_cpcff_form_settings_nonce = wp_create_nonce( 'cff-form-settings' );

// Load resources.
wp_enqueue_media();
if ( function_exists( 'wp_enqueue_code_editor' ) ) {
	wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
}
wp_enqueue_style('cff-select2-css', plugins_url('/vendors/select2/select2.min.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH), array(), CP_CALCULATEDFIELDSF_VERSION);
wp_enqueue_script('cff-select2-js', plugins_url('/vendors/select2/select2.min.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH), array("jquery"), CP_CALCULATEDFIELDSF_VERSION);

if ( ! defined( 'CP_CALCULATEDFIELDSF_ID' ) ) {
	define( 'CP_CALCULATEDFIELDSF_ID', isset( $_GET['cal'] ) && is_numeric( $_GET['cal'] ) ? intval( $_GET['cal'] ) : 0 );
}

$admin_url = 'admin.php?page=cp_calculated_fields_form&cal=' . CP_CALCULATEDFIELDSF_ID . '&_cpcff_nonce=' . urlencode( $_cpcff_form_settings_nonce ) . '&r=' . mt_rand();

$cpcff_main = CPCFF_MAIN::instance();
$form_obj   = $cpcff_main->get_form( intval( $_GET['cal'] ) );

if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' == $_SERVER['REQUEST_METHOD'] && isset( $_POST['cpcff_revision_to_apply'] ) && is_numeric( $_POST['cpcff_revision_to_apply'] ) ) {
	$revision_id = intval( $_POST['cpcff_revision_to_apply'] );
	if ( $revision_id ) {
		$form_obj->apply_revision( $revision_id );
	}
}

$message = '';
if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset( $_POST['cp_calculatedfieldsf_post_options'] ) ) {
	$message = esc_html__( 'Settings saved', 'calculated-fields-form' );
	echo "<div id='setting-error-settings_updated' class='updated settings-error'> <p><strong>" . $message . '</strong></p></div>';
}

$cpcff_texts_array = $form_obj->get_option( 'vs_all_texts', [] );

$section_nav_bar = '<div class="cff-navigation-sections-menu">
	<a href="#metabox_define_texts">' . esc_html__( 'Texts definition', 'calculated-fields-form' ) . '</a><span>&nbsp;|&nbsp;</span>
	<a href="#metabox_define_validation_texts">' . esc_html__( 'Error texts', 'calculated-fields-form' ) . '</a><span>&nbsp;|&nbsp;</span>
	<a href="#metabox_submit_thank">' . esc_html__( 'Submit button and thank you page', 'calculated-fields-form' ) . '</a><span>&nbsp;|&nbsp;</span>
	<a href="#metabox_notification_email">' . esc_html__( 'Notification email', 'calculated-fields-form' ) . '</a>&nbsp;
	<span>[</span><b>' . esc_html__( 'Commercial Features', 'calculated-fields-form' ) . ':</b>
	<a href="https://cff.dwbooster.com/download" target="_blank" style="color:#fc6756;">' . esc_html__( 'Payment settings', 'calculated-fields-form' ) . '</a><span>&nbsp;|&nbsp;</span>
	<a href="https://cff.dwbooster.com/download" target="_blank" style="color:#fc6756;">' . esc_html__( 'Email copy to user', 'calculated-fields-form' ) . '</a><span>&nbsp;|&nbsp;</span>
	<a href="https://cff.dwbooster.com/download" target="_blank" style="color:#fc6756;">' . esc_html__( 'Captcha settings', 'calculated-fields-form' ) . '</a><span>]</span>
 </div>';
?>
<div class="wrap cff-form-builder-backend">
	<div class="cff-navigation-main-menu" style="margin-bottom:10px;">
		<a href="admin.php?page=cp_calculated_fields_form_sub_new" class="button-primary"><?php esc_html_e( 'Add New', 'calculated-fields-form' ); ?></a>
		<a href="admin.php?page=cp_calculated_fields_form" class="button-secondary"><?php esc_html_e( 'Back to forms list...', 'calculated-fields-form' ); ?></a>
		<span><?php include_once dirname( __FILE__) . '/cpcff_video_tutorial.inc.php'; ?></span>
	</div>
	<h1 class="cff-form-name">
	<?php
		print esc_html__( 'Form', 'calculated-fields-form' ) . ' ' . esc_html( CP_CALCULATEDFIELDSF_ID ) . ' - ' . esc_html( $form_obj->get_option( 'form_name', '' ) ) . ' | Shortcode: [CP_CALCULATED_FIELDS id="' . esc_html( CP_CALCULATEDFIELDSF_ID ) . '"]';

	if ( get_option( 'CP_CALCULATEDFIELDSF_DIRECT_FORM_ACCESS', CP_CALCULATEDFIELDSF_DIRECT_FORM_ACCESS ) ) {
		$url  = CPCFF_AUXILIARY::site_url();
		$url .= ( strpos( $url, '?' ) === false ) ? '?' : '&';
		$url .= 'cff-form=' . CP_CALCULATEDFIELDSF_ID;
		print '<br><span style="font-size:14px;font-style:italic;">' . esc_html__( 'Direct form URL', 'calculated-fields-form' ) . ': <a href="' . esc_attr( $url ) . '" target="_blank">' . esc_html( $url ) . '</a></span>';
	}
	?>
	</h1>
	<form method="post" action="<?php echo esc_attr( $admin_url ); ?>" id="cpformconf" name="cpformconf" class="cff_form_builder">
		<input type="hidden" name="_cpcff_nonce" value="<?php echo esc_attr( $_cpcff_form_settings_nonce ); ?>" />
		<input name="cp_calculatedfieldsf_post_options" type="hidden" value="1" />
		<input name="cp_calculatedfieldsf_id" type="hidden" value="<?php echo esc_attr( CP_CALCULATEDFIELDSF_ID ); ?>" />

		<div id="normal-sortables" class="meta-box-sortables">
			<!-- Form category -->
			<input type="hidden" name="calculated-fields-form-category" value="<?php print esc_attr($form_obj->get_option('category', '')); ?>" list="calculated-fields-form-categories" />
			<datalist id="calculated-fields-form-categories"><?php
				print $cpcff_main->get_categories('DATALIST'); // phpcs:ignore WordPress.Security.EscapeOutput
			?></datalist>
			<hr />
			<?php print $section_nav_bar; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<hr />
			<div><?php esc_html_e( '* Different form styles available on the tab Form Settings &gt;&gt; Form Template', 'calculated-fields-form' ); ?></div>
			<div id="metabox_form_structure" class="postbox" >
				<div class="hndle">
					<div class="cff-revisions-container">
						<?php
						print $section_nav_bar;
						?>
						<div class="cff-revisions-container-flex">
						<?php
						if ( get_option( 'CP_CALCULATEDFIELDSF_DISABLE_REVISIONS', CP_CALCULATEDFIELDSF_DISABLE_REVISIONS ) == 0 ) :
							esc_html_e( 'Revisions', 'calculated-fields-form' );
							?>
							<select name="cff_revision_list" aria-label="<?php esc_attr_e( 'Form revisions list', 'calculated-fields-form' ); ?>" style="width:initial;">
							<?php
								print '<option value="0">' . esc_html__( 'Select a revision', 'calculated-fields-form' ) . '</option>';
								$revisions_obj = $form_obj->get_revisions();
								$revisions     = $revisions_obj->revisions_list();
							foreach ( $revisions as $revision_id => $revision_data ) {
								print '<option value="' . esc_attr( $revision_id ) . '">' . esc_html( $revision_data['time'] ) . '</option>';
							}
							?>
							</select>
							<input type="button" name="cff_apply_revision" value="<?php esc_attr_e( 'Load Revision', 'calculated-fields-form' ); ?>" class="button-secondary" style="float:none;" />&nbsp;|&nbsp;
							<?php
						endif;
						?>
						<input type="button" name="cff_fields_list" class="button-secondary" value="<?php print wp_is_mobile() ? '&#9776;' : esc_attr__( 'Fields List', 'calculated-fields-form' ); ?>" title="<?php esc_attr_e( 'Fields List', 'calculated-fields-form' ); ?>" onclick="fbuilderjQuery.fbuilder.printFields();" />
						<input type="button" name="previewbtn" id="previewbtn2" class="button-primary" value="<?php esc_attr_e( 'Save and Preview', 'calculated-fields-form' ); ?>" onclick="fbuilderjQuery.fbuilder.preview( this );" title="<?php esc_attr_e( "Saves the form's structure only, and opens a preview windows", 'calculated-fields-form' ); ?>" />
						&nbsp;|&nbsp;
						<input type="button" name="cff_ai_assistant" class="button cff-ai-assistant" value="<?php esc_attr_e( 'AI Assistant', 'calculated-fields-form' ); ?>" onclick="if('cff_ai_assistant_open' in window) cff_ai_assistant_open();" style="float:none;" />
						<div class="cff-form-builder-extend-shrink">
							<button type="button" name="cff_expand_btn" class="button-secondary" title="<?php esc_attr_e( 'Set form builder fullscreen', 'calculated-fields-form'); ?>"><?php esc_html_e( 'Fullscreen', 'calculated-fields-form' ); ?></button>
							<button type="button" name="cff_shrink_btn" class="button-secondary" title="<?php esc_attr_e( 'Taking form builder out of fullscreen mode', 'calculated-fields-form'); ?>"><?php esc_html_e( 'Shrink', 'calculated-fields-form' ); ?></button>
						</div>
						</div>
					</div>
					<div class="clearer"></div>
				</div>
				<div class="inside">
					<div class="form-builder-error-messages">
					<?php
						global $cff_structure_error;
					if ( ! empty( $cff_structure_error ) ) {
						echo $cff_structure_error; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
					</div>
					<p style="border:1px solid #F0AD4E;background:#fffaf4;padding:10px;box-sizing:border-box;"><span style="font-weight:600;"><?php esc_html_e( 'If the form isn\'t loading on the public website, try inserting its shortcode with the iframe attribute set to 1:', 'calculated-fields-form' ); ?> [CP_CALCULATED_FIELDS id="<?php print esc_html(CP_CALCULATEDFIELDSF_ID); ?>" iframe="1"]</span><br /><?php _e( 'For server-side processing like sending email copy to users, you\'ll need the <a href="https://cff.dwbooster.com/download" target="_blank">Commercial versions</a> of the plugin.', 'calculated-fields-form' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
					<input type="hidden" name="form_structure" id="form_structure" value="<?php print esc_attr( preg_replace( '/&(quot|lt|gt);/i', '&amp;$1;', json_encode( $form_obj->get_option( 'form_structure', CP_CALCULATEDFIELDSF_DEFAULT_form_structure ) ) ) ); ?>" />
					<input type="hidden" name="templates" id="templates" value="<?php print esc_attr( json_encode( CPCFF_TEMPLATES::load_templates() ) ); ?>" />
					<link href="<?php print esc_attr( plugins_url( '/vendors/jquery-ui/jquery-ui.min.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ) ); // phpcs:ignore WordPress.WP.EnqueuedResources ?>" type="text/css" rel="stylesheet" property="stylesheet" />
					<link href="<?php print esc_attr( plugins_url( '/vendors/jquery-ui/jquery-ui-1.12.icon-font.min.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ) ); // phpcs:ignore WordPress.WP.EnqueuedResources ?>" type="text/css" rel="stylesheet" property="stylesheet" />
					<pre style="display:none;">
						<script data-category="functional" type="text/javascript">
							var cff_metabox_nonce = '<?php print esc_js( wp_create_nonce( 'cff-metabox-status' ) ); ?>';
							try
							{
								function calculatedFieldsFormReady()
								{
									let $ = $calculatedfieldsfQuery;
									/* Nav sections menu */
									$('.cff-navigation-sections-menu a').on( 'mouseup', function(){
										if( $(this).prop('target') != '_blank' )
											$('#metabox_form_structure').removeClass('fullscreen');
									});
									/* Floating save button code */
									function _showHideSaveButtonPopUp() {
										let wt = $(window).scrollTop();
										let et = $('.cff-save-controls-frame').offset().top + $('.cff-save-controls-frame').outerHeight();
										if ( et < wt && !$('#metabox_form_structure.fullscreen').length ) {
											$('.cff-save-controls-floating-popup').show('slow');
										} else {
											$('.cff-save-controls-floating-popup').hide('slow');
										}
									};
									$(window).on('scroll', _showHideSaveButtonPopUp);
									_showHideSaveButtonPopUp();
									/* Revisions code */
									$('[name="cff_apply_revision"]').on( 'click',
										function(){
											var revision = $('[name="cff_revision_list"]').val();
											if(revision*1)
											{
												result = window.confirm('<?php print esc_js( __( 'The action will load the revision selected, the data are not stored will be lose. Do you want continue?', 'calculated-fields-form' ) ); ?>');
												if(result)
												{
													try {
														if('fbuilder' in $) $.fbuilder['formWasModified'] = false; // Form changes where saved.
													} catch ( err ) {}
													$('<form method="post" action="<?php echo esc_attr( $admin_url ); ?>" id="cpformconf" name="cpformconf" class="cff_form_builder"><input type="hidden" name="_cpcff_nonce" value="<?php echo esc_attr( $_cpcff_form_settings_nonce ); ?>" /><input name="cp_calculatedfieldsf_id" type="hidden" value="<?php echo esc_attr( CP_CALCULATEDFIELDSF_ID ); ?>" /><input type="hidden" name="cpcff_revision_to_apply" value="'+cff_esc_attr( revision )+'"></form>').appendTo('body').submit();
												}
											}
										}
									);

									/* Form builder code */
									var f;
									function run_fbuilder($)
									{
										f = $("#fbuilder").fbuilder();
										window['cff_form'] = f;
										f.fBuild.loadData( "form_structure", "templates" );
									};

									if(!('fbuilder' in $.fn))
									{
										$.getScript(
											location.protocol + '//' + location.host + location.pathname+'?page=cp_calculated_fields_form&cp_cff_resources=admin',
											function(){run_fbuilder(fbuilderjQuery);}
										);
									}
									else
									{
										run_fbuilder($);
									}

									$(".itemForm").on( 'click', function() {
										f.fBuild.addItem($(this).attr("id"));
									})
									.draggable({
										connectToSortable: '#fbuilder #fieldlist',
										delay: 100,
										helper: function() {
											var e = $(this),
												width = e.outerWidth(),
												text = e.text(),
												type = e.attr('id'),
												el = $('<div class="cff-button-drag '+type+'">');

											return el.html( text ).css( 'width', width ).attr('data-control',type);
										},
										revert: 'invalid',
										cancel: false,
										scroll: false,
										opacity: 1,
										containment: 'document',
										stop: function(){$('.ctrlsColumn .itemForm').removeClass('button-primary');}
									});

									jQuery(".metabox_disabled_section .inside")
									.on( 'click',  function(){
										if(confirm("<?php print esc_js( __( 'These features aren\'t available in this version. Do you want to open the plugin\'s page to check other versions?', 'calculated-fields-form' ) ); ?>"))
											window.open( 'https://cff.dwbooster.com/download', '_blank' );
									})
									.find('*')
									.prop('disabled', true);
								};
							}
							catch( err ){}
							try{$calculatedfieldsfQuery = jQuery.noConflict();} catch ( err ) {}
							if (typeof $calculatedfieldsfQuery == 'undefined')
							{
								if(window.addEventListener){
									window.addEventListener('load', function(){
										try{$calculatedfieldsfQuery = jQuery.noConflict();} catch ( err ) {return;}
										calculatedFieldsFormReady();
									});
								}else{
									window.attachEvent('onload', function(){
										try{$calculatedfieldsfQuery = jQuery.noConflict();} catch ( err ) {return;}
										calculatedFieldsFormReady();
									});
								}
							}
							else
							{
								$calculatedfieldsfQuery(document).ready( calculatedFieldsFormReady );
							}
						</script>
					</pre>
					<div style="background:#f8f8f8;" class="form-builder">
						<div class="column ctrlsColumn">
							<div id="tabs">
								<span class="ui-icon ui-icon-triangle-1-e expand-shrink"></span>
								<ul>
									<li><a href="#tabs-1"><?php esc_html_e( 'Add a Field', 'calculated-fields-form' ); ?></a></li>
									<li><a href="#tabs-2"><?php esc_html_e( 'Field Settings', 'calculated-fields-form' ); ?></a></li>
									<li><a href="#tabs-3"><?php esc_html_e( 'Form Settings', 'calculated-fields-form' ); ?></a></li>
								</ul>
								<div id="tabs-1"></div>
								<div id="tabs-2"></div>
								<div id="tabs-3"></div>
							</div>
						</div>
						<div class="columnr dashboardColumn padding10" id="fbuilder">
							<div id="formheader"></div>
							<div id="fieldlist"></div>
						</div>
						<div class="clearer"></div>
					</div>
				</div>
			</div>
			<p class="cff-save-controls-frame">
				<input type="submit" name="save" id="save2" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'calculated-fields-form' ); ?>"  title="<?php esc_attr_e("Saves the form's structure and settings and creates a revision", 'calculated-fields-form'); ?>" onclick="fbuilderjQuery.fbuilder.delete_form_preview_window();" />
				<input type="button" name="previewbtn" id="previewbtn" class="button-primary" value="<?php esc_attr_e( 'Preview', 'calculated-fields-form' ); ?>" onclick="fbuilderjQuery.fbuilder.preview( this );" title="<?php esc_attr_e("Saves the form's structure only, and opens a preview windows", 'calculated-fields-form'); ?>" />
				<?php
				if ( get_option( 'CP_CALCULATEDFIELDSF_DISABLE_REVISIONS', CP_CALCULATEDFIELDSF_DISABLE_REVISIONS ) == 0 ) :
					?>
					| <label><input type="checkbox" name="cff-revisions-in-preview"
					<?php
					if ( get_option( 'CP_CALCULATEDFIELDSF_REVISIONS_IN_PREVIEW', true ) ) {
						print 'CHECKED';}
					?>
						/>
					<?php
					esc_html_e( 'Generate revisions in the form preview as well', 'calculated-fields-form' );
					?></label><?php
				endif;
				?>
			</p>
			<div class="cff-save-controls-floating-popup">
				<div class="cff-website-icon"></div>
				<div class="cff-popup-icon">
					<div class="cff-popup-bubble"><?php esc_html_e( 'Save Changes', 'calculated-fields-form' ); ?></div>
					<input name="save" type="image" src="<?php print esc_attr( plugins_url('../images/icons/save.svg', __FILE__) ); ?>" alt="<?php esc_attr_e( 'Save Changes', 'calculated-fields-form' ); ?>" onclick="fbuilderjQuery.fbuilder.delete_form_preview_window();">
				</div>
				<div class="cff-popup-icon">
					<div class="cff-popup-bubble"><?php esc_html_e( 'Preview', 'calculated-fields-form' ); ?></div>
					<input name="prvw" type="image" src="<?php print esc_attr( plugins_url('../images/icons/preview.svg', __FILE__) ); ?>" alt="<?php esc_attr_e( 'Preview', 'calculated-fields-form' ); ?>" onclick="fbuilderjQuery.fbuilder.preview( this );">
				</div>
				<div class="cff-popup-icon-separator"></div>
				<div class="cff-popup-icon">
					<div class="cff-popup-bubble"><?php esc_html_e( 'Form Structure', 'calculated-fields-form' ); ?></div>
					<a href="#cpformconf"><img src="<?php print esc_attr( plugins_url('../images/icons/form.svg', __FILE__) ); ?>" alt="<?php esc_attr_e( 'Form Structure', 'calculated-fields-form' ); ?>"></a>
				</div>
				<div class="cff-popup-icon">
					<div class="cff-popup-bubble"><?php esc_html_e( 'General Texts', 'calculated-fields-form' ); ?></div>
					<a href="#metabox_define_texts"><img src="<?php print esc_attr( plugins_url('../images/icons/text.svg', __FILE__) ); ?>" alt="<?php esc_attr_e( 'General Texts', 'calculated-fields-form' ); ?>"></a>
				</div>
				<div class="cff-popup-icon">
					<div class="cff-popup-bubble"><?php esc_html_e( 'Validation Texts', 'calculated-fields-form' ); ?></div>
					<a href="#metabox_define_validation_texts"><img src="<?php print esc_attr( plugins_url('../images/icons/error.svg', __FILE__) ); ?>" alt="<?php esc_attr_e( 'Validation Texts', 'calculated-fields-form' ); ?>"></a>
				</div>
				<div class="cff-popup-icon">
					<div class="cff-popup-bubble"><?php esc_html_e( 'Submit Button and Thank You Page', 'calculated-fields-form' ); ?></div>
					<a href="#metabox_submit_thank"><img src="<?php print esc_attr( plugins_url('../images/icons/submit.svg', __FILE__) ); ?>" alt="<?php esc_attr_e( 'Submit Button and Thank You Page', 'calculated-fields-form' ); ?>"></a>
				</div>
				<div class="cff-popup-icon">
					<div class="cff-popup-bubble"><?php esc_html_e( 'Notification Email', 'calculated-fields-form' ); ?></div>
					<a href="#metabox_notification_email"><img src="<?php print esc_attr( plugins_url('../images/icons/email.svg', __FILE__) ); ?>" alt="<?php esc_attr_e( 'Notification Email', 'calculated-fields-form' ); ?>"></a>
				</div>
				<div class="cff-popup-icon-separator"></div>
				<div class="cff-popup-icon cff-popup-icon-disabled">
					<div class="cff-popup-bubble"><?php esc_html_e( 'Payment Settings', 'calculated-fields-form' ); ?></div>
					<a href="#metabox_payment_settings"><img src="<?php print esc_attr( plugins_url('../images/icons/payment.svg', __FILE__) ); ?>" alt="<?php esc_attr_e( 'Payment Settings', 'calculated-fields-form' ); ?>"></a>
				</div>
				<div class="cff-popup-icon cff-popup-icon-disabled">
					<div class="cff-popup-bubble"><?php esc_html_e( 'Email Copy to User', 'calculated-fields-form' ); ?></div>
					<a href="#metabox_email_copy_to_user"><img src="<?php print esc_attr( plugins_url('../images/icons/usercopy.svg', __FILE__) ); ?>" alt="<?php esc_attr_e( 'Email Copy to User', 'calculated-fields-form' ); ?>"></a>
				</div>
				<div class="cff-popup-icon cff-popup-icon-disabled">
					<div class="cff-popup-bubble"><?php esc_html_e( 'Captcha Settings', 'calculated-fields-form' ); ?></div>
					<a href="#metabox_captcha_settings"><img src="<?php print esc_attr( plugins_url('../images/icons/captcha.svg', __FILE__) ); ?>" alt="<?php esc_attr_e( 'Captcha Settings', 'calculated-fields-form' ); ?>"></a>
				</div>
				<div class="cff-popup-icon-separator"></div>
				<div class="cff-popup-icon">
					<div class="cff-popup-bubble"><?php esc_html_e( 'Add Ons', 'calculated-fields-form' ); ?></div>
					<a href="#metabox_addons_section"><img src="<?php print esc_attr( plugins_url('../images/icons/addons.svg', __FILE__) ); ?>" alt="<?php esc_attr_e( 'Add Ons', 'calculated-fields-form' ); ?>"></a>
				</div>
			</div>
			<?php print $section_nav_bar; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div id="metabox_define_texts" class="postbox cff-metabox <?php print esc_attr( $cpcff_main->metabox_status( 'metabox_define_texts' ) ); ?>" style="margin-top:20px;">
				<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Define Texts', 'calculated-fields-form' ); ?></span></h3>
				<div class="inside">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><label for="vs_text_submitbtn"><?php esc_html_e( 'Submit button label (text)', 'calculated-fields-form' ); ?>:</label></th>
							<td><input type="text" id="vs_text_submitbtn" name="vs_text_submitbtn" class="width75" value="<?php
							$label = $form_obj->get_option( 'vs_text_submitbtn', 'Submit' );
							echo esc_attr( '' == $label ? 'Submit' : $label );
							?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="vs_text_previousbtn"><?php esc_html_e( 'Previous button label (text)', 'calculated-fields-form' ); ?>:</label></th>
							<td><input type="text" id="vs_text_previousbtn" name="vs_text_previousbtn" class="width75" value="<?php
							$label = $form_obj->get_option( 'vs_text_previousbtn', 'Previous' );
							echo esc_attr( '' == $label ? 'Previous' : $label );
							?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="vs_text_nextbtn"><?php esc_html_e( 'Next button label (text)', 'calculated-fields-form' ); ?>:</label></th>
							<td><input type="text" id="vs_text_nextbtn" name="vs_text_nextbtn" class="width75" value="<?php
							$label = $form_obj->get_option( 'vs_text_nextbtn', 'Next' );
							echo esc_attr( '' == $label ? 'Next' : $label );
							?>" /></td>
						</tr>
						<tr valign="top">
							<td colspan="2">
								<?php _e( '- The styles can be applied into any of the CSS files of your theme or into the CSS file <em>"calculated-fields-form\css\stylepublic.css"</em>.', 'calculated-fields-form' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><br />
								<?php _e( '- For general CSS styles modifications to the form and samples <a href="https://cff.dwbooster.com/faq#q82" target="_blank">check this FAQ</a>.', 'calculated-fields-form' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</td>
						</tr>
						<?php
						// Display all other text fields.
						foreach ( $cpcff_texts_array as $cpcff_text_index => $cpcff_text_attr ) {
							if ( 'errors' !== $cpcff_text_index && isset( $cpcff_text_attr['label'] ) ) {
								print '
								<tr valign="top">
									<th scope="row"><label for="cpcff_text_array['.$cpcff_text_index.'][text]">' . esc_html( $cpcff_text_attr['label'] ) . ':</label></th>
									<td><input type="text" id="cpcff_text_array[' . esc_attr( $cpcff_text_index ) . '][text]" name="cpcff_text_array[' . esc_attr( $cpcff_text_index ) . '][text]" class="width75" value="' . esc_attr( $cpcff_text_attr['text'] ) . '" /></td>
								</tr>
								';
							}
						}
						?>
					</table>
					<div class="cff-goto-top"><a href="#cpformconf"><?php esc_html_e( 'Up to form structure', 'calculated-fields-form' ); ?></a></div>
				</div>
			</div>

			<div id="metabox_define_validation_texts" class="postbox cff-metabox <?php print esc_attr( $cpcff_main->metabox_status( 'metabox_define_validation_texts' ) ); ?>" >
				<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Validation Settings', 'calculated-fields-form' ); ?></span></h3>
				<div class="inside">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><label for="vs_text_is_required"><?php esc_html_e( '"is required" text', 'calculated-fields-form' ); ?>:</label></th>
							<td><input type="text" id="vs_text_is_required" name="vs_text_is_required" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'vs_text_is_required', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_is_required ) ); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="vs_text_is_email"><?php esc_html_e( '"is email" text', 'calculated-fields-form' ); ?>:</label></th>
							<td><input type="text" id="vs_text_is_email" name="vs_text_is_email" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'vs_text_is_email', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_is_email ) ); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="cv_text_enter_valid_captcha"><?php esc_html_e( '"is valid captcha" text', 'calculated-fields-form' ); ?>:</label></th>
							<td><input type="text" id="cv_text_enter_valid_captcha" name="cv_text_enter_valid_captcha" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'cv_text_enter_valid_captcha', CP_CALCULATEDFIELDSF_DEFAULT_cv_text_enter_valid_captcha ) ); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="vs_text_datemmddyyyy"><?php esc_html_e( '"is valid date (mm/dd/yyyy)" text', 'calculated-fields-form' ); ?>:</label></th>
							<td><input type="text" id="vs_text_datemmddyyyy" name="vs_text_datemmddyyyy" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'vs_text_datemmddyyyy', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_datemmddyyyy ) ); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="vs_text_dateddmmyyyy"><?php esc_html_e( '"is valid date (dd/mm/yyyy)" text', 'calculated-fields-form' ); ?>:</label></th>
							<td><input type="text" id="vs_text_dateddmmyyyy" name="vs_text_dateddmmyyyy" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'vs_text_dateddmmyyyy', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_dateddmmyyyy ) ); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="vs_text_number"><?php esc_html_e( '"is number" text', 'calculated-fields-form' ); ?>:</label></th>
							<td><input type="text" id="vs_text_number" name="vs_text_number" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'vs_text_number', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_number ) ); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="vs_text_digits"><?php esc_html_e( '"only digits" text', 'calculated-fields-form' ); ?>:</label></th>
							<td><input type="text" id="vs_text_digits" name="vs_text_digits" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'vs_text_digits', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_digits ) ); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="vs_text_max"><?php esc_html_e( '"under maximum" text', 'calculated-fields-form' ); ?>:</label></th>
							<td><input type="text" id="vs_text_max" name="vs_text_max" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'vs_text_max', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_max ) ); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="vs_text_min"><?php esc_html_e( '"over minimum" text', 'calculated-fields-form' ); ?>:</label></th>
							<td><input type="text" id="vs_text_min" name="vs_text_min" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'vs_text_min', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_min ) ); ?>" /></td>
						</tr>
						<?php
						// Display all other text fields.
						if ( ! empty( $cpcff_texts_array['errors'] ) ) {
							foreach ( $cpcff_texts_array['errors'] as $cpcff_text_index => $cpcff_text_attr ) {
								if ( isset( $cpcff_text_attr['label'] ) ) {
									print '
									<tr valign="top">
										<th scope="row"><label for="cpcff_text_array[errors][' . esc_attr( $cpcff_text_index ) .'][text]">' . esc_html( $cpcff_text_attr['label'] ) . ':</label></th>
										<td><input type="text" id="cpcff_text_array[errors][' . esc_attr( $cpcff_text_index ) . '][text]" name="cpcff_text_array[errors][' . esc_attr( $cpcff_text_index ) . '][text]" class="width75" value="' . esc_attr( $cpcff_text_attr['text'] ) . '" /></td>
									</tr>
									';
								}
							}
						}
						?>
					</table>
					<div class="cff-goto-top"><a href="#cpformconf"><?php esc_html_e( 'Up to form structure', 'calculated-fields-form' ); ?></a></div>
				</div>
			</div>

			<h2><?php esc_html_e( 'Form Processing', 'calculated-fields-form' ); ?>:</h2>
			<hr />

			<div id="metabox_submit_thank" class="postbox cff-metabox <?php print esc_attr( $cpcff_main->metabox_status( 'metabox_submit_thank' ) ); ?>" >
				<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Submit Button and Thank You Page', 'calculated-fields-form' ); ?></span></h3>
				<div class="inside">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><label for="enable_submit"><?php esc_html_e( 'Display submit button?', 'calculated-fields-form' ); ?></label></th>
							<td>
								<?php
								$option = $form_obj->get_option( 'enable_submit', CP_CALCULATEDFIELDSF_DEFAULT_display_submit_button );
								?>
								<select id="enable_submit" name="enable_submit">
									<option value="" <?php
									if ( '' == $option ) {
										echo ' selected';}
									?>><?php esc_html_e( 'Yes', 'calculated-fields-form' ); ?></option>
									<option value="no" <?php
									if ( 'no' == $option ) {
										echo ' selected';}
									?>><?php esc_html_e( 'No', 'calculated-fields-form' ); ?></option>
								</select>
								<label style="margin-left:20px;<?php if( 'no' != $option) print 'display:none;'; ?>" class="disable-submissions-section"><input type="checkbox" id="fp_disable_submissions" name="fp_disable_submissions" <?php
										if ( $form_obj->get_option('fp_disable_submissions',0) && 'no' == $option ) print 'CHECKED';
									?>><?php esc_html_e( 'Turn off form submissions entirely (button & code)', 'calculated-fields-form' ); ?>
								</label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="fp_return_page"><?php esc_html_e( 'Thank you page (after sending the message)', 'calculated-fields-form' ); ?></label></th>
							<td>
								<input type="text" id="fp_return_page" name="fp_return_page" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'fp_return_page', CP_CALCULATEDFIELDSF_DEFAULT_fp_return_page ) ); ?>" /><br />
								<p><i><?php esc_html_e( 'Enter <%from_page%> to reload the form page after submission.', 'calculated-fields-form'); ?></i></p>
								<div style="border:1px solid #F0AD4E;background:#fffaf4;padding:10px;color:#3c434a;margin-top:20px;margin-bottom:20px;box-sizing:border-box;" class="cff-expand-mssg width75">
									<p><?php esc_html_e( 'Commercial plugin versions allow you to include a summary of the information collected by the form on the "Thank You Page" content.', 'calculated-fields-form' ); ?> <a href="https://cff.dwbooster.com/download" target="_blank" class="button-primary"><?php esc_html_e( 'Upgrade Now', 'calculated-fields-form' ); ?></a></p>
								</div>
							</td>
						</tr>
						<tr valign="top">
							<td colspan="2" style="text-align:center;">
								<?php esc_html_e( '- OR -', 'calculated-fields-form' ); ?>
							</td>
						</tr>
						<tr valign="top">
							<td></td>
							<td>
								<label><input type="checkbox" name="fp_ajax" value="1" <?php echo $form_obj->get_option('fp_ajax', 0) ? 'CHECKED' : ''; ?> />
								<?php esc_html_e( 'Submit the form using AJAX instead of redirecting the user to the thank you page.', 'calculated-fields-form' ); ?></label><br />
								<label><input type="checkbox" name="fp_ajax_reset_form" value="1" <?php echo $form_obj->get_option('fp_ajax_reset_form', 0) ? 'CHECKED' : ''; ?> />
								<?php esc_html_e( "Reset the fields' values after submitting the form using AJAX.", 'calculated-fields-form' ); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="fp_thanks_mssg"><?php esc_html_e( 'Thank you message', 'calculated-fields-form' ); ?></label></th>
							<td>
								<textarea id="fp_thanks_mssg" name="fp_thanks_mssg" class="width75" style="" rows="4"><?php
								print esc_textarea( $form_obj->get_option('fp_thanks_mssg', '') );
								?></textarea>
							</td>
						</tr>
					</table>

					<div class="cff-goto-top"><a href="#cpformconf"><?php esc_html_e( 'Up to form structure', 'calculated-fields-form' ); ?></a></div>
				</div>
			</div>
			<script data-category="functional">
			jQuery(document).on('change', '[name="enable_submit"]', function(){
				if ( 'no' == jQuery('[name="enable_submit"]').val() ) {
					jQuery('.disable-submissions-section').css('display', 'inline-block');
				} else {
					jQuery('.disable-submissions-section').hide();
					jQuery('[name="fp_disable_submissions"]').prop('checked', false);
				}
			});
			</script>

			<div id="metabox_notification_email" class="postbox cff-metabox <?php print esc_attr( $cpcff_main->metabox_status( 'metabox_notification_email' ) ); ?>" >
				<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Form Processing / Email Settings', 'calculated-fields-form' ); ?></span></h3>
				<div class="inside">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php esc_html_e( '"From" email', 'calculated-fields-form' ); ?></th>
							<td><input type="text" name="fp_from_email" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'fp_from_email', CP_CALCULATEDFIELDSF_DEFAULT_fp_from_email ) ); ?>" placeholder="<?php print esc_attr( 'Ex. admin@' . str_replace( 'www.', '', $_SERVER["HTTP_HOST"] ) ); ?>" /><br><b><em style="font-size:11px;">Ex. admin@<?php echo esc_html( isset( $_SERVER['HTTP_HOST'] ) ? str_replace( 'www.', '', sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '' ); ?></em></b></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Destination emails (comma separated)', 'calculated-fields-form' ); ?></th>
							<td>
								<input type="text" name="fp_destination_emails" class="width75" value="<?php echo esc_attr($form_obj->get_option('fp_destination_emails', CP_CALCULATEDFIELDSF_DEFAULT_fp_destination_emails)); ?>" placeholder="Ex. destination-email@domain.com" />
								<p><a href="javascript:void(0);" onclick="document.getElementsByName('fp_destination_emails')[0].value='';"><?php esc_html_e( 'If you do not want to receive emails, please leave the "destination" attribute blank.', 'calculated-fields-form' ); ?></a></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Reply-To (comma separated)', 'calculated-fields-form' ); ?></th>
							<td>
								<input type="text" name="fp_reply_to_emails" class="width75" value="<?php echo esc_attr($form_obj->get_option('fp_reply_to_emails', '')); ?>" placeholder="Ex. reply-to-email@domain.com" />
								<p><em><?php esc_html_e( 'Please enter the email fields\' tags separated by commas (e.g., <%fieldname1%>,<%fieldname2%>). If the attribute is left empty, the plugin will utilize the email fields selected from the "Email field on the form" attribute in the "Email Copy to User" section.', 'calculated-fields-form' ); ?></em></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Email subject', 'calculated-fields-form' ); ?></th>
							<td><input type="text" name="fp_subject" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'fp_subject', CP_CALCULATEDFIELDSF_DEFAULT_fp_subject ) ); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Include additional information?', 'calculated-fields-form' ); ?></th>
							<td>
								<?php $option = $form_obj->get_option( 'fp_inc_additional_info', CP_CALCULATEDFIELDSF_DEFAULT_fp_inc_additional_info ); ?>
								<select name="fp_inc_additional_info">
									<option value="true" <?php
									if ( 'true' == $option ) {
										echo ' selected';}
									?>><?php esc_html_e( 'Yes', 'calculated-fields-form' ); ?></option>
									<option value="false" <?php
									if ( 'false' == $option ) {
										echo ' selected';}
									?>><?php esc_html_e( 'No', 'calculated-fields-form' ); ?></option>
								</select>&nbsp;<em style="font-size:11px;"><?php esc_html_e( 'If the "No" option is selected the plugin won\'t capture the IP address of users.', 'calculated-fields-form' ); ?></em>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Include attachments?', 'calculated-fields-form' ); ?></th>
							<td>
								<select name="fp_inc_attachments">
									<option value="0" <?php
									if ( $form_obj->get_option( 'fp_inc_attachments', 0 ) != '1' ) {
										echo 'selected';}
									?>><?php esc_html_e( 'No', 'calculated-fields-form' ); ?></option>
									<option value="1" <?php
									if ( $form_obj->get_option( 'fp_inc_attachments', 0 ) == '1' ) {
										echo 'selected';}
									?>><?php esc_html_e( 'Yes', 'calculated-fields-form' ); ?></option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Email format?', 'calculated-fields-form' ); ?></th>
							<td>
								<?php $option = $form_obj->get_option( 'fp_emailformat', CP_CALCULATEDFIELDSF_DEFAULT_email_format ); ?>
								<select name="fp_emailformat" class="width75">
									<option value="text" <?php
									if ( 'html' != $option ) {
										echo ' selected';}
									?>><?php esc_html_e( 'Plain Text (default)', 'calculated-fields-form' ); ?></option>
									<option value="html" <?php
									if ( 'html' == $option ) {
										echo ' selected';}
									?>><?php esc_html_e( 'HTML (use html in the textarea below)', 'calculated-fields-form' ); ?></option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Message', 'calculated-fields-form' ); ?></th>
							<td>
								<textarea type="text" name="fp_message" rows="6" class="width75" style="resize:vertical;"><?php echo esc_textarea( $form_obj->get_option( 'fp_message', CP_CALCULATEDFIELDSF_DEFAULT_fp_message ) ); ?></textarea>
								<div style="border:1px solid #F0AD4E;background:#fffaf4;padding:10px;color:#3c434a;margin-top:20px;margin-bottom:20px;box-sizing:border-box;" class="cff-expand-mssg width75">
									<p><?php esc_html_e( 'The plugin replaces the <%INFO%> tag in the email content with a summary of the information collected by the form. However, you can customize the email content and design by combining the fields and HTML tags. Learn more about the fields and informative tags supported by the notification emails by visiting the link:', 'calculated-fields-form' ); ?> <a href="https://cff.dwbooster.com/documentation#special-tags" target="_blank"><?php esc_html_e( 'Fields and informative tags', 'calculated-fields-form' ); ?></a></p>
								</div>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Attach static file', 'calculated-fields-form' ); ?></th>
							<td>
								<input type="text" name="fp_attach_static" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'fp_attach_static', '' ) ); ?>" />
								<input type="button" value="<?php echo esc_attr__( 'Select file', 'calculated-fields-form' ); ?>" class="button-secondary cff-attach-file" />
								<br><em style="font-size:11px;"><?php esc_html_e( 'Enter the path to a static file you wish to attach to the notification email.', 'calculated-fields-form' ); ?></em>
							</td>
						</tr>
					</table>

					<div style="border:1px solid #F0AD4E;background:#fffaf4;padding:10px;color:#3c434a;margin-bottom:20px;box-sizing:border-box;">
						<p>
						<?php
							esc_html_e(
								'If you or your users do not receive the notification emails, they are probably being blocked by the web server. If so, install any of the SMTP connection plugins distributed through the WordPress directory, and configure it to use your hosting provider\'s SMTP server.',
								'calculated-fields-form'
							);
							?>
						</p>
						<p><a href="https://wordpress.org/plugins/search/SMTP+Connection/" target="_blank" style="width:100%;display:block;overflow:hidden;text-overflow:ellipsis;">https://wordpress.org/plugins/search/SMTP+Connection/</a></p>
					</div>

					<div class="cff-goto-top"><a href="#cpformconf"><?php esc_html_e( 'Up to form structure', 'calculated-fields-form' ); ?></a></div>
				</div>
			</div>

			<div id="metabox_basic_settings" class="postbox" style="margin-right:30px;">
				<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Note', 'calculated-fields-form' ); ?></span></h3>
				<div class="inside">
					<?php esc_html_e( 'To display the form in a post/page, enter your shortcode in the post/page content:', 'calculated-fields-form' ); ?>
					<?php print '<b>[CP_CALCULATED_FIELDS id="' . esc_attr( CP_CALCULATEDFIELDSF_ID ) . '"]</b>'; ?><br />
					<?php esc_html_e( 'The CFF plugin implements widgets and blocks to allow inserting the form visually with the most popular page builders such as Gutenberg Editor, Classic Editor, Elementor, Site Origin, Visual Composer, Beaver Builder, Divi, and for the other page builders insert the shortcode directly.', 'calculated-fields-form' ); ?>
					<br /><br />
				</div>
			</div>

			<p class="submit">
				<input type="submit" name="save" id="save1" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'calculated-fields-form' ); ?>" title="<?php esc_attr_e("Saves the form's structure and settings and creates a revision", 'calculated-fields-form'); ?>" onclick="fbuilderjQuery.fbuilder.delete_form_preview_window();" />
			</p>

			[<a href="https://cff.dwbooster.com/customization" target="_blank"><?php esc_html_e( 'Request Custom Modifications', 'calculated-fields-form' ); ?></a>] | [<a href="https://wordpress.org/support/plugin/calculated-fields-form#new-post" target="_blank"><?php esc_html_e( 'Help', 'calculated-fields-form' ); ?></a>]

			<br /><br /><br />
			<style>.cff-metabox,.metabox_disabled_section{margin-right:30px;}@media screen and (min-width:710px){.cff-plugin-promote{width: calc( 100% - 180px );}} @media screen and (max-width:710px){.cff-plugin-logo-promote{display:none;} .cff-expand-mssg{width:100% !important;} }#cff-payment-gateways-accordion .cff-metabox, #cff-payment-gateways-accordion .metabox_disabled_section{margin-bottom:5px;} .cff-addons-complementary-plugin-form-settings:empty::before{content: "<?php esc_attr_e( '- Empty Area -', 'calculated-fields-form' ); ?>"; margin-bottom:40px;display:block;text-align:center;}</style>

			<div id="cff-upgrade-frame" style="border:1px solid #F0AD4E;background:#FBE6CA;padding:10px;color:#3c434a;margin-bottom:20px;box-sizing:border-box;margin-right:30px;">
				<a href="https://cff.dwbooster.com/download" target="_blank" style="text-decoration:none;float:left;" class="cff-plugin-logo-promote"><img src="https://ps.w.org/calculated-fields-form/assets/icon-256x256.jpg" style="width:160px;border:2px solid white;margin-right:10px;margin-bottom:10px;"></a>
				<div style="float:left;" class="cff-plugin-promote">
					<div style="font-weight:500;font-size:20px;line-height:28px;"><?php _e( 'The following features are available in the commercial version of the <a href="https://cff.dwbooster.com/download" target="_blank" style="text-decoration:none;">"Calculated Fields Form"</a>', 'calculated-fields-form' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<div style="text-transform: uppercase; font-weight:700; font-size:24px;margin-top:15px;margin-bottom:15px;line-height:28px;"><a href="https://cff.dwbooster.com/download" target="_blank" style="text-decoration:none;color:#3c434a;text-shadow:1px 1px 2px white;"><?php esc_html_e( 'Pay only ONCE, use it FOREVER', 'calculated-fields-form' ); ?></a></div>
					<div style="font-size:18px; font-weight:400;line-height:28px;">No additional charges, <span style="background:white;display:inline-block;padding:0 5px;"><a href="https://cff.dwbooster.com/terms" target="_blank" style="text-decoration:none;">lifetime updates</a></span>, one copy for all your websites.</div>
					<div style="font-size:16px; font-weight:400; font-style: italic;">And you get notification emails, payment gateways integration, data and forms exportation, advanced operations and more...</div>
					<?php
					print get_option( 'cff-t-t', '<div style="text-align:right; font-size:16px; font-weight:600;margin-top:15px;">To test some of the commercial features of the "Calculated Fields Form" plugin, you can <a class="button-primary" href="admin.php?page=cp_calculated_fields_form&cal=' . CP_CALCULATEDFIELDSF_ID . '&_cpcff_nonce=' . $_cpcff_form_settings_nonce . '&cff-install-trial=1#cff-upgrade-frame">install the trial version</a></div>' ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</div>
				<div style="clear:both;"></div>
			</div>

			<div id="metabox_payment_settings" class="postbox metabox_disabled_section cff-metabox <?php print esc_attr( $cpcff_main->metabox_status( 'metabox_payment_settings' ) ); ?>" style="position:relative;">
				<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Payment Settings', 'calculated-fields-form' ); ?></span></h3>
				<div class="inside">

					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Request cost', 'calculated-fields-form' ); ?></th>
							<td><select name="request_cost" id="request_cost" class="width75"></select></td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Currency', 'calculated-fields-form' ); ?></th>
							<td><input type="text" name="currency" value="<?php echo esc_attr( $form_obj->get_option( 'currency', CP_CALCULATEDFIELDSF_DEFAULT_CURRENCY ) ); ?>" class="width50" /><br>
							<b>USD</b> (<?php esc_html_e( 'United States dollar', 'calculated-fields-form' ); ?>), <b>EUR</b> (Euro), <b>GBP</b> (<?php esc_html_e( 'Pound sterling', 'calculated-fields-form' ); ?>), ... (<a href="https://developer.paypal.com/docs/api/reference/currency-codes/" target="_blank"><?php esc_html_e( 'Currency Codes', 'calculated-fields-form' ); ?></a>)
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Base amount', 'calculated-fields-form' ); ?>:</th>
							<td><input type="text" name="paypal_base_amount" value="<?php echo esc_attr( $form_obj->get_option( 'paypal_base_amount', '0.01' ) ); ?>" class="width50" /><br><i style="font-size:11px;"><?php esc_html_e( 'Minimum amount to charge. If the final price is lesser than this number, the base amount will be applied.', 'calculated-fields-form' ); ?></i>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Product name', 'calculated-fields-form' ); ?></th>
							<td><input type="text" name="paypal_product_name" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'paypal_product_name', CP_CALCULATEDFIELDSF_DEFAULT_PRODUCT_NAME ) ); ?>" /></td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Discount Codes', 'calculated-fields-form' ); ?></th>
							<td>
								<div id="dex_nocodes_availmsg"><?php esc_html_e( 'This feature isn\'t available in this version.', 'calculated-fields-form' ); ?></div>
								<br />
								<strong><?php esc_html_e( 'Add new discount code', 'calculated-fields-form' ); ?>:</strong>
								<br />
								<nobr><?php esc_html_e( 'Code', 'calculated-fields-form' ); ?>: <input type="text" name="dex_dc_code" id="dex_dc_code" value="" /></nobr> &nbsp; &nbsp; &nbsp;
								<nobr><?php esc_html_e( 'Discount', 'calculated-fields-form' ); ?>: <input type="text" size="3" name="dex_dc_discount" id="dex_dc_discount"  value="25" />
								<select name="dex_dc_discounttype" id="dex_dc_discounttype">
									<option value="0"><?php esc_html_e( 'Percent', 'calculated-fields-form' ); ?></option>
									<option value="1"><?php esc_html_e( 'Fixed Value', 'calculated-fields-form' ); ?></option>
								</select></nobr>&nbsp; &nbsp;
								<nobr><?php esc_html_e( 'Valid until', 'calculated-fields-form' ); ?>: <input type="text"  size="10" name="dex_dc_expires" id="dex_dc_expires" value="" /></nobr>&nbsp; &nbsp; &nbsp;
								<input type="button" name="dex_dc_subccode" id="dex_dc_subccode" value="<?php esc_attr_e( 'Add', 'calculated-fields-form' ); ?>" onclick="alert('This feature ins\'t available in this version');" class="button-secondary" />
								<br />
								<em style="font-size:11px;"><?php esc_html_e( 'Note: Expiration date based in server time. Server time now is', 'calculated-fields-form' ); ?> <?php echo esc_html( gmdate( 'Y-m-d H:i' ) ); ?></em>
							</td>
						</tr>
					</table>
					<!-- PAYMENT GATEWAYS -->
					<hr />
					<h3>&#128176; <?php esc_html_e( 'Payment Methods', 'calculated-fields-form' ); ?></h3>
					<div id="cff-payment-gateways-accordion">
						<!-- PAY LATER -->
						<div id="metabox_pay_later" class="postbox cff-metabox <?php print ! empty( $form_obj->get_option('enable_pay_later', 0) ) ? ' cff-payment-gateway-enabled ' : '';  ?>">
							<div class="inside">
								<table class="form-table">
									<tr valign="top">
										<th scope="row"><?php _e( 'Enable "Pay Later" option?', 'calculated-fields-form' ); ?></th>
										<td>
											<select name="enable_pay_later" disabled>
												<option><?php _e( 'No', 'calculated-fields-form' ); ?></option>
											</select>
											<br /><i style="font-size:11px;"><?php esc_html_e( 'Note: When "multiple" payment methods are active, a radio button appears in the form for selection.', 'calculated-fields-form' ); ?></i>
											<div id="cff_paypal_options_label" style="margin-top:10px;background:#EEF5FB;border: 1px dotted #888888;padding:10px;" class="width75">
												<?php _e( 'Label for the "<strong>Pay later</strong>" option (for optional payments)', 'calculated-fields-form' ); ?>:<br />
												<input type="text" name="enable_paypal_option_no" size="70" style="width:100%;" value="" disabled />
											</div>
										</td>
									</tr>
								</table>
								<script data-category="functional">
								jQuery(document).on('change', '[name="enable_pay_later"]', function(){
									jQuery('#metabox_pay_later')[( this.tagName == 'INPUT' && this.checked ) || this.value*1 ? 'addClass' : 'removeClass' ]('cff-payment-gateway-enabled');
								});
								</script>
							</div>
						</div>
						<!-- END PAY LATER -->

						<!-- PAYPAL SECTION -->
						<div id="metabox_paypal_integration" class="postbox metabox_disabled_section cff-metabox cff-metabox-closed">
							<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Paypal Payment Configuration', 'calculated-fields-form' ); ?></span></h3>
							<div class="inside">
							</div>
						</div>

						<div id="metabox_stripe_checkout_addon_form_settings" class="postbox metabox_disabled_section cff-metabox cff-metabox-closed">
							<h3 class='hndle' style="padding:5px;"><span><?php _e( 'CFF - Stripe Checkout', 'calculated-fields-form' ); ?></span></h3>
							<div class="inside">
							</div>
						</div>
						<!-- END PAYPAL SECTION -->
					</div> <!-- PAYMENT GATEWAYS ACCORDION -->
					<div class="cff-goto-top"><a href="#cpformconf"><?php esc_html_e( 'Up to form structure', 'calculated-fields-form' ); ?></a></div>
				</div>
			</div>


			<div id="metabox_email_copy_to_user" class="postbox metabox_disabled_section cff-metabox <?php print esc_attr( $cpcff_main->metabox_status( 'metabox_email_copy_to_user' ) ); ?>" >
				<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Email Copy to User', 'calculated-fields-form' ); ?></span></h3>
				<div class="inside">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Send confirmation/thank you message to user?', 'calculated-fields-form' ); ?></th>
							<td>
								<?php $option = $form_obj->get_option( 'cu_enable_copy_to_user', CP_CALCULATEDFIELDSF_DEFAULT_cu_enable_copy_to_user ); ?>
								<select name="cu_enable_copy_to_user">
									<option value="true" <?php
									if ( 'true' == $option ) {
										echo ' selected';}
									?>><?php esc_html_e( 'Yes', 'calculated-fields-form' ); ?></option>
									<option value="false" <?php
									if ( 'false' == $option ) {
										echo ' selected';}
									?>><?php esc_html_e( 'No', 'calculated-fields-form' ); ?></option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Email field on the form', 'calculated-fields-form' ); ?></th>
							<td><select id="cu_user_email_field" name="cu_user_email_field" def="<?php echo esc_attr( $form_obj->get_option( 'cu_user_email_field', CP_CALCULATEDFIELDSF_DEFAULT_cu_user_email_field ) ); ?>" class="width75"></select></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'BCC', 'calculated-fields-form' ); ?></th>
							<td><input type="email" name="cu_user_email_bcc_field" class="width75" placeholder="Ex. bcc-email@domain.com" disabled />
							<p><em><?php esc_html_e( 'Email address for Blind Carbon Copy.', 'calculated-fields-form' ); ?></em></p></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Reply-To (comma separated)', 'calculated-fields-form' ); ?></th>
							<td>
								<input type="text" name="cu_reply_to_emails" class="width75" placeholder="Ex. reply-to-email@domain.com" disabled />
								<p><em><?php esc_html_e( 'Kindly input email addresses separated by commas. If the field is left empty, the plugin will use the email address provided in the "From" attribute within the "Form Processing / Email Settings" section.', 'calculated-fields-form' ); ?></em></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Email subject', 'calculated-fields-form' ); ?></th>
							<td><input type="text" name="cu_subject" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'cu_subject', CP_CALCULATEDFIELDSF_DEFAULT_cu_subject ) ); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Email format?', 'calculated-fields-form' ); ?></th>
							<td>
								<?php $option = $form_obj->get_option( 'cu_emailformat', CP_CALCULATEDFIELDSF_DEFAULT_email_format ); ?>
								<select name="cu_emailformat" class="width75">
									<option value="text" <?php
									if ( 'html' != $option ) {
										echo ' selected';}
									?>><?php esc_html_e( 'Plain Text (default)', 'calculated-fields-form' ); ?></option>
									<option value="html" <?php
									if ( 'html' == $option ) {
										echo ' selected';}
									?>><?php esc_html_e( 'HTML (use html in the textarea below)', 'calculated-fields-form' ); ?></option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Message', 'calculated-fields-form' ); ?></th>
							<td><textarea type="text" name="cu_message" rows="6" class="width75" style="resize:vertical;"><?php echo esc_textarea( $form_obj->get_option( 'cu_message', CP_CALCULATEDFIELDSF_DEFAULT_cu_message ) ); ?></textarea></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Attach static file', 'calculated-fields-form' ); ?></th>
							<td>
								<input type="text" name="cu_attach_static" class="width75" value="<?php echo esc_attr( $form_obj->get_option( 'cu_attach_static', '' ) ); ?>" />
								<input type="button" value="<?php echo esc_attr__( 'Select file', 'calculated-fields-form' ); ?>" class="button-secondary cff-attach-file" />
								<br><em style="font-size:11px;"><?php esc_html_e( 'Enter the path to a static file you wish to attach to the copy to user email.', 'calculated-fields-form' ); ?></em>
							</td>
						</tr>
					</table>

					<div class="cff-goto-top"><a href="#cpformconf"><?php esc_html_e( 'Up to form structure', 'calculated-fields-form' ); ?></a></div>
				</div>
			</div>

			<div id="metabox_captcha_settings" class="postbox metabox_disabled_section cff-metabox <?php print esc_attr( $cpcff_main->metabox_status( 'metabox_captcha_settings' ) ); ?>" >
				<h3 class='hndle' style="padding:5px;"><span><?php esc_html_e( 'Captcha Verification', 'calculated-fields-form' ); ?></span></h3>
				<div class="inside">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Use Captcha Verification?', 'calculated-fields-form' ); ?></th>
							<td colspan="5">
								<?php $option = $form_obj->get_option( 'cv_enable_captcha', CP_CALCULATEDFIELDSF_DEFAULT_cv_enable_captcha ); ?>
								<select name="cv_enable_captcha">
									<option value="true" <?php
									if ( 'true' == $option ) {
										echo ' selected';}
									?>><?php esc_html_e( 'Yes', 'calculated-fields-form' ); ?></option>
									<option value="false" <?php
									if ( 'false' == $option ) {
										echo ' selected';}
									?>><?php esc_html_e( 'No', 'calculated-fields-form' ); ?></option>
								</select>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Width', 'calculated-fields-form' ); ?>:</th>
							<td><input type="number" readonly=readonly name="cv_width" size="10" max="300" value="<?php echo esc_attr( $form_obj->get_option( 'cv_width', CP_CALCULATEDFIELDSF_DEFAULT_cv_width ) ); ?>" /></td>
							<th scope="row"><?php esc_html_e( 'Height', 'calculated-fields-form' ); ?>:</th>
							<td><input type="number" readonly=readonly name="cv_height" size="10" max="300" value="<?php echo esc_attr( $form_obj->get_option( 'cv_height', CP_CALCULATEDFIELDSF_DEFAULT_cv_height ) ); ?>" /></td>
							<th scope="row"><?php esc_html_e( 'Chars', 'calculated-fields-form' ); ?>:</th>
							<td><input type="number" readonly=readonly name="cv_chars" size="10" value="<?php echo esc_attr( $form_obj->get_option( 'cv_chars', CP_CALCULATEDFIELDSF_DEFAULT_cv_chars ) ); ?>" /></td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Min font size', 'calculated-fields-form' ); ?>:</th>
							<td><input type="number" readonly=readonly name="cv_min_font_size" size="10" value="<?php echo esc_attr( $form_obj->get_option( 'cv_min_font_size', CP_CALCULATEDFIELDSF_DEFAULT_cv_min_font_size ) ); ?>" /></td>
							<th scope="row"><?php esc_html_e( 'Max font size', 'calculated-fields-form' ); ?>:</th>
							<td><input type="number" readonly=readonly name="cv_max_font_size" size="10" value="<?php echo esc_attr( $form_obj->get_option( 'cv_max_font_size', CP_CALCULATEDFIELDSF_DEFAULT_cv_max_font_size ) ); ?>" /></td>
							<td colspan="2" rowspan="">
								<?php esc_html_e( 'Preview', 'calculated-fields-form' ); ?>:<br /><br />
								<img src="<?php echo esc_url( plugins_url( '/captcha/captcha.png', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ) ); ?>"  id="captchaimg" alt="<?php esc_attr_e( 'security code', 'calculated-fields-form' ); ?>" border="0" class="skip-lazy" />
							</td>
						</tr>


						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Noise', 'calculated-fields-form' ); ?>:</th>
							<td><input type="number" readonly=readonly name="cv_noise" size="10" value="<?php echo esc_attr( $form_obj->get_option( 'cv_noise', CP_CALCULATEDFIELDSF_DEFAULT_cv_noise ) ); ?>" /></td>
							<th scope="row"><?php esc_html_e( 'Noise Length', 'calculated-fields-form' ); ?>:</th>
							<td><input type="number" readonly=readonly name="cv_noise_length" size="10" value="<?php echo esc_attr( $form_obj->get_option( 'cv_noise_length', CP_CALCULATEDFIELDSF_DEFAULT_cv_noise_length ) ); ?>" /></td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Background', 'calculated-fields-form' ); ?>:</th>
							<td><input type="text" readonly=readonly name="cv_background" size="10" value="<?php echo esc_attr( $form_obj->get_option( 'cv_background', CP_CALCULATEDFIELDSF_DEFAULT_cv_background ) ); ?>" /></td>
							<th scope="row">Border:</th>
							<td><input type="text" readonly=readonly name="cv_border" size="10" value="<?php echo esc_attr( $form_obj->get_option( 'cv_border', CP_CALCULATEDFIELDSF_DEFAULT_cv_border ) ); ?>" /></td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Font', 'calculated-fields-form' ); ?>:</th>
							<td>
								<select name="cv_font">
									<option value="font-1.ttf" <?php
									if ( 'font-1.ttf' == $form_obj->get_option( 'cv_font', CP_CALCULATEDFIELDSF_DEFAULT_cv_font ) ) {
										echo ' selected';}
									?>>Font 1</option>
									<option value="font-2.ttf" <?php
									if ( 'font-2.ttf' == $form_obj->get_option( 'cv_font', CP_CALCULATEDFIELDSF_DEFAULT_cv_font ) ) {
										echo ' selected';}
									?>>Font 2</option>
									<option value="font-3.ttf" <?php
									if ( 'font-3.ttf' == $form_obj->get_option( 'cv_font', CP_CALCULATEDFIELDSF_DEFAULT_cv_font ) ) {
										echo ' selected';}
									?>>Font 3</option>
									<option value="font-4.ttf" <?php
									if ( 'font-4.ttf' == $form_obj->get_option( 'cv_font', CP_CALCULATEDFIELDSF_DEFAULT_cv_font ) ) {
										echo ' selected';}
									?>>Font 4</option>
								</select>
							</td>
						</tr>
					</table>

					<div class="cff-goto-top"><a href="#cpformconf"><?php esc_html_e( 'Up to form structure', 'calculated-fields-form' ); ?></a></div>
				</div>
			</div>

			<a id="metabox_addons_section"></a>
			<?php
				_e( '<h2>Add-Ons - Complementary Plugin Settings:</h2><hr />', 'calculated-fields-form' );
				print '<div class="cff-addons-complementary-plugin-form-settings">';
				do_action( 'cpcff_form_settings', CP_CALCULATEDFIELDSF_ID );
				print '</div>';
			?>
		</div>

		<p class="submit">
			<input type="submit" name="save" id="save" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'calculated-fields-form' ); ?>"  title="Saves the form's structure and settings" onclick="fbuilderjQuery.fbuilder.delete_form_preview_window();" />
		</p>

		[<a href="https://cff.dwbooster.com/customization" target="_blank"><?php esc_html_e( 'Request Custom Modifications', 'calculated-fields-form' ); ?></a>] | [<a href="https://wordpress.org/support/plugin/calculated-fields-form#new-post" target="_blank"><?php esc_html_e( 'Help', 'calculated-fields-form' ); ?></a>]
	</form>

	<?php
	include_once dirname( __FILE__ ) . '/cpcff_admin_ai_assistant.inc.php';
	?>

</div>