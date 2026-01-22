<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$table = $wpdb->prefix . 'pfb_forms';

/* =========================
   LOAD FORM (EDIT MODE)
========================= */
$form_id   = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$form_name = '';

if ($form_id) {
    $form = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table WHERE id=%d", $form_id)
    );

    if ($form) {
        $form_name = $form->name;
    }
}

$fields = [];


$edit_field_id = isset($_GET['edit_field']) ? intval($_GET['edit_field']) : 0;
$edit_field = null;

if ($edit_field_id) {
    $edit_field = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE id=%d",
            $edit_field_id
        )
    );
}

if ($form_id) {
    $fields = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pfb_fields 
             WHERE form_id = %d 
             ORDER BY id ASC",
            $form_id
        )
    );
}

?>



<div class="wrap">

    <h1><?php echo $form_id ? 'Edit Form' : 'Create New Form'; ?></h1>

    <?php if (isset($_GET['saved'])): ?>
        <div class="notice notice-success">
            <p>Form saved successfully!</p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['field_added'])): ?>
        <div class="notice notice-success">
            <p>Field added successfully!</p>
        </div>
    <?php endif; ?>

    <!-- =====================
         FORM BASIC INFO
    ====================== -->
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('pfb_save_form_action', 'pfb_nonce'); ?>

        <input type="hidden" name="action" value="pfb_save_form">
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">


        <table class="form-table">
            <tr>
                <th>Form Name</th>
                <td>
                    <input type="text"
                           name="form_name"
                           class="regular-text"
                           value="<?php echo esc_attr($form_name); ?>"
                           required>
                </td>
            </tr>
        </table>

        <?php submit_button($form_id ? 'Update Form' : 'Save Form'); ?>
    </form>

    <?php if ($form_id): ?>
        <hr>

        <?php if ($fields): ?>
    <h2>Fields</h2>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Label</th>
                <th>Name</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fields as $field): ?>
                <tr>
                    <td><?php echo esc_html($field->id); ?></td>
                    <td><?php echo esc_html($field->label); ?></td>
                    <td><code><?php echo esc_html($field->name); ?></code></td>
                    <td><?php echo esc_html($field->type); ?></td>
                 <td>
                    <a href="<?php echo admin_url(
                        'admin.php?page=pfb-builder&form_id=' . $form_id . '&edit_field=' . $field->id
                    ); ?>">
                        Edit
                    </a>
                    |
                    <a href="<?php echo wp_nonce_url(
                        admin_url(
                            'admin-post.php?action=pfb_delete_field&field_id=' . $field->id . '&form_id=' . $form_id
                        ),
                        'pfb_delete_field_' . $field->id
                    ); ?>"
                    onclick="return confirm('Delete this field?');">
                        Delete
                    </a>
                </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>
