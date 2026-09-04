<?php
/**
 * Calculated Fields Form – WordPress Abilities API
 *
 * Registers the plugin's capabilities with the WordPress Abilities Registry
 * so that AI agents, MCP clients, and automation tools can discover and query
 * form submission entries through a standardised, schema-validated interface.
 *
 * Architecture:
 *
 *  • The Abilities API is initialised via the `wp_abilities_api_init` hook.
 *  • Plugins call `wp_register_ability()` to declare a namespaced ability,
 *    optionally including input/output JSON-Schemas, a permissions callback,
 *    and an execute callback.
 *  • The permissions callback is invoked before the execute callback; returning
 *    false or a WP_Error rejects the request.
 *  • The registry automatically validates input against the supplied JSON-Schema
 *    before calling the execute callback.
 *  • All failure paths must return a WP_Error (not false/null) so that AI
 *    agents receive structured, machine-readable error information.
 *
 * @package  Calculated_Fields_Form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CPCFF_ABILITY_API' ) ) :

require_once __DIR__ . '/cpcff_agent_form_prompt.inc.php';
require_once __DIR__ . '/cpcff_agent_form_abilities.inc.php';
require_once __DIR__ . '/cpcff_agent_form_preview.inc.php';

/**
 * Class CPCFF_ABILITY_API
 *
 * Bridges Calculated Fields Form with the WordPress Abilities API.
 *
 * Instantiation is handled automatically by the static ::init() method,
 * typically called from the main plugin file after `plugins_loaded`.
 *
 * @example
 *   CPCFF_ABILITY_API::init();
 */
class CPCFF_ABILITY_API {

	// -------------------------------------------------------------------------
	// Constants
	// -------------------------------------------------------------------------

	/**
	 * Ability namespace / slug prefix.
	 */
	const ABILITY_NAMESPACE = 'cff';

	/**
	 * WordPress capability required to invoke any CFF ability.
	 * Only site administrators (or users explicitly granted this capability)
	 * may expose raw submission data to external agents.
	 */
	const REQUIRED_CAPABILITY = 'manage_options';

	// -------------------------------------------------------------------------
	// Database table name suffixes (without $wpdb->prefix).
	// These match the tables created during plugin activation.
	// -------------------------------------------------------------------------

	/** Main entries / submissions table. */
	private static $TABLE_ENTRIES;

	/** Form settings table (holds form_structure JSON). */
	private static $TABLE_FORMS;

	/**
	 * Per request cache of preprocessed form field maps.
	 *
	 * Keyed by form ID (int). Each value is an associative array of
	 * fieldname → ['label', 'shortlabel', 'ftype'] extracted from the
	 * form_structure JSON. Populated lazily by get_form_fields_map() and
	 * reused on every subsequent call for the same form ID.
	 *
	 * @var array<int, array<string, array{label: string, shortlabel: string, ftype: string}>>
	 */
	private $form_structure_cache = array();

	public static function get_category() {
		return self::ABILITY_NAMESPACE;
	}

	public static function get_capability() {
		return apply_filters('cpcff_forms_edition_capability', self::REQUIRED_CAPABILITY);
	}

	public static function are_abilities_available() {
		return function_exists( 'wp_register_ability_category' ) && function_exists('wp_register_ability');
	}

