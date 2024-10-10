<?php
/**
 * Plugin Name: Calculated Fields Form
 * Plugin URI: https://cff.dwbooster.com
 * Description: Create forms with field values calculated based in other form field values.
 * Version: 5.2.44
 * Text Domain: calculated-fields-form
 * Author: CodePeople
 * Author URI: https://cff.dwbooster.com
 * License: GPL
 *
 * @package Calculated-Fields-Form
 */

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeEnd
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTagSquiz.Commenting.FunctionComment.MissingParamTag
// phpcs:disable Squiz.Commenting.FunctionComment.Missing

if ( ! defined( 'WP_DEBUG' ) || true != WP_DEBUG ) {
	error_reporting( E_ERROR | E_PARSE );
}

// Defining main constants.
define( 'CP_CALCULATEDFIELDSF_VERSION', '5.2.44' );
define( 'CP_CALCULATEDFIELDSF_MAIN_FILE_PATH', __FILE__ );
define( 'CP_CALCULATEDFIELDSF_BASE_PATH', dirname( CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ) );
define( 'CP_CALCULATEDFIELDSF_BASE_NAME', plugin_basename( CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ) );

// Feedback system.
require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/feedback/cp-feedback.php';
new CP_FEEDBACK( 'calculated-fields-form', __FILE__, 'https://cff.dwbooster.com/contact-us' );

require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_auxiliary.inc.php';
require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/config/cpcff_config.cfg.php';

require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_banner.inc.php';
require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_main.inc.php';

require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_trial.php';

require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_form_cache.inc.php';

// Global variables.
CPCFF_MAIN::instance(); // Main plugin's object.

add_action( 'init', 'cp_calculated_fields_form_check_posted_data', 11 );
add_action( 'init', 'cp_calculated_fields_form_direct_form_access', 1 );

// functions
// ------------------------------------------.

function cp_calculated_fields_form_direct_form_access() {
	if (
		! empty( $_GET['cff-form'] ) &&
		is_numeric( $_GET['cff-form'] ) &&
		intval( $_GET['cff-form'] ) &&
		(
			( get_option( 'CP_CALCULATEDFIELDSF_DIRECT_FORM_ACCESS', CP_CALCULATEDFIELDSF_DIRECT_FORM_ACCESS ) ) ||
			(
				! empty( $_GET['_nonce'] ) &&
				wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_nonce'] ) ), 'cff-iframe-nonce-' . intval( $_GET['cff-form'] ) )
			)
		)
	) {
		$cpcff_main     = CPCFF_MAIN::instance();
		$shortcode_atts = array( 'id' => intval( $_GET['cff-form'] ) );

		foreach ( $_GET as $_param_name => $_param_value ) {
			$_param_name  = sanitize_text_field( wp_unslash( $_param_name ) );
			$_param_value = sanitize_text_field( wp_unslash( $_param_value ) );

			if ( ! in_array( $_param_name, array( 'cff-form', '_nonce', 'cff-form-target', 'iframe' ) ) ) {
				$shortcode_atts[ $_param_name ] = $_param_value;
			}
		}

		$cpcff_main->form_preview(
			array(
				'shortcode_atts' => $shortcode_atts,
				'page_title'     => 'CFF',
				'page'           => true,
			)
		);
	}
} // End cp_calculated_fields_form_direct_form_access

