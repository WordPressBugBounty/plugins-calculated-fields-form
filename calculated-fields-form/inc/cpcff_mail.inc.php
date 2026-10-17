<?php
/**
 * CPCFF_MAIL process and sends the notification and confirmation emails
 *
 * @package CFF.
 * @since 5.0.216 (PRO), 5.0.257 (DEV), 10.0.288 (PLA)
 */

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeEnd
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTagSquiz.Commenting.FunctionComment.MissingParamTag

if ( ! class_exists( 'CPCFF_MAIL' ) ) {
	/**
	 * Class that sends the notification emails processing first the emails and subjects
	 *
	 * @since 5.0.215 (PRO), 5.0.256 (DEV), 10.0.287 (PLA)
	 */
	class CPCFF_MAIL {

		private $_encoding;

		public function __construct() {
			$this->_encoding = get_option( 'CP_CALCULATEDFIELDSF_ENCODING_EMAIL', false );
			add_action( 'wp_mail_failed', array( $this, 'debug_email' ) );
		} // End __construct.

		private function _default_if_empty( $message, $default ) {
			$processed = preg_replace( '/[\t\s\n\r]/', '', $message );
			return ! empty( $processed ) ? $message : $default;
		} // End _default_if_empty.

		private function _fix_encoding( $str, $base64 = true ) {
			if ( $this->_encoding ) {
				$str = mb_convert_encoding( $str, 'ISO-8859-2' );
				if ( $base64 ) {
					$str = chunk_split( base64_encode( $str ) );
				}
			}
			return $str;
		} // End _fix_encoding.

		private function _modify_encoding_header( $headers ) {
			if ( $this->_encoding ) {
				$headers[] = 'Content-Transfer-Encoding: base64';
			}
			return $headers;
		} // End _modify_encoding_header.

		private function _attachment_url_to_path( $url ) {
			$upload_directory = wp_get_upload_dir();
			if ( false == $upload_directory['error'] ) {
				$path     = str_ireplace( $upload_directory['baseurl'], $upload_directory['basedir'], $url );
				$path     = realpath( $path );
				$path     = str_replace( '\\', '/', $path );
				$base_dir = str_replace( '\\', '/', $upload_directory['basedir'] );

				if ( stripos( $path, $base_dir ) === 0 && file_exists( $path ) ) {
					// Get file info.
					$filetype = wp_check_filetype( basename( $path ), null );

					// Excluding dangerous files.
					if (
						false == $filetype['ext'] ||
						in_array( $filetype['ext'], array( 'php', 'asp', 'aspx', 'cgi', 'pl', 'perl', 'exe' ) )
					) {
						return false;
					}

					return $path;
				}
			}
			return false;
		} // End _attachment_url_to_path.

		/**
		 * Creates an entry in the error logs if there is an error sending emails
		 */
		public function debug_email( $error ) {
			error_log( $error->get_error_message() );
		} // End debug_email.

		/**
		 * Send the notification emails to the email address entered through the form's settings
		 *
		 * @return boolean.
		 */
		public function send_notification_email( $submission_id ) {

			$submission_obj = CPCFF_SUBMISSIONS::get( $submission_id );
			if ( empty( $submission_obj ) ) {
				return false;
			}

			$form_obj = CPCFF_SUBMISSIONS::get_form( $submission_id );
			if ( empty( $form_obj ) ) {
				return false;
			}

			$send_notification_email = apply_filters(
                'cpcff_send_notification_email',
                true,
                $submission_obj,
                $form_obj
            );
			if ( ! $send_notification_email ) {
				return false;
			}

			// Checks if the notification email includes a content.
			$email_message = $this->_default_if_empty( $form_obj->get_option( 'fp_message', CP_CALCULATEDFIELDSF_DEFAULT_fp_message ), CP_CALCULATEDFIELDSF_DEFAULT_fp_message );

			$email_format = $form_obj->get_option( 'fp_emailformat', CP_CALCULATEDFIELDSF_DEFAULT_email_format );

			$email_data = CPCFF_SUBMISSIONS::parsing_fields_on_text(
				$email_message,
				$form_obj->get_option('fp_emailformat', CP_CALCULATEDFIELDSF_DEFAULT_email_format),
				$submission_id
			);

			if ( 'true' == $form_obj->get_option( 'fp_inc_additional_info', CP_CALCULATEDFIELDSF_DEFAULT_fp_inc_additional_info ) ) {
				$chln = "\n";
				if ( 'html' == $email_format ) {
					$chln = '<br>';
				}

				$basic_data          = 'IP: ' . $submission_obj->ipaddr . "{$chln}Server Time:  " . gmdate( 'Y-m-d H:i:s' ) . $chln;
				$email_data['text'] .= "{$chln}ADDITIONAL INFORMATION{$chln}*********************************{$chln}" . $basic_data;
			}

			$subject = $form_obj->get_option( 'fp_subject', CP_CALCULATEDFIELDSF_DEFAULT_fp_subject );
			$subject = CPCFF_SUBMISSIONS::parsing_fields_on_text(
				$subject,
				'plain text',
				$submission_id
			);

			$to = CPCFF_SUBMISSIONS::parsing_fields_on_text(
				preg_replace("/(fieldname\d+)\s*%>/i", "$1_value%>", $form_obj->get_option('fp_destination_emails', CP_CALCULATEDFIELDSF_DEFAULT_fp_destination_emails)),
				'plain text',
				$submission_id
			);
			$to = explode( ',', trim( $to['text'] ) );

			$from = CPCFF_SUBMISSIONS::parsing_fields_on_text(
				preg_replace('/(fieldname\d+)\s*%>/i', '$1_value%>', $form_obj->get_option( 'fp_from_email', CP_CALCULATEDFIELDSF_DEFAULT_fp_from_email)),
				'plain text',
				$submission_id
			);
			$from = trim( $from['text'] );
			if ( ! empty( $from ) && strpos( $from, '>' ) === false ) {
				$from = '"' . $from . '" <' . $from . '>';
			}

			if ( ! $form_obj->get_option( 'fp_inc_attachments', 0 ) || empty($email_data['files']) ) {
				$email_data['files'] = array();
			}

			$subject['text']    = self::_fix_encoding( $subject['text'], false );
			$email_data['text'] = self::_fix_encoding( $email_data['text'] );

			$headers = array(
				'Content-Type: text/' . ( 'html' == $email_format ? 'html' : 'plain' ) . '; charset=utf-8',
				'X-Mailer: PHP/' . phpversion(),
			);
			if ( ! empty( $from ) ) {
				$headers[] = "From: {$from}";
			}

			try {
				$replyto = CPCFF_SUBMISSIONS::parsing_fields_on_text(
					preg_replace("/(fieldname\d+)\s*%>/i", "$1_value%>", $form_obj->get_option( 'fp_reply_to_emails', '' )),
					'plain text',
					$submission_id
				)['text'];

				$replyto = explode( ',', preg_replace( ['/\s/', '/\,+/'], ['', ','], $replyto ) );
				$replyto = array_filter( $replyto, 'is_email' );
			} catch ( Exception $err ) {}

			if ( ! empty( $replyto ) ) {
                $headers[] = "Reply-To: " . implode( ',', $replyto );
			}

			$headers = self::_modify_encoding_header( $headers );

			// Static file attachment.
			$fp_attach_static = $form_obj->get_option( 'fp_attach_static', '' );
			if ( ! empty( $fp_attach_static ) && false != ( $static_file = $this->_attachment_url_to_path( $fp_attach_static ) ) ) {
				$email_data['files'][] = $static_file;
			}

			foreach ( $to as $email ) {
				$email = sanitize_email( $email );
				if ( ! empty( $email ) ) {
					try {
						wp_mail(
							$email,
							apply_filters('cpcff_notification_email_subject', $subject[ 'text' ], $submission_id),
							apply_filters('cpcff_notification_email_message', $email_data[ 'text' ], $submission_id),
							$headers,
							$email_data['files']
						);
					} catch ( Throwable $mail_err ) {
						error_log( $mail_err->getMessage() );
					}
				}
			}
		} // End send_notification_email.
	} // End CPCFF_MAIL.
}
