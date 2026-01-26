<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Security check 
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to edit this entry.');
}


/* =========================
   GET ENTRY ID
========================= */
$entry_id = isset($_GET['entry_id']) ? intval($_GET['entry_id']) : 0;

if (!$entry_id) {
    wp_die('Invalid Entry ID');
}

/* =========================
   GET ENTRY
========================= */
$entry = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pfb_entries WHERE id = %d",
        $entry_id
    )
);

if (!$entry) {
    wp_die('Entry not found');
}

$form_id = $entry->form_id;
$form = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pfb_forms WHERE id = %d",
        $form_id
    )
);

if (!$form) {
    wp_die('Invalid form');
}


/* =========================
   GET FORM FIELDS
========================= */
$fields = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pfb_fields
         WHERE form_id = %d
         ORDER BY sort_order ASC, id ASC",
        $form_id
    )
);

/* =========================
   GET ENTRY META
========================= */
$meta_rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pfb_entry_meta
         WHERE entry_id = %d",
        $entry_id
    )
);

// Meta map for easy access
$meta = [];
foreach ($meta_rows as $m) {
    $meta[$m->field_name] = $m->field_value;
}

?>


<?php if (isset($_GET['updated'])): ?>
<div class="notice notice-success is-dismissible">
    <p>Entry updated successfully!</p>
</div>
<?php endif; ?>


<div class="wrap">
    <h1>Edit Entry</h1>

    <p><strong>Entry ID:</strong> <?php echo esc_html($entry_id); ?></p>
    <p><strong>User:</strong> <?php echo esc_html($entry->user_id ?: 'Guest'); ?></p>
    <p><strong>Date:</strong> <?php echo esc_html($entry->created_at); ?></p>

    <hr>
    

    <form method="post" class="pfb-admin-form"
        action="<?php echo admin_url('admin-post.php'); ?>"
        enctype="multipart/form-data">

        <?php wp_nonce_field('pfb_update_entry', 'pfb_nonce'); ?>

        <input type="hidden" name="action" value="pfb_update_entry">
        <input type="hidden" name="entry_id" value="<?php echo esc_attr($entry_id); ?>">

        <table class="form-table">
            <?php foreach ($fields as $field): 
                $value = $meta[$field->name] ?? '';
            ?>

            <tr class="pfb-field"
                <?php if (!empty($field->rules)): ?>
                    data-rules="<?php echo esc_attr($field->rules); ?>"
                <?php endif; ?>
            >

                <th>
                    <label><?php echo esc_html($field->label); ?></label>
                </th>
                <td>

                    <?php switch ($field->type):
                        case 'text':
                        case 'number':
                        case 'email':
                        case 'url':
                    ?>
                        <input type="<?php echo esc_attr($field->type); ?>"
                            name="fields[<?php echo esc_attr($field->name); ?>]"
                            value="<?php echo esc_attr($value); ?>"
                            class="regular-text">
                    <?php break; ?>

                    <?php case 'textarea': ?>
                        <textarea name="fields[<?php echo esc_attr($field->name); ?>]"
                                rows="4"
                                class="large-text"><?php echo esc_textarea($value); ?></textarea>
                    <?php break; ?>

                    <?php case 'select':
                        $options = json_decode($field->options, true) ?: [];
                    ?>
                        <select name="fields[<?php echo esc_attr($field->name); ?>]">
                            <option value="">Select</option>
                            <?php foreach ($options as $opt): ?>
                                <option value="<?php echo esc_attr($opt); ?>"
                                    <?php selected($value, $opt); ?>>
                                    <?php echo esc_html($opt); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php break; ?>

                    <?php case 'radio':
                        $options = json_decode($field->options, true) ?: [];
                    ?>
                        <?php foreach ($options as $opt): ?>
                            <label style="display:block;margin-bottom:4px;">
                                <input type="radio"
                                    name="fields[<?php echo esc_attr($field->name); ?>]"
                                    value="<?php echo esc_attr($opt); ?>"
                                    <?php checked($value, $opt); ?>>
                                <?php echo esc_html($opt); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php break; ?>

                    <?php case 'image': ?>

                        <?php if ($value): ?>
                            <div style="margin-bottom:10px;">
                                <img src="<?php echo esc_url($value); ?>"
                                    style="max-width:150px; display:block;">
                                <label>
                                    <input type="checkbox"
                                        name="delete_image[]"
                                        value="<?php echo esc_attr($field->name); ?>">
                                    Remove image
                                </label>
                            </div>
                        <?php endif; ?>

                        <input type="file"
                            name="<?php echo esc_attr($field->name); ?>">

                    <?php break; ?>

                    <?php endswitch; ?>

                </td>
            </tr>

            <?php endforeach; ?>

        </table>

        <?php submit_button('Update Entry'); ?>

    </form>

    <a href="<?php echo admin_url('admin.php?page=pfb-entries'); ?>">
        ← Back to Entries
    </a>
</div>




<!-- Admin JavaScript -->
<script>
function getFormData(form) {
    const data = {};
    form.querySelectorAll('[name]').forEach(el => {
        if (el.type === 'radio') {
            if (el.checked) data[el.name.replace(/^fields\[|\]$/g,'')] = el.value;
        } else {
            data[el.name.replace(/^fields\[|\]$/g,'')] = el.value;
        }
    });
    return data;
}

function evaluateRules(ruleGroups, formData) {
    return ruleGroups.some(group => {
        return group.rules.every(rule => {
            const currentValue = formData[rule.field] ?? '';
            if (currentValue === '') return false;

            if (rule.operator === 'is') return currentValue === rule.value;
            if (rule.operator === 'is_not') return currentValue !== rule.value;
            return false;
        });
    });
}

function applyAdminConditions() {
    const form = document.querySelector('.pfb-admin-form');
    if (!form) return;

    const formData = getFormData(form);

    form.querySelectorAll('.pfb-field').forEach(field => {

        if (!field.dataset.rules) {
            field.style.display = '';
            return;
        }

        const rules = JSON.parse(field.dataset.rules);
        const shouldShow = evaluateRules(rules, formData);

        field.style.display = shouldShow ? '' : 'none';

        field.querySelectorAll('input, select, textarea').forEach(el => {
            if (shouldShow) {
                el.disabled = false;
            } else {
                el.disabled = true;
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', applyAdminConditions);
document.addEventListener('change', applyAdminConditions);
</script>
