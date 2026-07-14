<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('CPCFF_AI_DISPATCHER')) {
    class CPCFF_AI_DISPATCHER
    {
        public static function dispatch() {
            require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_admin_ai_form_generator.inc.php';
            require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_admin_ai_assistant.inc.php';
            require_once CP_CALCULATEDFIELDSF_BASE_PATH . '/inc/cpcff_admin_ai_entry_searcher.inc.php';
            CPCFF_AI_FORM_GENERATOR::dispatch();
            CPCFF_AI_ASSISTANT::dispatch();
            CPCFF_AI_ENTRY_SEARCHER::dispatch();
        }
    }

    CPCFF_AI_DISPATCHER::dispatch();
}