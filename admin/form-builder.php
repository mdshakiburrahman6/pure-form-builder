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

$all_fields = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT name, label, type, options FROM {$wpdb->prefix}pfb_fields WHERE form_id=%d",
        $form_id
    )
);


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
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="email">Email</option>
                            <option value="number">Number</option>
                            <option value="url">URL</option>
                            <option value="select">Select</option>
                            <option value="radio">Radio</option>
                            <option value="file">File</option>
                            <option value="image">Image</option>
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

                        <label style="display:block; margin-bottom:10px;">
                            <input type="checkbox" id="enable_condition"
                                <?php echo (!empty($edit_field->rules)) ? 'checked' : ''; ?>>
                            Enable Conditional Logic
                        </label>

                        <div id="condition_builder" style="display:none; border-left:3px solid #2271b1; padding-left:15px;">

                            <div id="rule_groups">

                                <!-- Rule Group Template -->
                             

                            </div>

                            <button type="button" class="button button-secondary" id="add_rule_group">
                                + OR Rule Group
                            </button>

                            <p class="description" style="margin-top:10px;">
                                Rules inside a group use <strong>AND</strong>.  
                                Rule groups are evaluated using <strong>OR</strong>.
                            </p>

                        </div>
                    </td>
                </tr>



            </table>

            <?php submit_button($edit_field ? 'Update Field' : 'Add Field'); ?>
        </form>
    <?php endif; ?>

    <script>
        window.pfbSelectFields = `
        <?php foreach ($all_fields as $af): ?>
            <?php if (in_array($af->type, ['select','radio'])): ?>
                <option value="<?php echo esc_attr($af->name); ?>">
                    <?php echo esc_html($af->label); ?>
                </option>
            <?php endif; ?>
        <?php endforeach; ?>
        `;
    </script>

    <script>
        window.pfbFieldOptions = <?php
            $field_options = [];

            foreach ($all_fields as $af) {
                if (in_array($af->type, ['select', 'radio']) && !empty($af->options)) {
                    $decoded = json_decode($af->options, true);
                    if (is_array($decoded)) {
                        $field_options[$af->name] = $decoded;
                    }
                }
            }

            echo wp_json_encode($field_options);
        ?>;
    </script>

    <script>
        window.pfbExistingRules = <?php
            if (!empty($edit_field->rules)) {
                echo $edit_field->rules;
            } else {
                echo 'null';
            }
        ?>;
    </script>



   <script>
    function renderValueInput(groupIndex, ruleIndex, fieldName, selectedValue = '') {

        if (window.pfbFieldOptions[fieldName]) {
            // SELECT dropdown
            let optionsHTML = '<option value="">Select value</option>';

            window.pfbFieldOptions[fieldName].forEach(opt => {
                const selected = opt === selectedValue ? 'selected' : '';
                optionsHTML += `<option value="${opt}" ${selected}>${opt}</option>`;
            });

            return `
                <select name="rules[${groupIndex}][rules][${ruleIndex}][value]">
                    ${optionsHTML}
                </select>
            `;
        }

        // Default text input
        return `
            <input type="text"
                name="rules[${groupIndex}][rules][${ruleIndex}][value]"
                value="${selectedValue || ''}"
                placeholder="Value">
        `;
    }


    function createRuleHTML(groupIndex, ruleIndex, rule) {
        return `
            <div class="rule" style="margin-top:10px;">
                <select name="rules[${groupIndex}][rules][${ruleIndex}][field]" class="rule-field">
                    <option value="">Select field</option>
                    ${window.pfbSelectFields || ''}
                </select>

                <select name="rules[${groupIndex}][rules][${ruleIndex}][operator]">
                    <option value="is">is</option>
                    <option value="is_not">is not</option>
                </select>

                ${renderValueInput(groupIndex, ruleIndex, rule?.field, rule?.value)}

                <button type="button" class="button-link delete-rule">✕</button>
            </div>
        `;
    }



    document.addEventListener('DOMContentLoaded', function () {

        const enableCheckbox = document.getElementById('enable_condition');
        const builder = document.getElementById('condition_builder');
        const ruleGroups = document.getElementById('rule_groups');
        const addGroupBtn = document.getElementById('add_rule_group');

        /* ======================
        Toggle Builder
        ====================== */
        if (enableCheckbox?.checked) {
            builder.style.display = 'block';
        }

        enableCheckbox?.addEventListener('change', function () {
            builder.style.display = this.checked ? 'block' : 'none';
        });

        /* ======================
        Add AND Rule
        ====================== */
        document.addEventListener('click', function (e) {
            if (!e.target.classList.contains('add-rule')) return;

            const group = e.target.closest('.rule-group');
            const rulesBox = group.querySelector('.rules');

            const groupIndex = [...ruleGroups.children].indexOf(group);
            const ruleIndex = rulesBox.children.length;

            const ruleHTML = `
                <div class="rule" style="margin-top:10px;">
                    <select name="rules[${groupIndex}][rules][${ruleIndex}][field]" class="rule-field">
                        <option value="">Select field</option>
                        ${window.pfbSelectFields || ''}
                    </select>

                    <select name="rules[${groupIndex}][rules][${ruleIndex}][operator]">
                        <option value="is">is</option>
                        <option value="is_not">is not</option>
                    </select>

                    ${renderValueInput(groupIndex, ruleIndex, '')}

                    <button type="button" class="button-link delete-rule">✕</button>
                </div>
            `;

            rulesBox.insertAdjacentHTML('beforeend', ruleHTML);
        });

        /* ======================
        Add OR Rule Group
        ====================== */
        addGroupBtn?.addEventListener('click', function () {

            const groupIndex = ruleGroups.children.length;

            const groupHTML = `
                <div class="rule-group" style="margin-bottom:20px; padding:10px; background:#f9f9f9;">
                    <strong>Rule Group</strong>

                    <div class="rules">
                        <div class="rule" style="margin-top:10px;">
                            <select name="rules[${groupIndex}][rules][0][field]" class="rule-field">
                                <option value="">Select field</option>
                                ${window.pfbSelectFields || ''}
                            </select>

                            <select name="rules[${groupIndex}][rules][0][operator]">
                                <option value="is">is</option>
                                <option value="is_not">is not</option>
                            </select>

                            ${renderValueInput(groupIndex, 0, '')}

                        </div>
                    </div>

                    <button type="button" class="button add-rule" style="margin-top:10px;">
                        + AND Rule
                    </button>
                </div>
            `;

            ruleGroups.insertAdjacentHTML('beforeend', groupHTML);
        });

        /* ======================
        Delete Rule
        ====================== */
        document.addEventListener('click', function (e) {
            if (!e.target.classList.contains('delete-rule')) return;
            e.target.closest('.rule').remove();
        });



        /* ======================
        Edit Mode: Auto-load Rules
        ====================== */
        if (window.pfbExistingRules && Array.isArray(window.pfbExistingRules)) {

            // Show builder automatically
            if (enableCheckbox) {
                enableCheckbox.checked = true;
                builder.style.display = 'block';
            }

            ruleGroups.innerHTML = '';

            window.pfbExistingRules.forEach((group, groupIndex) => {

                const groupHTML = document.createElement('div');
                groupHTML.className = 'rule-group';
                groupHTML.style.cssText = 'margin-bottom:20px; padding:10px; background:#f9f9f9;';

                groupHTML.innerHTML = `
                    <strong>Rule Group</strong>
                    <div class="rules"></div>
                    <button type="button" class="button add-rule" style="margin-top:10px;">
                        + AND Rule
                    </button>
                `;

                const rulesBox = groupHTML.querySelector('.rules');

                group.rules.forEach((rule, ruleIndex) => {
                    rulesBox.insertAdjacentHTML(
                        'beforeend',
                        createRuleHTML(groupIndex, ruleIndex, rule)
                    );
                });

                ruleGroups.appendChild(groupHTML);

                // Set values after render
                group.rules.forEach((rule, ruleIndex) => {
                    const ruleEl = rulesBox.children[ruleIndex];

                    ruleEl.querySelector(
                        `[name="rules[${groupIndex}][rules][${ruleIndex}][field]"]`
                    ).value = rule.field;

                    ruleEl.querySelector(
                        `[name="rules[${groupIndex}][rules][${ruleIndex}][operator]"]`
                    ).value = rule.operator;

                    ruleEl.querySelector(
                        `[name="rules[${groupIndex}][rules][${ruleIndex}][value]"]`
                    ).value = rule.value;
                });

            });
        }

        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('rule-field')) return;

            const rule = e.target.closest('.rule');
            const rulesBox = rule.closest('.rules');
            const groupIndex = [...document.querySelectorAll('.rule-group')]
                .indexOf(rule.closest('.rule-group'));
            const ruleIndex = [...rulesBox.children].indexOf(rule);

            const oldValue = rule.querySelector('[name$="[value]"]')?.value || '';

            rule.querySelector('[name$="[value]"]').outerHTML =
                renderValueInput(groupIndex, ruleIndex, e.target.value, oldValue);
        });


    });
</script>




</div>
