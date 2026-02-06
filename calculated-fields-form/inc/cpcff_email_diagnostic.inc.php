<?php
if (! defined('ABSPATH')) {
    print 'Unauthorized access.';
    exit;
}

if (! class_exists('CPCFF_EMAIL_DIAGNOSTICS')) {
    class CPCFF_EMAIL_DIAGNOSTICS
    {

        private static $wp_mail_error = null;

        public static function init()
        {
            add_action('wp_ajax_cff_run_email_diagnostics', [self::class, 'run_diagnostics']);
        }

        public static function error_handler($wp_error)
        {
            // Store the error in a class property
            self::$wp_mail_error = $wp_error;
        }

        public static function show_diagnostic_form($from_field_address_selector = '')
        {
            $from_field_address_selector = json_encode($from_field_address_selector);
?>
            <div class="cff-diagnostic-dialog" style="border:1px solid #F0AD4E;background:white;padding:10px;color:#3c434a;margin-bottom:20px;box-sizing:border-box;">
                <div class="cff-diagnostic-dialog-title"><span style="font-size:120%;">&#9993;</span>&nbsp;<span style="font-weight:600;"><?php esc_html_e('Email Delivery Diagnostics (Send a Test Email)', 'calculated-fields-form'); ?></span></div>
                <div class="cff-diagnostic-dialog-content">
                    <p style="margin-top:10px;font-style:italic;"><?php esc_html_e('Enter a valid email address to send a test email and ensure your email configuration is functioning as expected.', 'calculated-fields-form'); ?></p>
                    <div style="display:flex;gap:10px;margin-top:10px;align-items:center;">
                        <input type="text" id="cff_diagnostic_from_address" name="cff_diagnostic_from_address" placeholder="<?php esc_attr_e('From Address', 'calculated-fields-form'); ?>" style="flex-grow:1" />
                        <input type="text" id="cff_diagnostic_to_address" name="cff_diagnostic_to_address" placeholder="<?php esc_attr_e('To Address', 'calculated-fields-form'); ?>" style="flex-grow:1" />
                        <button type="button" id="cff_run_email_diagnostics" class="button button-primary"><?php esc_html_e('Run Diagnostics', 'calculated-fields-form'); ?></button>
                    </div>
                    <div id="cff_email_diagnostics_result" style="margin-top:10px;"></div>
                </div>
            </div>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    if (<?php echo $from_field_address_selector; ?> !== '') {
                        var from_diagnostic_address = $('#cff_diagnostic_from_address').val();
                        if (from_diagnostic_address === '') {
                            var from_address = $(<?php echo $from_field_address_selector; ?>).val();
                            $('#cff_diagnostic_from_address').val(from_address);
                        }
                    }
                    $('#cff_run_email_diagnostics').on('click', function() {
                        var from_address = $('#cff_diagnostic_from_address').val();
                        var to_address = $('#cff_diagnostic_to_address').val();

                        if (!from_address || !to_address) {
                            alert('<?php echo esc_js(__('Please enter both From and To email addresses.', 'calculated-fields-form')); ?>');
                            return;
                        }

                        $('#cff_email_diagnostics_result').html('<div class="cff-processing-form"></div>');

                        var data = {
                            action: 'cff_run_email_diagnostics',
                            from_address: from_address,
                            to_address: to_address,
                            security: '<?php echo esc_js(wp_create_nonce('cff_email_diagnostics_nonce')); ?>'
                        };

                        $.post("<?php echo esc_js(admin_url('admin-ajax.php')); ?>", data, function(response) {
                            if (response.success) {
                                if (response.data.email_sent) {
                                    $('#cff_email_diagnostics_result').html('<span style="color:green;"><?php echo esc_js(__('Email sent successfully!', 'calculated-fields-form')); ?></span>');
                                } else if (response.data.error) {
                                    $('#cff_email_diagnostics_result').html('<span style="color:red;"><?php echo esc_js(__('Email sending failed.', 'calculated-fields-form')); ?> ' + response.data.error + '</span>');
                                }
                            } else {
                                $('#cff_email_diagnostics_result').html('<span style="color:red;"><?php echo esc_js(__('An error occurred during diagnostics.', 'calculated-fields-form')); ?> ' + response?.data?.error || '' + '</span>');
                            }
                        });
                    });
                });
            </script>
<?php
        }

        public static function run_diagnostics()
        {
            // Verify nonce and permissions
            check_ajax_referer('cff_email_diagnostics_nonce', 'security');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['error' => __('Unauthorized access.', 'calculated-fields-form')], 403);
            }

            // Validate and sanitize input
            $from_address = isset($_POST['from_address']) ? sanitize_email(wp_unslash($_POST['from_address'])) : '';
            $to_address = isset($_POST['to_address']) ? sanitize_email(wp_unslash($_POST['to_address'])) : '';

            if (empty($from_address) || empty($to_address)) {
                wp_send_json_error(['error' => __('From/to valid email addresses are required for email diagnostics.', 'calculated-fields-form')]);
            }

            // Test email sending
            $subject = __('CPCFF Email Diagnostics Test', 'calculated-fields-form');
            $message = __('This is a test email sent from the Calculated Fields Form plugin to verify email functionality.', 'calculated-fields-form');
            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_address
            ];

            self::$wp_mail_error = null;
            add_action('wp_mail_failed', [self::class, 'error_handler']);

            try {
                if (wp_mail($to_address, $subject, $message, $headers)) {
                    wp_send_json_success(['email_sent' => true]);
                } else {
                    $error = __('Email sending failed.', 'calculated-fields-form');
                    if (self::$wp_mail_error && is_wp_error(self::$wp_mail_error)) {
                        $error .= ' ' . self::$wp_mail_error->get_error_message();
                    }
                    throw new Exception($error);
                }
            } catch (Throwable $e) {
                wp_send_json_error(['error' => $e->getMessage()]);
            } finally {
                remove_action('wp_mail_failed', [self::class, 'error_handler']);
            }
        }
    }

    CPCFF_EMAIL_DIAGNOSTICS::init();
}
