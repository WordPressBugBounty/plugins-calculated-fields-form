<?php
if( !defined('CP_CALCULATEDFIELDSF_BASE_PATH') )
{
	print 'Direct access not allowed.';
	exit;
}

if(!class_exists( 'CPCFF_PRICE_CHECK' ))
{
	/**
	 * Validation of the request cost against the list of fields declared in the form
	 * settings.
	 *
	 * The browser sends, for every declared field that is going to be submitted, the
	 * structure of the selection (option index and quantity) and the values it holds. The
	 * fields excluded by a dependency or by the form itself are not sent, and that is
	 * checked against the submitted fields in both pipelines. A declared field missing
	 * from either the payload or the submission stops the request.
	 *
	 * The value of every field is resolved by the server: from its own configuration when
	 * the options are static, from the values sent by the browser when they are not, and
	 * from the submission when there is one. The fields with static options are rebuilt
	 * with the resolution, so the stored data and the price cannot disagree.
	 *
	 * In the equation mode the price is not the one sent by the browser: it is the result
	 * of the equation saved in the settings, evaluated with the resolved values of the
	 * fields it references. The equation uses the same syntax as the equations of the form
	 * builder, restricted to what the server can evaluate, and it is parsed into an
	 * abstract syntax tree and evaluated by the server, never run as code.
	 */
	class CPCFF_PRICE_CHECK
	{
		/** Tolerance allowed for floating point arithmetic. */
		const TOLERANCE = 0.0000001;

		/** The value was resolved by the server from its own configuration. */
		const SOURCE_SERVER = 'server';

		/** The value was sent by the browser and could not be reproduced. */
		const SOURCE_BROWSER = 'browser';

		/** The error is reported with a full html page. */
		const OUTPUT_PAGE = 'page';

		/** The error is reported with a json response. */
		const OUTPUT_JSON = 'json';

		/** Field types with indexed options. */
		private static $choice_types = array( 'fcheck', 'fradio', 'fdropdown', 'fcheckds', 'fradiods', 'fdropdownds' );

		/** The form being processed. */
		private static $form_obj = null;

		/** The form fields, indexed by name. */
		private static $fields = array();

		/** The selection sent by the browser, indexed by field name. */
		private static $payload = array();

		/** The names of the fields declared for the verification. */
		private static $check_fields = array();

		/** Original price contributors, excluding fields enrolled only for constraints. */
		private static $sum_fields = array();

		/** How a failed validation is reported. */
		private static $error_output = self::OUTPUT_PAGE;

		/** The accumulated sum of the resolved values. */
		private static $sum = 0.0;

		/** The resolution of every field already validated, indexed by name. */
		private static $resolved = array();

		/** Whether the verification is enabled for the form. */
		private static $enabled = false;

		/** -1 = unknown mode, 0 = no mode, 1 = sum mode, 2 = equation mode. */
		private static $mode = -1;

		/** The parsed server side equation: its AST and the fields it references. */
		private static $equation = null;

		/** Numeric custom operands frozen for the current initialized calculation. */
		private static $equation_operands = array();

		/** The functions available in a server side equation, the same ones the client offers. */
		private static $equation_functions = array( 'ABS', 'ROUND', 'FLOOR', 'CEIL', 'PREC', 'MIN', 'MAX', 'SUM', 'AVERAGE', 'POW', 'SQRT', 'REMAINDER', 'IF' );

		/** The constants available in a server side equation. */
		private static $equation_constants = array( 'PI' => M_PI, 'E' => M_E );

		/** The operators of an equation, the longest first so that ** is not read as *. */
		private static $equation_operators = array( '**', '||', '&&', '==', '!=', '<=', '>=', '+', '-', '*', '/', '%', '<', '>', '!', '?', ':' );

		/** The limits of an equation: its length, its number of nodes and its depth. */
		private static $equation_limits = array( 'length' => 2000, 'nodes' => 300, 'depth' => 32 );

		/**
		 * Prepares the verification for the current request.
		 *
		 * @param object $form_obj      The form.
		 * @param array  $fields        The form fields, indexed by name.
		 * @param string $error_output  How a failed validation is reported.
		 */
		public static function init( $form_obj, $fields, $error_output = self::OUTPUT_PAGE )
		{
			self::$form_obj = $form_obj;
			self::$mode = self::get_mode( $form_obj );
			self::$fields = is_array( $fields ) ? $fields : array();
			self::$error_output = $error_output;
			self::$sum = 0.0;
			self::$resolved = array();
			self::$payload = array();
			self::$check_fields = array();
			self::$sum_fields = array();
			self::$equation = null;
			self::$equation_operands = array();
			self::$enabled = self::is_enabled( $form_obj );

			if ( ! self::$enabled ) return;

			if ( self::$mode === 2 ) {
				$equation = $form_obj->get_option( 'request_cost_equation', '' );
				if ( ! is_string( $equation ) || ! $form_obj->get_option( 'is_cost_equation_valid', false ) ) self::fail();
				self::$equation_operands = self::get_equation_operands( $form_obj );
				self::$equation = self::parse_equation( $equation, self::$equation_operands );
				if ( self::$equation === false ) self::fail();
				self::$check_fields = self::$equation['fields'];
			} else {
				self::$check_fields = self::price_fields( $form_obj );
			}
			self::$sum_fields = array_fill_keys( self::$check_fields, true );
			self::$check_fields = self::constraint_fields( self::$check_fields, self::$fields, self::get_request_cost() );
			if ( self::$check_fields === false ) self::fail();

			if ( self::$check_fields ) {
				$payload = self::get_payload();
				if ( $payload === false ) self::fail();
				self::$payload = $payload;
			}
		}

		/**
		 * Checks whether the verification is enabled for the form.
		 *
		 * @param object $form_obj
		 * @return bool
		 */
		public static function is_enabled( $form_obj )
		{
			$check_mode = self::get_mode( $form_obj );
			if ( $check_mode === 1 ) return ! empty( $form_obj->get_option( 'request_cost_sum', '' ) );
			if ( $check_mode !== 2 ) return false;
			$equation = $form_obj->get_option( 'request_cost_equation', '' );
			// Only an intentionally empty equation opts out. Invalid configuration must fail closed.
			return ! is_string( $equation ) || trim( $equation ) !== '';
		}

		/**
		 * Whether a field can take part in the check.
		 *
		 * The same criteria are applied when the check runs and when the equation is
		 * validated before saving it, so that the creator is never told that a field is
		 * valid when the server rejects it.
		 *
		 * @param string $name  The name of the field.
		 * @param object $field The field.
		 * @param string $cost  The name of the request cost field.
		 * @param array  $repeater_children The names of the children of the repeaters.
		 * @return bool
		 */
		private static function is_eligible_field( $name, $field, $cost, $repeater_children = array() )
		{
			if ( $name === $cost ) return false;
			if ( isset( $repeater_children[ strtolower( $name ) ] ) ) return false;
			$type = strtolower( (string) ( $field->ftype ?? '' ) );
			$types = array_merge( self::$choice_types, array('fnumber', 'fnumberds', 'fcurrency', 'fcurrencyds', 'fcalculated', 'fslider', 'ftext', 'ftextds', 'fhidden', 'fhiddends') );
			if ( ! in_array( $type, $types, true ) ) return false;
			if ( ! empty( $field->exclude ) ) return false;
			if ( $type === 'fslider' && ! empty( $field->range ) ) return false;
			return true;
		}

		/**
		 * The names of the fields that belong to a repeater.
		 *
		 * The children of a repeater are not available as checksum components: they are
		 * not submitted with their own name, but with the name of the clone.
		 *
		 * @param array $fields The fields of the form, indexed by name.
		 * @return array The names of the children, indexed by name.
		 */
		private static function repeater_children( $fields )
		{
			$children = array();
			if ( ! is_array( $fields ) ) return $children;
			foreach ( $fields as $field ) {
				if ( ! is_object( $field ) ) continue;
				if ( 'frepeater' !== strtolower( (string) ( $field->ftype ?? '' ) ) ) continue;
				if ( empty( $field->fields ) || ! is_array( $field->fields ) ) continue;
				foreach ( $field->fields as $child ) {
					if ( is_string( $child ) && $child !== '' ) $children[ strtolower( $child ) ] = true;
				}
			}
			return $children;
		}

		/**
		 * Validates the server side equation of a form.
		 *
		 * The equation is parsed, its references are resolved against the fields of the
		 * form with the criteria the server applies when it checks a submission, and it is
		 * checked for invalid constant arithmetic. Input-dependent errors are checked
			 * with the actual submitted values, not invented samples. Invalid equation text
			 * can be saved for correction in the editor, but rejects runtime submissions
			 * instead of silently accepting a browser-supplied price.
		 *
		 * @param string $equation The equation.
		 * @param array  $fields   The fields of the form, indexed by name.
		 * @param string $cost     The name of the request cost field.
		 * @param object|null $form_obj Form context for programmatic operands.
		 * @return bool Whether the equation can be used.
		 */
		public static function validate_equation( $equation, $fields = array(), $cost = '', $form_obj = null )
		{
			$parsed = self::parse_equation( $equation, self::get_equation_operands( $form_obj ) );
			if ( $parsed === false ) return false;

			if ( self::constraint_fields( $parsed['fields'], $fields, $cost ) === false ) return false;

			return self::equation_static_value( $parsed['ast'] ) !== false;
		}

		/** Validate constant arithmetic without inventing values for input-dependent terms.
		 * Returns null for an unknown field-dependent value, false for an error, or a float.
		 */
		private static function equation_static_value( $ast )
		{
			if ( $ast['type'] === 'field' || $ast['type'] === 'operand' ) return null;
			if ( $ast['type'] === 'num' ) return self::eval_equation( $ast, array() );
			if ( $ast['type'] === 'ternary' ) {
				$condition = self::equation_static_value( $ast['condition'] );
				if ( $condition === false ) return false;
				if ( $condition !== null ) return self::equation_static_value( $condition != 0 ? $ast['yes'] : $ast['no'] );
				return self::equation_static_value( $ast['yes'] ) === false || self::equation_static_value( $ast['no'] ) === false ? false : null;
			}
			$children = $ast['type'] === 'call' ? $ast['args'] : ( $ast['type'] === 'unary' ? array( $ast['arg'] ) : array( $ast['left'], $ast['right'] ) );
			$values = array();
			foreach ( $children as $child ) {
				$value = self::equation_static_value( $child );
				if ( $value === false ) return false;
				$values[] = $value;
				if ( $ast['type'] === 'binary' && count( $values ) === 1 && $value !== null ) {
					if ( $ast['op'] === '&&' && $value == 0 ) return 0.0;
					if ( $ast['op'] === '||' && $value != 0 ) return $value;
				}
			}
			if ( $ast['type'] === 'binary' && in_array( $ast['op'], array( '/', '%' ), true ) && $values[1] !== null && $values[1] == 0 ) return false;
			if ( $ast['type'] === 'call' ) {
				if ( $ast['name'] === 'REMAINDER' && $values[1] !== null && $values[1] == 0 ) return false;
				if ( $ast['name'] === 'PREC' && count( $values ) > 1 && $values[1] !== null ) {
					$precision = $values[1];
					if ( floor( $precision ) != $precision || $precision < 0 ) return false;
					$skip_rounding = $values[0] !== null && floor( $values[0] ) == $values[0] && count( $values ) > 2 && $values[2] !== null && $values[2] != 0;
					if ( $precision > 100 && ! $skip_rounding ) return false;
				}
			}
			if ( in_array( null, $values, true ) ) return null;
			// Replace known child expressions with numeric nodes, then reuse the runtime evaluator.
			$nodes = array_map( function( $value ) { return array( 'type' => 'num', 'value' => $value ); }, $values );
			if ( $ast['type'] === 'call' ) $ast['args'] = $nodes;
			elseif ( $ast['type'] === 'unary' ) $ast['arg'] = $nodes[0];
			else { $ast['left'] = $nodes[0]; $ast['right'] = $nodes[1]; }
			return self::eval_equation( $ast, array() );
		}

		/**
		 * Validates a field of the declared list and rebuilds its value.
		 *
		 * @param string $fieldname
		 * @param object $field
		 * @param mixed  $value  The value submitted for the field, null when there is no
		 *                        submission available (and therefore invalid).
		 * @return mixed The value that has to be stored.
		 */
		public static function validate_field( $fieldname, $field, $value )
		{
			if ( ! self::$enabled ) return $value;
			if( ! in_array( $fieldname, self::$check_fields, true ) ) return $value;
			if( isset( self::$resolved[ $fieldname ] ) ) {
				return self::$resolved[$fieldname]['stored'];
			}

			$resolution = self::resolve_field( $fieldname, $field, $value );
			if( $resolution === false ) self::fail();

			if ( isset( self::$sum_fields[ $fieldname ] ) ) self::$sum += $resolution['value'];
			self::$resolved[ $fieldname ] = $resolution;

			return $resolution['stored'];
		}

		/** Only the canonical successful control may supply a selected price field. */
		public static function validate_submission_key( $fieldname, $posted_name, $sequence )
		{
			if (!self::$enabled || !in_array($fieldname, self::$check_fields, true)) return;
			if (!is_string($sequence) || ($sequence !== '' && !preg_match('/\A_\d+\z/', $sequence)) || $posted_name !== $fieldname . $sequence) self::fail();
		}

		/**
		 * Validates every declared field at once.
		 *
		 * It is the mode used when there is no field by field processing, as in the
		 * ajax call that creates the payment. It receives the same selected successful
		 * controls as ordinary POST fields, separately from the parsed payload.
		 *
		 * @param array|null $submitted Unsuffixed values, or null to read the POST sequence.
		 */
		public static function validate_all( $submitted = null )
		{
			if ( ! self::$enabled ) return;
			if ( $submitted === null ) {
				$submitted = array();
				$sequence = isset($_POST['cp_calculatedfieldsf_pform_psequence']) && is_string($_POST['cp_calculatedfieldsf_pform_psequence']) ? wp_unslash($_POST['cp_calculatedfieldsf_pform_psequence']) : '';
				if ( $sequence !== '' && ! preg_match('/\A_\d+\z/', $sequence) ) self::fail();
				foreach ( self::$check_fields as $name ) {
					if ( array_key_exists($name . $sequence, $_POST) ) $submitted[$name] = wp_unslash($_POST[$name . $sequence]);
				}
			}
			if ( ! is_array( $submitted ) ) self::fail();

			foreach( self::$check_fields as $fieldname )
			{
				if( isset( self::$resolved[ $fieldname ] ) ) continue;
				if( ! array_key_exists( $fieldname, $submitted ) ) self::fail();
				$value = $submitted[$fieldname];
				if ( is_array($value) ) {
					foreach ($value as &$item) { if (!is_scalar($item)) self::fail(); $item = sanitize_text_field((string) $item); }
					unset($item);
				} elseif ( is_scalar($value) ) $value = sanitize_text_field((string) $value);
				self::validate_field( $fieldname, self::$fields[$fieldname], $value );
			}
			self::validate_pending();
		}

		/**
		 * Validates the declared fields that were not submitted.
		 *
		 * A declared field that is not in the payload was not submitted: the browser
		 * excluded it with a dependency or with the form itself. That invalidates the
		 * verification and the submission stops.
		 */
		public static function validate_pending()
		{
			if ( ! self::$enabled ) return;

			foreach( self::$check_fields as $fieldname )
			{
				if( ! isset( self::$resolved[ $fieldname ] ) ) self::fail();
			}
			// References can point forward in either form order or checksum order.
			foreach ( self::$resolved as $name => $resolution ) {
				if ( ! self::check_constraints($name, self::$fields[$name], $resolution) ) self::fail();
			}
		}

		/**
		 * Checks that the price covers the sum of the resolved values.
		 *
		 * @param mixed $price The price submitted by the browser.
		 */
		public static function check_price( $price )
		{
			if ( ! self::$enabled ) return $price;
			self::validate_pending();

			if ( self::$mode === 2 ) {
				$values = array();
				foreach ( self::$resolved as $name => $resolution ) $values[ $name ] = $resolution['value'];
				$calculated = self::eval_equation( self::$equation['ast'], $values );
				if ( $calculated === false || ! is_finite( $calculated ) ) self::fail();
				return $calculated;
			}

			if ( ! is_finite( self::$sum ) || ! is_numeric( $price ) || ! is_finite( (float) $price ) ) self::fail();

			if ( self::$sum > (float) $price + self::TOLERANCE ) self::fail();

			return $price;
		}

		/**
		 * Describes how every declared field was verified.
		 *
		 * @return array Map of fieldname => array( 'value', 'source', 'constrained' ).
		 */
		public static function report()
		{
			$report = array();

			foreach( self::$resolved as $fieldname => $item )
			{
				$report[ $fieldname ] = array(
					'value'       => $item['value'],
					'source'      => $item['source'],
					'constrained' => self::has_constraints( self::$fields[ $fieldname ] ),
				);
			}

			return $report;
		}

		/**
		 * Returns the message displayed when the submitted price cannot be validated.
		 *
		 * It is a method and not a constant because the message has to be translated
		 * with the locale in use at the moment it is displayed.
		 *
		 * @return string
		 */
		public static function error_message()
		{
			return esc_html__( 'The submitted price could not be validated.', 'calculated-fields-form' );
		}

		/**
		 * The mode of the check of a form.
		 *
		 * It is not cached: the same request can render more than one form and each one
		 * has its own mode. The mode of the form being processed is kept in self::$mode.
		 *
		 * @param object $form_obj
		 * @return int
		 */
		private static function get_mode( $form_obj )
		{
			return (int) $form_obj->get_option( 'check_request_cost', 0 );
		}
		/**
		 * Reports the failure and stops the request.
		 */
		private static function fail()
		{
			$message = self::error_message();

			if ( self::OUTPUT_JSON === self::$error_output )
			{
				echo json_encode( array( 'error' => $message ) );
				exit;
			}

			if ( ! headers_sent() ) {
				status_header( 400 );
				header( 'Content-Type: text/html; charset=utf-8' );
			}
			echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $message . '</title><style>body {margin: 0;padding: 40px 20px;background: #f7f8fc;color: #242936;font-family: sans-serif;text-align: center;}.cff-error-message {max-width: 560px;margin: 0 auto 24px;padding: 24px;border: 1px solid #e4e7ec;border-radius: 12px;background: #fff;box-shadow: 0 8px 24px rgba(0, 0, 0, .06);font-size: 18px;}.cff-error-back {display: inline-block;padding: 11px 18px;border-radius: 8px;background: #b42318;color: #fff;font-weight: 600;text-decoration: none;}</style></head><body><div class="cff-error-message">' . $message . '</div><a class="cff-error-back" href="#" onclick="history.back(); return false;">' . esc_html__( 'Back to the form', 'calculated-fields-form' ) . '</a></div></body></html>';
			exit;
		}

		/**
		 * Resolves the value of a declared field.
		 *
		 * @param string $fieldname
		 * @param object $field
		 * @param mixed  $value  The value submitted for the field, null when there is no
		 *                        submission available.
		 * @return array|false The resolution, or false when the submission was tampered.
		 */
		private static function resolve_field( $fieldname, $field, $value = null )
		{
			$type = property_exists( $field, 'ftype' ) ? strtolower( (string) $field->ftype ) : '';
			if ( $value === null || ! isset( self::$payload[$fieldname] ) || ! is_array(self::$payload[$fieldname]) ) return false;
			$selection = self::$payload[ $fieldname ];
			foreach (array('opts', 'qty', 'vals') as $key) {
				if ( ! isset($selection[$key]) || ! is_array($selection[$key]) ) return false;
			}
			$opts = $selection['opts']; $qty = $selection['qty']; $vals = $selection['vals'];
			$is_choice = in_array( $type, self::$choice_types, true );
			$multiple = in_array($type, array('fcheck', 'fcheckds'), true) || !empty($field->multiple);
			// Reject impossible shapes before walking attacker-controlled arrays.
			if ($is_choice) {
				if (count($opts) !== count($vals) || (!$multiple && count($opts) > 1) || count($qty) > count($opts)) return false;
			} elseif ($opts || $qty || count($vals) !== 1 || !is_scalar($value)) return false;
			if (!self::is_list($opts) || !self::is_list($vals)) return false;
			foreach ($vals as $parsed) {
				// An optional empty successful scalar control is present, and SUM
				// treats its empty parsed representation as zero.
				if (self::parsed_value($parsed) === false && !(!$is_choice && $value === '' && $parsed === '')) return false;
			}
			// DS constructors may retain the ordinary field's example choices.
			$static_options = in_array($type, array('fcheck', 'fradio', 'fdropdown'), true);
			$sum = 0.0;
			$quantities = array();
			$source = self::SOURCE_BROWSER;
			$stored = $value;
			if( $is_choice )
			{
				$seen = array();
				foreach ($opts as $index) {
					if ( ! is_int($index) || $index < 0 || isset($seen[$index]) ) return false;
					$seen[$index] = true;
				}
				$has_quantity = in_array($type, array('fcheck', 'fcheckds'), true) && ! empty($field->quantity);
				foreach ($qty as $index => $quantity) {
					if ( ! $has_quantity || ! isset($seen[$index]) || self::parsed_value($quantity) === false || $quantity < 1 ) return false;
				}
				$posted = is_array($value) ? array_values($value) : ($value === '' ? array() : array($value));
				$use_post = ! $static_options && ($field->toSubmit ?? 'value') !== 'text';
				if (!$static_options) {
					if ($multiple !== is_array($value) || count($posted) !== count($opts) || (is_array($value) && !self::is_list($value))) return false;
					foreach ($posted as $item) { if (!is_scalar($item) || is_bool($item)) return false; }
				}
				$choices_val = self::choice_values($field);
				foreach( $opts as $i => $option_index )
				{
					$multiplier = isset($qty[$option_index]) ? (float) $qty[$option_index] : 1.0;
					$quantities[ $option_index ] = $multiplier;
					$raw = !$static_options ? $posted[$i] : null;
					if (!$static_options && $has_quantity) {
						if (!preg_match('/ \(([^()]*)\)\z/', (string) $raw, $quantity_match)) return false;
						if (self::parsed_value($quantity_match[1]) !== $multiplier) return false;
						$raw = substr((string) $raw, 0, -strlen($quantity_match[0]));
					}
					if( $static_options )
					{
						if( ! isset( $choices_val[ $option_index ] ) || ! empty($field->optgroup[$option_index]) ) return false;
						$option_value = self::choice_value( $field, $choices_val[$option_index] );
						$source = self::SOURCE_SERVER;
					}
					elseif ( $use_post ) {
						$option_value = self::choice_value($field, $raw);
					}
					else
					{
						$option_value = self::parsed_value($vals[$i]);
					}
					if ( $option_value === false ) return false;
					$sum += $option_value * $multiplier;
				}
				if( $static_options ) $stored = self::rebuild_value( $field, $type, $opts, $qty );
			}
			else
			{
				$configured = $type === 'fhidden' ? self::parsed_value( $field->predefined ?? null ) : false;
				if ( $configured !== false ) {
					$sum = $configured;
					$stored = (string) $field->predefined;
					$source = self::SOURCE_SERVER;
				} else {
					$sum = self::numeric_value( $field, $value );
				}
			}
			if ( $sum === false || ! is_finite($sum) || $stored === null ) return false;
			if ( ! empty($field->required) && ($stored === '' || $stored === array()) ) return false;
			return array(
				'value'      => $sum,
				'source'     => $source,
				'quantities' => $quantities,
				'stored'     => $stored,
			);
		}

		/**
		 * Checks a resolved value against the restrictions of its field.
		 *
		 * @param string $fieldname
		 * @param object $field
		 * @param array  $resolution
		 * @return bool
		 */
		private static function check_constraints( $fieldname, $field, $resolution )
		{
			$min = self::bound_value( $field, 'min' );
			$max = self::bound_value( $field, 'max' );
			if ( $min === false || $max === false ) return false;
			$value = $resolution['value'];
			if (in_array(strtolower((string) $field->ftype), array('fcheck', 'fcheckds'), true)) {
				$value = count($resolution['quantities']);
				if ($min === -1.0) $min = null;
				if ($max === -1.0) $max = null;
			} elseif ( ($field->dformat ?? '') === 'percent' ) $value *= 100;

			if( $min !== null && $value < $min ) return false;
			if( $max !== null && $value > $max ) return false;

			$min_quantity = self::quantity_bound( $field, 'minQuantity' );
			$max_quantity = self::quantity_bound( $field, 'maxQuantity' );

			foreach( $resolution['quantities'] as $quantity )
			{
				if( $min_quantity !== null && $quantity < $min_quantity ) return false;
				if( $max_quantity !== null && $quantity > $max_quantity ) return false;
			}

			return true;
		}

		/**
		 * Rebuilds the value of a choice field from the selection and the field own
		 * configuration, in the same format the browser posts it.
		 *
		 * @param object $field
		 * @param string $type
		 * @param array  $opts
		 * @param array  $qty
		 * @return array|string|null
		 */
		private static function rebuild_value( $field, $type, $opts, $qty )
		{
			$choices     = ( property_exists( $field, 'choices' ) && is_array( $field->choices ) ) ? $field->choices : array();
			$choices_val = self::choice_values($field);
			$to_submit   = ( property_exists( $field, 'toSubmit' ) && $field->toSubmit == 'text' ) ? 'text' : 'value';
			$has_quantity = ( property_exists( $field, 'quantity' ) && $field->quantity );

			$values = array();

			foreach( $opts as $option_index )
			{
				if( ! isset( $choices_val[ $option_index ] ) ) return null;

				$text = ( $to_submit == 'text' && isset( $choices[ $option_index ] ) ) ? $choices[ $option_index ] : $choices_val[ $option_index ];

				if( $has_quantity )
				{
					$quantity = isset( $qty[ $option_index ] ) ? (float) $qty[ $option_index ] : 1;
					$text .= ' (' . $quantity . ')';
				}

				$values[] = $text;
			}

			if( 'fcheck' === $type || 'fcheckds' === $type ) return $values;

			if( property_exists( $field, 'multiple' ) && $field->multiple ) return $values;

			return count( $values ) > 0 ? $values[0] : '';
		}

		/**
		 * Reads the name of the field that acts as the request cost.
		 *
		 * @return string
		 */
		private static function get_request_cost()
		{
			if ( ! is_object( self::$form_obj ) ) return '';

			return (string) self::$form_obj->get_option( 'request_cost', CP_CALCULATEDFIELDSF_DEFAULT_COST );
		}

		/**
		 * Reads all fields required for verification, including min/max dependencies.
		 * Discovery stays local and never changes an initialized calculation.
		 *
		 * @param object $form_obj
		 * @return array
		 */
		public static function get_fields( $form_obj )
		{
			$names = self::price_fields( $form_obj );
			// Legacy callers may supply only get_option(); init still checks its full field map.
			if ( ! is_callable( array( $form_obj, 'get_fields' ) ) ) return $names;
			$fields = self::constraint_fields( $names, $form_obj->get_fields(), (string) $form_obj->get_option( 'request_cost', CP_CALCULATEDFIELDSF_DEFAULT_COST ) );
			return $fields === false ? array() : $fields;
		}

		/** Price roots only; constraint dependencies must not become sum contributors. */
		private static function price_fields( $form_obj )
		{
			$mode = self::get_mode( $form_obj );
			if ( $mode === 2 ) {
				$equation = self::parse_equation( (string) $form_obj->get_option( 'request_cost_equation', '' ), self::get_equation_operands( $form_obj ) );
				return $equation === false ? array() : $equation['fields'];
			}
			if ( $mode !== 1 ) return array();
			$names = array_map('trim', explode(',', (string) $form_obj->get_option('request_cost_sum', '')));
			foreach ($names as $name) { if (!preg_match('/\Afieldname\d+\z/', $name)) return array(); }
			return array_values(array_unique($names));
		}

		/** Iterative closure: each declared field is visited once, even for cyclic bounds. */
		private static function constraint_fields( $names, $fields, $cost )
		{
			if ( ! is_array( $fields ) ) return false;
			$pending = array_values( array_unique( $names ) );
			$seen = array_fill_keys( $pending, true );
			$children = self::repeater_children( $fields );
			for ( $i = 0; $i < count( $pending ); $i++ ) {
				$name = $pending[ $i ];
				if ( ! isset( $fields[ $name ] ) || ! self::is_eligible_field( $name, $fields[ $name ], $cost, $children ) ) return false;
				foreach ( array( 'min', 'max' ) as $property ) {
					$bound = self::bound_definition( $fields[ $name ], $property );
					if ( $bound === false ) return false;
					if ( is_string( $bound ) && ! isset( $seen[ $bound ] ) ) {
						$seen[ $bound ] = true;
						$pending[] = $bound;
					}
				}
			}
			return $pending;
		}

		/**
		 * Reads the payload sent by the browser.
		 *
		 * The payload is mandatory: a missing or corrupted payload means the browser did
		 * not send the selection structure, and the verification cannot be performed.
		 *
		 * @return array|false Map of fieldname => array( 'opts' => array, 'qty' => array, 'vals' => array ),
		 *                   or false when the payload is missing or invalid.
		 */
		private static function get_payload()
		{
			if( empty( $_POST['cff_sum_values'] ) || ! is_string( $_POST['cff_sum_values'] ) ) return false;
			$max_bytes = apply_filters('cpcff_price_sum_payload_max_bytes', 1024 * 1024, self::$form_obj);
			if (!is_int($max_bytes) || $max_bytes < 1) return false;
			// WordPress slashing can at most double the submitted string size.
			if (strlen($_POST['cff_sum_values']) > $max_bytes * 2) return false;
			$json = wp_unslash($_POST['cff_sum_values']);
			if (strlen($json) > $max_bytes) return false;
			$payload = json_decode( $json, true );

			if( json_last_error() !== JSON_ERROR_NONE || ! is_array( $payload ) ) return false;

			if( ! isset( $payload['v'], $payload['f'] ) || $payload['v'] !== 1 || ! is_array( $payload['f'] ) ) return false;

			return $payload['f'];
		}

		/** Dense JSON lists, without allocating array_keys() and range() copies. */
		private static function is_list( $values )
		{
			$next = 0;
			foreach ($values as $key => $unused) { if ($key !== $next++) return false; }
			return true;
		}

		/**
		 * Reads a value the browser already parsed.
		 *
		 * The values travel in the payload already parsed, so they must not be parsed
		 * again with the display format of the field: the separators of a field are for
		 * the display, and applying them to a number that is already parsed changes it.
		 *
		 * @param mixed $raw
		 * @return float|false
		 */
		private static function parsed_value( $raw )
		{
			if ( ! is_int($raw) && ! is_float($raw) && ! (is_string($raw) && is_numeric(trim($raw))) ) return false;
			$value = (float) $raw;
			return is_finite($value) ? $value : false;
		}

		/**
		 * Parses a value stored in the configuration of a field.
		 *
		 * It reproduces the way the browser parses the values of the options, that do not
		 * depend on the display format of the field.
		 *
		 * @param mixed $raw
		 * @return float
		 */
		private static function choice_value( $field, $raw )
		{
			$type = strtolower((string) $field->ftype);
			if ( in_array($type, array('fcheck', 'fcheckds'), true) && ! empty($field->merge) ) return self::browser_number($raw);
			return self::parsed_value($raw);
		}

		private static function choice_values( $field )
		{
			if (!empty($field->choicesVal) && is_array($field->choicesVal)) return $field->choicesVal;
			return isset($field->choices) && is_array($field->choices) ? $field->choices : array();
		}

		/** Equivalent to parseVal's separator heuristic and signed/exponent extraction. */
		private static function browser_number( $raw, $grouping = ',', $decimal = '.' )
		{
			if ( ! is_scalar($raw) || is_bool($raw) ) return false;
			$raw = trim((string) $raw);
			if ($raw === '') return 0.0;
			if (preg_match('/(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{4})|(\d{4}[\/\.\-]\d{1,2}[\/\.\-]\d{1,2})/', $raw)) return false;
			$heuristic_separator = $grouping !== '' ? $grouping : ',';
			if (substr_count($raw, $heuristic_separator) === 1) {
				$raw = preg_replace('/' . preg_quote($heuristic_separator, '/') . '(\d{1,2})$/', $decimal . '$1', $raw);
			}
			if ($grouping !== '') $raw = str_replace($grouping, '', $raw);
			if ($decimal !== '') $raw = str_replace($decimal, '.', $raw);
			$raw = preg_replace('/\s/u', '', $raw);
			if (!preg_match('/[+\-]?((\d+(\.\d+)?)|(\.\d+))(?:[eE][+\-]?\d+)?/', $raw, $match)) return false;
			return self::parsed_value($match[0]);
		}

		/**
		 * Converts the value of a field to a number using the field own configuration.
		 * Reproduces the strip the plugin already applies to the request cost field.
		 *
		 * @param object $field
		 * @param mixed  $raw
		 * @return float
		 */
		private static function numeric_value( $field, $raw )
		{
			if( ! is_scalar( $raw ) || is_bool($raw) ) return false;
			$raw = trim( sanitize_text_field( (string) $raw ) );
			if( $raw === '' ) return 0.0;
			$type = strtolower((string) $field->ftype);
			if (in_array($type, array('ftext', 'ftextds', 'fhidden', 'fhiddends'), true)) return self::parsed_value($raw);
			if (($field->dformat ?? '') === 'digits' && preg_match('/[^\d]/', $raw)) return false;
			$currency = in_array($type, array('fcurrency', 'fcurrencyds'), true);
			$prefix = $currency ? ($field->currencySymbol ?? '') : ($field->prefix ?? '');
			$suffix = $currency ? ($field->currencyText ?? '') : ($field->postfix ?? $field->suffix ?? '');
			$grouping = (string) ($field->groupingsymbol ?? $field->thousandSeparator ?? ',');
			$decimal = (string) ($field->decimalsymbol ?? $field->decimalSymbol ?? $field->centSeparator ?? '.');
			$is_number = in_array($type, array('fnumber', 'fnumberds'), true);
			$format = ($currency || $is_number) && (!empty($field->formatDynamically) || ($field->dformat ?? '') === 'percent');
			if (!$format) {
				foreach (array($prefix, $suffix) as $affix) { if ($affix !== '') $raw = str_replace($affix, '', $raw); }
			}
			$value = self::browser_number($raw, $grouping, $decimal);
			if ($value !== false && $format && !empty($field->twoDecimals)) $value = self::round_browser_cents($value);
			if ($value !== false && $currency && $format && !empty($field->noCents)) $value = $value < 0 ? ceil($value) : floor($value);
			if ($value !== false && ($field->dformat ?? '') === 'percent') $value /= 100;
			return $value;
		}

		/** Number.toFixed(2) rounds the exact binary value, unlike PHP round(). */
		private static function round_browser_cents( $value )
		{
			$absolute = abs($value);
			if ($absolute === 0.0 || $absolute >= 1.0e21) return $value;
			// Twenty fractional places distinguish adjacent doubles at the smallest
			// half-cent boundary, without 64-bit-only integer operations.
			list($whole, $fraction) = explode('.', sprintf('%.20F', $absolute));
			$cents = (int) substr($fraction, 0, 2) + ($fraction[2] >= '5' ? 1 : 0);
			if ($cents === 100) { $whole = sprintf('%.0F', (float) $whole + 1); $cents = 0; }
			$rounded = (float) ($whole . '.' . sprintf('%02d', $cents));
			return $value < 0 ? -$rounded : $rounded;
		}

		/**
		 * Reads a bound definition without treating an invalid restriction as absent.
		 *
		 * @param object $field
		 * @param string $property
		 * @return float|string|null|false Numeric bound, reference, absent, or invalid.
		 */
		private static function bound_definition( $field, $property )
		{
			if( ! property_exists( $field, $property ) ) return null;

			$bound = $field->$property;
			if ( $bound === null ) return null;

			if( is_int( $bound ) || is_float( $bound ) ) return is_finite( (float) $bound ) ? (float) $bound : false;

			if( ! is_scalar( $bound ) ) return false;

			$bound = trim( (string) $bound );
			if( $bound === '' ) return null;

			if( is_numeric( $bound ) ) return is_finite( (float) $bound ) ? (float) $bound : false;
			return preg_match( '/\Afieldname\d+\z/i', $bound ) ? strtolower( $bound ) : false;
		}

		/** Resolve a configured bound; false means unverifiable, never an omitted limit. */
		private static function bound_value( $field, $property )
		{
			$bound = self::bound_definition( $field, $property );
			if ( ! is_string( $bound ) ) return $bound;
			return isset( self::$resolved[ $bound ] ) ? (float) self::$resolved[ $bound ]['value'] : false;
		}

		/**
		 * Reads a quantity restriction of a field.
		 *
		 * @param object $field
		 * @param string $property
		 * @return int|null
		 */
		private static function quantity_bound( $field, $property )
		{
			if( ! property_exists( $field, $property ) ) return null;

			$bound = $field->$property;

			if( ! is_numeric( $bound ) ) return null;

			$bound = (float) $bound;

			return ( $bound >= 1 ) ? $bound : null;
		}

		/**
		 * Checks whether a field has any restriction that can be verified.
		 *
		 * @param object $field
		 * @return bool
		 */
		private static function has_constraints( $field )
		{
			$checkbox = in_array(strtolower((string) $field->ftype), array('fcheck', 'fcheckds'), true);
			foreach (array('min', 'max') as $name) {
				$bound = self::bound_value($field, $name);
				if ($bound !== null && !($checkbox && $bound === -1.0)) return true;
			}
			if( self::quantity_bound( $field, 'minQuantity' ) !== null ) return true;
			if( self::quantity_bound( $field, 'maxQuantity' ) !== null ) return true;

			return false;
		}

		/**
		 * Parses a server side equation into an abstract syntax tree.
		 *
		 * The grammar is the one of the equations of the form builder, restricted to what
		 * the server can evaluate: numbers, the names of the fields of the form, the
		 * arithmetic, logical and comparison operators, and a short list of functions. The
		 * equation is never evaluated as code: the value of a field enters the tree as
		 * data and never as code, and the limits of the equation are checked while it is
		 * parsed.
		 *
		 * @param string $equation
		 * @return array|false array( 'ast' => array, 'fields' => array ), or false when the
		 *                   equation does not parse.
		 */
		private static function parse_equation( $equation, $operands = array() )
		{
			if ( ! is_string( $equation ) ) return false;
			$text = $equation;
			if ( strlen( $text ) > self::$equation_limits['length'] ) return false;
			$position = 0;
			$state = array( 'fields' => array(), 'nodes' => 0, 'depth' => 0, 'operands' => $operands );
			$ast = self::parse_ternary( $text, $position, $state );
			if ( $ast === false ) return false;
			self::skip_spaces( $text, $position );
			if ( $position !== strlen( $text ) ) return false;
			return array( 'ast' => $ast, 'fields' => array_keys( $state['fields'] ) );
		}

		/** Build a local registry; never invoke a name or a value supplied by the map. */
		private static function get_equation_operands( $form_obj )
		{
			try {
				$registered = apply_filters( 'cpcff_price_check_equation_operands', array(), $form_obj );
			} catch ( \Throwable $error ) {
				return array(); // Referenced operands will be unknown and fail closed.
			}
			if ( ! is_array( $registered ) ) return array();
			$keywords = '__halt_compiler abstract and array as break callable case catch class clone const continue '
				. 'declare default die do echo else elseif empty enddeclare endfor endforeach endif endswitch endwhile '
				. 'enum eval exit extends final finally fn for foreach from function global goto if implements include '
				. 'include_once instanceof insteadof interface isset list match namespace new or parent print private '
				. 'protected public readonly require require_once return self static switch throw trait try unset use '
				. 'var while xor yield true false null int float string bool void never mixed object iterable resource '
				. '__class__ __dir__ __file__ __function__ __line__ __method__ __namespace__ __trait__ __property__';
			$reserved = array_fill_keys( explode( ' ', $keywords ), true );
			foreach ( array_keys( get_defined_constants() ) as $name ) $reserved[ strtolower( $name ) ] = true;
			foreach ( self::$equation_functions as $name ) $reserved[ strtolower( $name ) ] = true;
			foreach ( array_keys( self::$equation_constants ) as $name ) $reserved[ strtolower( $name ) ] = true;
			$operands = array();
			$seen = array();
			foreach ( $registered as $name => $value ) {
				if ( ! is_string( $name ) || ! preg_match( '/\A[A-Za-z_][A-Za-z0-9_]{0,63}\z/', $name ) ) continue;
				$key = strtolower( $name );
				if ( isset( $seen[ $key ] ) ) { unset( $operands[ $key ] ); continue; }
				$seen[ $key ] = true;
				if ( isset( $reserved[ $key ] ) || function_exists( $name ) || strpos( $key, 'fieldname' ) === 0 ) continue;
				if ( ( ! is_int( $value ) && ! is_float( $value ) ) || ! is_finite( (float) $value ) ) continue;
				$operands[ $key ] = (float) $value;
			}
			return $operands;
		}

		/** Moves the position over the spaces of the equation. */
		private static function skip_spaces( $text, &$position )
		{
			$length = strlen( $text );
			while ( $position < $length && strpos( " \t\r\n", $text[ $position ] ) !== false ) $position++;
		}

		/**
		 * The operator at the position, or an empty string. The spaces are skipped.
		 */
		private static function peek_operator( $text, &$position )
		{
			self::skip_spaces( $text, $position );
			foreach ( self::$equation_operators as $operator ) {
				if ( substr( $text, $position, strlen( $operator ) ) === $operator ) return $operator;
			}
			return '';
		}

		/**
		 * Adds a node to the tree, checking the limit of nodes.
		 */
		private static function make_node( &$state, $node )
		{
			$state['nodes']++;
			if ( $state['nodes'] > self::$equation_limits['nodes'] ) return false;
			return $node;
		}

		/** Bound every recursive nesting edge, not the non-recursive precedence layers. */
		private static function parse_nested( $text, &$position, &$state, $rule )
		{
			if ( $state['depth'] >= self::$equation_limits['depth'] ) return false;
			$state['depth']++;
			switch ( $rule ) {
				case 'power': $node = self::parse_power( $text, $position, $state ); break;
				case 'unary': $node = self::parse_unary( $text, $position, $state ); break;
				default: $node = self::parse_ternary( $text, $position, $state ); break;
			}
			$state['depth']--;
			return $node;
		}

		/** expression := ternary */
		private static function parse_ternary( $text, &$position, &$state )
		{
			$condition = self::parse_or( $text, $position, $state );
			if ( $condition === false ) return false;
			if ( self::peek_operator( $text, $position ) !== '?' ) return $condition;
			$position++;
			$if_true = self::parse_nested( $text, $position, $state, 'ternary' );
			if ( $if_true === false ) return false;
			if ( self::peek_operator( $text, $position ) !== ':' ) return false;
			$position++;
			$if_false = self::parse_nested( $text, $position, $state, 'ternary' );
			if ( $if_false === false ) return false;
			return self::make_node( $state, array( 'type' => 'ternary', 'condition' => $condition, 'yes' => $if_true, 'no' => $if_false ) );
		}

		/** or := and ( '||' and )* */
		private static function parse_or( $text, &$position, &$state )
		{
			$left = self::parse_and( $text, $position, $state );
			if ( $left === false ) return false;
			while ( self::peek_operator( $text, $position ) === '||' ) {
				$position += 2;
				$right = self::parse_and( $text, $position, $state );
				if ( $right === false ) return false;
				$left = self::make_node( $state, array( 'type' => 'binary', 'op' => '||', 'left' => $left, 'right' => $right ) );
			}
			return $left;
		}

		/** and := cmp ( '&&' cmp )* */
		private static function parse_and( $text, &$position, &$state )
		{
			$left = self::parse_cmp( $text, $position, $state );
			if ( $left === false ) return false;
			while ( self::peek_operator( $text, $position ) === '&&' ) {
				$position += 2;
				$right = self::parse_cmp( $text, $position, $state );
				if ( $right === false ) return false;
				$left = self::make_node( $state, array( 'type' => 'binary', 'op' => '&&', 'left' => $left, 'right' => $right ) );
			}
			return $left;
		}

		/** Equality has lower precedence than relational comparisons, as in JavaScript. */
		private static function parse_cmp( $text, &$position, &$state )
		{
			$left = self::parse_relational( $text, $position, $state );
			if ( $left === false ) return false;
			while ( in_array( self::peek_operator( $text, $position ), array( '==', '!=' ), true ) ) {
				$operator = self::peek_operator( $text, $position );
				$position += 2;
				$right = self::parse_relational( $text, $position, $state );
				if ( $right === false ) return false;
				$left = self::make_node( $state, array( 'type' => 'binary', 'op' => $operator, 'left' => $left, 'right' => $right ) );
			}
			return $left;
		}

		private static function parse_relational( $text, &$position, &$state )
		{
			$left = self::parse_sum( $text, $position, $state );
			if ( $left === false ) return false;
			while ( true ) {
				$operator = self::peek_operator( $text, $position );
				if ( ! in_array( $operator, array( '<', '<=', '>', '>=' ), true ) ) break;
				$position += strlen( $operator );
				$right = self::parse_sum( $text, $position, $state );
				if ( $right === false ) return false;
				$left = self::make_node( $state, array( 'type' => 'binary', 'op' => $operator, 'left' => $left, 'right' => $right ) );
			}
			return $left;
		}

		/** sum := term ( ( '+' | '-' ) term )* */
		private static function parse_sum( $text, &$position, &$state )
		{
			$left = self::parse_term( $text, $position, $state );
			if ( $left === false ) return false;
			while ( true ) {
				$operator = self::peek_operator( $text, $position );
				if ( $operator !== '+' && $operator !== '-' ) break;
				$position += 1;
				$right = self::parse_term( $text, $position, $state );
				if ( $right === false ) return false;
				$left = self::make_node( $state, array( 'type' => 'binary', 'op' => $operator, 'left' => $left, 'right' => $right ) );
			}
			return $left;
		}

		/** term := power ( ( '*' | '/' | '%' ) power )* */
		private static function parse_term( $text, &$position, &$state )
		{
			$left = self::parse_power( $text, $position, $state );
			if ( $left === false ) return false;
			while ( true ) {
				$operator = self::peek_operator( $text, $position );
				if ( $operator !== '*' && $operator !== '/' && $operator !== '%' ) break;
				$position += 1;
				$right = self::parse_power( $text, $position, $state );
				if ( $right === false ) return false;
				$left = self::make_node( $state, array( 'type' => 'binary', 'op' => $operator, 'left' => $left, 'right' => $right ) );
			}
			return $left;
		}

		/** power := unary ( '**' power )? The exponent is right associative. */
		private static function parse_power( $text, &$position, &$state )
		{
			$base = self::parse_unary( $text, $position, $state );
			if ( $base === false ) return false;
			if ( self::peek_operator( $text, $position ) === '**' ) {
				$position += 2;
				$exponent = self::parse_nested( $text, $position, $state, 'power' );
				if ( $exponent === false ) return false;
				return self::make_node( $state, array( 'type' => 'binary', 'op' => '**', 'left' => $base, 'right' => $exponent ) );
			}
			return $base;
		}

		/** unary := ( '-' | '+' | '!' )? primary */
		private static function parse_unary( $text, &$position, &$state )
		{
			$operator = self::peek_operator( $text, $position );
			if ( $operator === '-' || $operator === '+' || $operator === '!' ) {
				$position += 1;
				$operand = self::parse_nested( $text, $position, $state, 'unary' );
				if ( $operand === false ) return false;
				return self::make_node( $state, array( 'type' => 'unary', 'op' => $operator, 'arg' => $operand ) );
			}
			return self::parse_primary( $text, $position, $state );
		}

		/** primary := NUMBER | fieldref | call | '(' expression ')' */
		private static function parse_primary( $text, &$position, &$state )
		{
			self::skip_spaces( $text, $position );
			$length = strlen( $text );
			if ( $position >= $length ) return false;
			$character = $text[ $position ];

			if ( $character === '(' ) {
				$position++;
				$expression = self::parse_nested( $text, $position, $state, 'ternary' );
				if ( $expression === false ) return false;
				self::skip_spaces( $text, $position );
				if ( $position >= $length || $text[ $position ] !== ')' ) return false;
				$position++;
				return $expression;
			}

			if ( ( $character >= '0' && $character <= '9' ) || $character === '.' ) {
				$number = '';
				while ( $position < $length && ( ( $text[ $position ] >= '0' && $text[ $position ] <= '9' ) || $text[ $position ] === '.' ) ) {
					$number .= $text[ $position ];
					$position++;
				}
				if ( ! is_numeric( $number ) ) return false;
				return self::make_node( $state, array( 'type' => 'num', 'value' => (float) $number ) );
			}

			if ( ctype_alpha( $character ) || $character === '_' ) {
				$name = '';
				while ( $position < $length && ( ctype_alnum( $text[ $position ] ) || $text[ $position ] === '_' ) ) {
					$name .= $text[ $position ];
					$position++;
				}
				$upper = strtoupper( $name );
				if ( isset( self::$equation_constants[ $upper ] ) ) {
					return self::make_node( $state, array( 'type' => 'num', 'value' => self::$equation_constants[ $upper ] ) );
				}
				$field = strtolower( $name );
				if ( preg_match( '/\Afieldname\d+\z/', $field ) ) {
					$state['fields'][ $field ] = true;
					return self::make_node( $state, array( 'type' => 'field', 'name' => $field ) );
				}
				if ( array_key_exists( $field, $state['operands'] ) ) {
					return self::make_node( $state, array( 'type' => 'operand', 'name' => $field ) );
				}
				if ( ! in_array( $upper, self::$equation_functions, true ) ) return false;
				$arguments = self::parse_call( $text, $position, $state );
				if ( $arguments === false ) return false;
				if ( ! self::equation_arity( $upper, count( $arguments ) ) ) return false;
				return self::make_node( $state, array( 'type' => 'call', 'name' => $upper, 'args' => $arguments ) );
			}

			return false;
		}

		/** The arguments of a call: '(' ( expression ( ',' expression )* )? ')' */
		private static function parse_call( $text, &$position, &$state )
		{
			self::skip_spaces( $text, $position );
			$length = strlen( $text );
			if ( $position >= $length || $text[ $position ] !== '(' ) return false;
			$position++;
			$arguments = array();
			self::skip_spaces( $text, $position );
			if ( $position < $length && $text[ $position] === ')' ) {
				$position++;
				return $arguments;
			}
			while ( true ) {
				$argument = self::parse_nested( $text, $position, $state, 'ternary' );
				if ( $argument === false ) return false;
				$arguments[] = $argument;
				self::skip_spaces( $text, $position );
				if ( $position >= $length ) return false;
				if ( $text[ $position ] === ',' ) { $position++; continue; }
				if ( $text[ $position ] === ')' ) { $position++; return $arguments; }
				return false;
			}
		}

		/**
		 * Evaluates the parsed equation with the values of the fields.
		 *
		 * Everything is a float: the comparisons and the logical operators return one or
		 * zero. A value that is not a number, a field that is not there, a division by zero
		 * and a result that is not finite are all a failure.
		 *
		 * @param array $ast    The parsed equation.
		 * @param array $values The value of every field, indexed by name.
		 * @return float|false The result, or false when it cannot be evaluated.
		 */
		private static function eval_equation( $ast, $values )
		{
			$value = self::eval_equation_node( $ast, $values );
			return $value !== false && is_finite( $value ) ? $value : false;
		}

		/** Every recursive evaluation passes through the finite-result guard above. */
		private static function eval_equation_node( $ast, $values )
		{
			switch ( $ast['type'] ) {
				case 'num':
					return (float) $ast['value'];
				case 'field':
					if ( ! array_key_exists( $ast['name'], $values ) ) return false;
					if ( ! is_numeric( $values[ $ast['name'] ] ) ) return false;
					return (float) $values[ $ast['name'] ];
				case 'operand':
					return array_key_exists( $ast['name'], self::$equation_operands ) ? self::$equation_operands[ $ast['name'] ] : false;
				case 'unary':
					$operand = self::eval_equation( $ast['arg'], $values );
					if ( $operand === false ) return false;
					switch ( $ast['op'] ) {
						case '-': return -$operand;
						case '+': return $operand;
						case '!': return ( 0 == $operand ) ? 1.0 : 0.0;
					}
					return false;
				case 'binary':
					return self::eval_binary( $ast, $values );
				case 'ternary':
					$condition = self::eval_equation( $ast['condition'], $values );
					if ( $condition === false ) return false;
					return self::eval_equation( $condition != 0 ? $ast['yes'] : $ast['no'], $values );
				case 'call':
					$arguments = array();
					foreach ( $ast['args'] as $argument ) {
						$value = self::eval_equation( $argument, $values );
						if ( $value === false ) return false;
						$arguments[] = $value;
					}
					return self::equation_function( $ast['name'], $arguments );
			}
			return false;
		}

		/** Evaluates a binary operation. */
		private static function eval_binary( $ast, $values )
		{
			$left = self::eval_equation( $ast['left'], $values );
			if ( $left === false ) return false;
			switch ( $ast['op'] ) {
				case '&&':
					// The right side is not evaluated when the left one is false, as in the client.
					if ( 0 == $left ) return 0.0;
					return self::eval_equation( $ast['right'], $values );
				case '||':
					if ( 0 != $left ) return $left;
					return self::eval_equation( $ast['right'], $values );
			}
			$right = self::eval_equation( $ast['right'], $values );
			if ( $right === false ) return false;
			switch ( $ast['op'] ) {
				case '+': return $left + $right;
				case '-': return $left - $right;
				case '*': return $left * $right;
				case '/':
					if ( 0 == $right ) return false;
					return $left / $right;
				case '%':
					if ( 0 == $right ) return false;
					return self::equation_remainder( $left, $right );
				case '**':
					return self::equation_power( $left, $right );
				case '==': return ( $left == $right ) ? 1.0 : 0.0;
				case '!=': return ( $left != $right ) ? 1.0 : 0.0;
				case '<': return ( $left < $right ) ? 1.0 : 0.0;
				case '<=': return ( $left <= $right ) ? 1.0 : 0.0;
				case '>': return ( $left > $right ) ? 1.0 : 0.0;
				case '>=': return ( $left >= $right ) ? 1.0 : 0.0;
			}
			return false;
		}

		/** Check arity while parsing, including calls in branches that will not run. */
		private static function equation_arity( $name, $count )
		{
			if ( in_array( $name, array( 'ABS', 'SQRT' ), true ) ) return $count === 1;
			if ( in_array( $name, array( 'POW', 'REMAINDER' ), true ) ) return $count === 2;
			if ( in_array( $name, array( 'ROUND', 'FLOOR', 'CEIL' ), true ) ) return $count >= 1 && $count <= 2;
			if ( in_array( $name, array( 'PREC', 'IF' ), true ) ) return $count >= 1 && $count <= 3;
			return $count >= 1;
		}

		/**
		 * Evaluates one of the functions of a server side equation, with the semantics of
		 * the client (js/modules/01_mathematical_logical/public/module_public.js).
		 *
		 * There are four deliberate differences with the client:
		 * 1. PREC, ROUND, FLOOR and CEIL return a float here, and the client returns a
		 *    string built with toFixed.
		 * 2. PREC with an invalid precision is a silent no-op in the client and a failure
		 *    here.
		 * 3. SUM ignores the values that are not numbers in the client and fails here.
		 * 4. ROUND is half toward +Infinity in both, and not the round() of PHP, which is
		 *    half away from zero.
		 *
		 * @param string $name      The name of the function.
		 * @param array  $arguments The value of every argument.
		 * @return float|false
		 */
		private static function equation_function( $name, $arguments )
		{
			$count = count( $arguments );
			switch ( $name ) {
				case 'ABS':
					if ( $count != 1 ) return false;
					return abs( $arguments[0] );
				case 'SQRT':
					if ( $count != 1 ) return false;
					$value = sqrt( $arguments[0] );
					return ( is_nan( $value ) || is_infinite( $value ) ) ? false : $value;
				case 'POW':
					if ( $count != 2 ) return false;
					return self::equation_power( $arguments[0], $arguments[1] );
				case 'REMAINDER':
					if ( $count != 2 ) return false;
					if ( 0 == $arguments[1] ) return false;
					return self::equation_remainder( $arguments[0], $arguments[1] );
				case 'MIN':
					if ( ! $count ) return false;
					return min( $arguments );
				case 'MAX':
					if ( ! $count ) return false;
					return max( $arguments );
				case 'SUM':
					if ( ! $count ) return false;
					$sum = 0.0;
					foreach ( $arguments as $argument ) $sum += $argument;
					return $sum;
				case 'AVERAGE':
					if ( ! $count ) return false;
					$sum = 0.0;
					foreach ( $arguments as $argument ) $sum += $argument;
					return $sum / $count;
				case 'ROUND':
					if ( ! $count || $count > 2 ) return false;
					return self::equation_roundx( 'round', $arguments[0], $count > 1 ? $arguments[1] : null );
				case 'FLOOR':
					if ( ! $count || $count > 2 ) return false;
					return self::equation_roundx( 'floor', $arguments[0], $count > 1 ? $arguments[1] : null );
				case 'CEIL':
					if ( ! $count || $count > 2 ) return false;
					return self::equation_roundx( 'ceil', $arguments[0], $count > 1 ? $arguments[1] : null );
				case 'PREC':
					if ( ! $count || $count > 3 ) return false;
					return self::equation_prec( $arguments[0], $count > 1 ? $arguments[1] : 0, $count > 2 ? $arguments[2] : 0 );
				case 'IF':
					if ( ! $count || $count > 3 ) return false;
					if ( 0 != $arguments[0] ) return $count > 1 ? $arguments[1] : 1.0;
					return $count > 2 ? $arguments[2] : 0.0;
			}
			return false;
		}

		/**
		 * The remainder of a division, with the truncated quotient of the client.
		 */
		private static function equation_remainder( $dividend, $divisor )
		{
			return fmod( $dividend, $divisor );
		}

		/**
		 * The power of a number. A result that is not finite is a failure.
		 */
		private static function equation_power( $base, $exponent )
		{
			if ( 0 == $base && $exponent < 0 ) return false;
			$value = pow( $base, $exponent );
			return ( is_nan( $value ) || is_infinite( $value ) ) ? false : $value;
		}

		/**
		 * Rounds a value half toward +Infinity, the way the browser rounds it and not the
		 * way PHP rounds it, which is half away from zero.
		 *
		 * @param float $value
		 * @param int   $decimals
		 * @return float
		 */
		private static function round_half_up( $value, $decimals = 0 )
		{
			$factor = pow( 10, (int) $decimals );
			if ( abs( $value ) >= 1e21 && $decimals >= 0 ) return $value;
			$scaled = $value * $factor;
			$floor = floor( $scaled );
			return ( $scaled - $floor < 0.5 ? $floor : $floor + 1.0 ) / $factor;
		}

		/**
		 * PREC: the precision of a number, as the client does it. The client ignores an
		 * invalid precision; the server rejects the equation.
		 */
		private static function equation_prec( $value, $precision, $if_not_integer = 0 )
		{
			if ( ! is_numeric( $precision ) || floor( $precision ) != $precision || $precision < 0 ) return false;
			if ( ! is_numeric( $value ) ) return false;
			if ( floor( $value ) == $value && $if_not_integer ) return (float) $value;
			if ( $precision > 100 ) return false; // The browser's toFixed precision limit.
			return self::round_half_up( $value, (int) $precision );
		}

		/**
		 * ROUNDx: the rounding of the client, with the step and the precision it applies.
		 */
		private static function equation_roundx( $operation, $value, $step = null )
		{
			if ( $step !== null && $step != 0 ) {
				$result = self::equation_operation( $operation, $value / $step ) * $step;
				$decimals = self::equation_decimals( $step );
				if ( $decimals !== null ) {
					$precision = self::equation_prec( $result, $decimals );
					if ( $precision === false ) return false;
					$result = $precision;
				}
				return $result;
			}
			return self::equation_operation( $operation, $value );
		}

		/** The operation of ROUNDx. */
		private static function equation_operation( $operation, $value )
		{
			switch ( $operation ) {
				case 'round': return self::round_half_up( $value );
				case 'floor': return floor( $value );
				case 'ceil': return ceil( $value );
			}
			return false;
		}

		/** The number of decimals of a number, as the client reads them from its string. */
		private static function equation_decimals( $value )
		{
			$text = (string) $value;
			$dot = strpos( $text, '.' );
			if ( $dot === false ) return null;
			return strlen( $text ) - $dot - 1;
		}
	}
}
