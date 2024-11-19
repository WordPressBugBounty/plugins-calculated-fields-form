<?php
if ( ! defined('WP_DEBUG') || true != WP_DEBUG ) {
	error_reporting(E_ERROR|E_PARSE);
}
add_action( 'init', 'cp_calculatedfieldsf_form_cache', 1 );

function cp_calculatedfieldsf_form_cache() {
	if (
		! empty( $_REQUEST['_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_nonce'] ) ), 'cff-client-side-auxilary-nonce' )
	) {
		if (
			! empty( $_REQUEST['cffaction'] ) &&
			'cff_register_height' == sanitize_text_field( wp_unslash( $_REQUEST['cffaction'] ) ) &&

			! empty( $_REQUEST['form_height'] ) &&
			is_numeric( $_REQUEST['form_height'] ) &&

			! empty( $_REQUEST['screen_width'] ) &&
			is_numeric( $_REQUEST['screen_width'] ) &&

			! empty( $_REQUEST['form'] ) &&
			is_numeric( $_REQUEST['form'] )
		) {
			global $wpdb;
			$table_name = $wpdb->prefix.CP_CALCULATEDFIELDSF_FORMS_TABLE;
			$form_id    = intval( $_REQUEST['form'] );

			if ( $result  = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . $table_name . '` WHERE id=%d', $form_id ), ARRAY_A ) ) {
				if ( ! array_key_exists( 'extra', $result ) ) {
					$wpdb->query( "ALTER TABLE  `" . $table_name . "` ADD `extra` LONGTEXT" );
				}

				if ( ! empty( $result['extra'] ) ) {
					try {
						$extra = json_decode( $result['extra'], true );
					} catch ( Exception $err ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) error_log( $err->getMessage() );
					}
				}

				if ( empty( $extra ) ) {
					$extra = array( 'form_height' => array() );
				}

				if ( empty( $extra['form_height'] ) ) {
					$extra['form_height'] = array(
						320 => 0,
						480 => 0,
						768 => 0,
						1024=> 0,
					);
				}

				$screen_width = intval( $_REQUEST['screen_width'] );
				$form_height  = intval( $_REQUEST['form_height'] );

				if ( $screen_width <= 480 ) {
					$extra['form_height'][320] = max( $extra['form_height'][320], $form_height );
				} elseif ( $screen_width <= 768 ) {
					$extra['form_height'][480] = max( $extra['form_height'][480], $form_height );
				} elseif ( $screen_width <= 1024 ) {
					$extra['form_height'][768] = max( $extra['form_height'][768], $form_height );
				} else {
					$extra['form_height'][1024] = max( $extra['form_height'][1024], $form_height );
				}

				$wpdb->update(
					$wpdb->prefix.CP_CALCULATEDFIELDSF_FORMS_TABLE,
					array( 'extra' => json_encode( $extra ) ),
					array( 'id' => $form_id ),
					array( '%s' ),
					array( '%d' )
				);
			}
			print 'ok';
			exit;
		}
	}
} // End cp_calculatedfieldsf_form_cache
