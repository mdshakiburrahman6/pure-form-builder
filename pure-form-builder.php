<?php
/*
Plugin Name: Pure Custom Form Builder
Version: 1.0.0
Author: Md Shakibur Rahman
*/

if (!defined('ABSPATH')) exit;

define('PFB_PATH', plugin_dir_path(__FILE__));
define('PFB_URL', plugin_dir_url(__FILE__));

require_once PFB_PATH . 'includes/activator.php';
require_once PFB_PATH . 'admin/menu.php';
require_once PFB_PATH . 'public/shortcode.php';
require_once PFB_PATH . 'includes/ajax-save.php';
require_once PFB_PATH . 'includes/admin-actions.php';


register_activation_hook(__FILE__, 'pfb_activate');


add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'pfb-public',
        PFB_URL . 'assets/public.js',
        [],
        '1.0',
        true
    );
});
