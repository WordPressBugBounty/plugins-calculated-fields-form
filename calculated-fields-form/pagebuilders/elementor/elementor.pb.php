<?php

// phpcs:disable Squiz.Commenting.FunctionComment.Missing
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Elementor_CFF_Widget extends Widget_Base {

	public function get_name() {
		return 'calculated-fields-form';
	} // End get_name

	public function get_title() {
		return 'Calculated Fields Form';
	} // End get_title

	public function get_icon() {
		return 'eicon-apps';
	} // End get_icon

	public function get_categories() {
		return array( 'calculated-fields-form-cat' );
	} // End get_categories

	public function is_reload_preview_required() {
		return true;
	} // End is_reload_preview_required

	protected function register_controls() {
		global $wpdb;

		$this->start_controls_section(
			'cff_section',
			array(
				'label' => __( 'Calculated Fields Form', 'calculated-fields-form' ),
			)
		);

		$options = array();
		$default = '';

		$rows = $wpdb->get_results( 'SELECT id, form_name FROM ' . $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows as $item ) {
			$options[ $item->id ] = $item->form_name;
			if ( empty( $default ) ) {
				$default = $item->id;
			}
		}

		$templates = array('' => ' - ');
		$default_template = '';

		require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_templates.inc.php';
		$templates_list = \CPCFF_TEMPLATES::load_templates();
		foreach ( $templates_list as $template_item ) {
			$templates[ $template_item['prefix'] ] = esc_html( $template_item['title'] );
		}

		$this->add_control(
			'form',
			array(
				'label'   => __( 'Select a form', 'calculated-fields-form' ),
				'type'    => Controls_Manager::SELECT,
				'classes' => 'cff-widefat',
				'options' => $options,
				'default' => $default,
			)
		);

		$this->add_control(
			'attrs',
			array(
				'label'       => __( 'Additional attributes', 'calculated-fields-form' ),
				'type'        => Controls_Manager::TEXT,
				'classes'     => 'cff-widefat',
				'input_type'  => 'text',
				'placeholder' => 'attr="value"',
			)
		);

		$this->add_control(
			'iframe',
			array(
				'label' =>  __('Load form into an iframe', 'calculated-fields-form'),
				'type' => Controls_Manager::SWITCHER,
				'description' => __('If you are using a cache management or website optimization plugin and the form is not displaying on the public website, please check the box labeled "Load form into an iframe." This will help ensure that the form is properly rendered for users.', 'calculated-fields-form')
			)
		);

		$this->add_control(
			'templates',
			array(
				'label' 	=>  __('Apply template', 'calculated-fields-form'),
				'type' 		=> Controls_Manager::SELECT,
				'classes' 	=> 'cff-widefat cff-template-selector',
				'options' 	=> $templates,
				'default' 	=> $default_template
			)
		);

		$this->add_control(
			'template_thumbnail',
			array(
				'type' 			=> Controls_Manager::RAW_HTML,
				'show_label' 	=> false,
				'classes' 		=> 'cff-widefat',
				'raw'			=> '<div class="cff-thumbnail-container"></div>'
			)
		);

		if ( current_user_can( 'manage_options' ) ) {
			$this->add_control(
				'open-editor',
				array(
					'type'        => Controls_Manager::BUTTON,
					'separator'   => 'before',
					'text'        => __( 'Edit form', 'calculated-fields-form' ),
					'classes'     => 'cff-edit-form-btn',
					'button_type' => 'success',
					'event'       => 'cff_open_form_editor',
				)
			);
		}

		$this->end_controls_section();
	} // End register_controls

	private function _get_shortcode() {
		$settings  	= $this->get_settings_for_display();

		$form		= ! empty( $settings['form'] ) && is_numeric( $settings['form'] ) ? intval( $settings['form'] ) : 0;
		$attrs     	= ! empty( $settings['attrs']  ) ? sanitize_text_field( $settings['attrs'] ) : '';
		$iframe 	= ! empty( $settings['iframe'] ) ? ' iframe="1"' : '';
		$template 	= ! empty( $settings['templates'] ) ? sanitize_text_field($settings['templates']) : '';

		if ( ! empty( $attrs ) ) {
			$attrs = ' ' . $attrs;
		}

		if ( ! empty( $template ) ) {
			$template = ' template="' . esc_attr( $template ) . '"';
		}

		return '[CP_CALCULATED_FIELDS id="' . $form . '"' . $attrs . $template . $iframe . ']';
	} // End _get_shortcode

	protected function render() {
		$shortcode = $this->_get_shortcode();
		if (
			isset( $_REQUEST['action'] ) &&
			(
				'elementor' == $_REQUEST['action'] ||
				'elementor_ajax' == $_REQUEST['action']
			)
		) {
			$atts = preg_replace( array( '/\[\s*cp_calculated_fields\s*/i', '/\s*\]$/' ), '', $shortcode );
			$atts = shortcode_parse_atts( $atts );
			$url  = \CPCFF_AUXILIARY::site_url();
			$url .= ( ( strpos( $url, '?' ) === false ) ? '?' : '&' ) . 'cff-editor-preview=1&cff-amp-redirected=1';
			foreach ( $atts as $i => $v ) {
				if ( is_numeric( $i ) ) {
					continue;
				}
				if ( 'id' == $i ) {
					$url .= '&cff-amp-form';
				} elseif ( 'template' == $i ) {
					$url .= '&cff-form-attr-template';
				} else {
					$url .= '&' . urlencode( sanitize_text_field( $i ) );
				}
				$url .= '=' . urlencode( sanitize_text_field( $v ) );
			}

			?>
			<div class="cff-iframe-container" style="position:relative;">
				<div class="cff-iframe-overlay" style="position:absolute;top:0;right:0;bottom:0;left:0;"></div>
				<iframe height="0" width="100%" src="<?php print esc_attr( $url ); ?>">
			</div>
			<?php
		} else {
			print do_shortcode( shortcode_unautop( $shortcode ) );
		}
	} // End render

	public function render_plain_content() {
		echo esc_html( $this->_get_shortcode() );
	} // End render_plain_content
} // End ClassElementor_CFF_Widget

