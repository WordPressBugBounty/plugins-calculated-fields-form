<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CPCFF_AGENT_FORM_BOOTSTRAP_ABILITY' ) ) :

	class CPCFF_AGENT_FORM_BOOTSTRAP_ABILITY {

		const ID = 'cff/ai-form-bootstrap';

		public static function check_permission( $input = null ) {
			if ( ! current_user_can( apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ) ) ) {
				return new WP_Error(
					'cff/unauthorized',
					__( 'You do not have permission to generate CFF forms via the agent API.', 'calculated-fields-form' ),
					array( 'status' => 403 )
				);
			}
			return true;
		}

		public static function execute( $input = null ) {
			return array(
				'prompt'      => CPCFF_AGENT_FORM_PROMPT::get_template(),
				'schema_url'  => plugins_url( 'js/schema.min.json', defined( 'CP_CALCULATEDFIELDSF_BASE_PATH' ) ? CP_CALCULATEDFIELDSF_BASE_PATH . '/cp_calculatedfieldsf_platinum.php' : __DIR__ . '/../cp_calculatedfieldsf_platinum.php' ),
				'instructions' => CPCFF_AGENT_FORM_PROMPT::get_instructions(),
			);
		}
	}

endif;

if ( ! class_exists( 'CPCFF_AGENT_FORM_ISSUE_PREVIEW_URL_ABILITY' ) ) :

	class CPCFF_AGENT_FORM_ISSUE_PREVIEW_URL_ABILITY {

		const ID = 'cff/issue-preview-url';
		const TRANSIENT_PREFIX = 'cff_agent_form_preview_';
		const DEFAULT_TTL      = 3600; // 1 hour

		public static function check_permission( $input = null ) {
			if ( ! current_user_can( apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ) ) ) {
				return new WP_Error(
					'cff/unauthorized',
					__( 'You do not have permission to issue CFF form preview URLs.', 'calculated-fields-form' ),
					array( 'status' => 403 )
				);
			}
			return true;
		}

		public static function execute( $input = null ) {
			if ( empty( $input['structure'] ) || ! is_string( $input['structure'] ) ) {
				return new WP_Error(
					'cff/invalid-input',
					__( 'A form structure is required.', 'calculated-fields-form' ),
					array( 'status' => 400 )
				);
			}

			$raw_name  = is_string( $input['form_name'] ?? null ) ? $input['form_name'] : '';
			$form_name = sanitize_text_field( wp_unslash( $raw_name ) );

			$token = strtolower( wp_generate_password( 32, false ) );
			$ttl   = (int) apply_filters( 'cpcff_agent_form_preview_ttl', self::DEFAULT_TTL );
			set_transient(
				self::TRANSIENT_PREFIX . $token,
				array(
					'structure' => (string) $input['structure'],
					'form_name' => $form_name,
				),
				$ttl
			);

			return array(
				'preview_url' => admin_url( 'admin.php?page=cff-agent-preview&token=' . $token ),
				'expires_in'  => $ttl,
			);
		}
	}

endif;
