<?php
/**
 * CFF Akismet Integration
 *
 * Integrates the Akismet API into the CFF form submission flow
 * to automatically detect and manage spam.
 *
 * @package     Calculated Fields Form\Akismet
 * @version     1.0.0
 * @requires    WordPress 5.8+, PHP 7.4+
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Main class
// ---------------------------------------------------------------------------

if ( ! class_exists( 'CPCFF_Akismet' ) ) :

class CPCFF_Akismet {

    // -----------------------------------------------------------------------
    // Possible check results
    // -----------------------------------------------------------------------

    private const RESULT_HAM     = 'ham';     // Legitimate
    private const RESULT_SPAM    = 'spam';    // Normal spam (review manually)
    private const RESULT_DISCARD = 'discard'; // Obvious spam (silence without saving)
    private const RESULT_UNKNOWN = 'unknown'; // Akismet unavailable

    public const SIGNUP_URL = 'https://akismet.com/signup/';

    // -----------------------------------------------------------------------
    // Configuration
    // -----------------------------------------------------------------------

    private const VERSION    = '1.0.0';
    private const ENABLE_OPTION = 'CP_CALCULATEDFIELDSF_AKISMET_ENABLED';
    private const OPTION_KEY = 'CP_CALCULATEDFIELDSF_AKISMET_API_KEY';
    private const LOG_OPTION = 'CP_CALCULATEDFIELDSF_AKISMET_ACTIVITY_LOG';
    private const TEST_MODE_OPTION = 'CP_CALCULATEDFIELDSF_AKISMET_TEST_MODE';
    private const API_BASE   = 'https://rest.akismet.com/1.1';
    private const ENDPOINT   = 'https://%s.rest.akismet.com/1.1/';

    private const TIMEOUT = 30; // seconds

    // -----------------------------------------------------------------------
    // Singleton
    // -----------------------------------------------------------------------

    private static ?CPCFF_Akismet $instance = null;

    /**
     * Returns the singleton instance of the class, creating it on first call.
     *
     * @return self
     */
    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor: enforces the singleton pattern and registers all WordPress hooks.
     */
    private function __construct() {
        $this->register_hooks();
    }

    /**
     * Registers all WordPress action and filter hooks consumed by the integration:
     *  - cpcff_process_data_before_insert      -> handle_submission
     *  - cpcff_free_notification_email_subject -> mark_email_as_spam
     *
     * @return void
     */
    private function register_hooks(): void {
        // Check submission before CFF processes it
        add_action( 'cpcff_process_data_before_insert', [ $this, 'handle_submission' ], 10, 3 );
        add_action( 'cpcff_free_process_data_before_insert', [ $this, 'handle_submission' ], 10, 3 );

        // Mark notification emails as spam/ham based on Akismet result
        add_filter( 'cpcff_free_notification_email_subject', [ $this, 'mark_email_as_spam' ], 10, 2 );

    }

    // -----------------------------------------------------------------------
    // Configuration accessors
    // -----------------------------------------------------------------------

    /**
     * Returns whether the Akismet integration is enabled in the plugin settings.
     *
     * @return bool
     */
    static public function is_enabled(): bool {
        return (bool) get_option( self::ENABLE_OPTION, false );
    }

    /**
     * Returns the Akismet API key stored in the WordPress options table.
     *
     * @return string API key, or empty string when not configured.
     */
    static public function get_api_key(): string {
        return (string) get_option( self::OPTION_KEY, '' );
    }

    /**
     * Returns whether the activity log is enabled in the plugin settings.
     *
     * @return bool
     */
    static public function is_activity_log_enabled(): bool {
        return (bool) get_option( self::LOG_OPTION, false );
    }

    /**
     * Returns whether the test mode is enabled in the plugin settings.
     *
     * @return bool
     */
    static public function is_test_mode_enabled(): bool {
        return (bool) get_option( self::TEST_MODE_OPTION, false );
    }

    /**
     * Checks if the integration is correctly configured: enabled and with a non-empty API key.
     *
     * @return bool
     */
    private function is_configured(): bool {
        return self::is_enabled() && ! empty( self::get_api_key() );
    }

    // -----------------------------------------------------------------------
    // Settings management
    // -----------------------------------------------------------------------

    /**
     * Persists the integration settings and invalidates the cached API key validation.
     *
     * @param bool   $is_enabled Whether the integration should be enabled.
     * @param string $api_key    Akismet API key (whitespace-trimmed before storage).
     * @param bool   $enable_log Whether the activity log should be enabled.
     * @param bool   $test_mode  Whether the test mode should be enabled.
     * @return void
     */
    public function save_settings( bool $is_enabled, string $api_key, bool $enable_log, bool $test_mode ): void {
        $api_key = trim( $api_key );
        delete_transient( 'cff_akismet_key_valid_' . md5( $api_key ) ); // Clear cached validation
        update_option( self::OPTION_KEY, $api_key );
        update_option( self::LOG_OPTION, $enable_log );
        update_option( self::ENABLE_OPTION, $is_enabled );
        update_option( self::TEST_MODE_OPTION, $test_mode );
    }

    /**
     * Returns a localized, color-coded HTML status indicator for the configured API key.
     * Returns an empty string when no key is configured.
     *
     * @return string HTML fragment, or empty string when no key is set.
     */
    public function api_key_status(): string {
        $api_key = self::get_api_key();
        if ( empty( $api_key ) ) { return ''; } // No key configured
        return $this->verify_key( $api_key )
                ? ' <span style="color:green;font-weight:bold;">(' . esc_html__( 'Valid API key', 'calculated-fields-form' ) . ')</span>'
                : ' <span style="color:red;font-weight:bold;">(' . esc_html__( 'Invalid API key', 'calculated-fields-form' ) . ')</span>';
    }

    // -----------------------------------------------------------------------
    // API: Verify API Key
    // -----------------------------------------------------------------------

    /**
     * Verifies that the API key is valid against Akismet.
     * The result is cached for 1 hour to avoid redundant remote calls.
     *
     * @param  string $api_key 12-character Akismet API key.
     * @return bool            True if the key is valid, false otherwise.
     */
    private function verify_key(string $api_key): bool
    {
        if (
            empty($api_key) ||
            ! preg_match('/^[a-zA-Z0-9]{12}$/', $api_key)
        ) {
            return false;
        }

        $api_key_validation = get_transient('cff_akismet_key_valid_' . md5($api_key));
        if (false !== $api_key_validation) {
            return $api_key_validation;
        }

        $blog_url = get_site_url();

        $response = wp_remote_post(self::API_BASE . '/verify-key', [
            'timeout' => self::TIMEOUT,
            'body'    => [
                'key'  => $api_key,
                'blog' => $blog_url,
            ],
        ]);

        if (is_wp_error($response)) {
            $this->log('verify-key error: ' . $response->get_error_message(), 'error');
            return false;
        }

        $is_valid = wp_remote_retrieve_body($response) === 'valid';
        set_transient('cff_akismet_key_valid_' . md5($api_key), $is_valid, HOUR_IN_SECONDS); // Cache for 1 hour

        return $is_valid;
    }

    // -----------------------------------------------------------------------
    // API: Build submission payload
    // -----------------------------------------------------------------------

    /**
     * Builds the payload for the Akismet API from the form data.
     *
     * @param  string $summary Aggregated form submission text (sent as comment_content).
     * @return array           Akismet comment-check payload.
     */
    private function build_submission_payload(string $summary): array
    {
        $submission = [
            // Site and visitor information (required)
            'blog'                 => get_site_url(),
            'user_ip'              => $this->get_client_ip(),
            'user_agent'           => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'referrer'             => sanitize_url($_SERVER['HTTP_REFERER'] ?? ''),
            'comment_content'      => $summary,
        ];
        if ( self::is_test_mode_enabled() ) {
            $submission['is_test'] = '1';
        }
        return $submission;
    }

    // -----------------------------------------------------------------------
    // API: Client IP (proxy-aware)
    // -----------------------------------------------------------------------

    /**
     * Returns the real client IP, taking common proxies into account.
     *
     * By default only REMOTE_ADDR is trusted to prevent IP spoofing.
     * Define CFF_AKISMET_TRUST_PROXY as true in wp-config.php to
     * trust CF/X-Forwarded-For/X-Real-IP headers (e.g. behind Cloudflare).
     * Falls back to 127.0.0.1 when no valid IP can be determined.
     *
     * @return string Client IP address.
     */
    private function get_client_ip(): string {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $remote = filter_var( $remote, FILTER_VALIDATE_IP ) ? $remote : '127.0.0.1';

        // Trust proxy headers only when explicitly enabled.
        if ( ! defined( 'CFF_AKISMET_TRUST_PROXY' ) || ! CFF_AKISMET_TRUST_PROXY ) {
            return $remote;
        }

        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Proxies / load balancers
            'HTTP_X_REAL_IP',        // Nginx
        ];

        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                // HTTP_X_FORWARDED_FOR can contain several IPs separated by comma
                $ip = trim( explode( ',', $_SERVER[ $header ] )[0] );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return $remote;
    }

    // -----------------------------------------------------------------------
    // API: Check if a submission is spam
    // -----------------------------------------------------------------------

    /**
     * Queries the Akismet API and returns the check result.
     *
     * @param  array  $submission Akismet comment-check payload (see build_submission_payload).
     * @return string             One of the RESULT_* constants: ham, spam, discard, or unknown.
     */
    public function check( array $submission ): string {
        $api_key = self::get_api_key();

        if ( ! $this->verify_key( $api_key ) ) {
            return self::RESULT_UNKNOWN;
        }

        $endpoint = sprintf( self::ENDPOINT, $api_key ) . 'comment-check';

        $response = wp_remote_post( $endpoint, [
            'timeout' => self::TIMEOUT,
            'body'    => $submission,
        ] );

        if ( is_wp_error( $response ) ) {
            $this->log( 'Akismet spam-check error: ' . $response->get_error_message(), 'error' );
            return self::RESULT_UNKNOWN;
        }

        $body    = wp_remote_retrieve_body( $response );
        $headers = wp_remote_retrieve_headers( $response );

        if ( $body === 'true' ) {
            // The pro-tip: discard header indicates high-confidence spam
            $pro_tip = $headers['x-akismet-pro-tip'] ?? '';
            return ( $pro_tip === 'discard' ) ? self::RESULT_DISCARD : self::RESULT_SPAM;
        }

        return self::RESULT_HAM;
    }

    // -----------------------------------------------------------------------
    // Main submit handler
    // -----------------------------------------------------------------------

    /**
     * Runs before CFF processes a submission.
     * Stops execution if the submission is spam.
     *
     * @param  array  $submission_data     Submission payload (modified in place: spam_status, aborting_submission).
     * @param  string $submission_summary  Aggregated form data sent to Akismet as comment_content.
     * @param  array  $form_fields         Original form field definitions (unused; kept for the hook signature).
     * @return void
     */
    public function handle_submission( array &$submission_data, string &$submission_summary, array $form_fields ): void {

        if ( ! $this->is_configured() ) {
            return; // No API key configured or Akismet not enabled, do nothing
        }

        $submission = $this->build_submission_payload( $submission_summary );

        // Populate the other fields from the submission data if available
        foreach ( $submission_data as $key => $value ) {
            if (
                ! empty( $value ) &&
                isset( $form_fields[ $key ] ) &&
                isset( $form_fields[$key]->ftype ) &&
                in_array( strtolower( $form_fields[$key]->ftype ), [ 'femail', 'femailds' ] )
            ) {

                $submission['comment_author_email'] = $value;
                break; // Only take the first email field found
            }
        }

        $result     = $this->check( $submission );
        $form_id   = $submission_data['formid'] ?? 0;
        $submission_id = $submission_data['itemnumber'] ?? 0;

        $this->log( "Form #{$form_id} → Akismet result: {$result}" );

        switch ( $result ) {
            case self::RESULT_DISCARD:
                /*
                 * Very obvious spam (bots).
                 */
                do_action( 'cff_akismet_discarded', $form_id, $submission_data );
                $submission_data['spam_status'] = self::RESULT_DISCARD;
                $submission_data['aborting_submission'] = true; // Flag to abort CFF processing
                return;

            case self::RESULT_SPAM:
                /*
                 * Probable spam.
                 */
                do_action( 'cff_akismet_spam_detected', $form_id, $submission_data );
                $submission_data['spam_status'] = self::RESULT_SPAM;
                return;

            case self::RESULT_UNKNOWN:
                /*
                 * Akismet unavailable (network, API down...).
                 * By default we let it through and log the incident.
                 * Change exit to return if you prefer to block when in doubt.
                 */
                $this->log( "Akismet unavailable for form #{$form_id}, submission #{$submission_id}. Allowing submission.", 'warning' );
                break;

            case self::RESULT_HAM:
                $submission_data['spam_status'] = self::RESULT_HAM;
                break;
        }
    }

    // -----------------------------------------------------------------------
    // Admin UI: notification subject
    // -----------------------------------------------------------------------

    /**
     * Hook callback that prefixes the notification email subject with "[POSSIBLE SPAM]"
     * when the linked submission was flagged as spam by Akismet.
     *
     * @param  string $subject       Original email subject.
     * @param  array  $submission_data Submission data.
     * @return string                Possibly prefixed subject.
     */
    public function mark_email_as_spam( string $subject, array $submission_data ): string {
        if (
            isset( $submission_data['spam_status'] ) &&
            $submission_data['spam_status'] === self::RESULT_SPAM
        ) {
            $subject = esc_html__( '[POSSIBLE SPAM]', 'calculated-fields-form' ) . ' ' . $subject;
        }

        return $subject;
    }

    // -----------------------------------------------------------------------
    // Logging
    // -----------------------------------------------------------------------

    /**
     * Logs a message to the PHP error log if logging is enabled.
     *
     * @param string $message Message to log.
     * @param string $level   'info' | 'warning' | 'error'.
     * @return void
     */
    private function log( string $message, string $level = 'info' ): void {
        if ( ! self::is_activity_log_enabled() ) {
            return;
        }

        $prefix = strtoupper( $level );
        error_log( "[CFF Akismet] [{$prefix}] {$message}" );
    }

} // end class CPCFF_Akismet

endif;

// ---------------------------------------------------------------------------
// Initialization
// ---------------------------------------------------------------------------

// Start on 'plugins_loaded' to ensure WP is fully loaded
add_action( 'plugins_loaded', function (): void {
    CPCFF_Akismet::get_instance();
} );
