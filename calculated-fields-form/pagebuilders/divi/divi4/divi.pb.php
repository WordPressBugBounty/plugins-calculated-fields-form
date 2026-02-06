<?php
/**
 * Calculated Fields Form Module for Divi
 */

class CFF_DIVI extends ET_Builder_Module
{
    public $slug = 'cff_divi';
    public $vb_support = 'on';

    public function init()
    {
        $this->name = esc_html__('Calculated Fields Form', 'calculated-fields-form');
        $this->settings_modal_toggles = array(
            'general' => array(
                'toggles' => array(
                    'main_content' => esc_html__('Form', 'calculated-fields-form'),
                ),
            ),
        );

		$this->advanced_fields = array(
            'background'     => false, // Hide background tab completely
            'link_options'   => false, // Hide link options
		);
    }

	public function get_fields()
    {
        global $wpdb;
        $options = array( 0 => esc_html__('- Select a form -', 'calculated-fields-form'));
        $default = '';

        if (defined('CP_CALCULATEDFIELDSF_FORMS_TABLE')) {
            $rows = $wpdb->get_results(
                "SELECT id, form_name FROM " . $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE
            );

            foreach ($rows as $item) {
                $options[$item->id] = '(' . $item->id . ') ' . $item->form_name;
            }
        }

        return array(
            'cff_form_id' => array(
                'label'           => esc_html__('Select form', 'calculated-fields-form'),
                'type'            => 'select',
                'options'         => $options,
                'option_category' => 'basic_option',
                'description'     => esc_html__('Select the form.', 'calculated-fields-form'),
                'toggle_slug'     => 'main_content',
            ),
            'cff_class_name' => array(
                'label'           => esc_html__('Class name', 'calculated-fields-form'),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'toggle_slug'     => 'main_content',
            ),
            'cff_attributes' => array(
                'label'           => esc_html__('Additional attributes', 'calculated-fields-form'),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'description'     => 'attr1="value1" attr2="value2"',
                'toggle_slug'     => 'main_content',
            ),
            'cff_iframe' => array(
                'label'           => esc_html__('Load the Form within an iFrame', 'calculated-fields-form'),
                'type'            => 'yes_no_button',
				'options'         => array(
					'off' => esc_html__('No', 'your-text-domain'),
					'on'  => esc_html__('Yes', 'your-text-domain'),
				),
				'default'         => 'off',
                'option_category' => 'basic_option',
                'description'     => esc_html__('Load the form within an iframe tag to keep it separate from the page context', 'calculated-fields-form'),
                'toggle_slug'     => 'main_content',
            ),
        );
    }

    public function render($unprocessed_props, $content = null, $render_slug = '')
    {
        $output = '';
        $form = absint($this->props['cff_form_id']);

        if ($form > 0) {
            $output = '[CP_CALCULATED_FIELDS id="' . $form . '"';

            $class_name = sanitize_text_field($this->props['cff_class_name']);
            if (!empty($class_name)) {
                $output .= ' class="' . esc_attr($class_name) . '"';
            }

            $attributes = sanitize_text_field($this->props['cff_attributes']);
            if (!empty($attributes)) {
                $output .= ' ' . $attributes;
            }

            $iframe = sanitize_text_field($this->props['cff_iframe']);
            if (!empty($attributes)) {
                $output .= ' iframe="1"';
            }

            $output .= ']';
        }

        return do_shortcode($output);
    }
}

new CFF_DIVI();