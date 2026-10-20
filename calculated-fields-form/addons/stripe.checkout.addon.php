<?php
/*
Documentation: https://stripe.com/docs/quickstart
*/
require_once dirname( __FILE__ ) . '/base.addon.php';
require_once dirname( __FILE__ ) . '/stripe-php.addon/stripe_http_helper.php';

if ( ! class_exists( 'CPCFF_Stripe_Checkout' ) ) {
	class CPCFF_Stripe_Checkout extends CPCFF_BaseAddon {

		public static $category = 'Payment Gateways';

		/************* ADDON SYSTEM - ATTRIBUTES AND METHODS *************/
		protected $addonID = 'addon-stripe-checkout-20221004';
		protected $name    = 'CFF - Stripe Checkout';
		protected $description;
		protected $help              = 'https://cff.dwbooster.com/documentation#stripe-checkout-addon';
		protected $default_pay_label = 'Pay with Credit Cards';
		protected $active_by_default = true;

		public function get_addon_form_settings( $form_id ) {
			if ( $this->_form_settings_inserted_flag ) return;
			$this->_form_settings_inserted_flag = true;

			global $wpdb;
			$table = $wpdb->prefix . $this->form_table;

			// Insertion in database
			if (
				isset( $_REQUEST['cpcff_stripe_checkout_id'] )
			) {
				$wpdb->delete( $table, array( 'formid' => $form_id ), array( '%d' ) );
				$settings = array(
					'stripe_key'           => isset( $_REQUEST['stripe_checkout_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['stripe_checkout_key'] ) ) : '',
					'stripe_secretkey'     => isset( $_REQUEST['stripe_checkout_secretkey'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['stripe_checkout_secretkey'] ) ) : '',
					'frequency'            => '',
					'times'                => 0,
					'times_field'          => '',
					'trialdays'            => 0,
					'planname'             => '',
					'stripe_mode'          => isset( $_REQUEST['stripe_checkout_mode'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['stripe_checkout_mode'] ) ) : 1,
					'stripe_testkey'       => isset( $_REQUEST['stripe_checkout_testkey'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['stripe_checkout_testkey'] ) ) : '',
					'stripe_testsecretkey' => isset( $_REQUEST['stripe_checkout_testsecretkey'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['stripe_checkout_testsecretkey'] ) ) : '',
					'stripe_image'         => isset( $_REQUEST['stripe_checkout_image'] ) ? esc_url_raw( wp_unslash( $_REQUEST['stripe_checkout_image'] ) ) : '',
					'stripe_language'      => isset( $_REQUEST['stripe_checkout_language'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['stripe_checkout_language'] ) ) : 'EN',
					'enable_option_yes'    => isset( $_REQUEST['stripe_checkout_enable_option_yes'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['stripe_checkout_enable_option_yes'] ) ) : '',
					'askbilling'           => isset( $_REQUEST['stripe_checkout_askbilling'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['stripe_checkout_askbilling'] ) ) : 0,
					'tax_id_collection'    => isset( $_REQUEST['stripe_checkout_tax_id_collection'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['stripe_checkout_tax_id_collection'] ) ) : 0,
					'automatic_tax'        => isset( $_REQUEST['stripe_checkout_automatic_tax'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['stripe_checkout_automatic_tax'] ) ) : 0,
					'stripe_metadata'      => isset( $_REQUEST['stripe_checkout_metadata'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['stripe_checkout_metadata'] ) ) : '',
					'stripe_metadata_shortlabel' => isset( $_REQUEST['stripe_checkout_metadata_shortlabel'] ) ? 1 : 0,
				);

				$wpdb->insert(
					$table,
					array(
						'formid'   => $form_id,
						'enabled'  => isset( $_REQUEST['stripe_checkout_enabled'] ) && is_numeric( $_REQUEST['stripe_checkout_enabled'] ) ? intval( $_REQUEST['stripe_checkout_enabled'] ) : 0,
						'settings' => json_encode( $settings ),
					),
					array(
						'%d',
						'%d',
						'%s',
					)
				);
			}

			$enabled = 0;

			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM ' . $table . ' WHERE formid=%d', $form_id ),
				ARRAY_A
			);

			if ( ! empty( $row ) ) {
				$settings = $this->initialize_settings( $row['settings'] );
				$enabled  = $row['enabled']*1;
			} else {
				$settings = $this->initialize_settings();
			}

			$cpcff_main = CPCFF_MAIN::instance();
			?>
			<div id="metabox_stripe_checkout_addon_form_settings" class="postbox cff-add-on-settings <?php print esc_attr( __CLASS__ );
			print ! empty( $enabled ) ? ' cff-payment-gateway-enabled ' : '';
			?> cff-metabox <?php print esc_attr( $cpcff_main->metabox_status( 'metabox_stripe_checkout_addon_form_settings', 'close' ) ); ?>" >
				<h3 class='hndle' style="padding:5px;"><span><?php print esc_html( $this->name ); ?></span></h3>
				<div class="inside">
					<input type="hidden" name="cpcff_stripe_checkout_id" value="1" />
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Enable Stripe checkout?', 'calculated-fields-form' ); ?></th>
							<td>
								<select name="stripe_checkout_enabled">
									<option value="0" <?php if (!$enabled) {echo 'selected';} ?>><?php esc_html_e( 'No', 'calculated-fields-form' ); ?></option>
									<option value="1" <?php if ($enabled) {echo 'selected';} ?>><?php esc_html_e( 'Yes', 'calculated-fields-form' ); ?></option>
								</select>
								<br /><i style="font-size:11px;"><?php esc_html_e( 'Note: When "multiple" payment methods are active, a radio button appears in the form for selection.', 'calculated-fields-form' ); ?></i>
								<div class="width75 cff-settings-hint-blue">
									<?php esc_html_e( 'Label for this payment option', 'calculated-fields-form' ); ?>:<br />
									<input type="text" name="stripe_checkout_enable_option_yes" size="40" style="width:100%;" value="<?php echo esc_attr( $settings['enable_option_yes'] ); ?>" />
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Payment mode?', 'calculated-fields-form' ); ?></th>
							<td>
								<select name="stripe_checkout_mode" onchange="cff_stripe_checkout_changemode(this);" class="width50">
									<option value="0" <?php print empty( $settings['stripe_mode'] ) ? 'SELECTED' : ''; ?>>
										<?php esc_html_e( 'Test Mode', 'calculated-fields-form' ); ?>
									</option>
									<option value="1" <?php print 1 == $settings['stripe_mode'] ? 'SELECTED' : ''; ?>>
										<?php esc_html_e( 'Live/Production Mode', 'calculated-fields-form' ); ?>
									</option>
								</select>
							</td>
						</tr>
						<tr valign="top" <?php print empty( $settings['stripe_mode'] ) ? 'style="display:none"' : ''; ?> class="cff-stripe-checkout-production">
							<th scope="row">Stripe.com <span style="color:green">
								Production</span> <a href="https://manage.stripe.com/account/apikeys" target="_blank" title="<?php esc_attr_e( 'click to get the your stripe key', 'calculated-fields-form' ); ?>"><?php esc_html_e( 'Publishable Key', 'calculated-fields-form' ); ?></a>
							</th>
							<td>
								<input type="text" name="stripe_checkout_key" size="20" value="<?php echo esc_attr( $settings['stripe_key'] ); ?>" class="width75" />
							</td>
						</tr>
						<tr valign="top" <?php print empty( $settings['stripe_mode'] ) ? 'style="display:none"' : ''; ?> class="cff-stripe-checkout-production">
							<th scope="row">
								Stripe.com <span style="color:green">Production</span> <a href="https://manage.stripe.com/account/apikeys" target="_blank" title="<?php esc_attr_e( 'click to get the your stripe secret key', 'calculated-fields-form' ); ?>"><?php esc_html_e( 'Secret Key', 'calculated-fields-form' ); ?></a>
							</th>
							<td>
								<input type="text" name="stripe_checkout_secretkey" size="20" value="<?php echo esc_attr( $settings['stripe_secretkey'] ); ?>" class="width75" />
							</td>
						</tr>
						<tr valign="top" <?php print ! empty( $settings['stripe_mode'] ) ? 'style="display:none"' : ''; ?> class="cff-stripe-checkout-test">
							<th scope="row">
								Stripe.com <span style="color:red">TEST</span> <a href="https://manage.stripe.com/account/apikeys" target="_blank" title="<?php esc_attr_e( 'click to get the your stripe key', 'calculated-fields-form' ); ?>"><?php esc_html_e( 'Publishable Key', 'calculated-fields-form' ); ?></a>
							</th>
							<td>
								<input type="text" name="stripe_checkout_testkey" size="20" value="<?php echo esc_attr( $settings['stripe_testkey'] ); ?>" class="width75" />
							</td>
						</tr>
						<tr valign="top" <?php print ! empty( $settings['stripe_mode'] ) ? 'style="display:none"' : ''; ?> class="cff-stripe-checkout-test">
							<th scope="row">
								Stripe.com <span style="color:red">TEST</span> <a href="https://manage.stripe.com/account/apikeys" target="_blank" title="<?php esc_attr_e( 'click to get the your stripe secret key', 'calculated-fields-form' ); ?>"><?php esc_html_e( 'Secret Key', 'calculated-fields-form' ); ?></a>
							</th>
							<td>
								<input type="text" name="stripe_checkout_testsecretkey" size="20" value="<?php echo esc_attr( $settings['stripe_testsecretkey'] ); ?>" class="width75" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Language?', 'calculated-fields-form' ); ?>
							</th>
							<td>
								<select name="stripe_checkout_language" class="width50">
									<option value="auto" <?php print 'auto' == $settings['stripe_language'] ? 'SELECTED' : ''; ?>>
										<?php esc_html_e( 'auto (recommended)', 'calculated-fields-form' ); ?>
									</option>
									<option value="da" <?php print 'da' == $settings['stripe_language'] ? 'SELECTED' : ''; ?>>
										Danish (da)
									</option>
									<option value="nl" <?php print 'nl' == $settings['stripe_language'] ? 'SELECTED' : ''; ?>>
										Dutch (nl)
									</option>
									<option value="en" <?php print 'en' == $settings['stripe_language'] ? 'SELECTED' : ''; ?>>
										English (en)
									</option>
									<option value="fi" <?php print 'fi' == $settings['stripe_language'] ? 'SELECTED' : ''; ?>>
										Finnish (fi)
									</option>
									<option value="fr" <?php print 'fr' == $settings['stripe_language'] ? 'SELECTED' : ''; ?>>
										French (fr)
									</option>
									<option value="de" <?php print 'de' == $settings['stripe_language'] ? 'SELECTED' : ''; ?>>
										German (de)
									</option>
									<option value="it" <?php print 'it' == $settings['stripe_language'] ? 'SELECTED' : ''; ?>>
										Italian (it)
									</option>
									<option value="ja" <?php print 'ja' == $settings['stripe_language'] ? 'SELECTED' : ''; ?>>
										Japanese (ja)
									</option>
									<option value="no" <?php print 'no' == $settings['stripe_language'] ? 'SELECTED' : ''; ?>>
										Norwegian (no)
									</option>
									<option value="zh" <?php print 'zh' == $settings['stripe_language'] ? 'SELECTED' : ''; ?>>
										Simplified Chinese (zh)
									</option>
									<option value="es" <?php print 'es' == $settings['stripe_language'] ? 'SELECTED' : ''; ?>>
										Spanish (es)
									</option>
									<option value="sv" <?php print 'sv' == $settings['stripe_language'] ? 'SELECTED' : ''; ?>>
										Swedish (sv)
									</option>
								</select>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Ask for billing address?', 'calculated-fields-form' ); ?>
							</th>
							<td>
								<select name="stripe_checkout_askbilling" class="width50">
									<option value="0" <?php print empty( $settings['askbilling'] ) ? 'SELECTED' : ''; ?>>
										<?php esc_html_e( 'No', 'calculated-fields-form' ); ?>
									</option>
									<option value="1" <?php print ! empty( $settings['askbilling'] ) ? 'SELECTED' : ''; ?>>
										<?php esc_html_e( 'Yes', 'calculated-fields-form' ); ?>
									</option>
								</select>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Ask tax number?', 'calculated-fields-form' ); ?>
							</th>
							<td>
								<select name="stripe_checkout_tax_id_collection" class="width50">
									<option value="0" <?php print empty( $settings['tax_id_collection'] ) ? 'SELECTED' : ''; ?>>
										<?php esc_html_e( 'No', 'calculated-fields-form' ); ?>
									</option>
									<option value="1" <?php print ! empty( $settings['tax_id_collection'] ) ? 'SELECTED' : ''; ?>>
										<?php esc_html_e( 'Yes', 'calculated-fields-form' ); ?>
									</option>
								</select>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Automatic tax calculation?', 'calculated-fields-form' ); ?>
							</th>
							<td>
								<select name="stripe_checkout_automatic_tax" class="width50">
									<option value="0" <?php print empty( $settings['automatic_tax'] ) ? 'SELECTED' : ''; ?>>
										<?php esc_html_e( 'No', 'calculated-fields-form' ); ?>
									</option>
									<option value="1" <?php print ! empty( $settings['automatic_tax'] ) ? 'SELECTED' : ''; ?>>
										<?php esc_html_e( 'Yes', 'calculated-fields-form' ); ?>
									</option>
								</select>
							</td>
						</tr>

						<tr><td colspan="2"><hr></td></tr>

						<tr valign="top" class="cff-settings-hint-yellow">
							<th scope="row" style="padding:10px;">
								<?php esc_html_e( 'Payment frequency?', 'calculated-fields-form' ); ?>
							</th>
							<td style="padding:10px;">
								<select name="stripe_checkout_frequency" class="width50">
									<option value="" SELECTED>
										<?php esc_html_e( 'One time payment', 'calculated-fields-form' ); ?>
									</option>
								</select><br>
								<div style="margin-top: 5px;"><?php
									esc_html_e(
										'Recurring (subscription) payements are available in the commercial version of the plugin. ', 'calculated-fields-form'
									);
									print '[<a href="https://cff.dwbooster.com/download#comparison" target="_blank" style="font-weight:600;">' . esc_html__('Upgrade', 'calculated-fields-form') . '</a>]';
								?></div>
							</td>
						</tr>

						<tr><td colspan="2"><hr></td></tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'URL of product/store image', 'calculated-fields-form' ); ?>
							</th>
							<td>
								<input type="text" name="stripe_checkout_image" size="20" value="<?php echo esc_attr( $settings['stripe_image'] ); ?>" class="width75" />
								<p class="width75"><em style="font-size:11px;">* An absolute URL pointing to a square image of your brand or product. The recommended minimum size is 128x128px. The supported image types are: .gif, .jpeg, and .png.</em></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Metadata fields', 'calculated-fields-form' ); ?>
							</th>
							<td>
								<input type="text" name="stripe_checkout_metadata" size="80" value="<?php echo esc_attr( $settings['stripe_metadata'] ); ?>" class="width75" placeholder="fieldname3,fieldname#,fieldname#" />
								<p class="width75"><em style="font-size:11px;">* Comma separated, example: fieldname1, fieldname2, ...</em></p>
								<label style="display:block;margin-top:10px"><input type="checkbox" name="stripe_checkout_metadata_shortlabel" <?php echo ! empty( $settings['stripe_metadata_shortlabel'] ) ? 'CHECKED' : ''; ?>> <?php esc_html_e( "When possible, it sends the fields' short labels instead of their names", 'calculated-fields-form' ); ?></label>
							</td>
						</tr>
					</table>
					<div class="cff-goto-top">
						<a href="#cpformconf"><?php esc_html_e( 'Up to form structure', 'calculated-fields-form' ); ?></a>
					</div>
				</div>
			</div>
			<script data-category="functional" type="text/javascript">
				function cff_stripe_checkout_changemode(item) {
					if (item.options.selectedIndex == 0)
					{
						jQuery('.cff-stripe-checkout-production').hide();
						jQuery('.cff-stripe-checkout-test').show();
					}
					else
					{
						jQuery('.cff-stripe-checkout-production').show();
						jQuery('.cff-stripe-checkout-test').hide();
					}
				}
				jQuery(function() {
					var $ = jQuery;
					$(document).on('change', '[name="stripe_checkout_enabled"]', function(){
						$('#metabox_stripe_checkout_addon_form_settings')[this.value*1 ? 'addClass' : 'removeClass']('cff-payment-gateway-enabled');
					});
					<?php
						// For backward compatibility.
						if ( 2 == $enabled || 4 == $enabled ) {
							print "jQuery('[name=\"enable_pay_later\"]').val(1).trigger('change');";
						}
					?>
				});
			</script>
			<?php
		} // End get_addon_form_settings

		/************************ ADDON CODE *****************************/

		/************************ ATTRIBUTES *****************************/

		private $form_table = 'cp_calculated_fields_form_stripe_checkout';
		private $_cpcff_main;
		private $_form_settings_inserted_flag = false;

		/************************ CONSTRUCT *****************************/

		public function __construct() {
			$this->_cpcff_main = CPCFF_MAIN::instance();
			add_action('init', function(){
				$this->description = __( 'The add-on adds support for Stripe Checkout payments', 'calculated-fields-form' );
			});

			$this->set_actions();
		} // End __construct

		/************************ PRIVATE METHODS *****************************/

		/**
		 * Create the database tables
		 */
		protected function update_database() {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			$db_queries      = array();
			$db_queries[]    = 'CREATE TABLE ' . $wpdb->prefix . $this->form_table . ' (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					formid INT NOT NULL,
					enabled TINYINT DEFAULT 0 NOT NULL,
					settings TEXT,
					UNIQUE KEY id (id)
				)
				CHARACTER SET utf8
				COLLATE utf8_general_ci;';

			$this->_run_update_database( $db_queries );
		} // End update_database

		private function set_actions() {
			if ( ! $this->addon_is_active() ) {
				return;
			}

			add_action( 'cpcff_free_process_data_before_insert', array( $this, 'before_insert' ), 1, 3 );

			add_action( 'cpcff_free_process_data', array( $this, 'stripe_redirect' ), 11, 1 );

			add_action( 'init', array( $this, 'stripe_check_payment' ), 1 );

			add_filter( 'cpcff_the_form', array( $this, 'insert_payment_fields' ), 99, 2 );

			if ( is_admin() ) {
				// Delete forms
				add_action( 'cpcff_delete_form', array( $this, 'delete_form' ) );

				// Clone forms
				add_action( 'cpcff_clone_form', array( $this, 'clone_form' ), 10, 2 );

				// Export addon data
				add_action( 'cpcff_export_addons', array( $this, 'export_form' ), 10, 2 );

				// Import addon data
				add_action( 'cpcff_import_addons', array( $this, 'import_form' ), 10, 2 );

				// Show the form settings section
				add_action( 'cpcff_form_payment_gateways_settings', array($this, 'get_addon_form_settings') );
			}
		} // End set_actions

		/**
		 * Initialize the form settings
		 */
		private function initialize_settings( $settings = '' ) {
			$default_settings = array(
				'stripe_mode'          => 1,

				'stripe_key'           => '',
				'stripe_secretkey'     => '',
				'stripe_testkey'       => '',
				'stripe_testsecretkey' => '',

				'planname'             => '',
				'frequency'            => '',
				'times'                => 0,
				'times_field'          => '',
				'trialdays'            => 0,

				'stripe_subtitle'      => '',
				'stripe_image'         => '',
				'stripe_language'      => 'auto',

				'enable_option_yes'    => $this->default_pay_label,

				'askbilling'           => 0,
				'tax_id_collection'    => 0,
				'automatic_tax'        => 0,
				'stripe_metadata'      => '',
				'stripe_metadata_shortlabel' => 0,
			);

			if ( ! empty( $settings ) ) {
				$settings = json_decode( $settings, true );
				if ( is_array( $settings ) ) {
					$processed_settings = array_replace_recursive( $default_settings, $settings );
				}
			}

			return ! empty( $processed_settings ) ? $processed_settings : $default_settings;
		} // End initialize_settings

		/**
		 * Price formatting
		 */
		private function fix_price( $v, $c ) {
			$c = strtoupper( $c );

			if ( $this->is_zero_decimal_currency( $c ) ) {
				return ceil( $v );
			}

			if ( 'UGX' == $c ) {
				$v = ceil( $v );
			}

			return $v * 100;
		} // End fix_price

		/**
		 * Zero decimal currencies
		 */
		private function is_zero_decimal_currency( $c ) {
			$c = strtoupper( $c );
			return in_array( $c, array( 'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF' ) );
		} // End is_zero_decimal_currency

		/**
		 * Get metadata attributes to send to Stripe
		 */
		private function get_metadata( $fields, $params, $shortlabel = 0 ) {
			$metadata = array();
			if ( ! empty( $fields ) ) {
				$fields = explode(",",$fields);

				$form_fields = [];
				if ( $shortlabel && ! empty( $params["formid"] ) ) {
				   $form = new CPCFF_FORM($params["formid"]);
				   if ( is_object( $form ) ) {
					   $form_fields = $form->get_fields();
				   }
				}

				foreach ($fields as $item)
				{
					$key = trim($item);
					if ( $key && isset( $params[$key] ) ) {
						if ( ! empty( $form_fields[$key] ) ) {
							$properties = (array) $form_fields[$key];
							if ( ! empty( $properties['shortlabel'] ) ) {
								if ( isset( $metadata[ $properties['shortlabel'] ] ) ) {
									if ( ! is_array( $metadata[ $properties['shortlabel'] ] ) ) $metadata[ $properties['shortlabel'] ] = [ $metadata[ $properties['shortlabel'] ] ];

									$metadata[ $properties['shortlabel'] ][] = $params[$key];
								} else {
									$metadata[ $properties['shortlabel'] ] = $params[$key];
								}
								continue;
							}
						}

						$metadata[$key] = $params[$key];
					}
				}
			}
			return $metadata;
		} // End get_metadata

		/**
		 * Prevent replacing fields tags
		 */
		private function before_sanitize( $v ) {
			return str_replace( array( '<%', '%>' ), array( '&lt;%', '%&gt;' ), $v );
		} // End before_sanitize

		/**
		 * Prevent replacing fields tags
		 */
		private function after_sanitize( $v ) {
			return str_replace( array( '&lt;%', '%&gt;' ), array( '<%', '%>' ), $v );
		} // End before_sanitize

		/************************ PUBLIC METHODS  *****************************/
		public function activate() {
			parent::activate();
			$this->set_actions();
		} // End activate

		/**
		 * Inserts banks and Check if the Optional is enabled in the form, and inserts radiobutton
		 */
		public function insert_payment_fields( $form_code, $id ) {
			global $wpdb;

			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . $this->form_table . ' WHERE formid=%d', $id )
			);

			if ( empty( $row ) || ! $row->enabled || strpos( $form_code, 'vt="' . $this->addonID . '"' ) !== false ) {
				return $form_code;
			}

			$settings = $this->initialize_settings( $row->settings );

			// output radio-buttons here
			$form_code = preg_replace( '/<!--addons-payment-options-->/i', '<!--addons-payment-options--><div><label><input type="radio" name="bccf_payment_option_paypal" id="cffaddonidtp' . esc_attr( $id ) . '" vt="' . esc_attr( $this->addonID ) . '" value="' . esc_attr( $this->addonID ) . '" checked class="required"> ' . esc_html__( ( ! empty( $settings['enable_option_yes'] ) ? $settings['enable_option_yes'] : $this->default_pay_label ), 'calculated-fields-form' ) . '</label></div>', $form_code );

			if ( ( $this->_cpcff_main->get_form($id)->get_option('enable_pay_later',0) || 2 == $row->enabled || 4 == $row->enabled ) && ! strpos( $form_code, 'bccf_payment_option_paypal" vt="0' ) ) {
				$form_code = preg_replace( '/<!--addons-payment-options-->/i', '<!--addons-payment-options--><div><label><input type="radio" name="bccf_payment_option_paypal" vt="0" value="0" class="required"> ' . esc_html__( $this->_cpcff_main->get_form( $id )->get_option( 'enable_paypal_option_no', CP_CALCULATEDFIELDSF_PAYPAL_OPTION_NO ), 'calculated-fields-form' ) . '</label></div>', $form_code );
			}

			if ( substr_count( $form_code, 'name="bccf_payment_option_paypal"' ) > 1 ) {
				$form_code = str_replace( 'id="field-c0" style="display:none">', 'id="field-c0">', $form_code );
			}

			return $form_code;
		} // End insert_payment_fields

		public function before_insert( &$params, &$str, $fields ) {
			global $wpdb;

			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . $this->form_table . ' WHERE formid=%d', $params['formid'] )
			);

			$payment_option = ( isset( $_POST['bccf_payment_option_paypal'] ) ? sanitize_text_field( wp_unslash( $_POST['bccf_payment_option_paypal'] ) ) : $this->addonID );

			if (
				! empty( $row ) &&
				$row->enabled &&
				$payment_option == $this->addonID
			) {
				$params["payment_option"] = $this->name;
			}
		} // End before_insert

		/**
		 * Redirect to Stripe to proceed with the payment
		 */
		public function stripe_redirect( $params ) {
			global $wpdb;

			$row = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . $this->form_table . ' WHERE formid=%d', $params['formid'] )
			);

			$payment_option = ( isset( $_POST['bccf_payment_option_paypal'] ) ? sanitize_text_field( wp_unslash( $_POST['bccf_payment_option_paypal'] ) ) : $this->addonID );

			if (
				empty( $row ) ||
				! $row->enabled ||
				$payment_option != $this->addonID ||
				empty( $params['final_price'] ) ||
				! is_numeric( $params['final_price'] ) ||
				! ceil( $params['final_price'] * 100 )
			) {
				return;
			}

			$settings = $this->initialize_settings( $row->settings );

			$form_obj = CPCFF_SUBMISSIONS::get_form( $params['itemnumber'] );
			// Send mails before payment
			if ( $form_obj->get_option( 'paypal_notiemails', '0' ) == '1' ) {
				$this->_cpcff_main->send_mails( $params['itemnumber'] );
			}

			$stripe = new CPCFF_Stripe_HTTP( empty( $settings['stripe_mode'] ) ? $settings['stripe_testsecretkey'] : $settings['stripe_secretkey'] );

			$currency = $form_obj->get_option( 'currency', CP_CALCULATEDFIELDSF_DEFAULT_CURRENCY );
			$amount   = ceil( $this->fix_price( $params['final_price'], $currency ) );

			// Stripe common settings
			$cancel_url = isset( $_POST['cp_ref_page'] ) ? sanitize_text_field( wp_unslash( $_POST['cp_ref_page'] ) ) : CPCFF_AUXILIARY::site_url();
			$cancel_url = explode( '#', $cancel_url )[0];

			$stripe_arguments = array(
				'success_url'                => CPCFF_AUXILIARY::site_url() . '/?k=1&cff_formid=' . $params['formid'] . '&itemnumber=' . $params['itemnumber'] . '&cff_stripe_checkout_sessionid={CHECKOUT_SESSION_ID}',
				'cancel_url'                 => $cancel_url,
				'locale'                     => $settings['stripe_language'],
				'billing_address_collection' => empty( $settings['askbilling'] ) ? 'auto' : 'required',
				'line_items'                 => array(
					array(
						'quantity'   => 1,
						'price_data' => array(
							'currency'     => $currency,
							'unit_amount'  => $amount,
							'product_data' => array(),
						),
					),
				),
			);

			if ( ! empty( $settings['stripe_image'] ) ) {
				$stripe_arguments['line_items'][0]['price_data']['product_data']['images'] = array( $settings['stripe_image'] );
			}

			if ( ! empty( $settings['tax_id_collection'] ) ) {
				$stripe_arguments['tax_id_collection'] = array( 'enabled' => true );
			}

			if ( ! empty( $settings['automatic_tax'] ) ) {
				$stripe_arguments['automatic_tax']                               = array( 'enabled' => true );
				$stripe_arguments['line_items'][0]['price_data']['tax_behavior'] = 'exclusive';
			}

			$metadata                     = $this->get_metadata( $settings['stripe_metadata'], $params, $settings['stripe_metadata_shortlabel'] );
			$metadata['Submission ID']    = $params['itemnumber'];
			$stripe_arguments['metadata'] = $metadata;

			// ONETIME PAYMENT
			$stripe_arguments['mode'] = 'payment';
			$product_item_name        = $form_obj->get_option( 'paypal_product_name', CP_CALCULATEDFIELDSF_DEFAULT_PRODUCT_NAME );
			$stripe_arguments['payment_intent_data'] = ['metadata'=>$metadata];

			foreach ( $params as $item => $value ) {
				$product_item_name = str_replace(
					'<%' . $item . '%>',
					is_array( $value ) ? implode( ', ', $value ) : $value,
					$product_item_name
				);
			}

			if ( empty( $product_item_name ) ) {
				$product_item_name = $params['itemnumber'];
			}

			$product_item_name = sanitize_text_field( $product_item_name );

			/* $stripe_arguments['line_items'][0]['description'] = $product_item_name; */
			$stripe_arguments['line_items'][0]['price_data']['product_data']['name'] = $product_item_name;
			$stripe_arguments['client_reference_id']                                 = $product_item_name;

			$notifyto = explode( ',', $form_obj->get_option( 'cu_user_email_field', '' ) );
			if ( ! empty( $notifyto ) && ! empty( $params[ $notifyto[0] ] ) && is_email( $params[ $notifyto[0] ] ) ) {
				$stripe_arguments['customer_email'] = $params[ $notifyto[0] ];
			}

			try {
				$session = $stripe->post( 'checkout/sessions', $stripe_arguments );
				if ( is_wp_error( $session ) ) {
					echo wp_kses_post( $session->get_error_message() );
					exit;
				}
				set_transient(
					'cpcff_stripe_session_' . $session['id'],
					array(
						'itemnumber' => (int) $params['itemnumber'],
						'formid'     => (int) $params['formid'],
						'created'    => time(),
					),
					7 * DAY_IN_SECONDS
				);
			} catch ( Exception $e ) {
                        // API failures are handled inline with is_wp_error().
				echo wp_kses_post( $e->getMessage() );
				exit;
			}
			?>
