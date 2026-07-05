<?php
/**
 * Orchestrator for the Clippy-style inactivity assistant.
 *
 * Loaded only from inc/cpcff_admin_int.inc.php on the form-builder page.
 * @package CalculatedFieldsForm
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Boot the Clippy orchestrator on admin_init (after cap and scope guards).
 *
 * @return void
 */
function cpcff_clippy_init() {
    // Capability and context guards. Mirrors cpcff_admin_int.inc.php's own gate.
    if (! is_admin()) {
        return;
    }
    if (! current_user_can(apply_filters('cpcff_forms_edition_capability', 'manage_options'))) {
        return;
    }

    // Configurable constants. Override via wp-config.php or mu-plugin.
    if (! defined('CPCFF_CLIPPY_IDLE_MS')) {
        define('CPCFF_CLIPPY_IDLE_MS', 240000);
    }
    if (! defined('CPCFF_CLIPPY_VISIBLE_MS')) {
        define('CPCFF_CLIPPY_VISIBLE_MS', 60000);
    }
    if (! defined('CPCFF_CLIPPY_REAPPEAR_MS')) {
        define('CPCFF_CLIPPY_REAPPEAR_MS', 600000);
    }
    if (! defined('CPCFF_CLIPPY_Z_INDEX')) {
        define('CPCFF_CLIPPY_Z_INDEX', 1000001);
    }
    if (! defined('CPCFF_CLIPPY_URL')) {
        define('CPCFF_CLIPPY_URL', 'https://cff.dwbooster.com/customization');
    }

    printf(
        '<div id="cff-clippy-root" role="complementary" aria-label="%s" aria-live="polite" hidden>'
            . '<img id="cff-clippy-mascot" src="%s" alt="" width="96" height="96" aria-hidden="true" />'
            . '<div id="cff-clippy-bubble" role="group">'
            . '<button type="button" id="cff-clippy-close" class="cff-clippy-close" aria-label="%s">&times;</button>'
            . '<p class="cff-clippy-message"></p>'
            . '<button type="button" id="cff-clippy-cta" class="button button-primary cff-clippy-cta"></button>'
            . '</div>'
            . '</div>',
        esc_attr__('Assistant', 'calculated-fields-form'),
        esc_url(plugins_url('inc/cpcff-clippy/assets/clippy.svg', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH)),
        esc_attr__('Dismiss this assistant', 'calculated-fields-form')
    );


    echo '<script>window.cffClippyI18n = ' . wp_json_encode(array(
        'bubbleMessage'    => esc_html__('Want us to build your project?', 'calculated-fields-form'),
        'ctaLabel'         => esc_html__('Get a custom quote', 'calculated-fields-form'),
        'closeLabel'       => esc_attr__('Dismiss this assistant', 'calculated-fields-form'),
        'containerLabel'   => esc_attr__('Assistant', 'calculated-fields-form'),
        'customizationUrl' => esc_url_raw(CPCFF_CLIPPY_URL),
        'idleMs'           => (int) CPCFF_CLIPPY_IDLE_MS,
        'visibleMs'        => (int) CPCFF_CLIPPY_VISIBLE_MS,
        'reappearMs'       => (int) CPCFF_CLIPPY_REAPPEAR_MS,
    )) . '</script>';
    echo '<link rel="stylesheet" href="' . esc_url(plugins_url('inc/cpcff-clippy/cpcff-clippy.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH)) . '" type="text/css" media="all" />';
    echo '<script src="' . esc_url(plugins_url('inc/cpcff-clippy/cpcff-clippy.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH)) . '"></script>';
}

cpcff_clippy_init();