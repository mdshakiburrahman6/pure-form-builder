<?php
if (!defined('ABSPATH')) exit;

add_shortcode('pfb_form', function ($atts) {

    global $wpdb;

    $atts = shortcode_atts([
        'id' => 0
    ], $atts);

    $form_id = intval($atts['id']);
    if (!$form_id) return '';

    $form = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pfb_forms WHERE id = %d",
            $form_id
        )
    );

    if (!$form) {
        return '<p>Invalid form.</p>';
    }

    // ACCESS CHECK (EARLY, SAFE)
    $access_type   = $form->access_type ?? 'all';
    $redirect_type = $form->redirect_type ?? 'message';
    $redirect_page = intval($form->redirect_page ?? 0);

    if ($access_type === 'logged_in' && !is_user_logged_in()) {

        if ($redirect_type === 'login') {
            wp_safe_redirect( wp_login_url( get_permalink() ) );
            exit;
        }

        if ($redirect_type === 'page' && $redirect_page) {
            wp_safe_redirect( get_permalink($redirect_page) );
            exit;
        }
    }

    // Renderer
    $id = $form_id;
    ob_start();
    include PFB_PATH . 'public/renderer.php';
    return ob_get_clean();
});
