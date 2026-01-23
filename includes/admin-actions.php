<?php
if (!defined('ABSPATH')) exit;

add_action('admin_post_pfb_save_form', 'pfb_handle_save_form');
add_action('admin_post_pfb_add_field', 'pfb_handle_add_field');
add_action('admin_post_pfb_delete_field', 'pfb_handle_delete_field');
add_action('admin_post_pfb_delete_form', 'pfb_handle_delete_form');
add_action('admin_post_pfb_export_entries', 'pfb_export_entries_csv');


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
    $required = isset($_POST['field_required']) ? 1 : 0;



    // Conditional rules
    $rules = null;

    // =========================
    // RULE BUILDER SAVE
    // =========================
    $rules = null;

    if (!empty($_POST['rules']) && is_array($_POST['rules'])) {

        $clean_groups = [];

        foreach ($_POST['rules'] as $group) {

            if (empty($group['rules']) || !is_array($group['rules'])) {
                continue;
            }

            $clean_rules = [];

            foreach ($group['rules'] as $rule) {

                if (
                    empty($rule['field']) ||
                    empty($rule['operator']) ||
                    $rule['value'] === ''
                ) {
                    continue;
                }

                $clean_rules[] = [
                    'field'    => sanitize_key($rule['field']),
                    'operator' => sanitize_text_field($rule['operator']),
                    'value'    => sanitize_text_field($rule['value']),
                ];
            }

            if (!empty($clean_rules)) {
                $clean_groups[] = [
                    'rules' => $clean_rules
                ];
            }
        }

        if (!empty($clean_groups)) {
            $rules = wp_json_encode($clean_groups);
        }
    }

    $field_name_raw = $_POST['field_name'] ?? '';

    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $field_name_raw)) {
        wp_die('Invalid field name. Use only letters, numbers, underscore.');
    }

    $field_name = strtolower($field_name_raw);
    $field_type = sanitize_text_field($_POST['field_type']);
    $options = null;

    // SELECT / RADIO
    if (in_array($field_type, ['select','radio'])) {
        $options = !empty($_POST['field_options'])
            ? wp_json_encode(array_map('trim', explode(',', $_POST['field_options'])))
            : null;
    }




    $data = [
        'form_id'    => $form_id,
        'type'       => $field_type,
        'label'      => sanitize_text_field($_POST['field_label']),
        'name'       => $field_name,
        'options'    => in_array($field_type, ['select','radio']) ? $options : null,
        'required'   => $required,
        'rules'      => $rules,
        'sort_order' => 0,

        // ✅ IMAGE / FILE ONLY
        'file_types' => in_array($field_type, ['image','file'])
            ? sanitize_text_field($_POST['file_types'] ?? '')
            : null,

        'max_size' => in_array($field_type, ['image','file'])
            ? floatval($_POST['max_size'] ?? 0)
            : null,

        'min_size' => in_array($field_type, ['image','file'])
            ? floatval($_POST['min_size'] ?? 0)
            : null,
    ];



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




