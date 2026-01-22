<?php
if (!defined('ABSPATH')) exit;

add_shortcode('pfb_form', function ($atts) {

    $atts = shortcode_atts([
        'id' => 0
    ], $atts);

    if (!$atts['id']) return '';

    $id = intval($atts['id']);

    ob_start();
    include PFB_PATH . 'public/renderer.php';
    return ob_get_clean();
});
