<?php
// includes/submit-handler.php
if (!defined('ABSPATH')) exit;

// Form Submission Handler
add_action('admin_post_pfb_submit_form', 'pfb_handle_form_submit');
add_action('admin_post_nopriv_pfb_submit_form', 'pfb_handle_form_submit');

// Handle Form Submission
function pfb_handle_form_submit() {

    if (!isset($_POST['pfb_nonce']) || !wp_verify_nonce($_POST['pfb_nonce'], 'pfb_frontend_submit')) {
        wp_die('Security check failed');
    }

    global $wpdb;

    $form_id  = intval($_POST['pfb_form_id'] ?? 0);
    $entry_id = intval($_POST['entry_id'] ?? 0);
    $user_id  = get_current_user_id();

    if (!$form_id) {
        wp_die('Invalid form');
    }

    // Load fields
    $fields = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id = %d",
            $form_id
        )
    );

    $errors = [];
    $data   = [];

    // ===============================
    // FIELD VALIDATION + DATA COLLECT
    // ===============================
    foreach ($fields as $f) {

        $name = $f->name;
        $value = '';

        // FILE / IMAGE
        if (in_array($f->type, ['file', 'image'])) {

            if (!empty($_FILES[$name]['name'])) {

                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $upload = media_handle_upload($name, 0);

                if (is_wp_error($upload)) {
                    $errors[$name] = $f->label;
                } else {
                    $value = wp_get_attachment_url($upload);
                }

            } else {
                // EDIT MODE → keep old value
                if ($entry_id) {
                    $value = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT field_value FROM {$wpdb->prefix}pfb_entry_meta
                             WHERE entry_id=%d AND field_name=%s",
                            $entry_id,
                            $name
                        )
                    );
                }
            }

        } else {
            // NORMAL INPUT
            $value = sanitize_text_field($_POST[$name] ?? '');

            if (!empty($f->required) && $value === '') {
                $errors[$name] = $f->label;
            }
        }

        $data[$name] = $value;
    }

    // ===============================
    // IF ERRORS → REDIRECT BACK
    // ===============================
    if (!empty($errors)) {

        $url = add_query_arg([
            'pfb_errors' => urlencode(wp_json_encode($errors))
        ], wp_get_referer());

        wp_safe_redirect($url);
        exit;
    }

    // ===============================
    // INSERT / UPDATE ENTRY
    // ===============================

    if ($entry_id) {

        // UPDATE
        foreach ($data as $field => $value) {

            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}pfb_entry_meta
                     WHERE entry_id=%d AND field_name=%s",
                    $entry_id,
                    $field
                )
            );

            if ($exists) {
                $wpdb->update(
                    "{$wpdb->prefix}pfb_entry_meta",
                    ['field_value' => $value],
                    ['id' => $exists]
                );
            } else {
                $wpdb->insert(
                    "{$wpdb->prefix}pfb_entry_meta",
                    [
                        'entry_id'    => $entry_id,
                        'field_name'  => $field,
                        'field_value' => $value
                    ]
                );
            }
        }

    } else {

        // INSERT NEW ENTRY
        $wpdb->insert(
            "{$wpdb->prefix}pfb_entries",
            [
                'form_id' => $form_id,
                'user_id' => $user_id,
                'created' => current_time('mysql')
            ]
        );

        $entry_id = $wpdb->insert_id;

        foreach ($data as $field => $value) {
            $wpdb->insert(
                "{$wpdb->prefix}pfb_entry_meta",
                [
                    'entry_id'    => $entry_id,
                    'field_name'  => $field,
                    'field_value' => $value
                ]
            );
        }
    }
    
    // ===============================
    // SUCCESS REDIRECT (CLEAN URL)
    // ===============================
    $redirect_url = wp_get_referer();

    // remove edit related params
    $redirect_url = remove_query_arg(
        ['edit', 'entry_id', 'pfb_errors'],
        $redirect_url
    );

    // add success flag
    $redirect_url = add_query_arg('pfb_success', 1, $redirect_url);

    wp_safe_redirect($redirect_url);
    exit;

}