function cp_calculated_fields_form_check_posted_data() {

	global $wpdb;

	$cpcff_main = CPCFF_MAIN::instance();

	if (
		isset( $_SERVER['REQUEST_METHOD'] ) &&
		'POST' == $_SERVER['REQUEST_METHOD']
	) {
		// Save form settings.
		if (
			isset( $_POST['cp_calculatedfieldsf_post_options'] ) &&
			is_admin() &&
			isset( $_POST['_cpcff_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_cpcff_nonce'] ) ), 'cff-form-settings' )
		) {

			cp_calculatedfieldsf_save_options();

			if (
				isset( $_POST['preview'] ) &&
				isset( $_POST['cp_calculatedfieldsf_id'] ) &&
				is_numeric( $_POST['cp_calculatedfieldsf_id'] )
			) {
				$cpcff_main->form_preview(
					array(
						'shortcode_atts' => array( 'id' => intval( $_POST['cp_calculatedfieldsf_id'] ) ),
						'page_title'     => __( 'Form Preview', 'calculated-fields-form' ),
						'wp_die'         => 1,
						'banner'		 => 1
					)
				);
			}
			return;
		} elseif ( // Process form submission.
			isset( $_POST['cp_calculatedfieldsf_id'] ) &&
			is_numeric( $_POST['cp_calculatedfieldsf_id'] ) &&
			isset( $_POST['cp_calculatedfieldsf_pform_psequence'] ) &&
			isset( $_POST['_cpcff_public_nonce'] )
		) {
			$sequence = sanitize_text_field( wp_unslash( $_POST['cp_calculatedfieldsf_pform_psequence'] ) );
			define( 'CP_CALCULATEDFIELDSF_ID', intval( $_POST['cp_calculatedfieldsf_id'] ) );

			if (
				wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_POST['_cpcff_public_nonce'] ) ),
					'cpcff_form_' . CP_CALCULATEDFIELDSF_ID . $sequence
				)
			) {
				$form_obj = $cpcff_main->get_form( CP_CALCULATEDFIELDSF_ID );
				if ( $form_obj ) {
					require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
					// Defines the $params array.
					$params = array(
						'formid' => CP_CALCULATEDFIELDSF_ID,
					);

					$form_data = $form_obj->get_option( 'form_structure', CP_CALCULATEDFIELDSF_DEFAULT_form_structure );
					$fields    = array();

					foreach ( $form_data[0] as $item ) {
						$fields[ $item->name ] = $item;

						if ( 'fPhone' == $item->ftype && isset( $_POST[ $item->name . $sequence ] ) ) { // join fields for phone fields.
							$_POST[ $item->name . $sequence ] = '';
							for ( $i = 0; $i <= substr_count( $item->dformat, ' ' ) + ( $item->countryComponent ? 1 : 0 ); $i++ ) {
								$_POST[ $item->name . $sequence ] .=
									! empty( $_POST[ $item->name . $sequence . '_' . $i ] ) && '' != CPCFF_AUXILIARY::sanitize( $_POST[ $item->name . $sequence . '_' . $i ] ) ? ( 0 == $i ? '' : '-' ) . CPCFF_AUXILIARY::sanitize( $_POST[ $item->name . $sequence . '_' . $i ] ) : ''; // phpcs:ignore

								unset( $_POST[ $item->name . $sequence . '_' . $i ] );
							}
						}
					}

					$buffer = '';

					foreach ( $_POST as $item => $value ) {
						$fieldname = str_replace( $sequence, '', $item );
						if ( isset( $fields[ $fieldname ] ) ) {
							$current_field = $fields[$fieldname];
							// Sanitize the values based on their settings and type.
							if(
								property_exists($current_field,'ftype') &&
								! empty( $value )
							) {
								$invalid_format = false;
								switch( strtolower($current_field->ftype) ) {
									case 'femail':
									case 'femailds':
										$value = sanitize_email( $value );
										if ( empty( $value ) ) {
											$invalid_format = true;
										}
										break;
									case 'fphone':
									case 'fPhoneds':
										if ( ! preg_match( '/^\+?[^\-\d]+$/', $value ) ) {
											$invalid_format = true;
										}
										break;
									case 'fnumber':
									case 'fnumberds':
										if ( 'digits' === $current_field->dformat ) {
											if ( preg_match( '/[^\d]/', $value ) ) {
												$invalid_format = true;
											}
										} elseif ( preg_match( '/^[^\d]*$/', $value ) ) {
											$invalid_format = true;
										}
										break;
									case 'fcurrency':
									case 'fcurrencyds':
									case 'fslider':
										if ( preg_match( '/^[^\d]*$/', $value ) ) {
											$invalid_format = true;
										}
										break;
									case 'fcolor':
										if ( ! preg_match( '/#?[0-9,a-f]{6,9}/i', $value ) ) {
											$invalid_format = true;
										}
										break;
									case 'fdate':
									case 'fdateds':
										if ( ! preg_match( '/^(\d{2,4}[^\d]\d{2}[^\d]\d{2,4})?\s*(\d{1,2}\:\d{1,2}\s*([ap]m)?)?$/i', $value ) ) {
											$invalid_format = true;
										}
										break;
								}

								if ( $invalid_format ) {
									$_title = property_exists( $current_field, 'title' ) ? CPCFF_AUXILIARY::sanitize( $current_field->title ) : '';
									print ( esc_html__('The', 'calculated-fields-form') . ' ' . ( ! empty( $_title ) ? $_title : $fieldname ) . ' ' . esc_html__('value is invalid', 'calculated-fields-form') );
									exit;
								}
							}

							// Check if the field is required and it is empty.
							if (
								property_exists( $current_field, 'required' ) &&
								! empty( $current_field->required ) &&
								'' === $value
							) {
								esc_html_e( 'At least a required field is empty', 'calculated-fields-form' );
								exit;
							}

							// Processing the title and value to include in the summary.
							$_title = property_exists( $current_field, 'title' ) ? CPCFF_AUXILIARY::sanitize( $current_field->title ) : '';
							$_title = preg_replace( array( '/^\s+/', '/\s*\:*\s*$/' ), '', $_title );

							$params[ $fieldname ] = CPCFF_AUXILIARY::sanitize( $value );
							$_value               = is_array( $params[ $fieldname ] ) ? implode( ', ', $params[ $fieldname ] ) : $params[ $fieldname ];
							$_value               = preg_replace( '/^\s*\:*\s*/', '', $_value );

							$buffer .= ( '' !== $_title ? $_title . ': ' : '' ) . $_value . "\n";
						}
					}

					if ( ! empty( $_FILES ) ) {
						add_filter( 'upload_dir', 'CPCFF_AUXILIARY::upload_dir', 1 );
						foreach ( $_FILES as $item => $value ) {
							$item = str_replace( $sequence, '', $item );
							if (
								isset( $fields[ $item ] ) &&
								(
									'ffile' == $fields[ $item ]->ftype || 'frecordav' == $fields[ $item ]->ftype
								)
							) {
								$files_names_arr = array();
								$files_links_arr = array();
								$files_urls_arr  = array();

								$_uploaded_files_count = count( $value['name'] );
								for ( $f = 0; $f < $_uploaded_files_count; $f++ ) {
									if ( ! empty( $value['name'][ $f ] ) ) {
										$uploaded_file = array(
											'name'     => sanitize_text_field( $value['name'][ $f ] ),
											'type'     => sanitize_text_field( $value['type'][ $f ] ),
											'tmp_name' => sanitize_text_field( $value['tmp_name'][ $f ] ),
											'error'    => sanitize_text_field( $value['error'][ $f ] ),
											'size'     => sanitize_text_field( $value['size'][ $f ] ),
										);

										if ( CPCFF_AUXILIARY::check_uploaded_file( $uploaded_file ) ) {
											$movefile = wp_handle_upload( $uploaded_file, array( 'test_form' => false ) );
											if ( empty( $movefile['error'] ) ) {
												$files_links_arr[] = $movefile['file'];
												$files_urls_arr[]  = $movefile['url'];
												$files_names_arr[] = $uploaded_file['name'];

												$params[ $item . '_link' ][ $f ] = end( $files_links_arr );
												$params[ $item . '_path' ][ $f ] = $params[ $item . '_link' ][ $f ];
												$params[ $item . '_url' ][ $f ]  = end( $files_urls_arr );
											}
										}
									}
								}

								$joinned_files_names = implode( ', ', $files_names_arr );

								$_title = property_exists( $fields[ $item ], 'title' ) ? CPCFF_AUXILIARY::sanitize( $fields[ $item ]->title ) : '';
								$_title = preg_replace( array( '/^\s+/', '/\s*\:*\s*$/' ), '', $_title );

								$buffer                    .= ( ! empty( $_title ) ? $_title . ': ' : '' ) . $joinned_files_names . "\n";
								$params[ $item ]            = $joinned_files_names;
								$params[ $item . '_links' ] = implode( "\n", $files_links_arr );
								$params[ $item . '_paths' ] = $params[ $item . '_links' ];
								$params[ $item . '_urls' ]  = implode( "\n", $files_urls_arr );
							}
						}
						remove_filter( 'upload_dir', 'CPCFF_AUXILIARY::upload_dir', 1 );

					} // End uploaded files processing

					$ipaddr                            = ( 'true' == $form_obj->get_option( 'fp_inc_additional_info', CP_CALCULATEDFIELDSF_DEFAULT_fp_inc_additional_info ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
					$params['ipaddress']               = $ipaddr;
					$params['from_page']               = ! empty( $_POST['cp_ref_page'] ) ? sanitize_text_field( wp_unslash( $_POST['cp_ref_page'] ) ) : ( ! empty( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '' );
					$params['submissiondate_mmddyyyy'] = current_time( 'm/d/Y H:i:s' );
					$params['submissiondate_ddmmyyyy'] = current_time( 'd/m/Y H:i:s' );

					$params['itemnumber'] = str_replace( '.', '', strtoupper( uniqid( '', true ) ) ); // pseudo unique id.

					require_once __DIR__ . '/inc/cpcff_mail.inc.php';

					$cpcff_mail = new CPCFF_MAIL();
					$cpcff_mail->send_notification_email( $form_obj, $params, $buffer );

					$location = $form_obj->get_option( 'fp_return_page', CP_CALCULATEDFIELDSF_DEFAULT_fp_return_page, $params['itemnumber'] );
					$location = esc_url( CPCFF_AUXILIARY::replace_params_into_url( $location, $params ), null, null );

					if ( ! headers_sent() ) {
						header( 'Location: ' . $location );
					} else {
						print '<script>document.location.href="' . esc_js( $location ) . '";</script>';
					}

					remove_all_actions( 'shutdown' );
					exit;
				} // End Submission processing
			} else {
				esc_html_e( 'Failed security check', 'calculated-fields-form' );
				exit;
			}
		}
	}
}

