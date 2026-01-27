<?php
if (!defined('ABSPATH')) exit;

function pfb_activate() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Forms table
    dbDelta("CREATE TABLE {$wpdb->prefix}pfb_forms (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(200),
        access_type VARCHAR(50) DEFAULT 'all',
        allowed_roles TEXT NULL,
        redirect_type VARCHAR(50) DEFAULT 'message',
        redirect_page BIGINT(20) DEFAULT 0,
        allow_user_edit TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset;");

    // Fields table
    dbDelta("CREATE TABLE {$wpdb->prefix}pfb_fields (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        form_id BIGINT NOT NULL,
        type VARCHAR(50) NOT NULL,
        label VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        options LONGTEXT,
        rules LONGTEXT,
        required TINYINT(1) DEFAULT 0,
        file_types VARCHAR(255),
        max_size INT DEFAULT 0,
        min_size INT DEFAULT 0,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset;");

    // Entries table
    dbDelta("CREATE TABLE {$wpdb->prefix}pfb_entries (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        form_id BIGINT,
        user_id BIGINT,
        allow_update TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset;");

    // Entry meta table
    dbDelta("CREATE TABLE {$wpdb->prefix}pfb_entry_meta (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        entry_id BIGINT,
        field_name VARCHAR(255),
        field_value LONGTEXT
    ) $charset;");
}