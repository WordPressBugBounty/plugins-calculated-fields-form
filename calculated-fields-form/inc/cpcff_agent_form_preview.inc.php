<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CPCFF_AGENT_FORM_PREVIEW' ) ) :

	class CPCFF_AGENT_FORM_PREVIEW {

		const SAVE_ACTION  = 'cff_agent_save';
		const PREVIEW_SLUG = 'cff-agent-preview';

		private static function require_capability() {
			if ( ! current_user_can( apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ) ) ) {
				status_header( 403 );
				wp_die( esc_html__( 'You do not have permission to use this feature.', 'calculated-fields-form' ), '', array( 'response' => 403 ) );
			}
		}

		private static function load_from_transient( $token ) {
			$token = is_string( $token ) ? strtolower( $token ) : $token;
			if ( ! is_string( $token ) || ! preg_match( '/^[a-z0-9]{32}$/', $token ) ) {
				return null;
			}
			$data = get_transient( CPCFF_AGENT_FORM_ISSUE_PREVIEW_URL_ABILITY::TRANSIENT_PREFIX . $token );
			if ( ! is_array( $data ) || empty( $data['structure'] ) ) {
				return null;
			}
			return $data;
		}

		private static function read_structure_from_transient_data( $data ) {
			$structure = json_decode( $data['structure'], true );
			if ( ! is_array( $structure ) || empty( $structure[0] ) || ! is_array( $structure[0] ) || empty( $structure[1][0] ) ) {
				return null;
			}
			return $structure;
		}

		private static function ensure_formtemplate( &$structure ) {
			if ( ( is_object( $structure[1][0] ) && ! isset( $structure[1][0]->formtemplate ) )
				|| ( is_array( $structure[1][0] ) && ! isset( $structure[1][0]['formtemplate'] ) ) ) {
				if ( defined( 'CP_CALCULATEDFIELDSF_DEFAULT_template' ) ) {
					$default = get_option( 'CP_CALCULATEDFIELDSF_DEFAULT_template', CP_CALCULATEDFIELDSF_DEFAULT_template );
					if ( is_object( $structure[1][0] ) ) {
						$structure[1][0]->formtemplate = $default;
					} else {
						$structure[1][0]['formtemplate'] = $default;
					}
				} else {
					error_log( '[CFF] ensure_formtemplate: CP_CALCULATEDFIELDSF_DEFAULT_template is not defined. The form will be created without a template. The admin should configure the default template in the CFF plugin settings.' );
				}
			}
		}

		public static function build_preview_html( $structure, $form_name, $token ) {
			$json_structure = wp_json_encode( $structure );
			try {
				$preview_html = '<style>#codepeople-review-banner{display:none !important}</style>' . CPCFF_MAIN::instance()->no_form_preview( $json_structure );
			} catch ( Exception $err ) {
				status_header( 400 );
				wp_die( esc_html__( 'Invalid form structure.', 'calculated-fields-form' ), '', array( 'response' => 400 ) );
			}
			$cancel_url     = admin_url( 'admin.php?page=cp_calculated_fields_form' );

			ob_start();
			?>
			<!DOCTYPE html>
			<html>
			<head>
				<meta charset="utf-8">
				<title><?php esc_html_e( 'CFF Form Preview', 'calculated-fields-form' ); ?></title>
				<link rel="stylesheet" href="<?php echo esc_url( includes_url( 'css/buttons.css' ) ); ?>">
				<style>
					html, body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
					body.cff-agent-preview { background: #f0f0f1; }
					.cff-agent-preview-wrapper {
						margin-left: 5%;
						width: 90%;
						margin-top: 70px;
						margin-bottom: 20px;
						overflow-x: auto;
					}
					.cff-agent-preview-content {
						padding: 20px;
						background: #fff;
						border-radius: 4px;
						box-shadow: 0 1px 3px rgba(0,0,0,0.05);
					}
					.cff-agent-frame {
						position: fixed;
						top: 0; left: 0; right: 0;
						z-index: 99999;
						background: #1d2327;
						color: #fff;
						padding: 12px 20px;
						display: flex;
						justify-content: space-between;
						align-items: center;
						box-shadow: 0 2px 8px rgba(0,0,0,0.3);
					}
					.cff-agent-frame-info strong { font-size: 14px; margin-right: 12px; }
					.cff-agent-frame-info span { font-size: 12px; opacity: 0.85; }
					.cff-agent-frame-actions { display: flex; gap: 8px; }
				</style>
			</head>
			<body class="cff-agent-preview">
				<div class="cff-agent-preview-wrapper">
					<div class="cff-agent-preview-content">
						<?php echo $preview_html; ?>
					</div>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cff-agent-frame">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>">
					<input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">
					<?php wp_nonce_field( 'cff_agent_save', '_cpcff_nonce' ); ?>
					<div class="cff-agent-frame-info">
						<strong><?php echo esc_html( $form_name ); ?></strong>
						<span><?php esc_html_e( 'Preview from external agent', 'calculated-fields-form' ); ?></span>
					</div>
					<div class="wp-core-ui cff-agent-frame-actions">
						<a href="<?php echo esc_url( $cancel_url ); ?>" class="button-secondary" style="color: white; border-color: white;"><?php esc_html_e( 'Cancel', 'calculated-fields-form' ); ?></a>
						<button type="submit" class="button-primary"><?php esc_html_e( 'Use this form', 'calculated-fields-form' ); ?></button>
					</div>
				</form>
			</body>
			</html>
			<?php
			return ob_get_clean();
		}

		public static function render_preview() {
			self::require_capability();

			if ( empty( $_GET['token'] ) ) {
				status_header( 400 );
				wp_die( esc_html__( 'Missing token.', 'calculated-fields-form' ), '', array( 'response' => 400 ) );
			}

			$raw_token = wp_unslash( $_GET['token'] );
			$token     = is_string( $raw_token ) ? sanitize_text_field( $raw_token ) : '';
			if ( '' === $token || ! preg_match( '/^[a-z0-9]{32}$/', $token ) ) {
				status_header( 400 );
				wp_die( esc_html__( 'Invalid token.', 'calculated-fields-form' ), '', array( 'response' => 400 ) );
			}
			$data = self::load_from_transient( $token );
			if ( null === $data ) {
				status_header( 404 );
				wp_die( esc_html__( 'Preview not found or expired. Ask your agent to issue a fresh one.', 'calculated-fields-form' ), '', array( 'response' => 404 ) );
			}

			// Renew the TTL while the admin is reviewing the preview so the token
			// does not expire underneath them. save_form() will delete it on use.
			$ttl = (int) apply_filters( 'cpcff_agent_form_preview_ttl', CPCFF_AGENT_FORM_ISSUE_PREVIEW_URL_ABILITY::DEFAULT_TTL );
			set_transient( CPCFF_AGENT_FORM_ISSUE_PREVIEW_URL_ABILITY::TRANSIENT_PREFIX . $token, $data, $ttl );

			$structure = self::read_structure_from_transient_data( $data );
			if ( null === $structure ) {
				status_header( 400 );
				wp_die( esc_html__( 'Invalid form structure.', 'calculated-fields-form' ), '', array( 'response' => 400 ) );
			}

			$structure = CPCFF_FORM::sanitize_structure( $structure );
			self::ensure_formtemplate( $structure );

			status_header( 200 );
			echo self::build_preview_html( $structure, $data['form_name'], $token );
			exit;
		}

		public static function save_form() {
			self::require_capability();
			check_admin_referer( 'cff_agent_save', '_cpcff_nonce' );

			if ( empty( $_POST['token'] ) ) {
				status_header( 400 );
				wp_die( esc_html__( 'Missing token.', 'calculated-fields-form' ), '', array( 'response' => 400 ) );
			}

			$raw_token = wp_unslash( $_POST['token'] );
			$token = is_string($raw_token) ? sanitize_text_field( $raw_token ) : '';
			if ( '' === $token || null === ( $data = self::load_from_transient( $token ) ) ) {
				status_header( 404 );
				wp_die( esc_html__( 'Preview not found or expired. Ask your agent to issue a fresh one.', 'calculated-fields-form' ), '', array( 'response' => 404 ) );
			}

			$structure = self::read_structure_from_transient_data( $data );
			if ( null === $structure || ! isset( $structure[0], $structure[1] ) || ! is_array( $structure[0] ) || ! is_array( $structure[1] ) ) {
				status_header( 400 );
				wp_die( esc_html__( 'Invalid form structure.', 'calculated-fields-form' ), '', array( 'response' => 400 ) );
			}

			$form_name = ! empty( $data['form_name'] ) ? $data['form_name'] : __( 'Untitled form', 'calculated-fields-form' );

			$structure = CPCFF_FORM::sanitize_structure( $structure );
			self::ensure_formtemplate( $structure );

			$json_structure = wp_json_encode( $structure );

			// Create the form directly so the redirect lands on a real form_id the
			// form builder can edit. ai=1 / ftpl=ai-generator triggered the AI
			// generator UI instead of the form fields — we need a real form in the
			// database so the form builder shows the agent's structure for review.
			$created = CPCFF_FORM::create_default( $form_name, '', $json_structure );
			if ( ! $created ) {
				status_header( 500 );
				wp_die( esc_html__( 'Failed to create the form.', 'calculated-fields-form' ), '', array( 'response' => 500 ) );
			}

			global $wpdb;
			$form_id = (int) $wpdb->insert_id;
			if ( ! $form_id && method_exists( $created, 'get_id' ) ) {
				$form_id = (int) $created->get_id();
			}
			if ( ! $form_id ) {
				status_header( 500 );
				wp_die( esc_html__( 'Failed to create the form.', 'calculated-fields-form' ), '', array( 'response' => 500 ) );
			}

			// Consume the preview transient so the same token cannot be reused.
			delete_transient( CPCFF_AGENT_FORM_ISSUE_PREVIEW_URL_ABILITY::TRANSIENT_PREFIX . $token );

			// Redirect into the form builder with the new form_id so the admin can
			// review and edit. Matches the edit-form URL pattern (page + cal = form_id).
			$redirect_url = add_query_arg(
				array(
					'page' => 'cp_calculated_fields_form',
					'cal'  => $form_id,
					'_cpcff_nonce' => wp_create_nonce('cff-form-settings'),
				),
				admin_url( 'admin.php' )
			);

			wp_redirect( $redirect_url );
			exit;
		}
	}

endif;

add_action( 'admin_menu', function () {
	add_submenu_page(
		null,
		__( 'CFF - Agent Preview', 'calculated-fields-form' ),
		'',
		apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ),
		CPCFF_AGENT_FORM_PREVIEW::PREVIEW_SLUG,
		array( 'CPCFF_AGENT_FORM_PREVIEW', 'render_preview' )
	);
} );

add_action( 'admin_init', function () {
	if ( ! isset( $_GET['page'] ) || CPCFF_AGENT_FORM_PREVIEW::PREVIEW_SLUG !== $_GET['page'] ) {
		return;
	}
	CPCFF_AGENT_FORM_PREVIEW::render_preview();
	exit;
}, 1 );

add_action( 'admin_post_' . CPCFF_AGENT_FORM_PREVIEW::SAVE_ACTION, array( 'CPCFF_AGENT_FORM_PREVIEW', 'save_form' ) );