function cp_calculatedfieldsf_save_options() {
	check_admin_referer( 'cff-form-settings', '_cpcff_nonce' );
	global $wpdb;
	if ( ! defined( 'CP_CALCULATEDFIELDSF_ID' ) && isset( $_POST['cp_calculatedfieldsf_id'] ) ) {
		define( 'CP_CALCULATEDFIELDSF_ID', sanitize_text_field( wp_unslash( $_POST['cp_calculatedfieldsf_id'] ) ) );
	}

	$error_occur = false;
	if ( isset( $_POST['form_structure'] ) ) {

		$_cff_POST = $_POST;

		// Remove bom characters.
		$_cff_POST['form_structure'] = CPCFF_AUXILIARY::clean_bom( $_cff_POST['form_structure'] ); // phpcs:ignore WordPress.Security.EscapeOutput

		$form_structure_obj = CPCFF_AUXILIARY::json_decode( $_cff_POST['form_structure'] );
		if ( ! empty( $form_structure_obj ) ) {
			$form_structure_obj = CPCFF_FORM::sanitize_structure( $form_structure_obj );

			global $cpcff_default_texts_array;
			$cpcff_text_array = '';

			$_cff_POST                   = CPCFF_AUXILIARY::stripcslashes_recursive( $_cff_POST );
			$_cff_POST['form_structure'] = json_encode( $form_structure_obj );

			if ( isset( $_cff_POST['cpcff_text_array'] ) ) {
				$_cff_POST['vs_all_texts'] = $_cff_POST['cpcff_text_array'];
			}

			$cpcff_main                = CPCFF_MAIN::instance();
			$_cff_calculatedfieldsf_id = isset( $_cff_POST['cp_calculatedfieldsf_id'] ) && is_numeric( $_cff_POST['cp_calculatedfieldsf_id'] ) ? intval( $_cff_POST['cp_calculatedfieldsf_id'] ) : 0;
			if ( $cpcff_main->get_form( $_cff_calculatedfieldsf_id )->save_settings( $_cff_POST ) === false ) {
				global $cff_structure_error;
				$cff_structure_error = __( '<div class="error-text">The data cannot be stored in database because has occurred an error with the database structure. Please, go to the plugins section and Deactivate/Activate the plugin to be sure the structure of database has been checked, and corrected if needed. If the issue persist, please <a href="https://cff.dwbooster.com/contact-us">contact us</a></div>', 'calculated-fields-form' );
			}
		} else {
			$error_occur = true;
		}
	} else {
		$error_occur = true;
	}

	if ( $error_occur ) {
		global $cff_structure_error;
		$cff_structure_error = __( '<div class="error-text">The data cannot be stored in database because has occurred an error with the form structure. Please, try to save the data again. If have been copied and pasted data from external text editors, the data can contain invalid characters. If the issue persist, please <a href="https://cff.dwbooster.com/contact-us">contact us</a></div>', 'calculated-fields-form' );
	}
}
