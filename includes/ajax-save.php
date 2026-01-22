<?php
add_action('wp_ajax_pfb_save', 'pfb_save_entry');
add_action('wp_ajax_nopriv_pfb_save', 'pfb_save_entry');

function pfb_save_entry() {
    global $wpdb;

    $form_id = intval($_POST['form_id']);
    $wpdb->insert("{$wpdb->prefix}pfb_entries", [
        'form_id' => $form_id,
        'user_id' => get_current_user_id()
    ]);

    wp_send_json_success();
}