add_action('admin_post_nopriv_pfb_submit_form', 'pfb_handle_form_submit');
add_action('admin_post_pfb_submit_form', 'pfb_handle_form_submit');
function pfb_handle_form_submit() {

    if (
        !isset($_POST['pfb_nonce']) ||
        !wp_verify_nonce($_POST['pfb_nonce'], 'pfb_frontend_submit')
    ) {
        wp_die('Security check failed');
    }

    global $wpdb;

    $form_id = intval($_POST['pfb_form_id'] ?? 0);
    if (!$form_id) wp_die('Invalid form');

    $user_id = is_user_logged_in() ? get_current_user_id() : null;

    $fields = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id=%d",
            $form_id
        )
    );

    $errors = [];

    /* ========= REQUIRED CHECK ========= */
    foreach ($fields as $f) {

        // file / imagea
        if (in_array($f->type, ['file','image'])) {

            $file = $_FILES[$f->name] ?? null;

            if (!empty($f->required) && empty($file['name'])) {
                $errors[$f->name] = $f->label . ' is required';
                continue;
            }

            if (!empty($file['name'])) {

                $allowed_types = !empty($f->file_types)
                    ? array_map('trim', explode(',', strtolower($f->file_types)))
                    : [];

                $max_size = !empty($f->max_size) ? $f->max_size * 1024 * 1024 : 0;
                $min_size = !empty($f->min_size) ? $f->min_size * 1024 * 1024 : 0;


                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $size = $file['size'];

                if ($allowed_types && !in_array($ext, $allowed_types)) {
                    $errors[$f->name] = $f->label . ' invalid file type';
                }

                if ($max_size && $size > $max_size) {
                    $errors[$f->name] = $f->label . ' exceeds max size';
                }

                if ($min_size && $size < $min_size) {
                    $errors[$f->name] = $f->label . ' file too small';
                }
            }

            continue;
        }


        if (!isset($_POST[$f->name])) {
            if (!empty($f->required)) {
                $errors[$f->name] = $f->label . ' is required';
            }
            continue;
        }

        if (!empty($f->required) && trim($_POST[$f->name]) === '') {
            $errors[$f->name] = $f->label . ' is required';
        }
    }

    // Error Message
    if ($errors) {

        $error_payload = urlencode(wp_json_encode($errors));

        $redirect_url = wp_get_referer() ?: home_url();

        wp_redirect(
            add_query_arg('pfb_errors', $error_payload, $redirect_url)
        );
        exit;
    }




    /* ========= CREATE ENTRY ========= */
    $wpdb->insert(
        $wpdb->prefix . 'pfb_entries',
        [
            'form_id' => $form_id,
            'user_id' => $user_id
        ]
    );

    $entry_id = $wpdb->insert_id;

    /* ========= SAVE META ========= */
    foreach ($fields as $f) {
        // FILE / IMAGE
        if (in_array($f->type, ['file','image']) && !empty($_FILES[$f->name]['name'])) {

            require_once ABSPATH . 'wp-admin/includes/file.php';
            $upload = wp_handle_upload($_FILES[$f->name], ['test_form' => false]);

            if (!empty($upload['url'])) {
                $wpdb->insert(
                    $wpdb->prefix . 'pfb_entry_meta',
                    [
                        'entry_id'   => $entry_id,
                        'field_name' => $f->name,
                        'field_value'=> esc_url_raw($upload['url'])
                    ]
                );
            }
            continue;
        }


        // NORMAL FIELD
        if (isset($_POST[$f->name])) {
            $wpdb->insert(
                $wpdb->prefix . 'pfb_entry_meta',
                [
                    'entry_id'   => $entry_id,
                    'field_name' => $f->name,
                    'field_value'=> sanitize_text_field($_POST[$f->name])
                ]
            );
        }
    }

    $redirect_url = remove_query_arg(['pfb_errors'], wp_get_referer());

    wp_redirect(
        add_query_arg('pfb_success', '1', $redirect_url)
    );
    exit;

}



function pfb_handle_delete_form() {

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;

    if (!$form_id) {
        wp_die('Invalid form ID');
    }

    check_admin_referer('pfb_delete_form_' . $form_id);

    global $wpdb;

    $forms_table       = $wpdb->prefix . 'pfb_forms';
    $fields_table      = $wpdb->prefix . 'pfb_fields';
    $entries_table     = $wpdb->prefix . 'pfb_entries';
    $entry_meta_table  = $wpdb->prefix . 'pfb_entry_meta';

    // 1️. Get entry IDs first
    $entry_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT id FROM $entries_table WHERE form_id = %d",
            $form_id
        )
    );

    // 2️. Delete entry meta
    if (!empty($entry_ids)) {
        $in = implode(',', array_map('intval', $entry_ids));
        $wpdb->query("DELETE FROM $entry_meta_table WHERE entry_id IN ($in)");
    }

    // 3️. Delete entries
    $wpdb->delete($entries_table, ['form_id' => $form_id]);

    // 4️. Delete fields
    $wpdb->delete($fields_table, ['form_id' => $form_id]);

    // 5. Delete form
    $wpdb->delete($forms_table, ['id' => $form_id]);

    wp_redirect(
        admin_url('admin.php?page=pfb-forms&deleted=1')
    );
    exit;
}

function pfb_export_entries_csv() {

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    global $wpdb;

    $form_id = intval($_GET['form_id']);

    $entries = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT e.id, e.created_at, u.user_login
             FROM {$wpdb->prefix}pfb_entries e
             LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
             WHERE e.form_id = %d
             ORDER BY e.id DESC",
            $form_id
        )
    );

    // STEP — collect all unique field names for this form
    $all_fields = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT em.field_name
            FROM {$wpdb->prefix}pfb_entry_meta em
            INNER JOIN {$wpdb->prefix}pfb_entries e ON em.entry_id = e.id
            WHERE e.form_id = %d
            ORDER BY em.field_name ASC",
            $form_id
        )
    );


    if (!$entries) {
        wp_die('No entries found.');
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="form-' . $form_id . '-entries.csv"');

    $output = fopen('php://output', 'w');

    // CSV Header
    $header = array_merge(
        ['Entry ID', 'User', 'Date'],
        $all_fields
    );

    fputcsv($output, $header);


    foreach ($entries as $entry) {

        $meta = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT field_name, field_value 
                 FROM {$wpdb->prefix}pfb_entry_meta 
                 WHERE entry_id = %d",
                $entry->id
            )
        );

        $row_data = [
            $entry->id,
            $entry->user_login ?: 'Guest',
            $entry->created_at
        ];

        // prepare empty values
        $values_map = array_fill_keys($all_fields, '');

        // fill values
        foreach ($meta as $m) {
            $values_map[$m->field_name] = $m->field_value;
        }

        // merge and export
        fputcsv($output, array_merge($row_data, array_values($values_map)));

    }

    fclose($output);
    exit;
}
