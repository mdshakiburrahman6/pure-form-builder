<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$entry_id = intval($_GET['entry_id']);

/* =========================
   ENTRY INFO
========================= */
$entry = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT e.*, f.name AS form_name
         FROM {$wpdb->prefix}pfb_entries e
         LEFT JOIN {$wpdb->prefix}pfb_forms f ON e.form_id = f.id
         WHERE e.id = %d",
        $entry_id
    )
);

if (!$entry) {
    wp_die('Entry not found.');
}

/* =========================
   ENTRY META
========================= */
$meta = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pfb_entry_meta
         WHERE entry_id = %d",
        $entry_id
    )
);

/* =========================
   FIELD LABEL MAP
========================= */
$fields = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT name, label, type
         FROM {$wpdb->prefix}pfb_fields
         WHERE form_id = %d",
        $entry->form_id
    )
);

$field_map = [];
foreach ($fields as $f) {
    $field_map[$f->name] = $f;
}
?>

<div class="wrap">
    <h1>Entry Details</h1>

    <p><strong>Form:</strong> <?php echo esc_html($entry->form_name); ?></p>
    <p><strong>Entry ID:</strong> <?php echo esc_html($entry->id); ?></p>
    <p><strong>User:</strong>
        <?php
        if ($entry->user_id) {
            $user = get_userdata($entry->user_id);
            echo esc_html($user ? $user->user_login : 'Deleted User');
        } else {
            echo 'Guest';
        }
        ?>
    </p>
    <p><strong>Date:</strong> <?php echo esc_html($entry->created_at); ?></p>

    <hr>

    <table class="widefat striped">
        <thead>
            <tr>
                <th width="30%">Field</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>

        <?php foreach ($meta as $m): 
            $label = $field_map[$m->field_name]->label ?? $m->field_name;
            $type  = $field_map[$m->field_name]->type ?? '';
        ?>
            <tr>
                <td><strong><?php echo esc_html($label); ?></strong></td>
                <td>
                    <?php
                    // IMAGE PREVIEW + DOWNLOAD
                    if ($type === 'image' && filter_var($m->field_value, FILTER_VALIDATE_URL)) {

                        $img_url = esc_url($m->field_value);

                        echo '<div style="display:flex; flex-direction:column; gap:8px;">';

                        echo '<img src="' . $img_url . '" style="max-width:200px; border:1px solid #ccc; padding:4px;">';

                        echo '<a href="' . $img_url . '" download class="button button-small" style="text-align:center; width:150px">
                                ⬇ Download Image
                            </a>';

                        echo '</div>';


                    // FILE DOWNLOAD
                    } elseif ($type === 'file' && filter_var($m->field_value, FILTER_VALIDATE_URL)) {
                        echo '<a href="' . esc_url($m->field_value) . '" target="_blank">Download file</a>';

                    // NORMAL TEXT
                    } else {
                        echo nl2br(esc_html($m->field_value));
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>

    <p style="margin-top:20px;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=pfb-entries&form_id=' . $entry->form_id)); ?>" class="button">
            ← Back to Entries
        </a>
    </p>
</div>