<?php endif; ?>


        <!-- =====================
             ADD FIELD
        ====================== -->
        <h2><?php echo $edit_field ? 'Edit Field' : 'Add Field'; ?></h2>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('pfb_add_field_action', 'pfb_field_nonce'); ?>

            
            <input type="hidden" name="action" value="pfb_add_field">
            <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">


            <?php if ($edit_field): ?>
                <input type="hidden" name="field_id" value="<?php echo esc_attr($edit_field->id); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th>Field Type</th>
                    <td>
                        <select name="field_type">
                            <option value="text" <?php selected($edit_field->type ?? '', 'text'); ?>>Text</option>
                            <option value="select" <?php selected($edit_field->type ?? '', 'select'); ?>>Select</option>
                        </select>

                    </td>
                </tr>

                <tr>
                    <th>Label</th>
                    <td>
                        <input type="text"
                            name="field_label"
                            value="<?php echo esc_attr($edit_field->label ?? ''); ?>"
                            required>

                    </td>
                </tr>

                <tr>
                    <th>Field Name</th>
                    <td>
                        <input type="text"
                            name="field_name"
                            value="<?php echo esc_attr($edit_field->name ?? ''); ?>"
                            required>
                        <p class="description">example: driver_name</p>
                    </td>
                </tr>

                <tr>
                    <th>Options (for select)</th>
                    <td>
                        <textarea name="field_options"
                                placeholder="Option 1, Option 2"><?php
                        if (!empty($edit_field->options)) {
                            echo esc_textarea(implode(', ', json_decode($edit_field->options, true)));
                        }
                        ?></textarea>

                    </td>
                </tr>


                <tr>
                    <th>Conditional Logic</th>
                    <td>
                        <label>
                            <input type="checkbox"
                                id="enable_condition"
                                <?php echo (!empty($edit_field->rules)) ? 'checked' : ''; ?>>

                        </label>

                        <div id="condition_box" style="display:none; margin-top:10px;">

                            <p>
                                Show this field if
                                <select name="condition_field" id="condition_field">
                                    <option value="">Select field</option>
                                    <?php
                                    // existing fields for this form
                                   $all_fields = $wpdb->get_results(
                                        $wpdb->prepare(
                                            "SELECT name, label, type, options 
                                            FROM {$wpdb->prefix}pfb_fields 
                                            WHERE form_id=%d",
                                            $form_id
                                        )
                                    );


                                    foreach ($all_fields as $af):
                                        if ($af->type === 'select'):
                                    ?>
                                        <option
                                            value="<?php echo esc_attr($af->name); ?>"
                                            data-options='<?php echo esc_attr($af->options); ?>'
                                            <?php
                                                if (!empty($edit_field->rules)) {
                                                    $rules = json_decode($edit_field->rules, true);
                                                    if (($rules['show_if']['field'] ?? '') === $af->name) {
                                                        echo 'selected';
                                                    }
                                                }
                                            ?>
                                        >
                                            <?php echo esc_html($af->label); ?>
                                        </option>

                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </select>

                                equals

                                <!-- <select name="condition_value" id="condition_value"> -->
                                <div id="condition_values" style="margin-top:8px;">
                                    <!-- JS will populate checkboxes here -->
                                </div>

                            </p>

                            <p class="description" style="margin-top:40px; max-width:650px;">
                                Control when this field appears based on another field’s value. <br>
                                Select one or more values to apply <strong>OR</strong> logic.
                            </p>
                        </div>
                    </td>
                </tr>


            </table>

            <?php submit_button($edit_field ? 'Update Field' : 'Add Field'); ?>
        </form>
    <?php endif; ?>

   <script>

    function renderConditionValues(selectEl) {
    const container = document.getElementById('condition_values');
    container.innerHTML = '';

    const selected = selectEl.options[selectEl.selectedIndex];
    const optionsData = selected.getAttribute('data-options');
    if (!optionsData) return;

        let options = JSON.parse(optionsData);

        const selectedValues = existingRules?.show_if?.values || [];

        options.forEach(opt => {
            const label = document.createElement('label');
            label.style.display = 'block';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'condition_values[]';
            checkbox.value = opt;

            // IMPORTANT PART
            if (selectedValues.includes(opt)) {
                checkbox.checked = true;
            }

            label.appendChild(checkbox);
            label.append(' ' + opt);
            container.appendChild(label);
        });
    }

        const enableCondition = document.getElementById('enable_condition');
        const conditionBox   = document.getElementById('condition_box');

        if (enableCondition && enableCondition.checked) {
            conditionBox.style.display = 'block';
        }

        enableCondition?.addEventListener('change', function () {
            conditionBox.style.display = this.checked ? 'block' : 'none';
        });

/*
        document.getElementById('condition_field')?.addEventListener('change', function () {

            const container = document.getElementById('condition_values');
            container.innerHTML = '';

            const selected = this.options[this.selectedIndex];
            const optionsData = selected.getAttribute('data-options');

            if (!optionsData) return;

            let options;
            try {
                options = JSON.parse(optionsData);
            } catch (e) {
                return;
            }

            options.forEach(opt => {
                const label = document.createElement('label');
                label.style.display = 'block';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = 'condition_values[]';
                checkbox.value = opt;

                label.appendChild(checkbox);
                label.append(' ' + opt);

                container.appendChild(label);
            });
        });
*/
        const conditionField = document.getElementById('condition_field');

        conditionField?.addEventListener('change', function () {
            renderConditionValues(this);
        });


        <?php if (!empty($edit_field->rules)): ?>
        const existingRules = <?php echo $edit_field->rules; ?>;

        if (existingRules?.show_if?.values) {
            existingRules.show_if.values.forEach(val => {
                const checkbox = document.querySelector(
                    'input[name="condition_values[]"][value="' + val + '"]'
                );
                if (checkbox) checkbox.checked = true;
            });
        }
        <?php endif; ?>

        // Force trigger on load if editing
        if (enableCondition && enableCondition.checked) {
            const event = new Event('change');
            document.getElementById('condition_field')?.dispatchEvent(event);
        }


        if (enableCondition && enableCondition.checked && conditionField?.value) {
            conditionBox.style.display = 'block';
            renderConditionValues(conditionField);
        }

    </script>



</div>
