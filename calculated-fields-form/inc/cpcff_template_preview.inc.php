<?php
/**
 * Template Preview Handler
 * 
 * Standalone endpoint for rendering form template previews in an iframe.
 * Loaded early via admin_init to ensure clean output without WordPress page rendering.
 * Pattern follows CPCFF_AI_FORM_GENERATOR::dispatch()
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('CPCFF_TEMPLATE_PREVIEW')) {
    class CPCFF_TEMPLATE_PREVIEW
    {
        public static function dispatch() {
            // Only act on template preview requests (same pattern as AI Generator)
            if (! isset($_GET['cff_template_preview'])) {
                return;
            }
            
            // Security check
            if (! current_user_can(apply_filters('cpcff_forms_edition_capability', 'manage_options'))) {
                wp_die(__('Access denied.', 'calculated-fields-form'));
            }
            
            // Remove all shutdown hooks to prevent any output after our response
            remove_all_actions('shutdown');
            
            // Verify nonce - required for security
            if (! isset($_GET['_cpcff_nonce']) || ! wp_verify_nonce($_GET['_cpcff_nonce'], 'cff-template-preview')) {
                wp_die(__('Security check failed.', 'calculated-fields-form'));
            }
            
            // Get template ID or form ID
            $ftpl = 0;
            $form_id = 0;
            
            if (isset($_GET['ftpl']) && is_numeric($_GET['ftpl'])) {
                $ftpl = intval($_GET['ftpl']);
            }
            
            if (isset($_GET['form_id']) && is_numeric($_GET['form_id'])) {
                $form_id = intval($_GET['form_id']);
            }
            
            // Validate we have at least one valid ID
            if ($ftpl <= 0 && $form_id <= 0) {
                echo esc_html__('Invalid request.', 'calculated-fields-form');
                exit;
            }
            
            // Fetch form structure
            $form_structure = '';
            
            if ($form_id > 0) {
                // Website form - get from local database
                if (! class_exists('CPCFF_FORM')) {
                    require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_form.inc.php';
                }
                
                // Verify form exists in database
                global $wpdb;
                $table = $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE;
                $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE id = %d", $form_id));
                
                if (! $exists) {
                    echo esc_html__('Form not found.', 'calculated-fields-form');
                    exit;
                }
                
                $form = new CPCFF_FORM($form_id);
                $form_structure = $form->get_option('form_structure', '');
                if (is_array($form_structure)) {
                    $form_structure = json_encode($form_structure);
                }
            } elseif ($ftpl > 0) {
                // Template - fetch from remote repository
                // Validate ftpl is a positive integer (already sanitized by intval)
                if ($ftpl <= 0) {
                    echo esc_html__('Invalid template ID.', 'calculated-fields-form');
                    exit;
                }
                
                $response = wp_remote_get(
                    'https://raw.githubusercontent.com/cffdwboostercom/formtemplates/main/' . $ftpl . '.cpfm',
                    ['sslverify' => false, 'timeout' => CP_CALCULATEDFIELDSF_TIMEOUT]
                );
                
                if (is_wp_error($response)) {
                    echo esc_html__('Error fetching template.', 'calculated-fields-form');
                    exit;
                }
                
                $body = wp_remote_retrieve_body($response);
                if (! empty($body)) {
                    $form_structure = $body;
                }
            }
            
            if (empty($form_structure)) {
                echo esc_html__('Form not found.', 'calculated-fields-form');
                exit;
            }
            
            // Render the form preview using the same approach as AI Generator
            // Load the main class if not already loaded
            if (! class_exists('CPCFF_MAIN')) {
                require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_main.inc.php';
            }
            
            $cpcff_main = CPCFF_MAIN::instance();
            
            // Use no_form_preview to generate the form HTML
            try {
                $preview_html = $cpcff_main->no_form_preview($form_structure);
                
                // Remove the review banner if present
                $preview_html = preg_replace('/<div id="codepeople-review-banner".*?<\/div>\s*<\/div>\s*/s', '', $preview_html);
                
                // Remove any wp-die wrappers or admin page chrome
                // Just output the clean form HTML
                echo $preview_html;
                
            } catch (Exception $e) {
                echo esc_html__('Error rendering preview.', 'calculated-fields-form');
            }
            
            exit;
        }
    }
    
    // Dispatch immediately - this runs on every admin_init call
    CPCFF_TEMPLATE_PREVIEW::dispatch();
}
