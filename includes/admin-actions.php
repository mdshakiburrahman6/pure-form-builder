<?php
if (!defined('ABSPATH')) exit;

add_action('admin_post_pfb_save_form', 'pfb_handle_save_form');
add_action('admin_post_pfb_add_field', 'pfb_handle_add_field');
add_action('admin_post_pfb_delete_field', 'pfb_handle_delete_field');

/* =========================
   SAVE / UPDATE FORM
========================= */
function pfb_handle_save_form() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    check_admin_referer('pfb_save_form_action', 'pfb_nonce');

    global $wpdb;
    $table = $wpdb->prefix . 'pfb_forms';

    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    $name    = sanitize_text_field($_POST['form_name']);

    if ($form_id) {
        $wpdb->update($table, ['name' => $name], ['id' => $form_id]);
    } else {
        $wpdb->insert($table, ['name' => $name]);
        $form_id = $wpdb->insert_id;
    }

    wp_redirect(
        admin_url('admin.php?page=pfb-builder&form_id=' . $form_id . '&saved=1')
    );
    exit;
}

/* =========================
   ADD FIELD (WITH RULES)
========================= */
function pfb_handle_add_field() {

    $field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : 0;


    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    check_admin_referer('pfb_add_field_action', 'pfb_field_nonce');

    global $wpdb;
    $field_table = $wpdb->prefix . 'pfb_fields';

    $form_id = intval($_POST['form_id']);

    // Conditional rules
    $rules = null;

    if (!empty($_POST['condition_field']) && !empty($_POST['condition_values'])) {
        $values = array_map('sanitize_text_field', $_POST['condition_values']);

        $rules = wp_json_encode([
            'show_if' => [
                'field'  => sanitize_key($_POST['condition_field']),
                'values' => $values
            ]
        ]);
    }


    $data = [
        'form_id'    => $form_id,
        'type'       => sanitize_text_field($_POST['field_type']),
        'label'      => sanitize_text_field($_POST['field_label']),
        'name'       => sanitize_key($_POST['field_name']),
        'options'    => wp_json_encode(
            array_map('trim', explode(',', $_POST['field_options']))
        ),
        'rules'      => $rules,
        'sort_order' => 0
    ];


    // Select options
    $options = [];
    if (!empty($_POST['field_options'])) {
        $options = array_map('trim', explode(',', $_POST['field_options']));
    }

    if ($field_id) {
        // UPDATE
        $wpdb->update(
            $field_table,
            $data,
            ['id' => $field_id]
        );
    } else {
        // INSERT
        $wpdb->insert($field_table, $data);
    }


    wp_redirect(
        admin_url('admin.php?page=pfb-builder&form_id=' . $form_id . '&field_added=1')
    );
    exit;
}

/* =========================
   DELETE FIELD
========================= */
function pfb_handle_delete_field() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $field_id = intval($_GET['field_id']);
    $form_id  = intval($_GET['form_id']);

    check_admin_referer('pfb_delete_field_' . $field_id);

    global $wpdb;
    $wpdb->delete(
        $wpdb->prefix . 'pfb_fields',
        ['id' => $field_id]
    );

    wp_redirect(
        admin_url('admin.php?page=pfb-builder&form_id=' . $form_id . '&field_deleted=1')
    );
    exit;
}
