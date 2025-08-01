<?php
/**
 * Form class with database interaction, data, and methods.
 *
 * @package CFF.
 * @since 1.0.179
 */

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeEnd
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTagSquiz.Commenting.FunctionComment.MissingParamTag

require_once __DIR__ . '/cpcff_revisions.inc.php';

if ( ! class_exists( 'CPCFF_FORM' ) ) {
	/**
	 * Class to create create, save, and read the forms data.
	 *
	 * @since  1.0.179
	 */
	class CPCFF_FORM {

		/**
		 * Form's id
		 * Instance property.
		 *
		 * @var integer $_id
		 */
		private $_id;

		/**
		 * Form's settings
		 * Instance property.
		 *
		 * @var array $_settings. Associative array with the form's settings.
		 */
		private $_settings;

		/**
		 * Form's fields
		 * Instance property.
		 *
		 * @var array $_fields. Associative array with the form's fields.
		 */
		private $_fields;

		/**
		 * @var object $_revisions_obj Instance of the CPCFF_REVISIONS object to interact with the form's revisions.
		 */
		private $_revisions_obj;
		/*********************************** PUBLIC METHODS  ********************************************/

		/**
		 * Constructs a CPCFF_FORM object.
		 *
		 * @param integer $id the form's id.
		 */
		public function __construct( $id ) {
			$this->_id            = $id;
			$this->_settings      = array();
			$this->_fields        = array();
			$this->_revisions_obj = new CPCFF_REVISIONS( $this );
			add_action( 'cpcff_wp_head', array( $this, 'get_page_header' ) );
		} // End __construct.

		public static function forms_list( $args = []  ) {
			global $wpdb;

			$category 		= isset( $args['category'] ) ? trim( $args['category'] ) : '';
			$search_term 	= isset( $args['search_term'] ) ? trim( $args['search_term'] ) : '';
			$orderby 		= empty( $args['order_by'] ) ? 'id' : $args['order_by'];
			$include_desc   = ! empty( $args['description'] ) ? true : false;

			$myrows = $wpdb->get_results(
				'SELECT id,form_name,category' .
				(
					$include_desc ?
					',form_structure' :
					''
				) .
				' FROM ' . $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE . ' WHERE 1=1 ' . ( '' != $category ? $wpdb->prepare( ' AND category=%s ', $category ) : '' ) . ( '' != $search_term ? $wpdb->prepare( ' AND (form_name LIKE %s OR form_structure LIKE %s)', '%' . $search_term . '%', '%' . $search_term . '%' ) : '' ) . ' ORDER BY ' . $orderby . ( 'id' == $orderby ? ' DESC' : ' ASC' ) );  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$return = [];

			foreach( $myrows as $row ) {
				$data = new stdClass();
				$data->id 			= $row->id;
				$data->form_name 	= sanitize_text_field( $row->form_name );
				$data->category  	= sanitize_text_field( $row->category );
				$data->description 	= '';

				if ( ! property_exists( $row, 'form_structure') ) {
					$form_structure = '';
					if ( '' == $data->form_name ) {
						$form_structure = $wpdb->get_var(
							$wpdb->prepare( 'SELECT form_structure FROM ' . $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE . ' WHERE id=%d', $data->id )
						);
						if ( ! empty( $form_structure ) ) {
							$form_structure = json_decode( $form_structure, true );
						}
					}
				} else {
					$form_structure = json_decode( $row->form_structure, true );
				}

				if (
					! empty( $form_structure ) &&
					! empty( $form_structure[1] ) &&
					is_array( $form_structure[1] ) &&
					! empty( $form_structure[1][0] ) &&
					is_array( $form_structure[1][0] )
				) {
					if ( empty( $data->form_name ) && ! empty( $form_structure[1][0]['title'] ) ) $data->form_name = sanitize_text_field( $form_structure[1][0]['title'] );

					if ( ! empty( $form_structure[1][0]['description'] ) ) $data->description = sanitize_text_field( $form_structure[1][0]['description'] );
				}
				$return[] = $data;
			}

			return $return;
		} // End forms_list.

		/**
		 * Creates a new form with the default data, and the name passed as parameter,
		 * and returns an instance of the CPCFF_FORM class.
		 *
		 * @param string $form_name The form's name displayed in the settings page of the plugin.
		 * @param string $category_name form category.
		 * @param number $form_template template id.
		 *
		 * @return object.
		 */
		public static function create_default( $form_name, $category_name = '', $form_template = 0 ) {
			global $wpdb, $cpcff_default_texts_array;

			$_form_structure = CP_CALCULATEDFIELDSF_DEFAULT_form_structure;
			// Get form structure from server !!!
			if ( ! empty( $form_template ) ) {
				if( is_numeric( $form_template ) ) {
					$response = wp_remote_get(
						'https://cff.dwbooster.com/forms/forms/'.$form_template.'.cpfm',
						array(
							'sslverify' => false
						)
					);

					if ( ! is_wp_error( $response ) ) {
						$_form_structure_tmp = wp_remote_retrieve_body( $response );
						if ( !empty( $_form_structure_tmp ) ) {
							$_form_structure = $_form_structure_tmp;
						}
					}
				} else if ( is_string( $form_template ) ) {
					json_decode( $form_template, 'mormal' );
					if ( json_last_error() === JSON_ERROR_NONE ) {
						$_form_structure = $form_template;
					}
				}
			}

			if (
				$wpdb->insert(
					$wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE,
					array(
						// Form name.
						'form_name'                   => stripcslashes( $form_name ),

						// Form structure.
						'form_structure'              => $_form_structure,

						// Notification email.
						'fp_from_email'               => CP_CALCULATEDFIELDSF_DEFAULT_fp_from_email,
						'fp_destination_emails'       => CP_CALCULATEDFIELDSF_DEFAULT_fp_destination_emails,
						'fp_subject'                  => CP_CALCULATEDFIELDSF_DEFAULT_fp_subject,
						'fp_inc_additional_info'      => CP_CALCULATEDFIELDSF_DEFAULT_fp_inc_additional_info,
						'fp_return_page'              => CP_CALCULATEDFIELDSF_DEFAULT_fp_return_page,
						'fp_message'                  => CP_CALCULATEDFIELDSF_DEFAULT_fp_message,

						// Notification email copy to the user.
						'cu_enable_copy_to_user'      => CP_CALCULATEDFIELDSF_DEFAULT_cu_enable_copy_to_user,
						'cu_user_email_field'         => CP_CALCULATEDFIELDSF_DEFAULT_cu_user_email_field,
						'cu_subject'                  => CP_CALCULATEDFIELDSF_DEFAULT_cu_subject,
						'cu_message'                  => CP_CALCULATEDFIELDSF_DEFAULT_cu_message,

						// Activate validation and validation's texts.
						'vs_use_validation'           => CP_CALCULATEDFIELDSF_DEFAULT_vs_use_validation,
						'vs_text_is_required'         => CP_CALCULATEDFIELDSF_DEFAULT_vs_text_is_required,
						'vs_text_is_email'            => CP_CALCULATEDFIELDSF_DEFAULT_vs_text_is_email,
						'vs_text_datemmddyyyy'        => CP_CALCULATEDFIELDSF_DEFAULT_vs_text_datemmddyyyy,
						'vs_text_dateddmmyyyy'        => CP_CALCULATEDFIELDSF_DEFAULT_vs_text_dateddmmyyyy,
						'vs_text_number'              => CP_CALCULATEDFIELDSF_DEFAULT_vs_text_number,
						'vs_text_digits'              => CP_CALCULATEDFIELDSF_DEFAULT_vs_text_digits,
						'vs_text_max'                 => CP_CALCULATEDFIELDSF_DEFAULT_vs_text_max,
						'vs_text_min'                 => CP_CALCULATEDFIELDSF_DEFAULT_vs_text_min,
						'vs_all_texts'                => serialize( $cpcff_default_texts_array ),

						// Paypal settings.
						'enable_paypal'               => get_option( 'CP_CALCULATEDFIELDSF_DEFAULT_ENABLE_PAYPAL', CP_CALCULATEDFIELDSF_DEFAULT_ENABLE_PAYPAL ),
						'paypal_email'                => CP_CALCULATEDFIELDSF_DEFAULT_PAYPAL_EMAIL,
						'request_cost'                => 'fieldname1',
						'paypal_product_name'         => CP_CALCULATEDFIELDSF_DEFAULT_PRODUCT_NAME,
						'currency'                    => CP_CALCULATEDFIELDSF_DEFAULT_CURRENCY,
						'paypal_language'             => CP_CALCULATEDFIELDSF_DEFAULT_PAYPAL_LANGUAGE,

						// Captcha settings.
						'cv_enable_captcha'           => CP_CALCULATEDFIELDSF_DEFAULT_cv_enable_captcha,
						'cv_width'                    => CP_CALCULATEDFIELDSF_DEFAULT_cv_width,
						'cv_height'                   => CP_CALCULATEDFIELDSF_DEFAULT_cv_height,
						'cv_chars'                    => CP_CALCULATEDFIELDSF_DEFAULT_cv_chars,
						'cv_font'                     => CP_CALCULATEDFIELDSF_DEFAULT_cv_font,
						'cv_min_font_size'            => CP_CALCULATEDFIELDSF_DEFAULT_cv_min_font_size,
						'cv_max_font_size'            => CP_CALCULATEDFIELDSF_DEFAULT_cv_max_font_size,
						'cv_noise'                    => CP_CALCULATEDFIELDSF_DEFAULT_cv_noise,
						'cv_noise_length'             => CP_CALCULATEDFIELDSF_DEFAULT_cv_noise_length,
						'cv_background'               => CP_CALCULATEDFIELDSF_DEFAULT_cv_background,
						'cv_border'                   => CP_CALCULATEDFIELDSF_DEFAULT_cv_border,
						'cv_text_enter_valid_captcha' => CP_CALCULATEDFIELDSF_DEFAULT_cv_text_enter_valid_captcha,

						'enable_submit'               => CP_CALCULATEDFIELDSF_DEFAULT_display_submit_button,
						'category'                    => $category_name,

						'cu_user_email_bcc_field'     => '',
					),
					array(
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
					)
				)
			) {
				return new self( $wpdb->insert_id );
			}
			return false;
		} // End create_default.

		/**
		 * Sanitize the form's structure. Fields titltes and instructions for users,
		 * form's title and description. etc.
		 *
		 * @param mixed $structure form structure.
		 */
		public static function sanitize_structure( $structure ) {

			function sanitize_attributes( $v, $i = '', $arr = [] ) { // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
				if ( is_array( $v ) ) {
					foreach ( $v as $k => $v1 ) {
						$v[ $k ] = sanitize_attributes( $v1, $k, $v );
					}
				} elseif ( is_object( $v ) ) {
					$keys = array_keys( get_object_vars( $v ) );
					foreach ( $keys as $k ) {
						$v->$k = sanitize_attributes( $v->$k, $k, (array) $v );
					}
				} elseif ( is_string( $v )  ) {
					$v = preg_replace( '/(\b)_style\s*=/i', '$1style=', $v);
					$i_l = strtolower( $i );
					if (
						! in_array( $i_l, array( 'eq', 'fcontent', 'customstyles', 'rule', 'sonclick', 'sonmousedown' ) )
					) {
						$v = str_replace( '&', 'cff___amp', $v );
						$v = CPCFF_AUXILIARY::sanitize( wp_slash($v), true, true );
 						$v = str_ireplace( ['&lt;', '&gt;', '&amp;'], ['<', '>', '&'], $v );
 						$v = str_replace( 'cff___amp', '&', $v );
					} elseif ( 'customstyles' == $i_l ) {
						$v = str_replace( '&gt;', '>', wp_kses( $v, 'strip') );
					}
				}

				return $v;
			}

			return sanitize_attributes( $structure );
		} // End sanitize_structure.

		/**
		 * Clones the current form.
		 *
		 * @return mixed, a new instance of the CPCFF_FORM or false.
		 */
		public function clone_form( $form_name = '' ) {
			global $wpdb;
			$form_name = trim( $form_name );
			$row = $this->_get_settings();
			if ( ! empty( $row ) ) {
				unset( $row['id'] );
				$row['form_name'] = ! empty( $form_name ) ? $form_name : ( ! empty( $row['form_name'] ) ? 'Cloned: ' . $row['form_name'] : '' );
				if ( isset( $row['extra'] ) && is_array( $row['extra'] ) ) {
					$row['extra'] = json_encode( $row['extra'] );
				}
				if ( $wpdb->insert( $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE, $row ) ) {
					return new self( $wpdb->insert_id );
				}
			}
			return false;
		} // End clone_form.

		/**
		 * Returns the id of current form.
		 *
		 * @return integer
		 */
		public function get_id() {
			return $this->_id;
		} // end get_id.

		/**
		 * Reads the corresponding attribute in the form's settings.
		 *
		 * Reads the attribute in the form's settings, and if it does not exists returns the default value.
		 * Applies the filter cpcff_get_option
		 *
		 * @param string $option name of the attribute to read.
		 * @param mixed  $default default value of the attribute.
		 * @return mixed it depends of the option to read.
		 */
		public function get_option( $option, $default ) {
			// Initialize the value with the default values.
			$value = $default;
			$this->_get_settings();

			if (
				isset( $this->_settings[ $option ] ) &&
				! is_null( $this->_settings[ $option ] )
			) {
				$value = @$this->_settings[ $option ];
			}

			// If the form's structure is a JSON text decodes it.
			if (
				'form_structure' == $option &&
				! is_array( $value )
			) {
				$form_data = CPCFF_AUXILIARY::json_decode( $value, 'normal' );
				if ( ! is_null( $form_data ) ) {
					$value = $form_data;
					$this->_settings['form_structure'] = $form_data;
				}
			} elseif ( $option == 'vs_all_texts' ) { // If the texts where not defined previously or the thank you page is empty populate them with the default values.
				error_reporting( E_ERROR | E_PARSE );

				global $cpcff_default_texts_array;

				$value = empty( $value ) ? $default : $value;

				if ( is_string( $value ) ) {
					$value = unserialize( $value );
				}

				if ( is_array( $value ) ) {
					$value = CPCFF_AUXILIARY::array_replace_recursive(
						$cpcff_default_texts_array,
						$value
					);
				} else {
					$value = $cpcff_default_texts_array;
				}
			} elseif ( $option == 'fp_return_page' ) {
				$value = empty( $value ) ? $default : $value;
			} elseif (
				in_array( $option, [ 'enable_pay_later', 'fp_attach_static', 'fp_reply_to_emails', 'cu_attach_static', 'cu_reply_to_emails', 'form_height', 'fp_ajax', 'fp_ajax_reset_form', 'fp_thanks_mssg', 'fp_disable_submissions' ] )
			) {
				if ( isset( $this->_settings['extra'][ $option ] ) ) {
					$value = $this->_settings['extra'][ $option ];
				} else {
					$value = $default;
				}
			}
			/**
			 * Filters applied before returning a form option,
			 * use three parameters: The value of option, the name of option and the form's id
			 * returns the new option's value
			 */
			if(!is_admin()) $value = apply_filters( 'cpcff_get_option', $value, $option, $this->_id );
			else $value = apply_filters( 'cpcff_admin_get_option', $value, $option, $this->_id );

			return $value;
		} // End get_option.

		/**
		 * Returns the list of fields in the forms.
		 *
		 * @return array, associative array of objects where the fields' names are the indices and fields' structures the values.
		 */
		public function get_fields() {
			if ( ! empty( $this->_fields ) ) {
				return $this->_fields;
			}

			$form_structure = $this->get_option( 'form_structure', array() );
			if ( ! empty( $form_structure[0] ) ) {
				foreach ( $form_structure[0] as $field ) {
					$this->_fields[ $field->name ] = $field;
				}
			}
			return $this->_fields;
		} // End get_fields.

		public function get_height( $selector ) {
			$form_heights = $this->get_option( 'form_height', array() );
			if ( empty( $form_heights ) || ! is_array( $form_heights ) ) {
				$form_heights = array();
			}

			$output = '';
			if ( ! empty( $form_heights ) ) {
				$output .= '<style>';
				foreach( $form_heights as $screen_size => $form_height ) {
					if ( empty( $form_height ) ) continue;
					switch ( $screen_size ) {
						case 320 : $output .= '@media (max-width:480px)';  break;
						case 480 : $output .= '@media (max-width:768px)';  break;
						case 768 : $output .= '@media (max-width:1024px)'; break;
						default  : $output .= '@media (min-width:1024px)'; break;
					}
					$output .= '{' . $selector . '{min-height:' . $form_height . 'px;}}';
				}
				$output .= '</style>';
			}
			return $output;
		} // End get_height.

		public function get_page_header( $p = 0 ) {
			$output = '';
			$form_structure = $this->get_option('form_structure', array());
			if (
				! empty( $form_structure ) &&
				is_array( $form_structure ) &&
				! empty( $form_structure[1] ) &&
				is_array( $form_structure[1] ) &&
				! empty( $form_structure[1][0] ) &&
				is_object( $form_structure[1][0] )
			) {
				if ( ! empty ( $form_structure[1][0]->title ) ) {
					$output .= '<title>' . wp_strip_all_tags( $form_structure[1][0]->title ) . '</title>';
				}

				if ( ! empty ( $form_structure[1][0]->description ) ) {
					$output .= '<meta name="description" content="' . esc_attr( $form_structure[1][0]->description ) . '">';
				}
			}

			print $output;
		} // End get_page_header.

		public function save_settings( $params ) {
			global $wpdb, $cpcff_default_texts_array;

			foreach ( $params as $i => $v ) {
				if ( 'form_structure' != $i && 'fp_message' != $i && 'cu_message' != $i ) {
					$params[ $i ] = CPCFF_AUXILIARY::sanitize( $v, true );
				} else if ( 'fp_message' == $i || 'cu_message' == $i ) {
					$params[ $i ] = CPCFF_AUXILIARY::sanitize( $v, true, false, true );
				}
			}

			$extra = array( 'form_height' => array() );

			if( isset( $params['enable_pay_later'] ) ) {
				$extra['enable_pay_later'] = $params['enable_pay_later'] == 1 ? 1 : 0;
			}
			if ( isset( $params['fp_attach_static'] ) ) {
				$extra['fp_attach_static'] = $params['fp_attach_static'];
			}
			if ( isset( $params['fp_reply_to_emails'] ) ) {
				$extra['fp_reply_to_emails'] = trim( $params['fp_reply_to_emails'] );
			}
			if ( isset( $params['cu_attach_static'] ) ) {
				$extra['cu_attach_static'] = $params['cu_attach_static'];
			}
			if ( isset( $params['cu_reply_to_emails'] ) ) {
				$extra['cu_reply_to_emails'] = trim( $params['cu_reply_to_emails'] );
			}
			$extra['fp_disable_submissions'] = isset( $params['fp_disable_submissions'] ) ? 1 : 0;
			$extra['fp_ajax'] = isset( $params['fp_ajax'] ) ? 1 : 0;
			$extra['fp_ajax_reset_form'] = isset( $params['fp_ajax_reset_form'] ) ? 1 : 0;
			if( isset( $params['fp_thanks_mssg'] ) ) {
				$extra['fp_thanks_mssg'] = sanitize_textarea_field( $params['fp_thanks_mssg'] );
			}

			$data = array(
				'form_structure'              => ( isset( $params['form_structure'] ) ) ? $params['form_structure'] : CP_CALCULATEDFIELDSF_DEFAULT_form_structure,

				// Notification email.
				'fp_from_email'               => ( isset( $params['fp_from_email'] ) ) ? $params['fp_from_email'] : '',
				'fp_destination_emails'       => ( isset( $params['fp_destination_emails'] ) ) ? $params['fp_destination_emails'] : '',
				'fp_subject'                  => ( isset( $params['fp_subject'] ) ) ? $params['fp_subject'] : '',
				'fp_inc_additional_info'      => ( isset( $params['fp_inc_additional_info'] ) && 'true' == $params['fp_inc_additional_info'] ) ? 'true' : 'false',
				'fp_inc_attachments'          => ( isset( $params['fp_inc_attachments'] ) && 1 == $params['fp_inc_attachments'] ) ? 1 : 0,
				'fp_return_page'              => ( isset( $params['fp_return_page'] ) ) ? $params['fp_return_page'] : '',
				'fp_message'                  => ( isset( $params['fp_message'] ) ) ? $params['fp_message'] : '',
				'fp_emailformat'              => ( isset( $params['fp_emailformat'] ) && 'text' == $params['fp_emailformat'] ) ? 'text' : 'html',

				// Notification email copy to the user.
				'cu_enable_copy_to_user'      => ( isset( $params['cu_enable_copy_to_user'] ) && 'true' == $params['cu_enable_copy_to_user'] ) ? 'true' : 'false',
				'cu_user_email_field'         => ( ! empty( $params['cu_user_email_field'] ) ) ? $params['cu_user_email_field'] : '',
				'cu_subject'                  => ( isset( $params['cu_subject'] ) ) ? $params['cu_subject'] : '',
				'cu_message'                  => ( isset( $params['cu_message'] ) ) ? $params['cu_message'] : '',
				'cu_emailformat'              => ( isset( $params['cu_emailformat'] ) && 'text' == $params['cu_emailformat'] ) ? 'text' : 'html',

				// PayPal settings.
				'enable_submit'               => ( isset( $params['enable_submit'] ) ) ? $params['enable_submit'] : CP_CALCULATEDFIELDSF_DEFAULT_display_submit_button,
				'enable_paypal'               => ( isset( $params['enable_paypal'] ) ) ? $params['enable_paypal'] : 0,
				'paypal_email'                => ( isset( $params['paypal_email'] ) ) ? $params['paypal_email'] : '',
				'request_cost'                => ( isset( $params['request_cost'] ) ) ? $params['request_cost'] : '',
				'paypal_product_name'         => ( isset( $params['paypal_product_name'] ) ) ? $params['paypal_product_name'] : '',
				'currency'                    => ( isset( $params['currency'] ) ) ? $params['currency'] : 'USD',
				'paypal_language'             => ( isset( $params['paypal_language'] ) ) ? $params['paypal_language'] : 'EN',
				'paypal_mode'                 => ( isset( $params['paypal_mode'] ) ) ? $params['paypal_mode'] : 'production',
				'paypal_recurrent'            => ( isset( $params['paypal_recurrent'] ) ) ? $params['paypal_recurrent'] : 0,
				'paypal_identify_prices'      => ( isset( $params['paypal_identify_prices'] ) ) ? $params['paypal_identify_prices'] : '0',
				'paypal_zero_payment'         => ( isset( $params['paypal_zero_payment'] ) ) ? $params['paypal_zero_payment'] : 0,

				// Texts.
				'vs_use_validation'           => CP_CALCULATEDFIELDSF_DEFAULT_vs_use_validation,
				'vs_text_is_required'         => ( isset( $params['vs_text_is_required'] ) ) ? $params['vs_text_is_required'] : CP_CALCULATEDFIELDSF_DEFAULT_vs_text_is_required,
				'vs_text_is_email'            => ( isset( $params['vs_text_is_email'] ) ) ? $params['vs_text_is_email'] : CP_CALCULATEDFIELDSF_DEFAULT_vs_text_is_email,
				'vs_text_datemmddyyyy'        => ( isset( $params['vs_text_datemmddyyyy'] ) ) ? $params['vs_text_datemmddyyyy'] : CP_CALCULATEDFIELDSF_DEFAULT_vs_text_datemmddyyyy,
				'vs_text_dateddmmyyyy'        => ( isset( $params['vs_text_dateddmmyyyy'] ) ) ? $params['vs_text_dateddmmyyyy'] : CP_CALCULATEDFIELDSF_DEFAULT_vs_text_dateddmmyyyy,
				'vs_text_number'              => ( isset( $params['vs_text_number'] ) ) ? $params['vs_text_number'] : CP_CALCULATEDFIELDSF_DEFAULT_vs_text_number,
				'vs_text_digits'              => ( isset( $params['vs_text_digits'] ) ) ? $params['vs_text_digits'] : CP_CALCULATEDFIELDSF_DEFAULT_vs_text_digits,
				'vs_text_max'                 => ( isset( $params['vs_text_max'] ) ) ? $params['vs_text_max'] : CP_CALCULATEDFIELDSF_DEFAULT_vs_text_max,
				'vs_text_min'                 => ( isset( $params['vs_text_min'] ) ) ? $params['vs_text_min'] : CP_CALCULATEDFIELDSF_DEFAULT_vs_text_min,
				'vs_text_submitbtn'           => ( isset( $params['vs_text_submitbtn'] ) ) ? $params['vs_text_submitbtn'] : 'Submit',
				'vs_text_previousbtn'         => ( isset( $params['vs_text_previousbtn'] ) ) ? $params['vs_text_previousbtn'] : 'Previous',
				'vs_text_nextbtn'             => ( isset( $params['vs_text_nextbtn'] ) ) ? $params['vs_text_nextbtn'] : 'Next',
				'vs_all_texts'                => ( isset( $params['vs_all_texts'] ) ) ? serialize( $params['vs_all_texts'] ) : serialize( $cpcff_default_texts_array ),

				// Captcha settings.
				'cv_enable_captcha'           => ( isset( $params['cv_enable_captcha'] ) ) ? $params['cv_enable_captcha'] : CP_CALCULATEDFIELDSF_DEFAULT_cv_enable_captcha,
				'cv_width'                    => ( isset( $params['cv_width'] ) ) ? $params['cv_width'] : CP_CALCULATEDFIELDSF_DEFAULT_cv_width,
				'cv_height'                   => ( isset( $params['cv_height'] ) ) ? $params['cv_height'] : CP_CALCULATEDFIELDSF_DEFAULT_cv_height,
				'cv_chars'                    => ( isset( $params['cv_chars'] ) ) ? $params['cv_chars'] : CP_CALCULATEDFIELDSF_DEFAULT_cv_chars,
				'cv_font'                     => ( isset( $params['cv_font'] ) ) ? $params['cv_font'] : CP_CALCULATEDFIELDSF_DEFAULT_cv_font,
				'cv_min_font_size'            => ( isset( $params['cv_min_font_size'] ) ) ? $params['cv_min_font_size'] : CP_CALCULATEDFIELDSF_DEFAULT_cv_min_font_size,
				'cv_max_font_size'            => ( isset( $params['cv_max_font_size'] ) ) ? $params['cv_max_font_size'] : CP_CALCULATEDFIELDSF_DEFAULT_cv_max_font_size,
				'cv_noise'                    => ( isset( $params['cv_noise'] ) ) ? $params['cv_noise'] : CP_CALCULATEDFIELDSF_DEFAULT_cv_noise,
				'cv_noise_length'             => ( isset( $params['cv_noise_length'] ) ) ? $params['cv_noise_length'] : CP_CALCULATEDFIELDSF_DEFAULT_cv_noise_length,
				'cv_background'               => ( isset( $params['cv_background'] ) ) ? $params['cv_background'] : CP_CALCULATEDFIELDSF_DEFAULT_cv_background,
				'cv_border'                   => ( isset( $params['cv_border'] ) ) ? $params['cv_border'] : CP_CALCULATEDFIELDSF_DEFAULT_cv_border,
				'cv_text_enter_valid_captcha' => ( isset( $params['cv_text_enter_valid_captcha'] ) ) ? $params['cv_text_enter_valid_captcha'] : CP_CALCULATEDFIELDSF_DEFAULT_cv_text_enter_valid_captcha,
				'category'                    => isset( $params['calculated-fields-form-category'] ) ? $params['calculated-fields-form-category'] : '',
				'extra'                       => json_encode( $extra ),

				'cu_user_email_bcc_field'     => ! empty( $params['cu_user_email_bcc_field'] ) ? $params['cu_user_email_bcc_field'] : '',
			);

			$updated_rows = $wpdb->update(
				$wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE,
				$data,
				array( 'id' => $this->_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			// Revisions.
			update_option( 'CP_CALCULATEDFIELDSF_REVISIONS_IN_PREVIEW', isset( $params['cff-revisions-in-preview'] ) ? true : false );

			if (
				false !== $updated_rows &&
				(
					! isset( $params['preview'] ) ||
					get_option( 'CP_CALCULATEDFIELDSF_REVISIONS_IN_PREVIEW' )
				) &&
				get_option( 'CP_CALCULATEDFIELDSF_DISABLE_REVISIONS', CP_CALCULATEDFIELDSF_DISABLE_REVISIONS ) == 0
			) {
				$this->_revisions_obj->create_revision();
			}
			return $updated_rows;
		} // End save_settings.

		/**
		 * Gets the correspond rown in the database
		 */
		public function get_raw_data() {
			global $wpdb;
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE . ' WHERE id=%d', $this->_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $row;
		} // End get_raw_data.

		/**
		 * Returns an instance of the CPCFF_REVISIONS revisions object
		 */
		public function get_revisions() {
			return $this->_revisions_obj;
		} // End get_revisions.

		/**
		 * Creates or replace the property $this->_settings with the data stored in the revisions
		 *
		 * @param number $revision_id revision id.
		 */
		public function apply_revision( $revision_id ) {
			$this->_get_settings();
			$revision_data   = $this->_revisions_obj->data( $revision_id );
			$this->_fields   = array();
			$this->_settings = CPCFF_AUXILIARY::array_replace_recursive( $this->_settings, $revision_data );
		} // End apply_revision.

		/**
		 * Updates the form's name
		 *
		 * @param string $form_name form name.
		 *
		 * @return bool
		 */
		public function update_name( $form_name ) {
			global $wpdb;
			return $wpdb->update(
				$wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE,
				array( 'form_name' => $form_name ),
				array( 'id' => $this->_id ),
				array( '%s' ),
				array( '%d' )
			);
		} // End update_name.

		/**
		 * Deletes the current form.
		 *
		 * @return mixed the number of deleted columns or false.
		 */
		public function delete_form() {
			global $wpdb;
			$this->_revisions_obj->delete_form();
			return $wpdb->delete(
				$wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE,
				array( 'id' => $this->_id ),
				array( '%d' )
			);
		}
		/*********************************** PRIVATE METHODS  ********************************************/

		/**
		 * Returns the form's settings.
		 * Checks if the settings were read previously, before reading the data from database.
		 *
		 * @since 1.0.184
		 *
		 * @return array, associative array with the database row.
		 */
		private function _get_settings() {
			if ( empty( $this->_settings ) ) {
				$row = $this->get_raw_data();
				if ( ! empty( $row ) ) {
					$this->_settings = $row;
					if ( ! empty( $this->_settings['extra'] ) && false != ( $extra = json_decode( $this->_settings['extra'], true ) ) ) {
						$this->_settings['extra'] = $extra;
					} else {
						$this->_settings['extra'] = array();
					}
				}
			}
			return $this->_settings;
		} // End _get_settings.
	} // End CPCFF_FORM.
}
