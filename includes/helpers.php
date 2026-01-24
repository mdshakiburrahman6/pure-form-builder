<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('pfb_handle_access_denied')) {
    function pfb_handle_access_denied($type, $page_id = 0) {

        // MESSAGE → renderer handle
        if ($type === 'message') {
            return 'message';
        }

        // REDIRECT → delay & hook based
        return [
            'type' => $type,
            'page' => $page_id,
        ];
    }
}

