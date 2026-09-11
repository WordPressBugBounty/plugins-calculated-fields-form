<?php

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * Class that handle "Simple Quick Module" module output in frontend.
 */
class CFF_DIVI5_MODULE implements DependencyInterface {
    /**
     * Register module.
     * `DependencyInterface` interface ensures class method name `load()` is executed for initialization.
     */
    public function load() {
        // Register module.
        static $registered = false;
        if ( $registered ) {
            return;
        }
        self::register_module();

        // Process the conversion outline so the D5 Migrator's initial compatibility
        self::maybe_process_outline();

        $registered = true;
    }

    /**
     * Process conversion outline so the D5 Migrator recognizes the module
     * as convertible in its initial compatibility check (Step 1).
     */
    public static function maybe_process_outline() {
        $outline_path = dirname( __FILE__ ) . '/conversion-outline.json';
        if ( ! file_exists( $outline_path ) ) {
            return;
        }
        $metadata = json_decode( file_get_contents( dirname( __FILE__ ) . '/module.json' ), true );
        if ( empty( $metadata['name'] ) ) {
            return;
        }
        ModuleRegistration::process_conversion_outline( $metadata, $outline_path );
    }

    /**
     * Register module.
     */
    public static function register_module() {
        // Path to module metadata that is shared between Frontend and Visual Builder.
        $module_json_folder_path = dirname( __FILE__ );

        ModuleRegistration::register_module(
            $module_json_folder_path,
            [
                'render_callback' => [ CFF_DIVI5_MODULE::class, 'render_callback' ],
            ]
        );
    }

    /**
     * Render module HTML output.
     */
    public static function render_callback( $attrs, $content, $block, $elements ) {
		$raw_form_id    = $attrs['cff_form_id']['innerContent']['desktop']['value'] ?? '';
		$raw_class_name = $attrs['cff_class_name']['innerContent']['desktop']['value'] ?? '';
		$raw_attributes = $attrs['cff_attributes']['innerContent']['desktop']['value'] ?? '';
		$iframe 		= $attrs['cff_iframe']['innerContent']['desktop']['value'] ?? '';

		// Sanitize / normalize
		$form_id = intval( $raw_form_id );

		$class_name = trim(
			preg_replace( '/[^a-zA-Z0-9\-\_\s]/', ' ', $raw_class_name )
		);

		$other_attributes = sanitize_text_field( $raw_attributes );


		if ( $form_id <= 0 ) {
			return '';
		}

		// ----------------------------------------------------------
		// Build shortcode
		// ----------------------------------------------------------
		$shortcode = '[CP_CALCULATED_FIELDS id="' . $form_id . '"';

		if ( ! empty( $class_name ) ) {
			$shortcode .= ' class="' . esc_attr( $class_name ) . '"';
		}

		if ( ! empty( $other_attributes ) ) {
			// attributes may contain key="value" pairs → keep raw
			$shortcode .= ' ' . $other_attributes;
		}

		if ( ! empty( $iframe ) && $iframe == 'on') {
			$shortcode .= ' iframe="1"';
		}

		$shortcode .= ']';

		// ----------------------------------------------------------
		// Return actual form (shortcode expanded on frontend)
		// ----------------------------------------------------------
		return do_shortcode( $shortcode );
    }
}