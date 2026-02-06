<?php
/**
 * Main class to interace with the different Content Editors: CPCFF_PAGE_BUILDERS class
 */

if ( ! class_exists( 'CPCFF_PAGE_BUILDERS' ) ) {
	class CPCFF_PAGE_BUILDERS {

		private static $_instance;
		private $wptexturize_flag = false;

		private function __construct() {}
		private static function instance() {
			if ( ! isset( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		} // End instance.

		public static function run() {
			$instance = self::instance();
			add_action( 'init', array( $instance, 'init' ) );
			add_action( 'after_setup_theme', array( $instance, 'after_setup_theme' ) );
		}

		public function init() {
			$instance = self::instance(); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments

			// Gutenberg editor.
			add_action( 'enqueue_block_editor_assets', array( $instance, 'gutenberg_editor' ) );
			add_filter( 'render_block', array( $instance, 'gutenberg_dissable_wptexturize' ), 10, 2 );
			if (
				isset( $_REQUEST['cff-action'] ) &&
				'cff-gutenberg-editor-config' == $_REQUEST['cff-action'] &&
				current_user_can( 'edit_posts' )
			) {
				remove_all_actions( 'shutdown' );
				die( json_encode( $this->gutenberg_editor_config() ) );
			}

			// Elementor.
			add_action( 'elementor/widgets/register', array( $instance, 'elementor_editor' ) );
			add_action( 'elementor/elements/categories_registered', array( $instance, 'elementor_editor_category' ) );
			add_action( 'elementor/editor/after_enqueue_scripts', function() {
				wp_enqueue_script( 'cp_calculatedfieldsf_script', plugins_url( '/js/cp_calculatedfieldsf_scripts.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );
			});

			// Beaver builder.
			if ( class_exists( 'FLBuilder' ) ) {
				include_once CP_CALCULATEDFIELDSF_BASE_PATH . '/pagebuilders/beaverbuilder/cff/cff.inc.php';
				include_once CP_CALCULATEDFIELDSF_BASE_PATH . '/pagebuilders/beaverbuilder/cffvar/cffvar.inc.php';
			}
		} // End init.

		public function after_setup_theme() {
			$instance = self::instance(); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments

			// SiteOrigin.
			add_filter( 'siteorigin_widgets_widget_folders', array( $instance, 'siteorigin_widgets_collection' ) );
			add_filter( 'siteorigin_panels_widget_dialog_tabs', array( $instance, 'siteorigin_panels_widget_dialog_tabs' ) );

			// Visual Composer.
			add_action( 'vcv:api', array( $instance, 'visualcomposer_editor' ) );

			// DIVI
			if ( function_exists( 'et_get_theme_version' ) ) {
				if ( version_compare( et_get_theme_version(), '5.0', '>=' ) ) { // DIVI 5
					add_action( 'et_builder_ready', array($instance, 'divi_editor') );
					add_action( 'divi_visual_builder_assets_before_enqueue_scripts',
						function() {
							if ( et_core_is_fb_enabled() && et_builder_d5_enabled() ) {
								global $wpdb;
								$options = array();

								if ( defined('CP_CALCULATEDFIELDSF_FORMS_TABLE') ) {
									$rows = $wpdb->get_results(
										"SELECT id, form_name FROM " . $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE
									);

									foreach ($rows as $item) {
										$options[$item->id] = ['label' => '(' . $item->id . ') ' . $item->form_name];
									}

								}
								wp_register_script( 'cff-divi5-forms', '', array(), null, true );
								wp_enqueue_script( 'cff-divi5-forms' );
								$script = 'var cff_divi5_forms_list = ' . json_encode( $options ) . ';';
								wp_add_inline_script( 'cff-divi5-forms', $script );

								\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
									[
										'name'    => 'cff-divi-5-module-visual-builder',
										'version' => '1.0.0',
										'script'  => [
											'src' => plugins_url('/pagebuilders/divi/divi5/divi.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH),
											'deps'=> [
												'react',
												'jquery',
												'divi-module-library',
												'wp-hooks',
												'divi-rest',
											],
											'enqueue_top_window' => false,
											'enqueue_app_window' => true,
										],
									]
								);
							}
						}
					);

					// Register module.
					add_action(
						'divi_module_library_modules_dependency_tree',
						function( $dependency_tree ) {
							// Load Divi 5 modules.
							require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/pagebuilders/divi/divi5/divi.pb.php';
							$dependency_tree->add_dependency( new CFF_DIVI5_MODULE() );
						}
					);

					add_filter(
						'divi.moduleLibrary.conversion.moduleConversionOutlineFile',
						function( $conversion_outline_file, $module_name ) {
							if ( 'cff/cff-divi5-module' === $module_name ) {
								return CP_CALCULATEDFIELDSF_BASE_PATH . '/pagebuilders/divi/divi5/conversion-outline.json';
							}
							return $conversion_outline_file;
						}, 10, 2
					);
				} else { // DIVI 4
					add_action( 'et_builder_ready', array($instance, 'divi_editor') );
					add_action( 'et_fb_enqueue_assets', array($instance, 'divi_editor_assets') );
				}
			}
		} // End after_setup_theme.

		/**************************** DIVI ****************************/
		public function divi_editor() {
			require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/pagebuilders/divi/divi4/divi.pb.php';
			// $this->divi_editor_assets();
		} // End divi_editor.

		public function divi_editor_assets()
		{
			 wp_enqueue_script(
				'cff-divi-module',
				plugins_url('/pagebuilders/divi/divi4/divi.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH),
				['jquery', 'react', 'react-dom'],
				CP_CALCULATEDFIELDSF_VERSION,
				true
			);
		} // End divi_editor_assets

		/**************************** GUTENBERG ****************************/
		private function gutenberg_editor_config() {
			global $wpdb;

			$url    = CPCFF_AUXILIARY::site_url();
			$url   .= ( ( strpos( $url, '?' ) === false ) ? '?' : '&' ) . 'cff-editor-preview=1&cff-amp-redirected=1&cff-amp-form=';
			$config = array(
				'url'      => $url,
				'is_admin' => current_user_can( 'manage_options' ),
				'editor'   => CPCFF_AUXILIARY::editor_url(),
				'forms'    => array(),
				'templates' => array(),
				'labels'   => array(
					'required_form' => __( 'Select a form', 'calculated-fields-form' ),
					'forms'         => __( 'Forms', 'calculated-fields-form' ),
					'templates'		=> __('Templates (form design)', 'calculated-fields-form'),
					'attributes'    => __( 'Additional attributes', 'calculated-fields-form' ),
					'iframe'        => __( 'Load form into an iframe', 'calculated-fields-form' ),
					'iframe_description' => __('If you are using a cache management or website optimization plugin and the form is not displaying on the public website, please check the box labeled "Load form into an iframe." This will help ensure that the form is properly rendered for users.', 'calculated-fields-form'),
					'edit_form'     => __( 'Edit form', 'calculated-fields-form' ),
				),
			);

			$forms = CPCFF_FORM::forms_list();

			foreach ( $forms as $form ) {
				$config['forms'][ $form->id ] = esc_attr( '(' . $form->id . ') ' . $form->form_name );
			}

			require_once CP_CALCULATEDFIELDSF_BASE_PATH.'/inc/cpcff_templates.inc.php';
			$templates_list = CPCFF_TEMPLATES::load_templates();
			foreach ( $templates_list as $template_item ) {
				$config['templates'][ $template_item['prefix'] ] = [ 'title' => esc_html( $template_item['title'] ), 'thumbnail' => esc_attr( ! empty( $template_item['thumbnail'] ) ? $template_item['thumbnail'] : '' ) ];
			}

			return $config;
		} // End gutenberg_integration_config.

		/**
		 * Loads the javascript resources to integrate the plugin with the Gutenberg editor
		 */
		public function gutenberg_editor() {
			wp_enqueue_style( 'cp_calculatedfieldsf_gutenberg_editor_css', plugins_url( '/pagebuilders/gutenberg/assets/css/gutenberg.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );
			wp_enqueue_script( 'cp_calculatedfieldsf_gutenberg_editor', plugins_url( '/pagebuilders/gutenberg/assets/js/gutenberg.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );

			wp_localize_script( 'cp_calculatedfieldsf_gutenberg_editor', 'cpcff_gutenberg_editor_config', $this->gutenberg_editor_config() );
		} // End gutenberg_editor.

		public function gutenberg_dissable_wptexturize( $block_content, $block ) {
			if (
				'cpcff/form-summary-shortcode' === $block['blockName'] ||
				'cpcff/form-summary-list-shortcode' === $block['blockName']
			) {
				if ( has_filter( 'the_content', 'wptexturize' ) ) {
					$this->wptexturize_flag = true;
					remove_filter( 'the_content', 'wptexturize' );
				}
			} elseif ( $this->wptexturize_flag && ! has_filter( 'the_content', 'wptexturize' ) ) {
				add_filter( 'the_content', 'wpautop' );
			}

			return $block_content;
		} // End gutenberg_dissable_wptexturize.

		/**************************** ELEMENTOR ****************************/
		public function elementor_editor_category() {
			require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/pagebuilders/elementor/elementor_category.pb.php';
		} // End elementor_editor.

		public function elementor_editor() {
			if ( is_admin() ) {
				wp_enqueue_script( 'cp_calculatedfieldsf_elementor_editor_js', plugins_url( '/pagebuilders/elementor/assets/elementor.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );

				// Templates with thumbnails
				require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_templates.inc.php';
				$templates_list = CPCFF_TEMPLATES::load_templates();
				$templates = array();
				foreach ( $templates_list as $template_item ) {
					$templates[ $template_item['prefix'] ] = esc_attr( ! empty( $template_item['thumbnail'] ) ? $template_item['thumbnail'] : '' );
				}

				wp_add_inline_script('cp_calculatedfieldsf_elementor_editor_js', 'const cff_template_thumbnail_list=' . json_encode( $templates ), 'before');

				wp_enqueue_style( 'cp_calculatedfieldsf_elementor_editor_css', plugins_url( '/pagebuilders/elementor/assets/elementor.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );

				if ( current_user_can( 'manage_options' ) ) {
					wp_localize_script(
						'cp_calculatedfieldsf_elementor_editor_js',
						'cp_calculatedfieldsf_elementor',
						array(
							'url' => CPCFF_AUXILIARY::editor_url(),
						)
					);
				}
			}
			require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/pagebuilders/elementor/elementor.pb.php';
		} // End elementor_editor.

		/**************************** SITEORIGIN ****************************/
		public function siteorigin_widgets_collection( $folders ) {
			$folders[] = CP_CALCULATEDFIELDSF_BASE_PATH . '/pagebuilders/siteorigin/';
			return $folders;
		} // End siteorigin_widgets_collection.

		public function siteorigin_panels_widget_dialog_tabs( $tabs ) {
			$tabs[] = array(
				'title'  => __( 'Calculated Fields Form', 'calculated-fields-form' ),
				'filter' => array(
					'groups' => array( 'calculated-fields-form' ),
				),
			);

			return $tabs;
		} // End siteorigin_panels_widget_dialog_tabs.

		/**************************** VISUAL COMPOSER ****************************/
		public function visualcomposer_editor( $api ) {
			$elementsToRegister = array( 'CFFForm' );
			$pluginBaseUrl      = rtrim( plugins_url( '/pagebuilders/visualcomposer/', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), '\\/' );
			$elementsApi        = $api->elements;
			foreach ( $elementsToRegister as $tag ) {
				$manifestPath   = CP_CALCULATEDFIELDSF_BASE_PATH . '/pagebuilders/visualcomposer/' . $tag . '/manifest.json';
				$elementBaseUrl = $pluginBaseUrl . '/' . $tag;
				$elementsApi->add( $manifestPath, $elementBaseUrl );
			}
		} // End visualcomposer_editor.
	} // End CPCFF_PAGE_BUILDERS.
}
