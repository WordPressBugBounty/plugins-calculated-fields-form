<?php
/**
 * Plugin Name: Calculated Fields Form
 * Plugin URI: https://cff.dwbooster.com
 * Description: Create forms with field values calculated based in other form field values.
 * Version: 5.5.0.3
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
define( 'CP_CALCULATEDFIELDSF_VERSION', '5.5.0.3' );
define( 'CP_CALCULATEDFIELDSF_TIMEOUT', 30 );
define( 'CP_CALCULATEDFIELDSF_MAIN_FILE_PATH', __FILE__ );
define( 'CP_CALCULATEDFIELDSF_BASE_PATH', dirname( CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ) );
define( 'CP_CALCULATEDFIELDSF_BASE_NAME', plugin_basename( CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ) );

require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_session.inc.php';
// Start Session
add_action('init', function () {
	if (is_admin()) return;
	if (defined('REST_REQUEST') && REST_REQUEST) return;
	if (defined('DOING_CRON') && DOING_CRON) return;
	CP_SESSION::session_start();
}, 0);

// Feedback system.
require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/feedback/cp-feedback.php';
new CP_FEEDBACK( 'calculated-fields-form', __FILE__, 'https://cff.dwbooster.com/contact-us' );

require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_auxiliary.inc.php';
require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/config/cpcff_config.cfg.php';

require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_banner.inc.php';
require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_main.inc.php';

require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_trial.php';

require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_form_cache.inc.php';
require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_email_diagnostic.inc.php';
require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_akismet.inc.php';

require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/captcha/cpcff_captcha.inc.php'; // Captcha

// Global variables.
CPCFF_MAIN::instance(); // Main plugin's object.

add_action( 'init', 'cp_calculated_fields_form_check_posted_data', 99 );
add_action( 'init', 'cp_calculated_fields_form_direct_form_access', 99 );
add_action( 'init', function(){
	add_filter( 'get_post_metadata', function( $v, $object_id, $meta_key, $single, $meta_type = '' ){
		if ('_elementor_element_cache' != $meta_key) return $v;
		require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_compatibility.inc.php';
		global $wpdb;

		$cached = CPCFF_COMPATIBILITY::get_elementor_cache($object_id);
		if ($cached !== null) {
			return $cached ? false : $v;
		}

		$has_cff = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . $wpdb->postmeta . ' WHERE post_id=%d AND meta_key="_elementor_element_cache" AND meta_value LIKE "%calculatedfields%";', $object_id));

		CPCFF_COMPATIBILITY::set_elementor_cache($object_id, $has_cff ? true : false);
		return $has_cff ? false : $v;
	}, 10, 5 );
} );
add_filter( 'nonce_life', function($life){
    return max($life, defined('CP_CALCULATEDFIELDSF_NONCE_LIFE') ? CP_CALCULATEDFIELDSF_NONCE_LIFE : 24 * HOUR_IN_SECONDS); // 24 hours
});

// functions
// ------------------------------------------.

function cp_calculated_fields_form_direct_form_access() {
	$in_iframe = function ( $form_id ) {
		// The form is loaded into an iFrame tag.
		if (
			! empty($_GET['cff_iframe']) &&
			preg_match( '/^cff-iframe-\d+$/', $_GET['cff_iframe'] )
		) {
			if(
				get_transient( $form_id . '|' . $_GET['cff_iframe'] )
			) {
				return ( isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false );
			} else {
				delete_transient( $form_id . '|' . $_GET['cff_iframe'] );
			}
		}
	};

	if (
		! empty( $_GET['cff-form'] ) &&
		is_numeric( $_GET['cff-form'] ) &&
		0 != ( $form_id = intval( $_GET['cff-form'] ) ) &&
		(
			( get_option( 'CP_CALCULATEDFIELDSF_DIRECT_FORM_ACCESS', CP_CALCULATEDFIELDSF_DIRECT_FORM_ACCESS ) ) ||
			( current_user_can(apply_filters('cpcff_forms_edition_capability', 'manage_options')) ) ||
			(
				$in_iframe( $form_id )
			)
		)
	) {
		$cpcff_main     = CPCFF_MAIN::instance();
		$shortcode_atts = array( 'id' => $form_id );

		$js_reserved = array( '__proto__', 'constructor', 'prototype', 'hasOwnProperty', 'toString', 'valueOf' );
		foreach ( $_GET as $_param_name => $_param_value ) {
			if ( in_array( $_param_name, $js_reserved, true ) ) continue;
			if ( ! preg_match( '/^[a-z_][a-z0-9_]*$/i', $_param_name ) ) continue;

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
		isset($_GET['cp_calculatedfieldsf']) &&
		$_GET['cp_calculatedfieldsf'] == 'captcha'
	) {
		$form_id = isset($_GET['cff']) ? intval($_GET['cff']) : 0;
		$ps = isset($_GET['ps']) ? sanitize_key(wp_unslash($_GET['ps'])) : '';
		CPCFF_CAPTCHA::get_captcha($form_id, $ps);
		exit('Invalid Request');
	}

	// Check if the captcha is enabled and validate it in background.
	$captcha_value = '';
	$captcha_is_valid = false;
	if (
		isset($_REQUEST['hdcaptcha_cp_calculated_fields_form_post'])
	) {
		$captcha_value = sanitize_text_field(wp_unslash($_REQUEST['hdcaptcha_cp_calculated_fields_form_post']));
		$sequence = '';
		if (isset($_GET["ps"])) {
			$sequence = sanitize_text_field(wp_unslash($_GET["ps"]));
		} elseif (isset($_POST["cp_calculatedfieldsf_pform_psequence"])) {
			$sequence = sanitize_text_field(wp_unslash($_POST["cp_calculatedfieldsf_pform_psequence"]));
		}
		$form_id = $_REQUEST['cp_calculatedfieldsf_id'] ?? 0;
		if (
			! is_numeric( $form_id ) ||
			($form_id = intval( $form_id )) <= 0 ||
			false == ($form_obj = $cpcff_main->get_form($form_id)) ||
			CPCFF_CAPTCHA::validate_captcha(
				$form_obj,
				$sequence,
				$captcha_value
			) === false
		) {
			echo 'captchafailed';
			remove_all_actions('shutdown');
			exit;
		} else {
			if (
				isset($_SERVER['REQUEST_METHOD']) &&
				'GET' == $_SERVER['REQUEST_METHOD']
			) { // Captcha validation only.
				echo 'OK';
				remove_all_actions('shutdown');
				exit;
			}
			$captcha_is_valid = true;
		}
	} // End if captcha validation.

	if (
		isset( $_SERVER['REQUEST_METHOD'] ) &&
		'POST' == $_SERVER['REQUEST_METHOD']
	) {
		// Save form settings.
		if (
			isset( $_POST['cp_calculatedfieldsf_post_options'] ) &&
			is_admin() &&
			isset( $_POST['_cpcff_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_cpcff_nonce'] ) ), 'cff-form-settings' ) &&
			current_user_can(apply_filters('cpcff_forms_edition_capability', 'manage_options'))
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
						'banner'		 => 1,
						'preview' 	 	 => 1
					)
				);
			}
			return;
		} elseif ( // Process form submission.
			isset( $_POST['cp_calculatedfieldsf_id'] ) &&
			is_numeric( $_POST['cp_calculatedfieldsf_id'] ) &&
			isset( $_POST['cp_calculatedfieldsf_pform_psequence'] )
		) {
			// JS code to redirect the user to the form page.
			$js_redirect = function() {
				print "<span id='cp_calculatedfieldsf_redirect_counter' style='margin-left:10px'></span><script type='text/javascript'>(function(){let n = 6; let __counter=function(){n--;if(n<=0){history.back();}else{document.getElementById('cp_calculatedfieldsf_redirect_counter').innerHTML=n;setTimeout(__counter, 1000);}};__counter();})();</script>";
			};

			$sequence = sanitize_text_field( wp_unslash( $_POST['cp_calculatedfieldsf_pform_psequence'] ) );
			define( 'CP_CALCULATEDFIELDSF_ID', intval( $_POST['cp_calculatedfieldsf_id'] ) );

			if (
				! get_option( 'CP_CALCULATEDFIELDSF_NONCE', 0 ) ||
				(
					isset( $_POST['_cpcff_public_nonce'] ) &&
					wp_verify_nonce(
						sanitize_text_field( wp_unslash( $_POST['_cpcff_public_nonce'] ) ),
						'cpcff_form_' . CP_CALCULATEDFIELDSF_ID . $sequence
					)
				)
			) {
				// Check the minimum time to submit the form
				$min_time = get_option('CP_CALCULATEDFIELDSF_MINIMUM_TIME_TO_SUBMIT', CP_CALCULATEDFIELDSF_MINIMUM_TIME_TO_SUBMIT);
				if ( ! empty( $min_time ) )
				{
					if (
						empty( $_POST['cff_form_start_time'] ) ||
						empty( $start_time = CPCFF_AUXILIARY::decrypt( $_POST['cff_form_start_time'] ) ) ||
                        ! is_numeric( $start_time ) ||
                        (int) $start_time <= 0 ||
						$start_time + $min_time > time()
					) {
						esc_html_e( 'You are submitting the form too quickly, or with invalid data, and are being identified as a spam bot. Please take more time to fill the form.', 'calculated-fields-form' );
						$js_redirect();
						exit;
					}
				}

				$form_obj = $cpcff_main->get_form( CP_CALCULATEDFIELDSF_ID );
				if ( $form_obj ) {
					// Validate captcha after form submission.
					if (
						$captcha_is_valid === false &&
						CPCFF_CAPTCHA::validate_captcha(
							$form_obj,
							$sequence,
							$captcha_value
						) === false
					) {
						echo 'captchafailed';
						remove_all_actions('shutdown');
						exit;
					}

					require_once( ABSPATH . "wp-admin" . '/includes/file.php' );

					// If for submissions is disabled echo message and exit.
					if ( $form_obj->get_option('fp_disable_submissions',0) ) {
						esc_html_e( 'Form submissions are currently disabled.', 'calculated-fields-form' );
						remove_all_actions('shutdown');
						exit;
					}

					// Defines the $params array.
					$params = array(
						'formid' => CP_CALCULATEDFIELDSF_ID,
					);

					$form_data = $form_obj->get_option( 'form_structure', CP_CALCULATEDFIELDSF_DEFAULT_form_structure );

					$fields    					= [];
					$clone_fields_to_original 	= []; // Use for repeaters

					// grab posted data
					//---------------------------
					$buffer = '';
					$passwords_to_delete = [];
					$passwords_to_hash   = [];
					$passwords_to_plain  = [];
					$count_of_non_empty_fields = 0;

					foreach ($form_data[0] as $item) {
						$fields[$item->name] = $item;
					}

					// Preprocess phone components.
					$preprocess_phone_components = function ($field_name, $item) use ($sequence) {
						if (
							! ($item->ftype == 'fPhone' || $item->ftype == 'fPhoneds') ||
							! isset($_POST[$field_name . $sequence])
						) {
							return;
						}

						static $processed_phone_fields = [];
						if (in_array($field_name, $processed_phone_fields)) {
							return;
						}
						$processed_phone_fields[] = $field_name;

						$i = 0;
						$formatted_phone = '';
						$_phone_connector_symbol = '-';
						if (property_exists($item, 'dseparator')) {
							switch ($item->dseparator) {
								case 'space':
									$_phone_connector_symbol = " ";
									break;
								case 'none':
									$_phone_connector_symbol = "";
									break;
								case '.':
									$_phone_connector_symbol = ".";
									break;
								case '-':
									$_phone_connector_symbol = "-";
									break;
							}
						}

						$_phone_connector = (
							isset($_POST[$field_name . $sequence . "_2"]) ||
							(
								isset($_POST[$field_name . $sequence . "_1"]) &&
								(
									! property_exists($item, 'countryComponent') ||
									! $item->countryComponent
								)
							)
						) ? $_phone_connector_symbol : '';

						while (isset($_POST[$field_name . $sequence . "_" . $i])) {
							$formatted_phone .= $_POST[$field_name . $sequence . "_" . $i] != '' ? ($i == 0 ? '' : $_phone_connector) . CPCFF_AUXILIARY::sanitize($_POST[$field_name . $sequence . "_" . $i]) : ''; // phpcs:ignore
							unset($_POST[$field_name . $sequence . "_" . $i]);
							$i++;
						}

						$_POST[$field_name . $sequence] = $formatted_phone;

					}; // END --> $preprocess_phone_components

					// Preprocess repeater matrix.
					$preprocess_repeater_matrix = function ($field_name, $item) use ($sequence, &$clone_fields_to_original, $fields, $preprocess_phone_components) {
						if (
							$item->ftype !== 'frepeater' ||
							empty($item->fields) ||
							! is_array($item->fields)
						) {
							return;
						}

						$matrix_key = $field_name . $sequence;
						if (
							! isset($_POST[$matrix_key]) ||
							! is_string($_POST[$matrix_key]) ||
							strlen($_POST[$matrix_key]) > 1000000  // 1MB max - extra security
						) {
							return;
						}

						$matrix = json_decode(wp_unslash($_POST[$matrix_key]), true);
						if (json_last_error() !== JSON_ERROR_NONE || ! is_array($matrix)) {
							return;
						}

						if (
							is_numeric($item->maxRows) &&
							0 < ($maxRows = intval($item->maxRows)) &&
							count($matrix) > $maxRows ||
							count($matrix) > 1000 // 1k max - extra security
						) {  // max rows
							return;
						}

						// Schema lookup: fieldname => 0 (for isset checks)
						$valid_schema = array_flip($item->fields);
						$count = count($valid_schema);

						$processed_matrix 	= [];
						$seen_clones 		= [];

						foreach ($matrix as $row) {
							if (
								! is_array($row) ||
								count($row) !== $count
							) {
								continue;
							}

							$flipped = [];
							$valid_row = true;
							foreach ($row as $schema_fieldname => $cloned_fieldname) {
								if (! isset($valid_schema[$schema_fieldname])) {
									$valid_row = false;
									break;
								}

								// Check cloned fieldname format.
								if (! is_string($cloned_fieldname) || ! preg_match('/^fieldname\d+$/', $cloned_fieldname)) {
									$valid_row = false;
									break;
								}

								// Ownership: cloned names must be self-maps (initial row)
								// OR must not collide with top-level fieldnames.
								if (
									$cloned_fieldname !== $schema_fieldname &&
									isset($fields[$cloned_fieldname])
								) {
									$valid_row = false;
									break;
								}

								// Uniqueness: no clone name may appear twice in the matrix.
								if (isset($seen_clones[$cloned_fieldname])) {
									$valid_row = false;
									break;
								}
								$seen_clones[$cloned_fieldname] = true;

								$flipped[$cloned_fieldname] = $schema_fieldname;
							}

							if (! $valid_row) continue;

							foreach ($flipped as $cloned => $original) {
								$preprocess_phone_components($cloned, $fields[$original]);
							}

							$clone_fields_to_original = array_merge($clone_fields_to_original, $flipped);
							$processed_matrix[] = $row;

						}

						$_POST[$field_name . $sequence] = $processed_matrix;

					}; // END --> $preprocess_repeater_matrix

					$process_field = function ($current_field, $value, &$summary, &$list) use (&$passwords_to_delete, &$passwords_to_hash, &$passwords_to_plain, &$count_of_non_empty_fields) {
						$fieldname = $current_field->name;
						$_title = property_exists($current_field, 'title') ? CPCFF_AUXILIARY::sanitize($current_field->title) : '';
						$ftype = '';

						// Sanitize the values based on their settings and type.
						if (
							property_exists($current_field, 'ftype') &&
							! empty($value)
						) {
							$invalid_format = false;

							$ftype = strtolower($current_field->ftype);
							if (is_array($value)) {
								$value = CPCFF_AUXILIARY::array_map_recursive($value, 'wp_unslash');
								$count_of_non_empty_fields += count($value) ? 1 : 0;
							} else {
								$value = wp_unslash($value);
								$count_of_non_empty_fields += $value !== '' ? 1 : 0;
							}

							if ($ftype == 'ftextarea' || $ftype == 'ftextareads') {
								if (
									! property_exists($current_field, 'accept_html') ||
									! $current_field->accept_html
								) {
									$value = sanitize_textarea_field($value);
								}
							} else {
								if (
									$ftype != 'fpassword' &&
									(
										! property_exists($current_field, 'accept_html') ||
										! $current_field->accept_html
									)
								) {
									if (is_array($value)) {
										$value = CPCFF_AUXILIARY::array_map_recursive($value, 'sanitize_text_field');
									} else {
										$value = sanitize_text_field($value);
									}
								}

								switch ($ftype) {
									case 'fpassword':
										if (! property_exists($current_field, 'store') || $current_field->store == 'plain') {
											if ($value !== '') $passwords_to_plain[] = $current_field->name;
										} else if ($current_field->store == 'no') {
											$passwords_to_delete[] = $current_field->name;
										} else if ($current_field->store == 'hash') {
											if ($value !== '') $passwords_to_hash[] = $current_field->name;
										}
										break;
									case 'femail':
									case 'femailds':
										$value = sanitize_email($value);
										if (empty($value)) {
											$invalid_format = true;
										}
										break;
									case 'fphone':
									case 'fPhoneds':
										if (! preg_match('/^\+?[\.\s\-\d]+$/', $value)) {
											$invalid_format = true;
										}
										break;
									case 'fnumber':
									case 'fnumberds':
										if ('digits' === $current_field->dformat) {
											if (preg_match('/[^\d]/', $value)) {
												$invalid_format = true;
											}
										} elseif (preg_match('/^[^\d]*$/', $value)) {
											$invalid_format = true;
										}
										break;
									case 'fcurrency':
									case 'fcurrencyds':
									case 'fslider':
										if (preg_match('/^[^\d]*$/', $value)) {
											$invalid_format = true;
										}
										break;
									case 'fcolor':
										if (! preg_match('/#?[0-9,a-f]{6,9}/i', $value)) {
											$invalid_format = true;
										}
										break;
									case 'fdate':
									case 'fdateds':
										if (! preg_match('/^((\d{1,2}|\d{4})[^\d]\d{1,2}[^\d](\d{1,2}|\d{4}))?\s*(\d{1,2}\:\d{1,2}\s*([ap]m)?)?$/i', $value)) {
											$invalid_format = true;
										}
										break;
									case 'ftimeslots':
									case 'ftimeslotsds':
										if (! preg_match('/^((\d{1,2}|\d{4})[^\d]\d{1,2}[^\d](\d{1,2}|\d{4})\s*\:\s*\d{1,2}\:\d{1,2}\s*\-\s*\d{1,2}\:\d{1,2}(\,\s*)?)*$/i', $value)) {
											$invalid_format = true;
										}
										break;
								}
							}

							if ($invalid_format) {
								$error_mssg = esc_html__('The', 'calculated-fields-form') . ' ' . (! empty($_title) ? $_title : $fieldname) . ' ' . esc_html__('value is invalid', 'calculated-fields-form');
								error_log('Calculated Fields Form: ' . $error_mssg);
								print($error_mssg);
								exit;
							}
						}

						// Check if the field is required and it is empty
						if (
							property_exists($current_field, 'required') &&
							!empty($current_field->required) &&
							($value === '' || (is_array($value) && count($value) == 0))
						) {
							$error_mssg = esc_html__('The', 'calculated-fields-form') . ' ' . (! empty($_title) ? $_title : $fieldname) . ' ' . esc_html__('is empty', 'calculated-fields-form');
							error_log('Calculated Fields Form: ' . $error_mssg);
							print($error_mssg);
							exit;
						}

						// Processing the title and value to include in the summary
						$list[$fieldname] = $ftype == 'fpassword' ? $value : CPCFF_AUXILIARY::sanitize($value);
						$_value = is_array($list[$fieldname]) ? implode(", ", $list[$fieldname]) : $list[$fieldname];
						$_value = preg_replace('/^\s*\:*\s*/', '', $_value);
						if ($ftype != 'fpassword') {
							$_title = preg_replace(array('/^\s+/', '/\s*\:*\s*$/'), '', $_title);
							$summary .= ($_title !== '' ?  $_title . ": " : "") . $_value . "\n";
						}
					}; // END --> $process_field

					$process_files_field = function ($current_field, $value, $field_name, &$summary, &$list) use ($form_obj, &$count_of_non_empty_fields) {
						if ($current_field->ftype == 'ffile' || $current_field->ftype == 'frecordav') {
							// Get accepted file extension.
							$accepted_file_extensions = [];
							if (! empty($current_field->accept) && is_string($current_field->accept)) {
								$tmp = strtolower($current_field->accept);
								$tmp = preg_replace('/[^\d,a-z\,]/i', '', $tmp);
								$tmp = trim($tmp);
								if (! empty($tmp)) $accepted_file_extensions = explode(',', $tmp);
							}

							// Get maximum file size.
							$max_file_size = 0;
							if (! empty($current_field->upload_size)) {
								$tmp = $current_field->upload_size;
								if (is_numeric($tmp)) $max_file_size = intval($tmp);
								elseif (is_string($tmp)) {
									$tmp = preg_replace('/[^\d\.]/', '', $tmp);
									if (is_numeric($tmp)) $max_file_size = intval($tmp);
								}
							}

							$files_names_arr = array();
							$files_links_arr = array();
							$files_urls_arr  = array();
							for ($f = 0; $f < count($value['name']); $f++) {
								if (!empty($value['name'][$f])) {
									$uploaded_file = array(
										'name' 		=> $value['name'][$f],
										'type' 		=> $value['type'][$f],
										'tmp_name' 	=> $value['tmp_name'][$f],
										'error' 	=> $value['error'][$f],
										'size' 		=> $value['size'][$f],
									);

									if (CPCFF_AUXILIARY::check_uploaded_file($uploaded_file, $accepted_file_extensions, $max_file_size)) {
										$movefile = wp_handle_upload($uploaded_file, array('test_form' => false));
										if (empty($movefile['error'])) {
											$files_links_arr[] = $movefile["file"];
											$files_urls_arr[]  = $movefile["url"];
											$files_names_arr[] = sanitize_file_name($uploaded_file['name']);

											/**
											 * Action called when the file is uploaded, the file's data is passed as parameter
											 */
											do_action(
												'cpcff_file_uploaded',
												$movefile,
												array(
													'names' => &$files_names_arr,
													'links' => &$files_links_arr,
													'urls'  => &$files_urls_arr,
													'formid' => $form_obj->get_id(),
													'params' => &$list,
													'item'  => $field_name,
													'index' => $f
												)
											);

											$list[$field_name . "_link"][$f] = end($files_links_arr);
											$list[$field_name . "_path"][$f] = $list[$field_name . "_link"][$f];
											$list[$field_name . "_url"][$f]  = end($files_urls_arr);
										}
									}
								}
							}

							$joinned_files_names = implode(", ", $files_names_arr);

							$_title = property_exists($current_field, 'title') ? CPCFF_AUXILIARY::sanitize($current_field->title) : '';
							$_title = preg_replace(array('/^\s+/', '/\s*\:*\s*$/'), '', $_title);

							$summary .= (! empty($_title) ? $_title . ": " : "") . $joinned_files_names . "\n";
							$list[$field_name] = $joinned_files_names;
							$list[$field_name . "_name"]  = $files_names_arr;
							$list[$field_name . "_links"] = implode("\n",  $files_links_arr);
							$list[$field_name . "_paths"] = $list[$field_name . "_links"];
							$list[$field_name . "_urls"]  = implode("\n",  $files_urls_arr);
							$count_of_non_empty_fields += ! empty($list[$field_name]) ? 1 : 0;
						}

					}; // END --> $process_files_field

					add_filter( 'upload_dir', 'CPCFF_AUXILIARY::upload_dir', 1 );
					// Preprocess the fields.
					foreach ($fields as $field => $item)
					{
						// Phone fields.
						$preprocess_phone_components($item->name, $item);

						// Repeater.
						$preprocess_repeater_matrix($item->name, $item);
					}

					// Process the form data.
					foreach ($_POST as $item => $value)
					{
						$fieldname = str_replace($sequence,'',$item);
						if ( isset( $clone_fields_to_original[$fieldname] ) ) { // The fields into repeaters are managed differently.
							continue;
						}

						if (array_key_exists($fieldname, $fields)) {
							$current_field = $fields[$fieldname];

							if ($current_field->ftype == 'frepeater') {
								if (is_array($value)) {
									$repeater_values_matrix = [];
									foreach ($value as $row) {
										if (is_array($row)) {
											$row_values = [];
											foreach($row as $original => $cloned) {
												if ( array_key_exists($original, $fields) ) {
													if (isset($_POST[$cloned . $sequence])) {
														$process_field($fields[$original], $_POST[ $cloned . $sequence ], $buffer, $row_values);
													} elseif( isset($_FILES[$cloned . $sequence]) ) {
														$process_files_field($fields[$original], $_FILES[$cloned . $sequence], $original, $buffer, $row_values);
													}
												}
											}
											// Skip rows where all values are semantically empty.
											$row_has_data = false;
											foreach ($row_values as $v) {
												if ($v !== '' && (!is_array($v) || !empty($v))) {
													$row_has_data = true;
													break;
												}
											}
											if ($row_has_data) {
												$repeater_values_matrix[] = $row_values;
											}
										}
									}
									if ( ! empty($repeater_values_matrix) ) {
										$params[$fieldname] = $repeater_values_matrix;
									}
								}
							} else { // Process directly.
								$process_field($current_field, $value, $buffer, $params);
							}
						}
					}

					// Process uploaded files.
					if (! empty($_FILES)) {
						foreach ($_FILES as $item => $value)
						{
							$fieldname = str_replace($sequence, '', $item);
							if (isset($clone_fields_to_original[$fieldname])) { // The fields into repeaters were treated previously.
								continue;
							}

							if(isset($fields[$fieldname]) &&  ( $fields[$fieldname]->ftype == 'ffile' || $fields[$fieldname]->ftype == 'frecordav' ))
							{
								$process_files_field($fields[$fieldname], $value, $fieldname, $buffer, $params);
							}
						}
					}
					remove_filter( 'upload_dir', 'CPCFF_AUXILIARY::upload_dir', 1 );

					if(count($params) < 2 || $count_of_non_empty_fields == 0) // only formid or empty fields, so the form is empty
					{
						esc_html_e( 'The form is empty', 'calculated-fields-form' );
						$js_redirect();
						exit;
					}

					$ipaddr                            = ( 'true' == $form_obj->get_option( 'fp_inc_additional_info', CP_CALCULATEDFIELDSF_DEFAULT_fp_inc_additional_info ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
					$params['ipaddress']               = $ipaddr;
					$params['from_page']               = ((! empty( $_POST['cp_ref_page'] )) ? sanitize_text_field( wp_unslash( $_POST['cp_ref_page'] ) ) : ( (! empty( $_SERVER['HTTP_REFERER'] )) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '' ));
					$params['submissiondate_mmddyyyy'] = current_time( 'm/d/Y H:i:s' );
					$params['submissiondate_ddmmyyyy'] = current_time( 'd/m/Y H:i:s' );

                    /**
                     * Action called before insert the data into database.
                     * To the function is passed an array with submitted data.
                     */
                    do_action_ref_array('cpcff_free_process_data_before_insert', array(&$params, &$buffer, $fields));

                    if (isset($params['aborting_submission']) && $params['aborting_submission'] === true) {
                        return false;
                    }

                    // insert into database
                    //---------------------------------
                    $item_number = CPCFF_SUBMISSIONS::insert(
                        array(
                            'formid' => CP_CALCULATEDFIELDSF_ID,
                            'time' => current_time('mysql'),
                            'ipaddr' => $ipaddr,
                            'notifyto' => '',
                            'paypal_post' => $params,
                            'data' => $buffer
                        )
                    );

                    if (!$item_number) {
                        esc_html_e('Error saving data! Please try again.', 'calculated-fields-form');
                        print '<br />';
                        esc_html_e('Error debug information: ', 'calculated-fields-form');
                        $wpdb->print_error();
                        $js_redirect();
                        exit;
                    }

					// Clear captcha
					CP_SESSION::set_var('rand_code' . $sequence, '');
                    $params['itemnumber'] = $item_number;

					/**
					 * Action called after processing the data.
					 * To the function is passed an array with submitted data.
					 */
					do_action_ref_array( 'cpcff_free_process_data', array(&$params) );

                    $update_entry_by_password_handling = false;
					foreach ( $passwords_to_delete as $password_to_delete ) {
						unset( $params[ $password_to_delete ] );
                        $update_entry_by_password_handling = true;
					}

					foreach ( $passwords_to_hash as $password_to_hash ) {
						if ( ! empty( $params[ $password_to_hash ] ) ) {
							$params[ $password_to_hash ] = wp_hash_password( $params[ $password_to_hash ] );
                            $update_entry_by_password_handling = true;
						}
					}

					foreach ( $passwords_to_plain as $password_to_plain ) {
						if ( ! empty( $params[ $password_to_plain ] ) ) {
							$params[ $password_to_plain ] = sanitize_text_field( $params[ $password_to_plain ] );
                            $update_entry_by_password_handling = true;
						}
					}

                    if ($update_entry_by_password_handling) {
                        CPCFF_SUBMISSIONS::update($params['itemnumber'], ['paypal_post' => $params]);
                    }

                    require_once __DIR__ . '/inc/cpcff_mail.inc.php';

					$cpcff_mail = new CPCFF_MAIL();
					$cpcff_mail->send_notification_email( $form_obj, $params, $buffer );

					$location = $form_obj->get_option( 'fp_return_page', CP_CALCULATEDFIELDSF_DEFAULT_fp_return_page, $params['itemnumber'] );
					$location = esc_url( CPCFF_AUXILIARY::replace_params_into_url( $location, $params ), null, null );

					if ( ! headers_sent() ) {
						header( 'Location: ' . $location );
					} else {
						print '<script data-category="functional">document.location.href="' . esc_js( $location ) . '";</script>';
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

// Loads the AI related modules.
add_action('admin_init', function(){
    require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_ai_dispatcher.inc.php';
});