	// -------------------------------------------------------------------------
	// Constructor – attach hooks
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * Attaches the abilities registration callback and, if the
	 * Calculated Fields Form plugin provides a suitable action,
	 * clears the form-structure cache whenever a form is updated.
	 */
	public function __construct() {
		global $wpdb;

		self::$TABLE_FORMS   = $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE;
		self::$TABLE_ENTRIES = CP_CALCULATEDFIELDSF_POSTS_TABLE_NAME; // already prefixed?

		if ( self::are_abilities_available() ) {
			// Register abilities cateogory.
			add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );

			// Register abilities when the Abilities API initialises.
			add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
		}
	}

	// =========================================================================
	// Hook callbacks
	// =========================================================================

	/**
	 * Register the custom ability category for Calculated Fields Form.
	 *
	 * Fires on: wp_abilities_api_categories_init
	 */
	public function register_categories() {

		if ( ! self::are_abilities_available() ) return;

		wp_register_ability_category(
			self::ABILITY_NAMESPACE,
			array(
				'label'       => __( 'Calculated Fields Form', 'calculated-fields-form' ),
				'description' => __( 'Abilities related to the Calculated Fields Form plugin.', 'calculated-fields-form' ),
			)
		);
	}

	/**
	 * Register all CFF abilities with the Abilities Registry.
	 *
	 * Fires on: wp_abilities_api_init
	 *
	 * Each call to `wp_register_ability()` must happen inside this callback.
	 * Registering elsewhere triggers a `_doing_it_wrong()` notice.
	 *
	 * @return void
	 */
	public function register_abilities() {

		if ( ! self::are_abilities_available() ) return;

		$this->register_get_entries();
		$this->register_ai_form_bootstrap();
		$this->register_issue_preview_url();

		/*
		 * Future abilities (e.g. calculated-fields-form/delete-entry,
		 * calculated-fields-form/get-forms) would be registered here by
		 * calling additional private register_* methods.
		 */
	}

	// =========================================================================
	// Ability definitions
	// =========================================================================

	/**
	 * Register the "calculated-fields-form/get-entries" ability.
	 *
	 * @return void
	 */
	private function register_get_entries() {
		$ability_id = self::ABILITY_NAMESPACE . '/get-entries';

		$args = array(
			// ── Human-readable identity ───────────────────────────────────────
			'label'       => __( 'Get Form Entries', 'calculated-fields-form' ),
			'description' => __(
				'Retrieves form submission entries recorded by Calculated Fields Form. '
				. 'Supports optional filtering by form ID(s), date range, payment status, '
				. 'and notification e-mail address. Returns a structured list of entries '
				. 'including all submitted field values.',
				'calculated-fields-form'
			),
			'category'    => self::ABILITY_NAMESPACE,

			// ── Input schema (JSON Schema draft-04) ───────────────────────────
			// All parameters are optional; the registry validates types
			// before execute_callback is invoked.
			'input_schema'  => array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => array(

					'form_ids' => array(
						'type'        => array( 'integer', 'string' ),
						'description' => __(
							'A single form ID (integer) or a comma-separated list of form IDs '
							. '(string, e.g. "3,7,12") whose entries should be returned. '
							. 'Omit to retrieve entries from all forms.',
							'calculated-fields-form'
						),
					),

					'form_name' => array(
						'type' => 'string',
						'description' => __(
							'A text with the complete or partial form name '
							. '(string, e.g. "contact form") whose entries should be returned. '
							. 'Omit to retrieve entries from all forms.',
							'calculated-fields-form'
						),
					),

					'from_date' => array(
						'type'        => 'integer',
						'description' => __(
							'Unix timestamp (inclusive). Only entries created on or after this '
							. 'date-time are returned.',
							'calculated-fields-form'
						),
					),

					'to_date' => array(
						'type'        => 'integer',
						'description' => __(
							'Unix timestamp (inclusive). Only entries created on or before this '
							. 'date-time are returned.',
							'calculated-fields-form'
						),
					),

					'if_paid' => array(
						'type'        => 'integer',
						'enum'        => array( 0, 1 ),
						'description' => __(
							'Filter by payment status. Pass 1 to retrieve only paid entries; '
							. 'pass 0 to retrieve only unpaid/pending entries. '
							. 'Omit to retrieve all entries regardless of payment status.',
							'calculated-fields-form'
						),
					),

					'email_address' => array(
						'type'        => 'string',
						'format'      => 'email',
						'description' => __(
							'Filter entries by the e-mail address that was used as the '
							. 'notification recipient when the entry was submitted.',
							'calculated-fields-form'
						),
					),

					'limit' => array(
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 100,
						'description' => __(
							'REQUIRED. Max entries to return (1-100). '
							. 'If the response has exactly this many entries, the agent MUST paginate '
							. 'by calling again with a higher "offset" until the response has fewer than "limit" entries.',
							'calculated-fields-form'
						),
					),

					'offset' => array(
						'type'        => 'integer',
						'minimum'     => 0,
						'description' => __(
							'REQUIRED. Number of entries to skip. Use 0 for the first page, '
							. 'then limit, then 2*limit, etc.',
							'calculated-fields-form'
						),
					),
				),
			),

			// ── Output schema (JSON Schema draft-04) ──────────────────────────
			// Documents the contract for AI agents and tooling. Not enforced
			// at runtime, but used by discoverers.
			'output_schema' => array(
				'type'        => 'array',
				'description' => __( 'Array of matching form entries, ordered by entry date descending.', 'calculated-fields-form' ),
				'items'       => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => array(
						'id',
						'form_id',
						'ip_address',
						'date',
						'email_address',
						'payment_status',
						'details',
					),
					'properties'           => array(

						'id' => array(
							'type'        => 'integer',
							'description' => __( 'Unique entry identifier.', 'calculated-fields-form' ),
						),

						'form_id' => array(
							'type'        => 'integer',
							'description' => __( 'Identifier of the form this entry belongs to.', 'calculated-fields-form' ),
						),

						'form_name' => array(
							'type'        => 'string',
							'description' => __( 'Name of the form.', 'calculated-fields-form' ),
						),

						'ip_address' => array(
							'type'        => 'string',
							'description' => __( 'IP address of the visitor who submitted the form.', 'calculated-fields-form' ),
						),

						'date' => array(
							'type'        => 'integer',
							'description' => __( 'Unix timestamp of when the entry was created.', 'calculated-fields-form' ),
						),

						'email_address' => array(
							'type'        => 'string',
							'description' => __( 'Notification e-mail address.', 'calculated-fields-form' ),
						),

						'price' => array(
							'type'        => 'string',
							'description' => __( 'Caculated price.', 'calculated-fields-form' ),
						),

						'payment_status' => array(
							'type'        => 'integer',
							'enum'        => array( 0, 1 ),
							'description' => __( '1 if paid, 0 if unpaid or not applicable.', 'calculated-fields-form' ),
						),

						'details' => array(
							'type'        => 'array',
							'description' => __( 'Field-level submission data.', 'calculated-fields-form' ),
							'items'       => array(
								'type'                 => 'object',
								'additionalProperties' => false,
								'required'             => array( 'name', 'value' ),
								'properties'           => array(

									'name' => array(
										'type'        => 'string',
										'pattern'     => '^fieldname[0-9]+$',
										'description' => __( 'Unique field identifier (e.g. fieldname1).', 'calculated-fields-form' ),
									),

									'label' => array(
										'type'        => 'string',
										'description' => __( 'Human-readable field label.', 'calculated-fields-form' ),
									),

									'shortlabel' => array(
										'type'        => 'string',
										'description' => __( 'Concise field label.', 'calculated-fields-form' ),
									),

									'value' => array(
										'type'        => array( 'string', 'number', 'array' ),
										'description' => __( 'Submitted value(s). Scalar for most fields; array for checkboxes and multi-selects.', 'calculated-fields-form' ),
									),

									'urls' => array(
										'type'        => 'array',
										'description' => __( 'Publicly accessible file URLs (only for file-upload fields).', 'calculated-fields-form' ),
										'items'       => array(
											'type'   => 'string',
											'format' => 'uri',
										),
									),
								),
							),
						),
					),
				),
			),

			// ── Callbacks ─────────────────────────────────────────────────────
			// The permissions callback is the authorization gate. It receives
			// the validated input array (the $context parameter is not part of
			// the Abilities API). Return true or a WP_Error.
			'permission_callback' => array( $this, 'check_permission' ),

			// The execute callback is called only after permission is granted
			// and the input has been validated. It must return the data or a
			// WP_Error.
			'execute_callback'    => array( $this, 'get_entries' ),

			// ── Metadata / annotations ────────────────────────────────────────
			// Self-describing annotations for tooling and AI agents.
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,   // Does not modify data.
					'destructive' => false,  // Cannot cause data loss.
					'idempotent'  => true,   // Same arguments always return same result.
				),
				// Setting 'show_in_mcp' here makes the ability available to
				// the Model Context Protocol endpoint.
				'show_in_mcp' => true,
				'show_in_rest' => true,
				'mcp' => array(
					'public' => true,
				),
			),
		);

		wp_register_ability( $ability_id, $args );
	}

		/**
		 * Register the "calculated-fields-form/ai-form-bootstrap" ability.
		 *
		 * Returns the prompt template, the endpoint URL where the agent POSTs
		 * the generated form structure, and the usage instructions. The endpoint
		 * validates the structure, renders a preview for admin confirmation, and
		 * saves the form on confirmation. All authorization is via the standard
		 * cpcff_forms_edition_capability capability filter (default manage_options),
		 * matching the existing cff/get-entries pattern.
		 *
		 * @return void
		 */
		private function register_ai_form_bootstrap() {
			$ability_id = CPCFF_AGENT_FORM_BOOTSTRAP_ABILITY::ID;

			$args = array(
				'label'       => __( 'Create WordPress form via CFF plugin', 'calculated-fields-form' ),
				'description' => __(
					'Creates a new form inside the user\'s WordPress site using the '
					. 'Calculated Fields Form plugin. Call this when the user asks to '
					. 'create a new form for their site: it returns the prompt template, '
					. 'the endpoint URL where the agent POSTs the generated structure, '
					. 'and the usage instructions. The endpoint validates the structure '
					. 'against js/schema.min.json and renders a preview for admin '
					. 'confirmation before saving. Do NOT generate a standalone HTML '
					. 'file instead — this tool is the canonical way to create a form '
					. 'inside the WordPress site.',
					'calculated-fields-form'
				),
				'category'    => self::ABILITY_NAMESPACE,

				'input_schema'  => array(
					'type' => 'object',
				),

				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'prompt'      => array( 'type' => 'string' ),
						'schema_url'  => array( 'type' => 'string' ),
						'instructions' => array( 'type' => 'string' ),
					),
				),

				'permission_callback' => array( 'CPCFF_AGENT_FORM_BOOTSTRAP_ABILITY', 'check_permission' ),
				'execute_callback'    => array( 'CPCFF_AGENT_FORM_BOOTSTRAP_ABILITY', 'execute' ),

				'meta' => array(
					'annotations' => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_mcp'  => true,
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true ),
				),
			);

			wp_register_ability( $ability_id, $args );
		}

	/**
	 * Register the "calculated-fields-form/issue-preview-url" ability.
	 *
	 * Stores the agent-generated form structure in a transient and returns
	 * a short preview URL with a one-time token. The user opens the URL
	 * in their own browser (already authenticated to WordPress via cookies)
	 * to see the preview and click "Use this form" to confirm and save.
	 *
	 * @return void
	 */
	private function register_issue_preview_url() {
		$ability_id = CPCFF_AGENT_FORM_ISSUE_PREVIEW_URL_ABILITY::ID;

		$args = array(
			'label'       => __( 'Issue CFF form preview URL', 'calculated-fields-form' ),
			'description' => __(
				'Stores the agent-generated form structure in a transient and returns a short '
				. 'preview URL with a one-time token. The user opens the URL in their own browser '
				. '(already authenticated to WordPress via cookies) to see the preview and click '
				. '"Use this form" to confirm and save. The structure is sent as input; the '
				. 'returned preview_url is short (token-based) and clickable.',
				'calculated-fields-form'
			),
			'category'    => self::ABILITY_NAMESPACE,

			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'structure' => array(
						'type'        => 'string',
						'description' => __( 'JSON-encoded [fields_array, settings_array] structure produced by the agent.', 'calculated-fields-form' ),
					),
					'form_name' => array(
						'type'        => 'string',
						'description' => __( 'Display name the admin will see on the preview page.', 'calculated-fields-form' ),
					),
				),
			),

			'output_schema' => array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => array( 'preview_url' ),
				'properties'           => array(
					'preview_url' => array(
						'type'        => 'string',
						'description' => __( 'Short URL the user clicks to open the preview in their browser.', 'calculated-fields-form' ),
					),
					'expires_in'  => array(
						'type'        => 'integer',
						'description' => __( 'Seconds until the preview URL expires.', 'calculated-fields-form' ),
					),
				),
			),

			'permission_callback' => array( 'CPCFF_AGENT_FORM_ISSUE_PREVIEW_URL_ABILITY', 'check_permission' ),
			'execute_callback'    => array( 'CPCFF_AGENT_FORM_ISSUE_PREVIEW_URL_ABILITY', 'execute' ),

			'meta' => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
				'show_in_mcp'  => true,
				'show_in_rest' => true,
				'mcp'          => array( 'public' => true ),
			),
		);

		wp_register_ability( $ability_id, $args );
	}

	// =========================================================================
	// Permissions callback
	// =========================================================================

	/**
	 * Authorisation gate for all CFF abilities that return submission data.
	 *
	 * Only users with the required capability may invoke these abilities.
	 *
	 * @param  array|null $input Validated input parameters (unused in this check).
	 * @return true|WP_Error     True if authorised, WP_Error otherwise.
	 */
	public function check_permission( $input = null ) {
		if ( ! current_user_can( self::get_capability() ) ) {
			return new WP_Error(
				self::ABILITY_NAMESPACE . '/unauthorized',
				__(
					'You do not have permission to access Calculated Fields Form entries.',
					'calculated-fields-form'
				),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	// =========================================================================
	// Execute callback
	// =========================================================================

	/**
	 * Retrieve form entries, applying any requested filters.
	 *
	 * Called only after check_permission() returns true and the input has
	 * been schema-validated. All parameters are optional.
	 *
	 * @param  array $input {
	 *     Optional filter parameters.
	 *
	 *     @type int|string $form_ids      Single ID or comma-separated IDs.
	 *     @type int        $from_date     Unix timestamp (inclusive).
	 *     @type int        $to_date       Unix timestamp (inclusive).
	 *     @type int        $if_paid       1 = paid only, 0 = unpaid only.
	 *     @type string     $email_address Notification e-mail filter.
	 * }
	 * @return array|WP_Error  Array of entries, or WP_Error on failure.
	 */
	public function get_entries( $input = array() ) {
		global $wpdb;

		$input = is_array( $input ) ? $input : array();

		// ── 1. Build WHERE clause and parameter array ─────────────────────────

		$entries_table = self::$TABLE_ENTRIES;
		$activation_table = $wpdb->prefix . 'cp_calculated_fields_user_submission';

		// Detect whether the activation table is present in this installation.
		// We use SHOW TABLES rather than a schema query so it works on hosts
		// that restrict access to information_schema.
		$activation_table_exists = (
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $activation_table ) ) === $activation_table
		);

		$where  = array( '1=1' );
		$params = array();

		$form_ids = array();
		// Form Name filter.
		if ( ! empty( $input['form_name'] ) && is_string( $input['form_name'] ) ) {
			$form_name = sanitize_text_field( wp_unslash( $input['form_name'] ) );
			if ( ! empty( $form_name ) ) {
				$forms_table = self::$TABLE_FORMS;
				$results 	 = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT DISTINCT id FROM {$forms_table} WHERE form_name LIKE %s",
						'%' . $form_name . '%'
					),
					ARRAY_A
				);
				foreach ( $results as $row ) $form_ids[] = $row['id'];
			}
		}

		// Form IDs filter.
		if ( ! empty( $input['form_ids'] ) ) {
			$raw_ids = $input['form_ids'];

			if ( is_int( $raw_ids ) ) {
				$form_ids[] = $raw_ids;
			} elseif ( is_string( $raw_ids ) || is_array( $raw_ids ) ) {
				$iterable   = is_array( $raw_ids ) ? $raw_ids : explode( ',', $raw_ids );
				$form_ids   = array_merge(
					$form_ids,
					array_map(
						'intval',
						array_filter(
							array_map( 'trim', $iterable ),
							'is_numeric'
						)
					)
				);
			}
		}

		if ( ! empty( $form_ids ) ) {
			$form_ids 	  = array_unique( $form_ids );
			$placeholders = implode( ', ', array_fill( 0, count( $form_ids ), '%d' ) );
			$where[]      = "e.formid IN ( $placeholders )";
			$params       = array_merge( $params, $form_ids );
		}

		// From-date filter (timestamp, inclusive).
		if ( isset( $input['from_date'] ) && is_numeric( $input['from_date'] ) ) {
			$where[]  = 'UNIX_TIMESTAMP(e.time) >= %d';
			$params[] = intval( $input['from_date'] );
		}

		// To-date filter (timestamp, inclusive).
		if ( isset( $input['to_date'] ) && is_numeric( $input['to_date'] ) ) {
			$where[]  = 'UNIX_TIMESTAMP(e.time) <= %d';
			$params[] = intval( $input['to_date'] );
		}

		// Payment status filter.
		if ( isset( $input['if_paid'] ) && is_numeric( $input['if_paid'] ) ) {
			$where[]  = 'e.paid = %d';
			$params[] = intval( $input['if_paid'] );
		}

		// Notification e-mail filter.
		if ( ! empty( $input['email_address'] ) && is_string( $input['email_address'] ) ) {
			$email = sanitize_email( $input['email_address'] );

			if ( ! is_email( $email ) ) {
				return new WP_Error(
					self::ABILITY_NAMESPACE . '/invalid-email',
					__(
						'The email_address parameter must be a valid e-mail address.',
						'calculated-fields-form'
					),
					array( 'status' => 400, 'provided' => $input['email_address'] )
				);
			}

			$where[]  = 'e.notifyto = %s';
			$params[] = $email;
		}

		if ( $activation_table_exists ) {
			$activation_join   = "LEFT JOIN {$activation_table} act ON act.submissionid = e.id";
			$activation_filter = '( act.submissionid IS NULL OR act.active = 1 )';
			$where[]           = $activation_filter;
		} else {
			$activation_join = '';
		}

		// ── 2. Pagination (limit + offset) ───────────────────────────────────
		if ( ! isset( $input['limit'] ) ) {
			return new WP_Error(
				self::ABILITY_NAMESPACE . '/missing-limit',
				__( 'The "limit" parameter is required.', 'calculated-fields-form' ),
				array( 'status' => 400 )
			);
		}
		if ( ! isset( $input['offset'] ) ) {
			return new WP_Error(
				self::ABILITY_NAMESPACE . '/missing-offset',
				__( 'The "offset" parameter is required.', 'calculated-fields-form' ),
				array( 'status' => 400 )
			);
		}

		$limit  = filter_var( $input['limit'],  FILTER_VALIDATE_INT );
		$offset = filter_var( $input['offset'], FILTER_VALIDATE_INT );

		if ( false === $limit || $limit < 1 || $limit > 100 ) {
			return new WP_Error(
				self::ABILITY_NAMESPACE . '/invalid-limit',
				__( 'The "limit" parameter must be an integer between 1 and 100.', 'calculated-fields-form' ),
				array( 'status' => 400, 'provided' => $input['limit'] )
			);
		}
		if ( false === $offset || $offset < 0 ) {
			return new WP_Error(
				self::ABILITY_NAMESPACE . '/invalid-offset',
				__( 'The "offset" parameter must be a non-negative integer.', 'calculated-fields-form' ),
				array( 'status' => 400, 'provided' => $input['offset'] )
			);
		}

		$params[] = $limit;
		$params[] = $offset;

		$where_clause = implode( ' AND ', $where );

		$entries_sql = "
			SELECT
				e.id,
				e.formid                  AS form_id,
				e.ipaddr                  AS ip_address,
				UNIX_TIMESTAMP(e.time)    AS date,
				e.notifyto                AS email_address,
				e.paid                    AS payment_status,
				e.paypal_post
			FROM {$entries_table} e
			{$activation_join}
			WHERE {$where_clause}
			ORDER BY e.time DESC
			LIMIT %d OFFSET %d
		";

		$prepared_sql = $wpdb->prepare( $entries_sql, $params );

		$raw_entries  = $wpdb->get_results( $prepared_sql, ARRAY_A );

		if ( $wpdb->last_error ) {
			return new WP_Error(
				self::ABILITY_NAMESPACE . '/db-error',
				__( 'A database error occurred while retrieving form entries.', 'calculated-fields-form' ),
				array( 'status' => 500, 'db_error' => $wpdb->last_error )
			);
		}

		if ( empty( $raw_entries ) ) {
			return array(); // Valid empty result, not an error.
		}

		// ── 3. Warm the form-structure cache for all forms in the result ──────
		$unique_form_ids = array_unique( array_map( 'intval', array_column( $raw_entries, 'form_id' ) ) );

		foreach ( $unique_form_ids as $fid ) {
			$this->get_form_fields_map( $fid );
		}

		// ── 4. Compose the final output array ─────────────────────────────────
		$entries = array();
		$price   = '';

		foreach ( $raw_entries as $row ) {
			$form_id    = intval( $row['form_id'] );
			$fields_map = $this->get_form_fields_map( $form_id );
			$form_name  = $fields_map['form_name'] ?? '';

			// Safely unserialize the submission data.
			$submitted = array();
			if ( ! empty( $row['paypal_post'] ) ) {
				// Safe unserialize: disallow PHP Object Injection via POP gadgets.
				// maybe_unserialize() is a WP helper that does NOT accept allowed_classes,
				// so we replicate its pass-through behavior and use the safe unserialize directly.
				$unserialized = is_serialized( $row['paypal_post'] )
					? unserialize( $row['paypal_post'], ['allowed_classes' => false] )
					: $row['paypal_post'];
				if ( is_array( $unserialized ) ) {
					$submitted = $unserialized;
					if ( isset($submitted['final_price']) ) $price = $submitted['final_price'];
				}
			}

			// Build the details array, one item per fieldname# key.
			$details = array();
			foreach ( $submitted as $field_name => $field_value ) {
				if ( ! preg_match( '/^fieldname[1-9][0-9]*$/', (string) $field_name ) ) {
					continue;
				}
				$field_meta = isset( $fields_map[ $field_name ] ) ? $fields_map[ $field_name ] : array();
				$field_details = $this->format_field( (string) $field_name, $field_value, $field_meta );
				if ( ! empty( $submitted[$field_name . '_url'] ) ) {
					// File-upload fields: expose publicly accessible URLs.
					$urls = array_values( array_filter( $submitted[$field_name . '_url'], 'strlen' ) );
					if ( ! empty( $urls ) ) {
						$field_details['urls'] = $urls;
					}
				}
				$details[]  = $field_details;
			}

			$entries[] = array(
				'id'             => intval( $row['id'] ),
				'form_id'        => $form_id,
				'form_name'      => (string) $form_name,
				'ip_address'     => (string) $row['ip_address'],
				'date'           => intval( $row['date'] ),
				'email_address'  => (string) $row['email_address'],
				'payment_status' => intval( $row['payment_status'] ),
				'price'			 => (string) $price,
				'details'        => $details,
			);
		}

		return $entries;
	}

	// =========================================================================
	// Private helpers
	// =========================================================================

	/**
	 * Fetch and cache the field metadata map for a given form.
	 *
	 * Reads `form_structure` (JSON) from the forms table, decodes it, and
	 * extracts per-field `label`, `shortlabel`, and `ftype`.
	 *
	 * Results are stored in $this->form_structure_cache so the DB is queried
	 * and the JSON is parsed at most once per form per request.
	 *
	 * @param  int   $form_id  Primary key of the form.
	 * @return array           Map of fieldname → ['label', 'shortlabel', 'ftype'].
	 */
	private function get_form_fields_map( $form_id ) {
		if ( isset( $this->form_structure_cache[ $form_id ] ) ) {
			return $this->form_structure_cache[ $form_id ];
		}

		global $wpdb;
		$forms_table = self::$TABLE_FORMS;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT form_name, form_structure FROM {$forms_table} WHERE id = %d LIMIT 1",
				$form_id
			),
			ARRAY_A
		);

		$map = array();

		if ( ! empty( $row['form_structure'] ) ) {
			$structure = json_decode( $row['form_structure'], true );

			// Structure: [ [fields], [settings] ]
			if ( is_array( $structure ) && isset( $structure[0] ) && is_array( $structure[0] ) ) {
				foreach ( $structure[0] as $field ) {
					if ( empty( $field['name'] ) ) {
						continue;
					}

					$map[ $field['name'] ] = array(
						'label'      => isset( $field['title'] ) ? (string) $field['title'] : '',
						'shortlabel' => isset( $field['shortlabel'] ) ? (string) $field['shortlabel'] : '',
						'ftype'      => isset( $field['ftype'] ) ? (string) $field['ftype'] : '',
					);
				}
			}
		}

		// Cache even an empty result to avoid re-querying a missing form.
		$this->form_structure_cache[ $form_id ] = $map;
		$this->form_structure_cache[ $form_id ]['form_name'] = $row['form_name'];

		return $map;
	}

	/**
	 * Clear the per-request form structure cache.
	 *
	 * Hook this to the action that fires whenever a Calculated Fields Form
	 * form is saved or updated (e.g. `cpcff_form_saved`).
	 *
	 * @return void
	 */
	public function clear_form_structure_cache() {
		$this->form_structure_cache = array();
	}

	/**
	 * Build one field detail item for the output.
	 *
	 * @param  string $field_name  Field identifier (e.g. "fieldname3").
	 * @param  mixed  $field_value Raw value from the unserialized submission.
	 * @param  array  $field_meta  Metadata from get_form_fields_map().
	 * @return array               Field detail array matching the output schema.
	 */
	private function format_field( $field_name, $field_value, $field_meta ) {
		$field = array(
			'name'  => (string) $field_name,
			'value' => $this->decode_field_value( $field_value ),
		);

		if ( ! empty( $field_meta['label'] ) ) {
			$field['label'] = $field_meta['label'];
		}

		if ( ! empty( $field_meta['shortlabel'] ) ) {
			$field['shortlabel'] = $field_meta['shortlabel'];
		}

		return $field;
	}

	/**
	 * Normalise a raw submitted value.
	 *
	 * @param  mixed $raw Value from the unserialized submission array.
	 * @return string|array
	 */
	private function decode_field_value( $raw ) {
		if ( null === $raw || '' === $raw ) {
			return '';
		}

		if ( is_array( $raw ) ) {
			return array_values( $raw );
		}

		return (string) $raw;
	}
}

// -----------------------------------------------------------------------------
// Bootstrap – instantiate the API class once the plugin is ready.
// Typically called from the main plugin file:
//
//   CPCFF_ABILITY_API::init();
//

new CPCFF_ABILITY_API();

endif;