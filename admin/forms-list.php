<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'pfb_forms';
$forms = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Forms</h1>

    <a href="<?php echo admin_url('admin.php?page=pfb-builder'); ?>"
       class="page-title-action">
       Add New
    </a>

    <hr class="wp-header-end">

    <?php if (!$forms): ?>
        <p>No forms found.</p>
    <?php else: ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Form Name</th>
                    <th>Shortcode</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forms as $form): ?>
                    <tr>
                        <td><?php echo esc_html($form->id); ?></td>
                        <td><?php echo esc_html($form->name); ?></td>
                        <td>
                            <code>[pfb_form id="<?php echo $form->id; ?>"]</code>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=pfb-builder&form_id=' . $form->id); ?>">
                                Edit
                            </a>
                            |
                            <a href="<?php echo wp_nonce_url(
                                admin_url('admin-post.php?action=pfb_delete_form&form_id=' . $form->id),
                                'pfb_delete_form_' . $form->id
                            ); ?>"
                            onclick="return confirm('Are you sure you want to delete this form?');"
                            style="color:red;">
                                Delete
                            </a>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
