<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'pfb_register_admin_menu');

function pfb_register_admin_menu() {

    // MAIN MENU
    add_menu_page(
        'Pure Form Builder',
        'Form Builder',
        'read', // 🔥 temporary relaxed capability
        'pfb-forms',
        'pfb_forms_list',
        'dashicons-feedback',
        26
    );

    // SUBMENU: Add New Form
    add_submenu_page(
        'pfb-forms',          // parent slug MUST match
        'Add New Form',
        'Add New',
        'read',               // 🔥 same capability
        'pfb-builder',
        'pfb_form_builder_page'
    );
}

function pfb_forms_list() {
    include PFB_PATH . 'admin/forms-list.php';
}

function pfb_form_builder_page() {
    include PFB_PATH . 'admin/form-builder.php';
}
