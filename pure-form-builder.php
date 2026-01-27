<?php
/*
Plugin Name: Pure Custom Form Builder
Version: 1.0.0
Author: Md Shakibur Rahman
*/

if (!defined('ABSPATH')) exit;

define('PFB_PATH', plugin_dir_path(__FILE__));
define('PFB_URL', plugin_dir_url(__FILE__));

/**
 * Activation
 */
require_once PFB_PATH . 'includes/activator.php';
register_activation_hook(__FILE__, 'pfb_activate');

/**
 * Runtime includes (NOT for activation)
 */
require_once PFB_PATH . 'admin/menu.php';
require_once PFB_PATH . 'public/shortcode.php';
require_once PFB_PATH . 'includes/ajax-save.php';
require_once PFB_PATH . 'includes/admin-actions.php';
require_once PFB_PATH . 'includes/helpers.php';
require_once PFB_PATH . 'includes/submit-handler.php';

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'pfb-frontend',
        plugins_url('assets/css/frontend.css', __FILE__)
    );

    wp_enqueue_script(
        'pfb-public',
        PFB_URL . 'assets/js/public.js',
        [],
        '1.0',
        true
    );
    wp_enqueue_script(
        'sweetalert2',
        'https://cdn.jsdelivr.net/npm/sweetalert2@11',
        [],
        null,
        true
    );
    wp_enqueue_script(
        'pfb-conditional',
        PFB_URL . 'assets/js/conditional.js',
        [],
        '1.0',
        true
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (
        isset($_GET['page']) &&
        in_array($_GET['page'], ['pfb-entry-view', 'pfb-entry-edit'])
    ) {
        wp_enqueue_style(
            'pfb-admin-css',
            PFB_URL . 'assets/css/admin-entry.css',
            [],
            '1.0'
        );
    }
    wp_enqueue_script(
        'pfb-conditional',
        PFB_URL . 'assets/js/conditional.js',
        [],
        '1.0',
        true
    );
});