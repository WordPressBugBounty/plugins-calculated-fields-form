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
                add_action('cpcff_form_settings', function ($formid = null) { // The parameter is included only for compatibility
                    $this->admin_styles();
                }, 10, 1);
            }
        } // End __construct

        protected function admin_scripts() {}
        protected function admin_styles() {}

	} // End Class
}