<?php
/*
....
*/
if (!defined('ABSPATH')) {
    exit;
}

if( class_exists( 'CPCFF_WidgetBase' ) )
{
    class CPCFF_Form_Widget extends CPCFF_WidgetBase
    {
        public function __construct()
        {
            parent::__construct();

            // Register admin AJAX action to get the forms list
            add_action( 'wp_ajax_cpcff_get_forms', array( $this, 'ajax_get_forms' ) );
            add_action( 'wp_ajax_cpcff_get_form_fields', array( $this, 'ajax_get_form_fields' ) );
        } // End __construct

        /**
         * AJAX handler for logged-in admin users.
         * Returns JSON list of form id/name pairs.
         */
        public function ajax_get_forms()
        {
            if ( ! current_user_can( apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ) ) ) {
                wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'calculated-fields-form' ) ) );
            }

            // Nonce check for admin AJAX requests
            check_ajax_referer( 'cff-form-widget', '_cpcff_nonce' );
            $exclude_form = isset( $_REQUEST['exclude_form'] ) && is_numeric( $_REQUEST['exclude_form'] ) ? intval( $_REQUEST['exclude_form'] ) : 0;
            $forms = CPCFF_FORM::forms_list( array( 'description' => false ) );
            $data = array();

            foreach ( $forms as $form ) {
                if ( $form->id == $exclude_form ) {
                    continue;
                }
                $data[] = array(
                    'id'   => intval( $form->id ),
                    'name' => sanitize_text_field( $form->form_name ),
                );
            }

            wp_send_json_success( $data );
        }

        /**
         * Returns field list for a form ID as an associative array (fieldName => fieldObject).
         * If form does not exist, returns empty array.
         *
         * @param int $form_id
         * @return array
         */
        public function get_form_fields( $form_id )
        {
            $form_id = intval( $form_id );
            if ( $form_id <= 0 ) {
                return array();
            }
            $form = new CPCFF_FORM( $form_id );
            $fields = $form->get_fields();
            return is_array( $fields ) ? $fields : array();
        }

        /**
         * AJAX handler for logged-in admin users.
         * Returns JSON list of field objects for the requested form ID.
         */
        public function ajax_get_form_fields()
        {
            if ( ! current_user_can( apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ) ) ) {
                wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'calculated-fields-form' ) ) );
            }

            check_ajax_referer('cff-form-widget', '_cpcff_nonce' );

            $form_id = isset( $_REQUEST['form_id'] ) && is_numeric( $_REQUEST['form_id'] ) ? intval( $_REQUEST['form_id'] ) : 0;
            $exclude_form = ! empty($_REQUEST['exclude_form']) && is_numeric($_REQUEST['exclude_form']) ? intval($_REQUEST['exclude_form']) : 0;

            if( $exclude_form == $form_id ) {
                wp_send_json_success( array() );
            }
            $fields = $this->get_form_fields( $form_id );

            wp_send_json_success( $fields );
        }

        protected function admin_scripts()
        {
            // Inject the URL with the nonce to get the forms list
            $url = add_query_arg(['_cpcff_nonce'  => wp_create_nonce('cff-form-widget')], admin_url('admin-ajax.php'));
            print 'var cpcff_form_widget_url = "' . esc_js( $url ) . '";';
            // Inject control scripts
            print file_get_contents(dirname(__FILE__) . '/assets/script/form.admin.js');
        } // End admin_scripts

        protected function admin_styles()
        {
            wp_enqueue_style('cpcff_form_widget_css', plugins_url('/assets/style/form.admin.css', __FILE__));
        }
	} // End Class

    if ( class_exists('CPCFF_WIDGETS_MANAGER') ) {
        CPCFF_WIDGETS_MANAGER::add( new CPCFF_Form_Widget() );
    }
}