class Elementor_CFFV_Widget extends Widget_Shortcode {

	public function get_name() {
		return 'calculated-fields-form-variable';
	} // End get_name

	public function get_title() {
		return 'Define Variable';
	} // End get_title

	public function get_icon() {
		return 'eicon-shortcode';
	} // End get_icon

	public function get_categories() {
		return array( 'calculated-fields-form-cat' );
	} // End get_categories

	public function is_reload_preview_required() {
		return true;
	} // End is_reload_preview_required

	protected function register_controls() {
		$this->start_controls_section(
			'section_shortcode',
			array(
				'label' => __( 'Variable Shortcode', 'calculated-fields-form' ),
			)
		);

		$this->add_control(
			'shortcode',
			array(
				'label'       => __( 'Variable shortcode', 'calculated-fields-form' ),
				'type'        => Controls_Manager::TEXT,
				'classes'     => 'cff-widefat',
				'input_type'  => 'text',
				'default'     => '[CP_CALCULATED_FIELDS_VAR name="varname"]',
				'description' => '<a href="https://cff.dwbooster.com/documentation#javascript-variables" target="_blank">' . __( 'I need help [+]', 'calculated-fields-form' ) . '</a>',
			)
		);

		$this->end_controls_section();
	} // End register_controls

	protected function render() {
		$cff_main = \CPCFF_MAIN::instance();
		add_shortcode( 'CP_CALCULATED_FIELDS_VAR', array( $cff_main, 'create_variable_shortcode' ) );
		parent::render();
	} // End render
} // End ClassElementor_CFFV_Widget

// Register the widgets.
Plugin::instance()->widgets_manager->register( new Elementor_CFF_Widget() );
Plugin::instance()->widgets_manager->register( new Elementor_CFFV_Widget() );
