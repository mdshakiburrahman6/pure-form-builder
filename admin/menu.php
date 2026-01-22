<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'pfb_register_admin_menu');

function pfb_register_admin_menu() {

    // MAIN MENU
    add_menu_page(
        'Pure Form Builder',
        'Form Builder',
        'manage_options',
        'pfb-forms',
        'pfb_forms_list',
        'dashicons-feedback',
        26
    );

    // SUBMENU: Forms list (default)
    add_submenu_page(
        'pfb-forms',
        'All Forms',
        'All Forms',
        'manage_options',
        'pfb-forms',
        'pfb_forms_list'
    );

    // SUBMENU: Add New Form
    add_submenu_page(
        'pfb-forms',
        'Add New Form',
        'Add New',
        'manage_options',
        'pfb-builder',
        'pfb_form_builder_page'
    );

    // SUBMENU: Entries
    add_submenu_page(
        'pfb-forms',
        'Entries',
        'Entries',
        'manage_options',
        'pfb-entries',
        'pfb_render_entries'
    );
}

/* CALLBACKS */

function pfb_forms_list() {
    include PFB_PATH . 'admin/forms-list.php';
}

function pfb_form_builder_page() {
    include PFB_PATH . 'admin/form-builder.php';
}

function pfb_render_entries() {
    include PFB_PATH . 'admin/entries.php';
}
