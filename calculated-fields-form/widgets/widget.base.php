<?php
/*
....
*/
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists( 'CPCFF_WidgetBase' ) )
{
    class CPCFF_WidgetBase
    {
        protected function __construct()
        {
            if ( is_admin() ) {
                add_action( 'cpcff_additional_admin_scripts', function(){
                    $this->admin_scripts();
                });
                add_action('cpcff_form_settings', function ($form_id = null) { // The parameter is included only for compatibility
                    $this->admin_styles();
                }, 10, 1);
            }

            add_action( 'cpcff_load_controls_public', function(){
                $this->public_scripts();
            });

            add_filter('cpcff_the_form', function($html_content, $form_id) { // The parameters are included only for compatibility
                $this->public_styles();
                return $html_content;
            }, 10, 2);

        } // End __construct

        protected function admin_scripts() {}
        protected function admin_styles() {}

        protected function public_scripts() {}
        protected function public_styles() {}

	} // End Class
}