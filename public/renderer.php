<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

/**
 * $id shortcode 
 * [pfb_form id="2"]
 */
$fields = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * 
         FROM {$wpdb->prefix}pfb_fields 
         WHERE form_id = %d 
         ORDER BY sort_order ASC, id ASC",
        $id
    )
);
?>

<!-- Form Sumbit Message -->
<?php if (isset($_GET['pfb_error'])): ?>
    <div class="pfb-message pfb-error">
        <?php echo esc_html(str_replace('|', '<br>', $_GET['pfb_error'])); ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['pfb_success'])): ?>
    <div class="pfb-message pfb-success">
        Form submitted successfully!
    </div>
<?php endif; ?>



<form class="pfb-form"
      method="post"
      action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
      enctype="multipart/form-data">


    <input type="hidden" name="action" value="pfb_submit_form">
    <input type="hidden" name="pfb_form_id" value="<?php echo esc_attr($id); ?>">

    <?php wp_nonce_field('pfb_frontend_submit', 'pfb_nonce'); ?>


    <?php foreach ($fields as $f): ?>
        <div class="pfb-field"
            <?php if (!empty($f->rules)): ?>
                data-conditions='<?php echo esc_attr($f->rules); ?>'
            <?php endif; ?>
        >

            <label><?php echo esc_html($f->label); ?></label>

        <?php
                switch ($f->type) {

                    case 'text':
                    case 'email':
                    case 'number':
                    case 'url':
                        ?>
                        <input
                            type="<?php echo esc_attr($f->type); ?>"
                            name="<?php echo esc_attr($f->name); ?>"
                            <?php echo !empty($f->required) ? 'required' : ''; ?>
                        >
                        <?php
                        break;

                    case 'textarea':
                        ?>
                        <textarea 
                            name="<?php echo esc_attr($f->name); ?>" 
                            <?php echo !empty($f->required) ? 'required' : ''; ?>
                        ></textarea>
                        <?php
                        break;

                    case 'select':
                        $options = json_decode($f->options, true) ?: [];
                        ?>
                        <select name="<?php echo esc_attr($f->name); ?>" <?php echo !empty($f->required) ? 'required' : ''; ?>>
                            <option value="">Select</option>
                            <?php foreach ($options as $opt): ?>
                                <option value="<?php echo esc_attr($opt); ?>">
                                    <?php echo esc_html($opt); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php
                        break;

                    case 'radio':
                        $options = json_decode($f->options, true) ?: [];
                        foreach ($options as $opt):
                        ?>
                            <label style="display:block;">
                                <input
                                    type="radio"
                                    name="<?php echo esc_attr($f->name); ?>"
                                    value="<?php echo esc_attr($opt); ?>"
                                    <?php echo !empty($f->required) ? 'required' : ''; ?>
                                >
                                <?php echo esc_html($opt); ?>
                            </label>
                        <?php
                        endforeach;
                        break;

                    case 'file':
                        ?>
                        <input
                            type="file"
                            name="<?php echo esc_attr($f->name); ?>"
                            <?php echo !empty($f->required) ? 'required' : ''; ?>
                        >
                        <?php
                        break;

                    case 'image':
                        ?>
                        <input
                            type="file"
                            accept="image/*"
                            name="<?php echo esc_attr($f->name); ?>"
                            <?php echo !empty($f->required) ? 'required' : ''; ?>
                        >
                        <?php
                        break;
                }
                ?>


        </div>
    <?php endforeach; ?>

    <button type="submit">Submit</button>
</form>




<script>
function getFormData(form) {
    const data = {};
    form.querySelectorAll('[name]').forEach(el => {
        if (el.type === 'radio') {
            if (el.checked) data[el.name] = el.value;
        } else {
            data[el.name] = el.value;
        }
    });
    return data;
}

function evaluateRules(ruleGroups, formData) {
    return ruleGroups.some(group => {
        return group.rules.every(rule => {
            const currentValue = formData[rule.field] ?? '';

            if (currentValue === '') return false;

            if (rule.operator === 'is') {
                return currentValue === rule.value;
            }

            if (rule.operator === 'is_not') {
                return currentValue !== rule.value;
            }

            return false;
        });
    });
}

function applyConditions() {
    const form = document.querySelector('.pfb-form');
    if (!form) return;

    const formData = getFormData(form);

    document.querySelectorAll('.pfb-field').forEach(field => {

        // No conditional logic → always visible
        if (!field.dataset.conditions) {
            field.style.display = '';
            return;
        }

        const rules = JSON.parse(field.dataset.conditions);
        const shouldShow = evaluateRules(rules, formData);

        field.style.display = shouldShow ? '' : 'none';

        // prevent hidden required bug
        field.querySelectorAll('input, select, textarea').forEach(el => {
            if (!el.dataset.wasRequired) {
                el.dataset.wasRequired = el.required ? '1' : '0';
            }

            if (shouldShow) {
                el.disabled = false;
                el.required = el.dataset.wasRequired === '1';
            } else {
                el.disabled = true;
                el.required = false;
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', applyConditions);
document.addEventListener('change', applyConditions);
</script>


