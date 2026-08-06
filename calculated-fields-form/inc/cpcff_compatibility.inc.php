<?php
/**
 * Operations for checking the compatibility with other plugins: CPCFF_COMPATIBILITY class
 *
 * @package CFF.
 * @since 1.0.370
 */

if(!class_exists('CPCFF_COMPATIBILITY'))
{
	class CPCFF_COMPATIBILITY
	{
		private static $elementor_cache = array();

		private static function init()
		{
			return array(
				array(
					'plugin' => 'Breeze - WordPress Cache Plugin',
					'check'	 => 'BREEZE_VERSION',
					'type'	 => 'constant',
					'mssg'	 => __('There is active the <b>Breeze - WordPress Cache Plugin</b> plugin. If the forms are not visible, please tick the <i>"There is active an optimization plugin in WordPress"</i> and Purge the Breeze cache.', 'calculated-fields-form')
				),
				array(
					'plugin' => 'Fast Velocity Minify',
					'check'	 => 'fvm_compat_checker',
					'type'	 => 'function',
					'mssg'	 => __('There is active the <b>Fast Velocity Minify</b> plugin. If the forms are not visible, please try disabling the <i>"Disable minification on JS files"</i> or <i>"Disable JavaScript processing"</i> options in the <b>Fast Velocity Minify</b> settings.', 'calculated-fields-form')
				),
				array(
					'plugin' => 'W3 Total Cache',
					'check'	 => 'W3TC',
					'type'	 => 'constant',
					'mssg'	 => __('There is active the <b>W3 Total Cache</b> plugin. If the forms are not visible, please tick the checkbox.', 'calculated-fields-form')
				),
				array(
					'plugin' => 'Autoptimize',
					'check'	 => 'autoptimize',
					'type'	 => 'function',
					'mssg'	 => __('There is active the <b>Autoptimize</b> plugin. If the forms are not visible, please try disabling the <i>"Force JavaScript in &lt;head&gt;"</i> option in the <b>Autoptimize</b> settings, or remove the jQuery file from the <i>"Exclude scripts from Autoptimize"</i> one.', 'calculated-fields-form')
				),
				array(
					'plugin' => 'LiteSpeed Cache',
					'check'	 => 'run_litespeed_cache',
					'type'	 => 'function',
					'mssg'	 => __('There is active the <b>LiteSpeed Cache</b> plugin. If the forms are not visible, please try disabling the <i>"JS Combine"</i> option in the <b>Optimize</b> tab of <b>LiteSpeed Cache</b> settings.', 'calculated-fields-form')
				),
				array(
					'plugin' => 'WP Rocket',
					'check'	 => 'WP_ROCKET_VERSION',
					'type'	 => 'constant',
					'mssg'	 => __('There is active the <b>WP Rocket</b> plugin. If the forms are not visible, please try disabling the <i>"Combine JavaScript files"</i> option in the <b>FILE OPTIMIZATION</b> tab of <b>WP Rocket</b> settings, and remember to clear the website cache.', 'calculated-fields-form')
				),
				array(
					'plugin' => 'SG Optimizer',
					'check'	 => 'siteground_optimizer_loader',
					'type'	 => 'object',
					'mssg'	 => __('There is active the <b>SG Optimizer</b> plugin. If the forms are not visible, please, follows the steps below: <ol><li>Go to the menu option: "SG Optimizer > Frontend"</li><li>Enter in the "JAVASCRIPT" tab</li><li>Select  the jQuer path through the "Exclude from Deferral of Render-blocking JS"</li><li>Purge the "SG Cache"</li></ol> If the issue persists, disable the options: <i>"Minify the HTML Output"</i> and <i>"Minify JavaScript Files"</i> in the <b>SG Optimizer</b> settings, and once more, to purge the website cache.', 'calculated-fields-form')
				),
				array(
					'plugin' => 'Hummingbird',
					'check'	 => 'WPHB_VERSION',
					'type'	 => 'constant',
					'mssg'	 => __('There is active the <b>Hummingbird</b> plugin. If the forms are not visible, check the <i>"Hummingbird &gt; Asset Optimization"</i> options. Make sure that jQuery or other required scripts are not configured to load after the page loads. Remember to purge the website cache, after edit the plugin settings.', 'calculated-fields-form')
				),
			);
		} // End init

		private static function format_warning_mssg($plugin)
		{
			return '<div class="cff-compatibility-warning">'.$plugin['mssg'].'</div>';
		} // End format_warning_mssg

		public static function warnings()
		{
			$plugins = self::init();
			$warning_mssgs = '';
			foreach($plugins as $plugin)
			{
				switch($plugin['type'])
				{
					case 'function':
						if(function_exists($plugin['check']))
						{
							$warning_mssgs .= self::format_warning_mssg($plugin);
						}
						break;
					case 'class':
						if(class_exists($plugin['check']))
						{
							$warning_mssgs .= self::format_warning_mssg($plugin);
						}
						break;
					case 'object':
						if(isset($GLOBALS[$plugin['check']]))
						{
							$warning_mssgs .= self::format_warning_mssg($plugin);
						}
						break;
					case 'constant':
						if(defined($plugin['check']))
						{
							$warning_mssgs .= self::format_warning_mssg($plugin);
						}
						break;
				}
			}
			return $warning_mssgs;
		} // End blog_id

        public static function troubleshoots()
        {
            if (! is_admin()) {
                if (get_option('CP_CALCULATEDFIELDSF_OPTIMIZATION_PLUGIN', CP_CALCULATEDFIELDSF_OPTIMIZATION_PLUGIN) * 1) {
                    // Solves a conflict caused by the "Speed Booster Pack" plugin
                    add_filter('option_sbp_settings', [self::class, 'speed_booster_pack_troubleshoot']);

                    // Solves a conflict caused by the "Autoptimize" plugin
                    if (class_exists('autoptimizeOptionWrapper')) {
                        $GLOBALS['CP_CALCULATEDFIELDSF_DEFAULT_DEFER_SCRIPTS_LOADING'] = true;
                        add_filter('cpcff_pre_form', function ($atts) {
                            add_filter('autoptimize_js_include_inline', function ($p) {
                                return false;
                            });
                            add_filter('autoptimize_filter_js_noptimize', function ($p1, $p2) {
                                return true;
                            }, 10, 2);
                            add_filter('autoptimize_filter_html_noptimize', function ($p1, $p2) {
                                return true;
                            }, 10, 2);
                            return $atts;
                        });
                    }

                    // Solves conflicts with "LiteSpeed Cache" plugin
                    if (function_exists('run_litespeed_cache')) {
                        add_action('the_post', [self::class, 'litespeed_control_set_nocache']);
                        add_filter('litespeed_optimize_js_excludes', [self::class, 'rocket_exclude_js']);
                    }

                    // Solves a conflict caused by the "WP Rocket" plugin
                    add_filter('rocket_exclude_js', [self::class, 'rocket_exclude_js']);
                    add_filter('rocket_exclude_defer_js', [self::class, 'rocket_exclude_js']);
                    add_filter('rocket_delay_js_exclusions', [self::class, 'rocket_exclude_js']);

                    // Some "WP Rocket" functions can be use with "WP-Optimize"
                    add_filter('wp-optimize-minify-blacklist', [self::class, 'rocket_exclude_js']);
                    add_filter('wp-optimize-minify-default-exclusions', [self::class, 'rocket_exclude_js']);
                    add_filter('rocket_preload_exclude_urls', function ($regexes) {
                        $regexes[] = '.*cff-form.*';
                        return $regexes;
                    });
                }
                add_filter('rocket_excluded_inline_js_content', [self::class, 'rocket_exclude_inline_js']);
                add_filter('rocket_defer_inline_exclusions', [self::class, 'rocket_exclude_inline_js']);
                add_filter('rocket_delay_js_exclusions', [self::class, 'rocket_exclude_inline_js']);

                // For Breeze conflicts
                if (defined('BREEZE_VERSION')) {
                    add_filter('breeze_filter_html_before_minify', [self::class, 'breeze_check_content']);
                    add_filter('breeze_html_after_minify', [self::class, 'breeze_return_content']);
                }
            } else {
                add_action('elementor/widget/before_render_content', function ($widget) {
                    try {
                        $widget_name = $widget->get_name();
                        if ('text-editor' ===  $widget_name || 'shortcode' ===  $widget_name) {
                            $settings = $widget->get_settings();
                            if (isset($settings['editor']) && strpos($settings['editor'], 'CP_CALCULATED_FIELDS') !== false) {
                                $content = $settings['editor'];
                            } else if (isset($settings['shortcode']) && strpos($settings['shortcode'], 'CP_CALCULATED_FIELDS') !== false) {
                                $content = $settings['shortcode'];
                            }

                            if (! empty($content)) {
                                $pattern = '/\[CP_CALCULATED_FIELDS\s+([^\]]*)\]/';
                                $content = preg_replace_callback($pattern, function ($matches) {
                                    $attributes = $matches[1];

                                    // Check if iframe attribute already exists
                                    if (preg_match('/\biframe\s*=\s*["\']?[^"\'\s]*["\']?/', $attributes)) {
                                        // Replace existing iframe attribute with iframe="1"
                                        $attributes = preg_replace(
                                            '/\biframe\s*=\s*["\']?[^"\'\s]*["\']?/',
                                            'iframe="1"',
                                            $attributes
                                        );
                                    } else {
                                        // Add iframe="1" attribute
                                        $attributes = trim($attributes) . ' iframe="1"';
                                    }

                                    return '[CP_CALCULATED_FIELDS ' . $attributes . ']';
                                }, $content);

                                if (isset($settings['editor'])) {
                                    $settings['editor'] = $content;
                                } else {
                                    $settings['shortcode'] = $content;
                                }
                                $widget->set_settings($settings);
                            }
                        }
                    } catch (Exception $err) {
                    }
                });

                add_action('cff_admin_state_changed', function ($context = '') {
                    do_action('litespeed_purge_all_object');
                });
            }

            add_filter('litespeed_esi_nonces', [self::class, 'litespeed_register_esi_nonces']);
        } // End troubleshoots

        public static function litespeed_register_esi_nonces($nonces = [])
        {
            if (is_array($nonces)) {
                $cff_patterns = [
                    'cff-*',
                    'cff_*',
                    'cpcff_*',
                    'cpcff-*',
                    'addon-*',
                ];
                $nonces = array_merge($nonces, $cff_patterns);
                $nonces = array_unique($nonces);
            }
            return $nonces;
        }

        public static function litespeed_control_set_nocache(&$post)
        {
            try {
                if (
                    is_object($post) &&
                    isset($post->post_content) &&
                    stripos($post->post_content, '[CP_CALCULATED_FIELDS') !== false
                ) do_action('litespeed_control_set_nocache', 'nocache CFF Form');
            } catch (Exception $err) {
                error_log($err->getMessage());
            }
            return $post;
        } // End litespeed_control_set_nocache

        public static function speed_booster_pack_troubleshoot($option)
        {
            if (is_array($option) && isset($option['jquery_to_footer'])) unset($option['jquery_to_footer']);
            return $option;
        } // End speed_booster_pack_troubleshoot

        public static function rocket_exclude_js($excluded_js)
        {
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
        } // End rocket_exclude_js

        public static function rocket_exclude_inline_js($excluded_js = [])
        {
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
        } // End rocket_exclude_inline_js

        public static function breeze_check_content($content)
        {
            if (strpos($content, 'form_structure_') !== false || strpos($content, 'cp_calculatedfieldsf_fbuilder_config_') !== false) {
                global $cff_breeze_content_bk;
                $cff_breeze_content_bk = $content;
            }
            return $content;
        } // End breeze_check_content

        public static function breeze_return_content($content)
        {
            global $cff_breeze_content_bk;
            if (! empty($cff_breeze_content_bk)) {
                $content = $cff_breeze_content_bk;
                unset($cff_breeze_content_bk);
            }
            return $content;
        } // End breeze_return_content

		public static function set_elementor_cache($id, $value) {
			self::$elementor_cache[$id] = $value;
		} // End set_elementor_cache

		public static function get_elementor_cache($id) {
			if ( ! isset( self::$elementor_cache[$id] ) ) return null;
			return self::$elementor_cache[$id];
		} // End get_elementor_cache
	} // End CPCFF_COMPATIBILITY
}