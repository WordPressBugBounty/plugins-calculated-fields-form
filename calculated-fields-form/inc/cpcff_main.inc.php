<?php
/**
 * Main class with main actions and filters: CPCFF_MAIN class
 *
 * @package CFF.
 * @since 1.0.170
 */

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeEnd
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTagSquiz.Commenting.FunctionComment.MissingParamTag

if ( ! class_exists( 'CPCFF_MAIN' ) ) {
	/**
	 * Class that defines the main actions and filters, and plugin's functionalities.
	 *
	 * @since  1.0.170
	 */
	class CPCFF_MAIN {

		/**
		 * Counter of forms in a same page
		 * Metaclass property.
		 *
		 * @since 1.0.170
		 * @var int $form_counter
		 */
		public static $form_counter = 0;

		/**
		 * Counter of iframes in a same page
		 * Metaclass property.
		 *
		 * @var int $iframe_counter
		 */
		public static $iframe_counter = 0;

		/**
		 * Instance of the CPCFF_MAIN class
		 * Metaclass property to implement a singleton.
		 *
		 * @since 1.0.179
		 * @var object $_instance
		 */
		private static $_instance;

		/**
		 * Identifies if the class was instanciated from the public website or WordPress
		 * Instance property.
		 *
		 * @sinze 1.0.170
		 * @var bool $_is_admin
		 */
		private $_is_admin = false;

		/**
		 * Plugin URL
		 * Instance property.
		 *
		 * @sinze 1.0.170
		 * @var string $_plugin_url
		 */
		private $_plugin_url;

		/**
		 * Flag to know if the public resources were included
		 * Instance property.
		 *
		 * @sinze 1.0.170
		 * @var bool $_are_resources_loaded default false
		 */
		private $_are_resources_loaded = false;

		/**
		 * Forms list.
		 * List of instances of the CPCFF_FORM class.
		 * Instance property.
		 *
		 * @sinze 1.0.179
		 * @var object $_active_form
		 */
		private $_forms = array();
		private $_current_form; // The form that is being making public.

		/**
		 * List of forms categories.
		 * Instance property.
		 */
		private $_categories = array();

		/**
		 * Instance of the CPCFF_AMP class to manage the forms in AMP pages
		 * Instance property.
		 *
		 * @sinze 1.0.230
		 * @var object $_amp
		 */
		private $_amp;

		private $mail_obj;

		/**
		 * Constructs a CPCFF_MAIN object, and define the hooks to the filters and actions.
		 * The constructor is private because this class is a singleton
		 */
		private function __construct() {
			require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_form.inc.php';
			require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_amp.inc.php';

			// Initializes the $_is_admin property.
			$this->_is_admin = is_admin();

			// Initializes the $_plugin_url property.
			$this->_plugin_url = plugin_dir_url( CP_CALCULATEDFIELDSF_MAIN_FILE_PATH );

			// Plugin activation/deactivation.
			$this->_activate_deactivate();

			// Load the language file.
			add_action( 'init', function(){ load_plugin_textdomain( 'calculated-fields-form', false, dirname( CP_CALCULATEDFIELDSF_BASE_NAME ) . '/languages/' ); } );
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );

			// Instanciate the AMP object.
			$this->_amp = new CPCFF_AMP( $this );

			// Run the initialization code.
			add_action( 'init', array( $this, 'init' ), 1 );

			// Redirect after first time installation.
			add_action( 'activated_plugin', function($p1, $p2){
				if ( get_transient('cff-video-tutorial') ) {
					$redirect_url = admin_url( 'admin.php?page=cp_calculated_fields_form' );
					wp_safe_redirect(
						esc_url( $redirect_url )
					);
					exit;
				}
			}, 10, 2 );

			// Run the initialization code of widgets.
			add_action( 'widgets_init', array( $this, 'widgets_init' ), 1 );

			// Integration with Page Builders.
			require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_page_builders.inc.php';
			CPCFF_PAGE_BUILDERS::run();
		} // End __construct.

		/**
		 * Returns the instance of the singleton.
		 *
		 * @since 1.0.179
		 * @return object self::$_instance
		 */
		public static function instance() {
			if ( ! isset( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		} // End instance.

		/**
		 * Loads the primary resources, previous to the plugin's initialization
		 *
		 * Loads resources like the laguages files, etc.
		 *
		 * @return void.
		 */
		public function plugins_loaded() {
			// Fix different troubleshoots.
			$this->troubleshoots();

			// Load controls scripts.
			$this->_load_controls_scrips();
		} // End plugins_loaded.

		/**
		 * Initializes the plugin, runs as soon as possible.
		 *
		 * Initilize the plugin's sections, intercepts the submissions, generates the resources etc.
		 *
		 * @return void.
		 */
		public function init() {
			CPCFF_AUXILIARY::clean_transients_hook(); // Set the hook for clearing the expired transients.

			if ( $this->_is_admin ) {
				if (
					false === ( $CP_CALCULATEDFIELDSF_VERSION = get_option( 'CP_CALCULATEDFIELDSF_VERSION' ) ) || // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
					CP_CALCULATEDFIELDSF_VERSION != $CP_CALCULATEDFIELDSF_VERSION
				) {
					if ( class_exists( 'CPCFF_INSTALLER' ) ) {
						CPCFF_INSTALLER::install( is_multisite() );
					}
					update_option( 'CP_CALCULATEDFIELDSF_VERSION', CP_CALCULATEDFIELDSF_VERSION );
				}

				// Update metabox status if corresponds.
				$this->update_metabox_status();

				// Adds the plugin links in the plugins sections.
				add_filter( 'plugin_action_links_' . CP_CALCULATEDFIELDSF_BASE_NAME, array( $this, 'links' ) );

				// Creates the menu entries in the WordPress menu.
				add_action( 'admin_menu', array( $this, 'admin_menu' ) );
				add_action( 'admin_head', array( $this, 'admin_menu_styles' ), 11 );

				// Displays the shortcode insertion buttons.
				add_action( 'media_buttons', array( $this, 'media_buttons' ) );

				// Loads the admin resources.
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_resources' ), 1 );
			}
			$this->_define_shortcodes();
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_loader' ) );
		} // End init.

		/**
		 * Registers the widgets.
		 *
		 * Registers the widget to include the forms on sidebars, and for loading the data collected by the forms in the dashboard.
		 *
		 * @since 1.0.178
		 *
		 * @return void.
		 */
		public function widgets_init() {
			// Replace the shortcodes into the text widgets.
			if ( ! $this->_is_admin ) {
				add_filter( 'widget_text', 'do_shortcode' );
			}
		} // End widgets_init.

		/**
		 * Adds the plugin's links in the plugins section.
		 *
		 * Links for accessing to the help, settings, developers website, etc.
		 *
		 * @param array $links.
		 *
		 * @return array.
		 */
		public function links( $links ) {
			array_unshift(
				$links,
				'<a href="https://cff.dwbooster.com/customization" target="_blank">' . __( 'Request custom changes' ) . '</a>',
				'<a href="admin.php?page=cp_calculated_fields_form">' . __( 'Settings' ) . '</a>',
				'<a href="https://cff.dwbooster.com/download" target="_blank" style="color:#93003f;font-weight:700;">' . __( 'Upgrade' ) . '*</a>',
				'<a href="https://wordpress.org/support/plugin/calculated-fields-form#new-post" target="_blank">' . __( 'Help' ) . '</a>'
			);
			return $links;
		} // End links.

		/**
		 * Prints the buttons for inserting the different shortcodes into the pages/posts contents.
		 *
		 * Prints the HTML code that appears beside the media button with the icons and code to insert the shortcodes:
		 *
		 * - CP_CALCULATED_FIELDS
		 * - CP_CALCULATED_FIELDS_VAR
		 *
		 * @return void.
		 */
		public function media_buttons() {
			print '<a href="javascript:cp_calculatedfieldsf_insertForm();" title="' . esc_attr__( 'Insert Calculated Fields Form', 'calculated-fields-form' ) . '"><img src="' . esc_attr( $this->_plugin_url ) . 'images/cp_form.gif" alt="' . esc_attr__( 'Insert Calculated Fields Form', 'calculated-fields-form' ) . '" /></a><a href="javascript:cp_calculatedfieldsf_insertVar();" title="' . esc_attr__( 'Create a JavaScript var from POST, GET, SESSION, or COOKIE var', 'calculated-fields-form' ) . '"><img src="' . esc_attr( $this->_plugin_url ) . 'images/cp_var.gif" alt="' . esc_attr__( 'Create a JavaScript var from POST, GET, SESSION, or COOKIE var', 'calculated-fields-form' ) . '" /></a>';
		} // End media_buttons.

		/**
		 * Generates the entries in the WordPress menu.
		 *
		 * @return void.
		 */
		public function admin_menu() {
			global $submenu;

			// Settings page.
			add_options_page( 'Calculated Fields Form Options', 'Calculated Fields Form', apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ), 'cp_calculated_fields_form', array( $this, 'admin_pages' ) );

			// Menu option.
			add_menu_page( 'Calculated Fields Form Options', 'Calculated Fields Form', apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ), 'cp_calculated_fields_form', array( $this, 'admin_pages' ), 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2NCA2NCIgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0Ij48cmVjdCB4PSI4IiB5PSI4IiB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIHJ4PSI1IiByeT0iNSIgZmlsbD0iI2ZmZmZmZiIgc3Ryb2tlPSJub25lIiBzdHJva2Utd2lkdGg9IjAiIC8+PGxpbmUgeDE9IjE0IiB5MT0iMjQiIHgyPSIzMCIgeTI9IjI0IiBzdHJva2U9IiMxZDIzMjciIHN0cm9rZS13aWR0aD0iMyIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiAvPjxsaW5lIHgxPSIyMiIgeTE9IjE2IiB4Mj0iMjIiIHkyPSIzMiIgc3Ryb2tlPSIjMWQyMzI3IiBzdHJva2Utd2lkdGg9IjMiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgLz48bGluZSB4MT0iMzQiIHkxPSI0MCIgeDI9IjUwIiB5Mj0iNDAiIHN0cm9rZT0iIzFkMjMyNyIgc3Ryb2tlLXdpZHRoPSIzIiBzdHJva2UtbGluZWNhcD0icm91bmQiIC8+PGxpbmUgeDE9IjQ4IiB5MT0iMTYiIHgyPSIxNiIgeTI9IjQ4IiBzdHJva2U9IiMxZDIzMjciIHN0cm9rZS13aWR0aD0iMyIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiAvPjwvc3ZnPg==' );

			// Submenu options.
			add_submenu_page( 'cp_calculated_fields_form', 'Calculated Fields Form', 'All Forms', apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ), 'cp_calculated_fields_form', array( $this, 'admin_pages' ) );

			add_submenu_page( 'cp_calculated_fields_form', 'Calculated Fields Form - New Form', 'Add New', apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ), 'cp_calculated_fields_form_sub_new', array( $this, 'admin_pages' ) );

			add_submenu_page( 'cp_calculated_fields_form', 'Calculated Fields Form - Troubleshoot Area & General Settings', 'Troubleshoot Area & General Settings', apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ), 'cp_calculated_fields_form_sub_troubleshoots_settings', array( $this, 'admin_pages' ) );

			add_submenu_page( 'cp_calculated_fields_form', 'Upgrade', 'Upgrade', apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ), 'cp_calculated_fields_form_sub_upgrade', array( $this, 'admin_pages' ) );

			add_submenu_page( 'cp_calculated_fields_form', 'Marketplace', 'Marketplace', apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ), 'cp_calculated_fields_form_sub_marketplace', array( $this, 'admin_pages' ) );

			add_submenu_page( 'cp_calculated_fields_form', 'Documentation', 'Documentation', apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ), 'cp_calculated_fields_form_sub_documentation', array( $this, 'admin_pages' ) );

			add_submenu_page( 'cp_calculated_fields_form', 'Online Help', 'Online Help', apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ), 'cp_calculated_fields_form_sub_forum', array( $this, 'admin_pages' ) );

			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
			if ( ! empty( $submenu ) && is_array( $submenu ) && ! empty( $submenu['cp_calculated_fields_form'] ) ) {
				foreach ( $submenu['cp_calculated_fields_form'] as $index => $item ) {
					if ( 'cp_calculated_fields_form_sub_marketplace' == $item[2] ) {
						if ( isset( $item[4] ) ) {
							$submenu['cp_calculated_fields_form'][ $index ][4] .= ' calculated-fields-form-submenu-marketplace';
						} else {
							$submenu['cp_calculated_fields_form'][ $index ][] = 'calculated-fields-form-submenu-marketplace';
						}
					}

					if ( 'cp_calculated_fields_form_sub_upgrade' == $item[2] ) {
						if ( isset( $item[4] ) ) {
							$submenu['cp_calculated_fields_form'][ $index ][4] .= ' calculated-fields-form-submenu-upgrade';
						} else {
							$submenu['cp_calculated_fields_form'][ $index ][] = 'calculated-fields-form-submenu-upgrade';
						}
					}
				}
			}
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
		} // End admin_menu.

		public function admin_menu_styles() {
			$styles = '';

			$styles .= 'a.calculated-fields-form-submenu-marketplace { background-color: #f0db4f !important; color: #323330 !important; font-weight: 600 !important; }';
			$styles .= 'a.calculated-fields-form-submenu-upgrade { background-color: #ee7878 !important; color: #ffffff !important; font-weight: 600 !important; }';

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			printf( '<style>%s</style>', $styles );
		} // End admin_menu_styles.

		/**
		 * Loads the corresponding pages in the WordPress or redirects the user to the external URLs.
		 *
		 * Loads the webpage with the list of forms, addons activation, general settings, etc.
		 * or redirects to external webpages like plugin's documentation
		 *
		 * @since 1.0.181
		 */
		public function admin_pages() {
			// Settings page of the plugin.
			if ( isset( $_GET['cal'] ) && '' != $_GET['cal'] ) {
				@include_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_admin_int.inc.php';
			} else {
				// Redirecting outer website.
				if ( isset( $_GET['page'] ) && 'cp_calculated_fields_form_sub_upgrade' == $_GET['page'] ) {
					if ( @wp_redirect( 'https://cff.dwbooster.com/download' ) ) {
						exit;
					}
				} elseif ( isset( $_GET['page'] ) && 'cp_calculated_fields_form_sub_documentation' == $_GET['page'] ) {
					if ( @wp_redirect( 'https://cff.dwbooster.com/documentation' ) ) {
						exit;
					}
				} elseif ( isset( $_GET['page'] ) && 'cp_calculated_fields_form_sub_marketplace' == $_GET['page'] ) {
					if ( @wp_redirect( 'https://cff-bundles.dwbooster.com' ) ) {
						exit;
					}
				} elseif ( isset( $_GET['page'] ) && 'cp_calculated_fields_form_sub_forum' == $_GET['page'] ) {
					if ( @wp_redirect( 'https://wordpress.org/support/plugin/calculated-fields-form#new-post' ) ) {
						exit;
					}
				} elseif( get_transient('cff-video-tutorial') ) {
					@include_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_admin_landing_page.inc.php';
				} else {
					@include_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_admin_int_list.inc.php';
				}
			}
		} // End admin_pages.

		/**
		 * Loads the javascript and style files.
		 *
		 * Checks if there is the settings page of the plugin for loading the corresponding JS and CSS files,
		 * or if it is a post or page the script for inserting the shortcodes in the content's editor.
		 *
		 * @since 1.0.171
		 *
		 * @param string $hook.
		 * @return void.
		 */
		public function admin_resources( $hook ) {
			// Checks if it is the plugin's page.
			if ( isset( $_GET['page'] ) ) {
				// Checks if it is to an external page.
				if (
					'cp_calculated_fields_form_sub_documentation' == $_GET['page'] ||
					'cp_calculated_fields_form_sub_marketplace' == $_GET['page'] ||
					'cp_calculated_fields_form_sub_upgrade' == $_GET['page'] ||
					'cp_calculated_fields_form_sub_forum' == $_GET['page']
				) {

					$redirect_url   = '';
					$cpcff_redirect = array();
					switch ( $_GET['page'] ) {
						case 'cp_calculated_fields_form_sub_documentation':
							$cpcff_redirect['url'] = 'https://cff.dwbooster.com/documentation';
							break;
						case 'cp_calculated_fields_form_sub_upgrade':
							$cpcff_redirect['url'] = 'https://cff.dwbooster.com/download';
							break;
						case 'cp_calculated_fields_form_sub_forum':
							$cpcff_redirect['url'] = 'https://wordpress.org/support/plugin/calculated-fields-form#new-post';
							break;
						case 'cp_calculated_fields_form_sub_marketplace':
							$cpcff_redirect['url'] = 'https://cff-bundles.dwbooster.com';
							break;
					}
					wp_enqueue_script( 'cp_calculatedfieldsf_redirect_script', plugins_url( '/js/redirect_script.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );
					wp_localize_script(
						'cp_calculatedfieldsf_redirect_script',
						'cpcff_redirect',
						$cpcff_redirect
					);

				} elseif (
					in_array( $_GET['page'], array( 'cp_calculated_fields_form', 'cp_calculated_fields_form_sub_new', 'cp_calculated_fields_form_sub_troubleshoots_settings' ) )
				) {

					wp_deregister_script( 'tribe-events-bootstrap-datepicker' );
					wp_register_script( 'tribe-events-bootstrap-datepicker', plugins_url( '/js/nope.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );

					wp_enqueue_script( 'jquery' );
					wp_enqueue_script( 'jquery-ui-core' );
					wp_enqueue_script( 'jquery-ui-sortable' );
					wp_enqueue_script( 'jquery-ui-tabs' );
					wp_enqueue_script( 'jquery-ui-droppable' );
					wp_enqueue_script( 'jquery-ui-button' );
					wp_enqueue_script( 'jquery-ui-datepicker' );

					// ULR to the admin resources.
					$admin_resources = admin_url( 'admin.php?page=cp_calculated_fields_form&cp_cff_resources=admin' );
					wp_enqueue_script( 'cp_calculatedfieldsf_builder_script', $admin_resources, array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-tabs', 'jquery-ui-droppable', 'jquery-ui-button', 'jquery-ui-accordion', 'jquery-ui-datepicker' ), CP_CALCULATEDFIELDSF_VERSION );

					wp_enqueue_script( 'cp_calculatedfieldsf_builder_library_script', plugins_url( '/js/library.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array( 'cp_calculatedfieldsf_builder_script' ), CP_CALCULATEDFIELDSF_VERSION );

					if( $_GET['page'] == 'cp_calculated_fields_form_sub_new' ) {
						wp_localize_script(
							'cp_calculatedfieldsf_builder_library_script',
							'cpcff_forms_library_config',
							array(
								'version'     			=> 'free',
								'website_url' 			=> 'admin.php?page=cp_calculated_fields_form&a=1&_cpcff_nonce=' . wp_create_nonce( 'cff-add-form' ),
								'ai_form_generator_url' => 'admin.php?page=cp_calculated_fields_form&_cpcff_nonce=' . wp_create_nonce( 'cff-ai-form-generator' ),
								'website_forms' 		=> CPCFF_FORM::forms_list( [ 'description' => true ] ),
								'texts' => [
									// Placeholders.
									'search_placeholder' 		   => esc_attr__( 'Search...', 'calculated-fields-form' ),
									'form_name_placeholder' 	   => esc_attr__( 'Form name...', 'calculated-fields-form' ),
									'api_key_placeholder' 		   => esc_attr__( 'Enter your Google AI Studio API Key', 'calculated-fields-form' ),
									'form_descritpion_placeholder' => esc_attr__( 'Please provide the form description preferably in English.', 'calculated-fields-form' ),

									// Menu options.
									'ai_form_generator_menu'	   => esc_html__( 'AI Form Generator (Beta)', 'calculated-fields-form' ),
									'website_forms_menu'		   => esc_html__( 'Use My Forms As Template', 'calculated-fields-form' ),
									'all_categories_menu'		   => esc_html__( 'All Categories', 'calculated-fields-form' ),

									// Labels.
									'form_descritpion_label'	   => esc_html__( 'Enter form description', 'calculated-fields-form' ),
									'no_form_label'	   			   => esc_html__( 'No form meets the search criteria', 'calculated-fields-form' ),
									'video_label'	   			   => esc_html__( '[video tutorial]', 'calculated-fields-form' ),

									// Instructions.
									'api_key_instruct'				   => sprintf( esc_html__( 'Get your API Key from %s. It will be stored in your browser\'s local storage, and can be deleted by pressing "Clear API Key".', 'calculated-fields-form' ), '<a href="https://aistudio.google.com/apikey" target="_blank">Google AI Studio</a>' ),

									// Buttons texts.
									'create_form_btn' 			   => esc_html__( 'Create Basic Form', 'calculated-fields-form' ),
									'save_api_key_btn' 			   => esc_html__( 'Save API Key', 'calculated-fields-form' ),
									'saving_api_key_btn' 		   => esc_html__( 'Saving...', 'calculated-fields-form' ),
									'clear_api_key_btn' 		   => esc_html__( 'Clear API Key', 'calculated-fields-form' ),
									'generate_form_btn'			   => esc_html__( 'Generate', 'calculated-fields-form' ),
									'use_it_btn' 		   		   => esc_html__( 'Use It', 'calculated-fields-form' ),
									'back_btn' 		   		   	   => esc_attr__( 'back', 'calculated-fields-form' ),

									// Errors.
									'api_key_requirement_error'	   	=> esc_html__( 'The API Key is empty.', 'calculated-fields-form' ),
									'description_requirement_error' => esc_html__( 'Please enter the form description.', 'calculated-fields-form' ),
								]
							)
						);
					}

					wp_enqueue_script( 'cp_calculatedfieldsf_builder_script_caret', plugins_url( '/vendors/jquery.caret.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array( 'jquery' ), CP_CALCULATEDFIELDSF_VERSION );
					wp_enqueue_script( 'cp_calculatedfieldsf_builder_script_purify', plugins_url( '/vendors/purify.min.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );
					wp_enqueue_style( 'cp_calculatedfieldsf_builder_style', plugins_url( '/css/style.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );
					wp_enqueue_style( 'cp_calculatedfieldsf_builder_library_style', plugins_url( '/css/stylelibrary.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array( 'cp_calculatedfieldsf_builder_style' ), CP_CALCULATEDFIELDSF_VERSION );
					wp_enqueue_style( 'jquery-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css', array(), CP_CALCULATEDFIELDSF_VERSION );

				}
			}

			// Checks if it is a page or post.
			if ( 'post.php' == $hook || 'post-new.php' == $hook ) {
				wp_enqueue_script( 'cp_calculatedfieldsf_script', plugins_url( '/js/cp_calculatedfieldsf_scripts.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );
			}
		} // End admin_resources.

		public function metabox_status( $metabox_id, $default = 'open' )
		{
			$statuses = get_option( 'cff-metaboxes-statuses', [] );
			if ( ! empty( $statuses ) && is_array( $statuses ) && isset( $statuses[ $metabox_id ] ) ) {
				return $statuses[ $metabox_id ] ? 'cff-metabox-opened' : 'cff-metabox-closed';
			}
			return 'open' == $default ? 'cff-metabox-opened' : 'cff-metabox-closed';
		} // End metabox_status.

		private function update_metabox_status() {
			if (
				! empty( $_POST['cff-metabox-nonce'] ) &&
				wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cff-metabox-nonce'] ) ), 'cff-metabox-status' ) &&
				isset( $_POST['cff-metabox-id'] ) &&
				isset( $_POST['cff-metabox-action'] )
			) {
				$metabox_id     = sanitize_text_field( wp_unslash( $_POST['cff-metabox-id'] ) );
				$metabox_action = sanitize_text_field( wp_unslash( $_POST['cff-metabox-action'] ) );

				if ( ! empty( $metabox_id ) ) {
					$statuses = get_option( 'cff-metaboxes-statuses', array() );
					if ( empty( $statuses ) || ! is_array( $statuses ) ) {
						$statuses = array();
					}
					$statuses[ $metabox_id ] = $metabox_action == 'open' ? 1 : 0; // phpcs:ignore WordPress.PHP.YodaConditions
					update_option( 'cff-metaboxes-statuses', $statuses );
				}
			}
		} // End update_metabox_status.

		public function no_form_preview( $form_structure ) {

			$output = '';
			$exception = new Exception( __( 'Invalid form structure', 'calculated-fields-form' ) );
			if ( ! is_string( $form_structure ) ) throw $exception;
			$form_structure = CPCFF_AUXILIARY::json_decode( $form_structure, false );
			if ( empty( $form_structure ) ) throw $exception;
			try {
				$form_structure = CPCFF_FORM::sanitize_structure( $form_structure );
			} catch ( Exception $err ) {
				throw $exception;
			}

			global $wp_styles, $wp_scripts;

			// Constant defined to protect the "inc/cpcff_public_int.inc.php" file against direct accesses.
			if ( !defined('CP_AUTH_INCLUDE') ) define('CP_AUTH_INCLUDE', true);

			add_filter( 'cpcff_admin_get_option', function( $value, $field, $id ) use ( $form_structure ) {
				if ( $field == 'form_structure' ) {
					$value = $form_structure;
				}
				return $value;
			}, 99, 3 );

			ob_start();
			try {

				self::$form_counter = 1;
				$this->_public_resources(0);

				/* TO-DO: This method should be analyzed after moving other functions to the main class . */
				$button_label = $this->get_form(0)->get_option('vs_text_submitbtn', 'Submit');
				$button_label = ($button_label==''?'Submit':$button_label);
				include CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_public_int.inc.php';

				if(!empty($wp_styles))  $wp_styles->do_items();
				if(!empty($wp_scripts)) $wp_scripts->do_items();

				$output .= ob_get_contents();
				$output .= '<script>document.forms[0].onsubmit=document.forms[0].submit=function(){alert("' . esc_js( esc_html__( 'This is a preview of the form, letting you see its setup and design before it goes live.', 'calculated-fields-form' ) ) . '");return false;};</script>';

				$output = '<style>
					#codepeople-review-banner{box-sizing:border-box; border:10px solid #EEE;background:#FFF;display:table;visibility:visible !important;margin-bottom:15px;width:100% !important;}
					#codepeople-review-banner ul{margin-bottom:0;margin-top:5px;}
					#codepeople-review-banner ul li{margin-bottom:2px;}
					#codepeople-review-banner *{visibility:visible !important;}
					#codepeople-review-banner .codepeople-review-banner-content{padding:10px;}
					</style>
					<div id="codepeople-review-banner">
						<div class="codepeople-review-banner-content">
							<div class="codepeople-review-banner-text">
								<div>' . __( 'If the form isn\'t displayed below, it means the AI was unable to generate a valid structure. We apologize for any inconvenience. Please click the "Back to Generate" button, adjust your form description, and then click "Generate" again.', 'calculated-fields-form' ) . '
								</div>
							</div>
						</div>
					</div>' . $output;

			} catch( Exception $err ) {
				error_log( $err->getMessage() );
				throw $exception;
			} finally {
				ob_end_clean();
			}

			return $output;

		} // End no_form_preview

		public function form_preview( $atts ) {
			if ( isset( $atts['shortcode_atts'] ) ) {
				error_reporting( E_ERROR | E_PARSE );
				global  $wp_styles, $wp_scripts;
				if ( ! empty( $wp_scripts ) ) {
					$wp_scripts->reset();
				}

				$message = (
					! empty( $atts['banner'] )
					? '
					<style>
					#codepeople-review-banner{box-sizing:border-box; border:10px solid #EEE;background:#FFF;display:table;visibility:visible !important;margin-bottom:15px;width:100% !important;}
					#codepeople-review-banner ul{margin-bottom:0;margin-top:5px;}
					#codepeople-review-banner ul li{margin-bottom:2px;}
					#codepeople-review-banner *{visibility:visible !important;}
					#codepeople-review-banner .codepeople-review-banner-content{padding:10px;}
					</style>
					<div id="codepeople-review-banner">
						<div class="codepeople-review-banner-content">
							<div class="codepeople-review-banner-text">
								<div>Upgrade to the <a href="https://cff.dwbooster.com/download" target="_blank" style="font-weight:700;color:#1582AB;">Professional plugin version</a> for advanced features. It\'s a one-time purchase with lifetime updates, and you can install it on all your websites. Thank you!
								<ul>
									<li>Improve user experience by emailing them a copy of the form data, including calculated field results.</li>
									<li>Save form data for analysis in tools like Excel or Google Sheets.</li>
									<li>Export the forms to your other websites easily.</li>
									<li>Integrate a payment gateway to charge users the calculated prices.</li>
								</ul>
								</div>
							</div>
						</div>
					</div>'
					: ''
				) . $this->public_form( $atts['shortcode_atts'] );

				ob_start();
				if ( ! empty( $wp_styles ) ) {
					$wp_styles->do_items();
				}
				if ( ! empty( $wp_scripts ) ) {
					$wp_scripts->do_items();
				}
				if ( class_exists( 'Error' ) ) {
					try {
						wp_footer(); } catch ( Error $err ) {
							error_log( $err->getMessage() );
						}
				}
				$message .= ob_get_contents();
				ob_end_clean();

				if (
					! empty( $atts['preview'] ) &&
					(
						empty( $this->_current_form ) ||
						preg_match( '/(' . preg_quote('<%from_page%>'). ')|(' . preg_quote( '/admin.php', '/' ) . ')/i', $this->_current_form->get_option('fp_return_page', '') )
					)
				) {
					$message .= '<script>document.forms[0].onsubmit=document.forms[0].submit=function(){alert("' . esc_js( esc_html__( 'This is a preview of the form, letting you see its setup and design before it goes live.', 'calculated-fields-form' ) ) . '");return false;};</script>';

				}

				$page_title = ( ! empty( $atts['page_title'] ) ) ? $atts['page_title'] : '';
				remove_all_actions( 'shutdown' );
				if ( ! empty( $atts['wp_die'] ) ) {
					wp_die( $message . '<style>body{margin:1.5em !important;max-width:100% !important;box-shadow:none !important;background:white !important;padding:0 !important; border:0 !important;}html{background:white !important;}.wp-die-message{margin:0 !important;}.wp-die-message>*:not(form){visibility: hidden;}  .pac-container, .ui-tooltip, .ui-tooltip *,.ui-datepicker,.ui-datepicker *,.select2-container,.select2-container *{visibility: visible;}</style>' . apply_filters( 'cpcff_form_preview_resources', '' ), esc_html( $page_title ), 200 ); // phpcs:ignore WordPress.Security.EscapeOutput
				} elseif ( ! empty( $atts['page'] ) ) {
					print '<!DOCTYPE html><html><head profile="http://gmpg.org/xfn/11">' .
					( get_option( 'CP_CALCULATEDFIELDSF_EXCLUDE_CRAWLERS', false ) ? '<meta name="robots" content="none" />' : '' ) .
					'<meta charset="utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1">';

					do_action( 'cpcff_wp_head', ( ! empty( $atts['shortcode_atts']['id'] ) ? $atts['shortcode_atts']['id'] : 0 ) );

					print '</head><body>';
					print $message; // phpcs:ignore WordPress.Security.EscapeOutput
					print '<style>body>*:not(form){visibility: hidden; width: 0; height: 0;} .pac-container, .ui-tooltip, .ui-tooltip *,.ui-datepicker,.ui-datepicker *,.select2-container,.select2-container *{visibility: visible; width: auto; height: auto;}</style>'.apply_filters('cpcff_form_preview_resources', '');

					do_action( 'cpcff_wp_footer', ( ! empty( $atts['shortcode_atts']['id'] ) ? $atts['shortcode_atts']['id'] : 0 ) );

					print '</body></html>';
					if ( function_exists( 'wp_ob_end_flush_all' ) ) {
						wp_ob_end_flush_all();
					}
					remove_all_actions('shutdown');
					exit;
				} else {
					print $message; // phpcs:ignore WordPress.Security.EscapeOutput
					if ( function_exists( 'wp_ob_end_flush_all' ) ) {
						wp_ob_end_flush_all();
					}
					remove_all_actions('shutdown');
					exit;
				}
			}
		} // End form_preview.

		public function enqueue_loader() {
			global $post;

			if ( ! empty( $post ) && has_shortcode( $post->post_content, 'CP_CALCULATED_FIELDS' ) ) {
				wp_enqueue_style( 'cpcff_loader', plugins_url( '/css/loader.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );
			}
		} // End enqueue_loader.

		/**
		 * Returns the public version of the form wih its resources.
		 *
		 * The method calls the filters: cpcff_pre_form, and cpcff_the_form
		 *
		 * @since 1.0.171
		 * @param array $atts includes the attributes required to identify the form, and create the variables.
		 * @return string $content a text with the public version of the form and resources.
		 */
		public function public_form( $atts ) {
			// If the website is being visited by crawler, display empty text.
			if ( CPCFF_AUXILIARY::is_crawler() ) {
				return '';
			}
			if ( empty( $atts ) ) {
				$atts = array();
			}
			if ( ! $this->_is_admin && $this->_amp->is_amp() ) {
				$atts['iframe'] = 1;
				$content = $this->_amp->get_iframe( $atts );
			} else {
				/**
				 * Filters applied before generate the form,
				 * is passed as parameter an array with the forms attributes, and return the list of attributes
				 */
				$atts = apply_filters( 'cpcff_pre_form',  $atts );

				global $wpdb, $cpcff_default_texts_array;

				if ( empty( $atts['id'] ) ) {
					$myrow = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				} else {
					$myrow = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE . ' WHERE id=%d', $atts['id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}

				if ( empty( $myrow ) ) {
					return ''; // The form does not exists, or there are no forms.
				}
				$atts['id'] = $myrow->id; // If was not passed the form's id, uses the if of first form.
				$id         = $atts['id']; // Alias for the $atts[ 'id' ] variable.
				$form_template = ! empty( $atts[ 'template' ] ) ? trim( $atts[ 'template' ] ) : '';
				$this->_current_form = $this->get_form( $id );
				if ( ! empty( $atts['iframe'] ) ) {
					$url  = CPCFF_AUXILIARY::site_url( true );
					$url .= ( strpos( $url, '?' ) === false ? '/?' : '&' ) . 'cff-form=' . $id .  ( ! empty( $form_template ) ? '&template=' . urlencode( $form_template ) : '' );

					// The attributes excepting "id", "iframe", "template", and "asynchronous" are converted in javascript variables with global scope
					if ( count( $atts ) > 1 ) {
						foreach ( $atts as $i => $v ) {
							if ( ! in_array( $i, array( 'id', 'iframe', 'class', 'asynchronous', 'template' ) ) && ! is_numeric( $i ) ) {
								$nV   = ( is_numeric( $v ) ) ? $v : sanitize_text_field( wp_unslash( $v ) ); // Sanitizing the attribute's value.
								$url .= '&' . urlencode( $i ) . '=' . urlencode( $nV );
							}
						}
					}

					$iframe_id  = 'cff-iframe-' . ++self::$iframe_counter;
					$iframe_tag = '<script type="text/javascript">window.addEventListener("message", function(e) {
							if ("data" in e && typeof e.data == "object" && ! Array.isArray(e.data) && "cff_height" in e.data && "cff_iframe" in e.data) {
								try{
									let el = document.getElementById(e.data.cff_iframe);
									el.style.height = e.data.cff_height + "px";
									el.style.minHeight="auto";
									if( "parent" in window ) parent.postMessage({sentinel: "amp",type:"embed-size",height: e.data.cff_height+25}, "*");
								} catch(err){}
							}
						}, false);
						window.addEventListener("load", function() {
							let el = document.getElementById("' . $iframe_id . '");
							if(el && el.hasAttribute("data-cff-src")) el.setAttribute("src", el.getAttribute("data-cff-src"));
						});</script><iframe ' . ' id="' . $iframe_id . '"';

					if ( ! empty( $this->_current_form ) ) {
						$iframe_tag = $this->_current_form->get_height( '#' . $iframe_id ) . $iframe_tag;
					}

					$url .= ( strpos( $url, '?' ) === false ? '?' : '&' ) . 'cff_iframe=' . $iframe_id;

					set_transient( $id . '|' . $iframe_id, 1, 60*60 );

					if ( ! empty( $atts['asynchronous'] ) ) {
						$iframe_tag	 .= ' src="about:blank" data-cff-src="' . esc_attr( $url ) . '"';
					} else {
						$iframe_tag .= ' src="' . esc_attr( $url ) . '"';
					}

					$iframe_tag .= ' style="border:none;width:100%;overflow-y:hidden;" onload="try{this.width=this.contentWindow.document.body.scrollWidth;this.height=this.contentWindow.document.body.scrollHeight+40;this.style.minHeight=\'auto\';}catch(err){}" scrolling="no"></iframe>';

					return $iframe_tag;
				}

				// Initializing the $form_counter.
				if ( ! isset( $GLOBALS['codepeople_form_sequence_number'] ) ) {
					$GLOBALS['codepeople_form_sequence_number'] = 0;
				}
				++$GLOBALS['codepeople_form_sequence_number'];
				self::$form_counter = $GLOBALS['codepeople_form_sequence_number']; // Current form.

				ob_start();

				// Constant defined to protect the "inc/cpcff_public_int.inc.php" file against direct accesses.
				if ( ! defined( 'CP_AUTH_INCLUDE' ) ) {
					define( 'CP_AUTH_INCLUDE', true );
				}

				$this->_public_resources( $id ); // Load form scripts and other resources.

				/* TO-DO: This method should be analyzed after moving other functions to the main class . */
				@include CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_public_int.inc.php';

				$content = ob_get_contents();

				// The attributes excepting "id" are converted in javascript variables with a global scope.
				if ( count( $atts ) > 1 ) {
					$content .= '<script>';
					foreach ( $atts as $i => $v ) {
						if ( 'id' != $i && 'class' != $i && ! is_numeric( $i ) ) {
							$nV = ( is_numeric( $v ) ) ? $v : json_encode( $v ); // Sanitizing the attribute's value.
							if ( is_scalar( $i ) ) {
								$i        = preg_replace( '/[^a-z0-9_\-]/i', '', $i );
								$content .= 'try{ if( ! ( "cff_var" in window ) )	window["cff_var"] = {}; window["cff_var"]["' . $i . '"]=' . $nV . '; if(typeof window["' . $i . '_arr"] == "undefined") window["' . $i . '_arr"]={}; window["' . $i . '_arr"]["_' . self::$form_counter . '"]=' . $nV . '; }catch( err ){}';
							}
						}
					}
					$content .= '</script>';
				}
				ob_end_clean();

				/**
				 * Filters applied after generate the form,
				 * is passed as parameter the HTML code of the form with the corresponding <LINK> and <SCRIPT> tags,
				 * and returns the HTML code to includes in the webpage
				 */
				$content = apply_filters( 'cpcff_the_form', $content, $atts['id'] );
			}

			return $content;
		} // End  public_form.

		/**
		 * Creates a javascript variable, from: Post, Get, Session or Cookie or directly.
		 *
		 * If the webpage is visited from a crawler or search engine spider, the shortcode is replaced by an empty text.
		 *
		 * @since 1.0.175
		 * @param array $atts includes the records:
		 *              - name, the variable's name.
		 *              - value, to create a variable splicitly with the value passed as attribute.
		 *              - from, identifies the variable source (POST, GET, SESSION or COOKIE), it is optional.
		 *              - default_value, used in combination with the from attribute to populate the variable
		 *                               with the default value of the source does not exist.
		 *
		 * @return string <script> tag with the variable's definition.
		 */
		public function create_variable_shortcode( $atts ) {
			if (
				! CPCFF_AUXILIARY::is_crawler() && // Checks for crawlers or search engine spiders.
				! empty( $atts['name'] ) &&
				( $var = trim( $atts['name'] ) ) != '' // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
			) {
				if ( isset( $atts['value'] ) ) {
					$value = json_encode( $atts['value'] );
				} else {
					$from = '_';
					if ( isset( $atts['from'] ) ) {
						$from .= strtoupper( trim( $atts['from'] ) );
					}
					if ( in_array( $from, array( '_POST', '_GET', '_SESSION', '_COOKIE' ) ) ) {
						if ( isset( $GLOBALS[ $from ][ $var ] ) ) {
							$value = json_encode( $GLOBALS[ $from ][ $var ] );
						} elseif ( isset( $atts['default_value'] ) ) {
							$value = json_encode( $atts['default_value'] );
						}
					} elseif ( isset( $_POST[ $var ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						$value = json_encode( CPCFF_AUXILIARY::sanitize( $_POST[ $var ] ) ); // phpcs:ignore
					} elseif ( isset( $_GET[ $var ] ) ) {
						$value = json_encode( CPCFF_AUXILIARY::sanitize( $_GET[ $var ] ) ); // phpcs:ignore
					} elseif ( isset( $_SESSION[ $var ] ) ) {
						$value = json_encode( CPCFF_AUXILIARY::sanitize( $_SESSION[ $var ] ) ); // phpcs:ignore
					} elseif ( isset( $_COOKIE[ $var ] ) ) {
						$value = json_encode( CPCFF_AUXILIARY::sanitize( $_COOKIE[ $var ] ) ); // phpcs:ignore
					} elseif ( isset( $atts['default_value'] ) ) {
						$value = json_encode( CPCFF_AUXILIARY::sanitize( $atts['default_value'] ) ); // phpcs:ignore
					}
				}
				if ( isset( $value ) ) {
					if ( is_scalar( $var ) ) {
						$var = preg_replace( '/[^a-z0-9_\-]/i', '', $var );
						return '
						<script>
							try{
							if( ! ( "cff_var" in window ) )	window["cff_var"] = {};
							window["cff_var"]["' . $var . '"]=' . $value . ';
							}catch( err ){}
						</script>
						';
					}
				}
			}
			return '';
		} // End create_variable_shortcode.

		/**
		 * Return the list of categories associted with the forms
		 */
		public function get_categories( $html = '', &$current = null ) {
			global $wpdb;

			if ( empty( $this->_categories ) ) {
				$this->_categories = $wpdb->get_results( 'SELECT DISTINCT category FROM ' . $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE . ' WHERE category IS NOT NULL AND category <> ""', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			if ( empty( $html ) ) {
				return $this->_categories;
			}

			$output = '';
			$flag   = false;

			if ( ! empty( $this->_categories ) ) {
				foreach ( $this->_categories as $category ) {
					$selected = '';

					if ( $current === $category['category'] ) {
						$selected = 'SELECTED';
						$flag     = true;
					}

					if ( 'SELECT' == $html ) {
						$output .= '<option value="' . esc_attr( $category['category'] ) . '" ' . $selected . ' >' . esc_html( $category['category'] ) . '</option>';
					} else // DATALIST.
					{
						$output .= '<option value="' . esc_attr( $category['category'] ) . '">';
					}
				}
			}

			if ( ! $flag ) {
				$current = '';
			}

			return $output;
		} // End get_categories.

		/**
		 * Returns an instance of the active form
		 *
		 * If there is not an active form generates the instance.
		 *
		 * @since 1.0.179
		 * @return object
		 */
		public function get_form( $id ) {
			if ( ! isset( $this->_forms[ $id ] ) ) {
				$this->_forms[ $id ] = new CPCFF_FORM( $id );
			}
			return $this->_forms[ $id ];
		} // End get_active_form.

		/**
		 * Creates a new form calling the static method CPCFF_FORM::create_default
		 *
		 * @since 1.0.179
		 *
		 * @param string $form_name, the name of form.
		 * @return mixed, an instance of the created form or false.
		 */
		public function create_form( $form_name, $category_name = '', $form_template = 0, $clone_form = 0 ) {
			if ( $clone_form ) {
				$base_form = new CPCFF_FORM( $form_template );
				if ( ! empty( $base_form ) ) $new_form = $base_form->clone_form( $form_name );
			} else $new_form = CPCFF_FORM::create_default( $form_name, $category_name, $form_template );

			if ( ! empty ( $new_form ) ) {
				$this->_forms[ $new_form->get_id() ] = $new_form;
				return $new_form;
			}
			return false;
		} // End create_form.

		/**
		 * Deletes the form.
		 * The methods throw the cpcff_delete_form hook after delete the form.
		 *
		 * @since 1.0.179
		 * @param integer $id, the form's id.
		 * @return mixed, the number of delete rows or false.
		 */
		public function delete_form( $id ) {
			$deleted = $this->get_form( $id )->delete_form();
			if ( $deleted ) {
				do_action( 'cpcff_delete_form', $id );
				unset( $this->_forms[ $id ] );
			}
			return $deleted;
		} // End delete_form.

		/**
		 * Clones a form.
		 *
		 * @since 1.0.179
		 * @param integer $id, the form's id.
		 * @return mixed, an instance of cloned form or false.
		 */
		public function clone_form( $id ) {
			if ( ! isset( $this->_forms[ $id ] ) ) {
				$this->_forms[ $id ] = new CPCFF_FORM( $id );
			}
			$cloned_form = $this->_forms[ $id ]->clone_form();
			if ( $cloned_form ) {
				/**
				 * Passes as parameter the original form's id, and the new form's id
				 */
				do_action( 'cpcff_clone_form', $id, $cloned_form->get_id() );
			}
			return $cloned_form;
		} // End clone_form.

		/*********************************** PRIVATE METHODS  ********************************************/

		/**
		 * Defines the activativation/deactivation hooks, and new blog hook.
		 *
		 * Requires the cpcff_install_uninstall.inc.php file with the activate/deactivate code, and the code to run with new blogs.
		 *
		 * @sinze 1.0.171
		 * @return void.
		 */
		private function _activate_deactivate() {
			require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_install_uninstall.inc.php';
			register_activation_hook( CP_CALCULATEDFIELDSF_MAIN_FILE_PATH, array( 'CPCFF_INSTALLER', 'install' ) );
			register_deactivation_hook( CP_CALCULATEDFIELDSF_MAIN_FILE_PATH, array( 'CPCFF_INSTALLER', 'uninstall' ) );
			add_action( 'wpmu_new_blog', array( 'CPCFF_INSTALLER', 'new_blog' ), 10, 6 );
		} // End _activate_deactivate.

		/**
		 * Loads the controls scripts.
		 *
		 * Checks if there is defined the "cp_cff_resources" parameter, and loads the public or admin scripsts for the controls.
		 * If the scripsts are loaded the plugin exits the PHP execution.
		 *
		 * @return void.
		 */
		private function _load_controls_scrips() {
			if ( isset( $_REQUEST['cp_cff_resources'] ) ) {
				if ( ! defined( 'WP_DEBUG' ) || true != WP_DEBUG ) {
					error_reporting( E_ERROR | E_PARSE );
				}
				// Set the corresponding header.
				if ( ! headers_sent() ) {
					header( 'Content-type: application/javascript' );
				}

				if ( ! $this->_is_admin || 'public' == $_REQUEST['cp_cff_resources'] ) {
					require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/js/fbuilder-loader-public.php';
				} else {
					require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/js/fbuilder-loader-admin.php';
				}
				remove_all_actions( 'shutdown' );
				exit;
			}
		} // End _load_controls_scrips.

		/**
		 * Defines the shortcodes used by the plugin's code:
		 *
		 * - CP_CALCULATED_FIELDS
		 * - CP_CALCULATED_FIELDS_VAR
		 *
		 * @return void.
		 */
		private function _define_shortcodes() {
			add_shortcode( 'CP_CALCULATED_FIELDS', array( $this, 'public_form' ) );
			add_shortcode( 'CP_CALCULATED_FIELDS_VAR', array( $this, 'create_variable_shortcode' ) );
		} // End _define_shortcodes.
		/**
		 * Returns a JSON object with the configuration object.
		 *
		 * Uses the global variable $cpcff_default_texts_array, defined in the "config/cpcff_config.cfg.php"
		 *
		 * @sinze 1.0.171
		 * @param int $formid the form's id.
		 * @return string $json
		 */
		private function _get_form_configuration( $formid ) {
			$form_obj       = $this->get_form( $formid );
			$previous_label = $form_obj->get_option( 'vs_text_previousbtn', 'Previous' );
			$previous_label = ( '' == $previous_label ? 'Previous' : $previous_label );
			$next_label     = $form_obj->get_option( 'vs_text_nextbtn', 'Next' );
			$next_label     = ( '' == $next_label ? 'Next' : $next_label );

			$cpcff_texts_array = $form_obj->get_option('vs_all_texts', []);

			$obj = array(
				'pub'        => true,
				'form'		 => $formid,
				'identifier' => '_' . self::$form_counter,
				'messages'   => array(
					'required'       => $form_obj->get_option( 'vs_text_is_required', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_is_required ),
					'email'          => $form_obj->get_option( 'vs_text_is_email', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_is_email ),
					'datemmddyyyy'   => $form_obj->get_option( 'vs_text_datemmddyyyy', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_datemmddyyyy ),
					'dateddmmyyyy'   => $form_obj->get_option( 'vs_text_dateddmmyyyy', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_dateddmmyyyy ),
					'number'         => $form_obj->get_option( 'vs_text_number', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_number ),
					'digits'         => $form_obj->get_option( 'vs_text_digits', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_digits ),
					'max'            => $form_obj->get_option( 'vs_text_max', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_max ),
					'min'            => $form_obj->get_option( 'vs_text_min', CP_CALCULATEDFIELDSF_DEFAULT_vs_text_min ),
					'previous'       => $previous_label,
					'next'           => $next_label,
					'pageof'         => $cpcff_texts_array['page_of_text']['text'],
					'audio_tutorial' => $cpcff_texts_array['audio_tutorial_text']['text'],
					'minlength'      => $cpcff_texts_array['errors']['minlength']['text'],
					'maxlength'      => $cpcff_texts_array['errors']['maxlength']['text'],
					'equalTo'        => $cpcff_texts_array['errors']['equalTo']['text'],
					'accept'         => $cpcff_texts_array['errors']['accept']['text'],
					'upload_size'    => $cpcff_texts_array['errors']['upload_size']['text'],
					'phone'          => $cpcff_texts_array['errors']['phone']['text'],
					'currency'       => $cpcff_texts_array['errors']['currency']['text'],
				),
			);
				return json_encode( $obj );
		} // End _get_form_configuration.

		/**
		 * Loads the javascript and style files used by the public forms.
		 *
		 * Checks if the plugin was configured for loading HTML tags directly, or to use the WordPress functions.
		 *
		 * @since 1.0.171
		 * @param int $formid the form's id.
		 * @return void.
		 */
		private function _public_resources( $formid ) {
			if (
				get_option( 'CP_CALCULATEDFIELDSF_USE_CACHE', CP_CALCULATEDFIELDSF_USE_CACHE ) &&
				file_exists( CP_CALCULATEDFIELDSF_BASE_PATH . '/js/cache/all.js' )
			) {
				$public_js_path = plugins_url( '/js/cache/all.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH );
			}

			if ( empty( $public_js_path ) ) {
				global $cff_backend_script_generator, $cff_script_generator_min;

				$cff_backend_script_generator = 1;
				$cff_script_generator_min     = get_option( 'CP_CALCULATEDFIELDSF_USE_CACHE', CP_CALCULATEDFIELDSF_USE_CACHE );
				include_once CP_CALCULATEDFIELDSF_BASE_PATH . '/js/fbuilder-loader-public.php';
			}

			if (
				file_exists( CP_CALCULATEDFIELDSF_BASE_PATH . '/js/cache/all.js' )
			) {
				$public_js_path = plugins_url( '/js/cache/all.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH );
			} else {
				$public_js_path = CPCFF_AUXILIARY::wp_current_url() . ( ( strpos( CPCFF_AUXILIARY::wp_current_url(), '?' ) === false ) ? '?' : '&' ) . 'cp_cff_resources=public&min=' . get_option( 'CP_CALCULATEDFIELDSF_USE_CACHE', CP_CALCULATEDFIELDSF_USE_CACHE );
			}

			$config_json = $this->_get_form_configuration( $formid );

			if ( $GLOBALS['CP_CALCULATEDFIELDSF_DEFAULT_DEFER_SCRIPTS_LOADING'] ) {
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-button' );
				wp_enqueue_script( 'jquery-ui-widget' );
				wp_enqueue_script( 'jquery-ui-position' );
				wp_enqueue_script( 'jquery-ui-tooltip' );
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script( 'jquery-ui-slider' );

				wp_deregister_script( 'cp_calculatedfieldsf_validate_script' );
				wp_register_script( 'cp_calculatedfieldsf_validate_script', plugins_url( '/vendors/jquery.validate.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array( 'jquery' ), 'pro' );
				wp_enqueue_script( 'cp_calculatedfieldsf_builder_script', $public_js_path, array( 'jquery', 'jquery-ui-core', 'jquery-ui-button', 'jquery-ui-widget', 'jquery-ui-position', 'jquery-ui-tooltip', 'cp_calculatedfieldsf_validate_script', 'jquery-ui-datepicker', 'jquery-ui-slider' ), CP_CALCULATEDFIELDSF_VERSION, true );
				wp_enqueue_script( 'cp_calculatedfieldsf_builder_script_purify', plugins_url( '/vendors/purify.min.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );
				wp_localize_script( 'cp_calculatedfieldsf_builder_script', 'cp_calculatedfieldsf_fbuilder_config_' . self::$form_counter, array( 'obj' => $config_json ) );
			} else {
				// This code won't be used in most cases. This code is for preventing problems in wrong WP themes and conflicts with third party plugins.
				if ( ! $this->_are_resources_loaded ) {
					global $wp_version;
					$this->_are_resources_loaded = true; // Resources loaded.

					$includes_url = includes_url();

					// Used for compatibility with old versions of WordPress.
					$prefix_ui = ( @file_exists( CP_CALCULATEDFIELDSF_BASE_PATH . '/../../../wp-includes/js/jquery/ui/jquery.ui.core.min.js' ) ) ? 'jquery.ui.' : '';

					if ( ! wp_script_is( 'jquery', 'done' ) ) {
						print '<script type="text/javascript" src="' . esc_attr( $includes_url ) . 'js/jquery/jquery.js"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources
					}
					if ( ! wp_script_is( 'jquery-ui-core', 'done' ) ) {
						print '<script type="text/javascript" src="' . esc_attr( $includes_url ) . 'js/jquery/ui/' . esc_attr( $prefix_ui ) . 'core.min.js"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources
					}
					if ( ! wp_script_is( 'jquery-ui-datepicker', 'done' ) ) {
						print '<script type="text/javascript" src="' . esc_attr( $includes_url ) . 'js/jquery/ui/' . esc_attr( $prefix_ui ) . 'datepicker.min.js"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources
					}

					if ( version_compare( $wp_version, '5.5.4', '<' ) ) {
						if ( ! wp_script_is( 'jquery-ui-widget', 'done' ) ) {
							print '<script type="text/javascript" src="' . esc_attr( $includes_url ) . 'js/jquery/ui/' . esc_attr( $prefix_ui ) . 'widget.min.js"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources
						}
						if ( ! wp_script_is( 'jquery-ui-position', 'done' ) ) {
							print '<script type="text/javascript" src="' . esc_attr( $includes_url ) . 'js/jquery/ui/' . esc_attr( $prefix_ui ) . 'position.min.js"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources
						}
					}

					if ( ! wp_script_is( 'jquery-ui-tooltip', 'done' ) ) {
						print '<script type="text/javascript" src="' . esc_attr( $includes_url ) . 'js/jquery/ui/' . esc_attr( $prefix_ui ) . 'tooltip.min.js"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources
					}
					if ( ! wp_script_is( 'jquery-ui-mouse', 'done' ) ) {
						print '<script type="text/javascript" src="' . esc_attr( $includes_url ) . 'js/jquery/ui/' . esc_attr( $prefix_ui ) . 'mouse.min.js"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources
					}
					if ( ! wp_script_is( 'jquery-ui-slider', 'done' ) ) {
						print '<script type="text/javascript" src="' . esc_attr( $includes_url ) . 'js/jquery/ui/' . esc_attr( $prefix_ui ) . 'slider.min.js"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources
					}
					?>
					<script type='text/javascript'> if( typeof fbuilderjQuery == 'undefined' && typeof jQuery != 'undefined' ) fbuilderjQuery = jQuery;</script>
					<script type='text/javascript' src='<?php echo esc_attr( plugins_url( 'vendors/jquery.validate.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ) ); // phpcs:ignore WordPress.WP.EnqueuedResources ?>'></script>
					<script type='text/javascript' src='<?php echo esc_attr( plugins_url( 'vendors/purify.min.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ) ); // phpcs:ignore WordPress.WP.EnqueuedResources ?>'></script>
					<script type='text/javascript' src='<?php echo esc_attr( $public_js_path . ( ( strpos( $public_js_path, '?' ) == false ) ? '?' : '&' ) . 'ver=' . CP_CALCULATEDFIELDSF_VERSION ); // phpcs:ignore WordPress.WP.EnqueuedResources ?>'></script>
					<?php
				}
				?>
				<pre style="display:none !important;"><script type='text/javascript'><?php
					print 'cp_calculatedfieldsf_fbuilder_config_' . esc_js( self::$form_counter ) . '={"obj":' . $config_json . '};'; // phpcs:ignore WordPress.Security.EscapeOutput
				?></script></pre>
				<?php
			}
		} // End _public_resources.

		/** TROUBLESHOOTS SECTION **/
		public function compatibility_warnings() {
			require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_compatibility.inc.php';
			return CPCFF_COMPATIBILITY::warnings();
		} // End compatibility_warnings.

		private function troubleshoots() {
			if ( ! $this->_is_admin ) {
				if ( get_option( 'CP_CALCULATEDFIELDSF_OPTIMIZATION_PLUGIN', CP_CALCULATEDFIELDSF_OPTIMIZATION_PLUGIN ) * 1 ) {
					// Solves a conflict caused by the "Speed Booster Pack" plugin.
					add_filter( 'option_sbp_settings', 'CPCFF_MAIN::speed_booster_pack_troubleshoot' );

					// Solves a conflict caused by the "Autoptimize" plugin.
					if ( class_exists( 'autoptimizeOptionWrapper' ) ) {
						$GLOBALS['CP_CALCULATEDFIELDSF_DEFAULT_DEFER_SCRIPTS_LOADING'] = true;
						add_filter(
							'cpcff_pre_form',
							function ( $atts ) {
								add_filter(
									'autoptimize_js_include_inline',
									function ( $p ) {
										return false;
									}
								);
								add_filter(
									'autoptimize_filter_js_noptimize',
									function ( $p1, $p2 ) {
										return true;
									},
									10,
									2
								);
								add_filter(
									'autoptimize_filter_html_noptimize',
									function ( $p1, $p2 ) {
										return true;
									},
									10,
									2
								);
								return $atts;
							}
						);
					}

					// Solves conflicts with "LiteSpeed Cache" plugin.
					if ( function_exists( 'run_litespeed_cache' ) ) {
						add_action( 'the_post', 'CPCFF_MAIN::litespeed_control_set_nocache' );
						add_filter( 'litespeed_optimize_js_excludes', 'CPCFF_MAIN::rocket_exclude_js' );
					}

					// Solves a conflict caused by the "WP Rocket" plugin.
					add_filter( 'rocket_exclude_js', 'CPCFF_MAIN::rocket_exclude_js' );
					add_filter( 'rocket_exclude_defer_js', 'CPCFF_MAIN::rocket_exclude_js' );
					add_filter( 'rocket_delay_js_exclusions', 'CPCFF_MAIN::rocket_exclude_js' );

					// Some "WP Rocket" functions can be use with "WP-Optimize".
					add_filter( 'wp-optimize-minify-blacklist', 'CPCFF_MAIN::rocket_exclude_js' );
					add_filter( 'wp-optimize-minify-default-exclusions', 'CPCFF_MAIN::rocket_exclude_js' );
					add_filter( 'rocket_preload_exclude_urls', function( $regexes ) {
						$regexes[] = '.*cff-form.*';
						return $regexes;
					});
				}
				add_filter( 'rocket_excluded_inline_js_content', 'CPCFF_MAIN::rocket_exclude_inline_js' );
				add_filter( 'rocket_defer_inline_exclusions', 'CPCFF_MAIN::rocket_exclude_inline_js' );
				add_filter( 'rocket_delay_js_exclusions', 'CPCFF_MAIN::rocket_exclude_inline_js' );

				// For Breeze conflicts.
				if ( defined( 'BREEZE_VERSION' ) ) {
					add_filter( 'breeze_filter_html_before_minify', 'CPCFF_MAIN::breeze_check_content', 10 );
					add_filter( 'breeze_html_after_minify', 'CPCFF_MAIN::breeze_return_content', 10 );
				}
			}
		} // End troubleshoots.

		public static function litespeed_control_set_nocache( &$post ) {
			try {
				if (
					is_object( $post ) &&
					isset( $post->post_content ) &&
					stripos( $post->post_content, '[CP_CALCULATED_FIELDS' ) !== false
				) {
					do_action( 'litespeed_control_set_nocache', 'nocache CFF Form' );
				}
			} catch ( Exception $err ) {
				error_log( $err->getMessage() );}
			return $post;
		} // End litespeed_control_set_nocache.

		public static function speed_booster_pack_troubleshoot( $option ) {
			if ( is_array( $option ) && isset( $option['jquery_to_footer'] ) ) {
				unset( $option['jquery_to_footer'] );
			}
			return $option;
		} // End speed_booster_pack_troubleshoot.

		public static function rocket_exclude_js( $excluded_js ) {
			$excluded_js[] = '/jquery.js';
			$excluded_js[] = '/jquery.min.js';
			$excluded_js[] = '/jquery/';
			$excluded_js[] = '/calculated-fields-form/';

			$excluded_js[] = '/jquery/(.*)';
			$excluded_js[] = '(.*)/jquery.js';
			$excluded_js[] = '(.*)/jquery.min.js';
			$excluded_js[] = '(.*)/jquery/(.*)';
			$excluded_js[] = '(.*)/calculated-fields-form/(.*)';
			return $excluded_js;
		} // End rocket_exclude_js.

		public static function rocket_exclude_inline_js( $excluded_js = array() ) {
			$excluded_js[] = 'form_structure_';
			$excluded_js[] = 'fbuilderjQuery';
			$excluded_js[] = 'fbuilderjQuery(.*)';
			$excluded_js[] = '(.*)fbuilderjQuery(.*)';
			$excluded_js[] = 'doValidate_';
			$excluded_js[] = 'cpcff_default';
			$excluded_js[] = 'cp_calculatedfieldsf_fbuilder_config_';
			$excluded_js[] = 'form_structure(.*)';
			$excluded_js[] = 'doValidate(.*)';
			$excluded_js[] = 'cp_calculatedfieldsf_fbuilder_config(.*)';
			return $excluded_js;
		} // End rocket_exclude_inline_js.

		public static function breeze_check_content( $content ) {
			if ( strpos( $content, 'form_structure_' ) !== false || strpos( $content, 'cp_calculatedfieldsf_fbuilder_config_' ) !== false ) {
				global $cff_breeze_content_bk;
				$cff_breeze_content_bk = $content;
			}
			return $content;
		} // End breeze_check_content.

		public static function breeze_return_content( $content ) {
			global $cff_breeze_content_bk;
			if ( ! empty( $cff_breeze_content_bk ) ) {
				$content = $cff_breeze_content_bk;
				unset( $cff_breeze_content_bk );
			}
			return $content;
		} // End breeze_return_content.
	} // End CPCFF_MAIN.
}
