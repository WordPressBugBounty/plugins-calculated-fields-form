<?php
/*
....
*/
if (!defined('ABSPATH')) {
    exit;
}

if( !class_exists( 'CPCFF_WIDGETS_MANAGER' ) )
{
    class CPCFF_WIDGETS_MANAGER
    {
        private static $_widgets_list = [];

        /**** PRIVATE METHODS *****/

        /**
         * Includes the widgets list file and instantiate their main class.
         */
        static private function load() {
            // Load the widget base.
            if ( ! file_exists( __DIR__ . '/widget.base.php' ) ) return;
            require_once( __DIR__ . '/widget.base.php' );

            // Load the widget files.
            if ( file_exists( __DIR__ . '/widgets.list.php' ) ) {
                include_once( __DIR__ . '/widgets.list.php' );
                if ( isset($cff_widgets_list) && is_array($cff_widgets_list) ) {
                    foreach ( $cff_widgets_list as $widget ) {
                        if ( ! file_exists( __DIR__ . '/' . $widget ) ) continue;
                        require_once( __DIR__ . '/' . $widget);
                    }
                }
            }
        } // End load

        /**** PUBLIC METHODS *****/

        static public function init() {
            self::load();
        } // End init

        static public function add($widget) {
            self::$_widgets_list[] = $widget;
        } // End add

	} // End Class
}