<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (! class_exists('CPCFF_CAPTCHA')) {
    class CPCFF_CAPTCHA
    {
        private static function _is_captcha_enabled($form)
        {
            return ! (
                $form->get_option('cv_enable_captcha', CP_CALCULATEDFIELDSF_DEFAULT_cv_enable_captcha) == 'false' ||
                $form->get_option('cv_enable_captcha', CP_CALCULATEDFIELDSF_DEFAULT_cv_enable_captcha) == false
            );
        }

        private static function _get_captcha_method($form)
        {
			return CP_CALCULATEDFIELDSF_DEFAULT_cv_captcha_method;
        }

        private static function _preprocess_sequence($sequence = '')
        {
            $sequence = preg_replace('/[^\d]/', '', $sequence);
            return $sequence;
        }

        public static function validate_captcha($form, $sequence, $user_input)
        {
            if (! self::_is_captcha_enabled($form)) {
                return true;
            }
            $sequence = self::_preprocess_sequence($sequence);
            $expected_value = CP_SESSION::get_var('rand_code_' . $sequence);
            return $expected_value !== '' && $expected_value !== false && strtolower($user_input) === strtolower($expected_value);
        }

        public static function get_captcha($form_id, $sequence = '')
        {
            $form = CPCFF_MAIN::instance()->get_form($form_id);
            if (! $form || ! self::_is_captcha_enabled($form)) {
                return;
            }
            $sequence = self::_preprocess_sequence($sequence);
            CPCFF_CAPTCHA_MATH::get_captcha($form, $sequence);
        }

        public static function display_captcha($form, $sequence, $cpcff_texts_array)
        {
            if (! CPCFF_CAPTCHA::_is_captcha_enabled($form)) {
                return;
            }
            $sequence = self::_preprocess_sequence($sequence);
            CPCFF_CAPTCHA_MATH::display_captcha($form, $sequence, $cpcff_texts_array);
        }

        public static function script_for_reset($form, $sequence)
        {
            if (! CPCFF_CAPTCHA::_is_captcha_enabled($form)) {
                return;
            }
            $sequence = self::_preprocess_sequence($sequence);
            CPCFF_CAPTCHA_MATH::script_for_reset($form, $sequence);
        }
    }
}

if (! class_exists('CPCFF_CAPTCHA_MATH')) {
    class CPCFF_CAPTCHA_MATH
    {
        private static function _get_operands($form, $sequence = '')
        {
            $complexity = $form->get_option('cv_captcha_math_complexity', 1);
            $a = rand(1, pow(10, $complexity) - 1);
            $b = rand(1, pow(10, $complexity) - 1);
            CP_SESSION::set_var('rand_code_' . $sequence, $a + $b);
            return array($a, $b);
        }
        public static function get_captcha($form, $sequence = '')
        {
            list($a, $b) = self::_get_operands($form, $sequence);
            wp_send_json_success(array('captcha' => $a . ' + ' . $b . ' = '));
        }

        public static function display_captcha($form, $sequence, $cpcff_texts_array)
        {
            list($a, $b) = CPCFF_CAPTCHA_MATH::_get_operands($form, $sequence);
        ?>
            <div class="fields">
                <label><?php echo ($cpcff_texts_array['captcha_text']['text']); ?></label>
                <div class="dfield">
                    <span id="cff_captcha_math_<?php echo $sequence; ?>" class="cff-captcha-math-label"><?php echo esc_html($a . ' + ' . $b . ' = '); ?></span>
                    <input type="text" size="8" name="hdcaptcha_cp_calculated_fields_form_post" id="hdcaptcha_cp_calculated_fields_form_post_<?php echo $sequence; ?>" value="" autocomplete="off" />
                    <div class="error message" id="hdcaptcha_error_<?php echo $sequence; ?>" style="display:none;"></div>
                </div>
                <div class="clearer"></div>
            </div>
        <?php
        }

        public static function script_for_reset( $form, $sequence = '' )
        {
        ?>
            $dexQuery.ajax({
                type: "GET",
                url: _form.prop('action').replace(/\?$/g, ''),
                data: {
                    ps: '_<?php echo esc_js($sequence); ?>',
                    cp_calculatedfieldsf: 'captcha',
                    cff: '<?php echo esc_js($form->get_id()); ?>',
                    no_cache: Date.now()
                },
                success: function(response) {
                    if (response.success && response.data.captcha) {
                        _form.find('[id^="cff_captcha_math_"]').text(response.data.captcha);
                    }
                }
            });
        <?php
        }
    }
}
