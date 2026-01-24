<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$entry_id = intval($_GET['entry_id'] ?? 0);
if (!$entry_id) {
    echo '<div class="notice notice-error"><p>Invalid entry ID</p></div>';
    return;
}

$entry = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pfb_entries WHERE id=%d",
        $entry_id
    )
);

if (!$entry) {
    echo '<div class="notice notice-error"><p>Entry not found</p></div>';
    return;
}

$meta = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT m.field_value, f.label, f.type
         FROM {$wpdb->prefix}pfb_entry_meta m
         JOIN {$wpdb->prefix}pfb_fields f ON f.name = m.field_name
         WHERE m.entry_id = %d",
        $entry_id
    )
);
?>

<div class="wrap">
    <h1 style="margin-bottom:20px">
        Entry Details
    </h1>

    <div class="pfb-admin-entry">
        <?php foreach ($meta as $m): 
            if (empty($m->field_value)) continue;
        ?>

            <div class="pfb-field-card">

            <div class="pfb-field-label">
                <?php echo esc_html($m->label); ?>
            </div>

            <div class="pfb-field-value">
                <?php if ($m->type === 'image' || $m->type === 'file'): ?>

                <div class="pfb-image-box">
                    <img src="<?php echo esc_url($m->field_value); ?>">
                    <a class="button button-primary"
                    href="<?php echo esc_url($m->field_value); ?>"
                    download>
                    ⬇ Download
                    </a>
                </div>

                <?php else: ?>
                <?php echo esc_html($m->field_value); ?>
                <?php endif; ?>
            </div>

        </div>

        <?php endforeach; ?>

    </div>


    <p style="margin-top:20px;">
        <a class="button button-primary"
           href="<?php echo admin_url('admin.php?page=pfb-entry-edit&entry_id=' . $entry_id); ?>">
           Edit Entry
        </a>
    </p>
</div>
