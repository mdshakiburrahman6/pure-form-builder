<?php
function pfb_activate() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta("CREATE TABLE {$wpdb->prefix}pfb_forms (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset;");

    dbDelta("CREATE TABLE {$wpdb->prefix}pfb_fields (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        form_id BIGINT,
        type VARCHAR(50),
        label VARCHAR(255),
        name VARCHAR(255),
        options LONGTEXT,
        rules LONGTEXT,
        sort_order INT
    ) $charset;");

    dbDelta("CREATE TABLE {$wpdb->prefix}pfb_entries (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        form_id BIGINT,
        user_id BIGINT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset;");

    dbDelta("CREATE TABLE {$wpdb->prefix}pfb_entry_meta (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        entry_id BIGINT,
        field_name VARCHAR(255),
        field_value LONGTEXT
    ) $charset;");
}