<html><head><title>Redirecting to Stripe Checkout</title><body>
<script data-category="functional" src="https://js.stripe.com/v3"></script>
<script data-category="functional"> var stripe = Stripe('<?php echo esc_js( empty( $settings['stripe_mode'] ) ? $settings['stripe_testkey'] : $settings['stripe_key'] ); ?>'); stripe.redirectToCheckout({sessionId: '<?php echo esc_js( $session['id'] ); ?>'}).then(function (result) {alert(result.error.message);});</script>
</body>
</html>
			<?php
			exit;
		} // End stripe_redirect

		/**
		 * script process payment
		 */
		public function stripe_check_payment() {
			global $wpdb;

			if (
				! empty( $_REQUEST['cff_stripe_checkout_sessionid'] ) &&
				'' != ( $sessionid = sanitize_text_field( wp_unslash( $_REQUEST['cff_stripe_checkout_sessionid'] ) ) ) &&
				/*
				 * Client-supplied id reaching the API path. Charset allowlist rather than the
				 * documented id shape, so a Stripe prefix rename cannot silently break
				 * payments. \A and \z because PCRE lets $ match before a trailing newline.
				 */
				preg_match( '/\A[A-Za-z0-9_]+\z/', $sessionid ) &&
				! empty( $_REQUEST['cff_formid'] ) &&
				is_numeric( $_REQUEST['cff_formid'] ) &&
				! empty( $_REQUEST['itemnumber'] ) &&
				is_numeric( $_REQUEST['itemnumber'] )
			) {
				$formid     = intval( $_REQUEST['cff_formid'] );
				$itemnumber = intval( $_REQUEST['itemnumber'] );
				$row        = $wpdb->get_row(
					$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . $this->form_table . ' WHERE formid=%d', $formid )
				);

				if ( ! empty( $row ) ) {
					try {
						$settings = $this->initialize_settings( $row->settings );
						$stripe  = new CPCFF_Stripe_HTTP( empty( $settings['stripe_mode'] ) ? $settings['stripe_testsecretkey'] : $settings['stripe_secretkey'] );
						$session = $stripe->get( 'checkout/sessions/' . $sessionid );
						if ( is_wp_error( $session ) ) $this->verification_error( $session->get_error_message() );
						$binding = get_transient( 'cpcff_stripe_session_' . $sessionid );
						if (
							! is_array($binding)
							|| (int) $binding['itemnumber'] !== (int) $itemnumber
							|| (int) $binding['formid']     !== (int) $formid
						) {
							return;
						}
						$object = null;
						if ( ! empty( $session['payment_intent'] ) ) {
							$object = $stripe->get( 'payment_intents/' . $session['payment_intent'] );
						}

						if ( is_wp_error( $object ) ) $this->verification_error( $object->get_error_message() );

						if ( ! empty( $object ) && ! empty( $object['status'] ) && $object['status'] === 'succeeded' ) {
							$submission = CPCFF_SUBMISSIONS::get( $itemnumber );
							if ( empty( $submission ) ) {
								return;
							}
							$params               = $submission->paypal_post;
							$params['itemnumber'] = $itemnumber;

							// mark item as paid
							$args = array( 'paid' => 1 );
							if ( isset( $object['id'] ) ) $args['txn_id'] = $object['id'];

							CPCFF_SUBMISSIONS::update( $itemnumber,  $args );
							do_action( 'cpcff_payment_processed', $params );

							$form_obj = CPCFF_SUBMISSIONS::get_form( $itemnumber );
							if ( $form_obj->get_option( 'paypal_notiemails', '0' ) != '1' ) {
								$this->_cpcff_main->send_mails( $itemnumber );
							}

							$redirect = true;

							/**
							 * Filters applied to decide if the website should be redirected to the thank you page after submit the form,
							 * pass a boolean as parameter and returns a boolean
							 */
							$redirect = apply_filters( 'cpcff_redirect', $redirect );

							if ( $redirect ) {
								$location = CPCFF_AUXILIARY::replace_params_into_url( $form_obj->get_option( 'fp_return_page', CP_CALCULATEDFIELDSF_DEFAULT_fp_return_page ), $params );
								wp_redirect( $location, 302, 'CPCFF' );
								exit;
							}
						} else {
							print 'Error: Purchase cannot be verified. Please contact the seller.';
							exit;
						}
					} catch ( Exception $e ) {
                        // API failures are handled inline with is_wp_error().
						$this->verification_error( $e->getMessage() );
					}
				}
				print 'Error: Invalid data. Please contact the seller.';
				exit;
			}
		} // End stripe_check_payment

		/**
		 * Reports that the purchase could not be verified. It does not return.
		 *
		 * @param string $message Optional detail about the Stripe API failure.
		 */
		private function verification_error( $message = '' ) {
			print 'Error: Purchase cannot be verified. Please contact the seller.'
				. ( '' !== $message ? ' Error code: <strong>' . wp_kses_post( $message ) . '</strong>' : '' );
			exit;
		} // End verification_error

		/**
		 *  Delete the form from the addon's table
		 */
		public function delete_form( $formid ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . $this->form_table, array( 'formid' => $formid ), '%d' );
		} // delete_form

		/**
		 *  Clone the form's row
		 */
		public function clone_form( $original_form_id, $new_form_id ) {
			 global $wpdb;

			$form_row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . $this->form_table . ' WHERE formid=%d', $original_form_id ), ARRAY_A );

			if ( ! empty( $form_row ) ) {
				unset( $form_row['id'] );
				$form_row['formid'] = $new_form_id;
				$wpdb->insert( $wpdb->prefix . $this->form_table, $form_row );
			}
		} // End clone_form

		/**
		 *  It is called when the form is exported to export the addons data too.
		 *  Receive an array with the other addons data, and the form's id for filtering.
		 */
		public function export_form( $addons_array, $formid ) {
			global $wpdb;
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . $this->form_table . ' WHERE formid=%d', $formid ), ARRAY_A );

			if ( ! empty( $row ) ) {
				unset( $row['id'] );
				unset( $row['formid'] );
				$addons_array[ $this->addonID ] = $row;
			}
			return $addons_array;
		} // End export_form

		/**
		 *  It is called when the form is imported to import the addons data too.
		 *  Receive an array with all the addons data, and the new form's id.
		 */
		public function import_form( $addons_array, $formid ) {
			global $wpdb;
			if ( isset( $addons_array[ $this->addonID ] ) ) {
				$settings = $addons_array[ $this->addonID ]['settings'] ?? [];
				if ( is_string( $settings ) ) {
					if (! is_array(json_decode($settings, true))) {
						$settings = json_encode([]);
					}
				} elseif ( is_array( $settings ) ) {
					$settings = json_encode( $settings );
				}

				$data = [
					'formid'   => (int) $formid,
					'enabled'  => (!empty( $addons_array[ $this->addonID ]['enabled'] ) ? 1 : 0),
					'settings' => $settings
				];

				$wpdb->insert(
					$wpdb->prefix . $this->form_table,
					$data,
					['%d','%d','%s']
				);
			}
		} // End import_form

	} // End Class

	// Main add-on code
	$CPCFF_Stripe_Checkout_obj = new CPCFF_Stripe_Checkout();

	CPCFF_ADDONS::add( $CPCFF_Stripe_Checkout_obj );
	CPCFF_ADDONS::activate_addon( $CPCFF_Stripe_Checkout_obj->get_addon_id() ); // Activate by default.
}
