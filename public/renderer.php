<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

/**
 * $id shortcode থেকে আসছে
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

<form class="pfb-form">

<?php foreach ($fields as $f): ?>
    <div class="pfb-field" style="display:none;"
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
                    >
                    <?php
                    break;

                case 'textarea':
                    ?>
                    <textarea name="<?php echo esc_attr($f->name); ?>"></textarea>
                    <?php
                    break;

                case 'select':
                    $options = json_decode($f->options, true) ?: [];
                    ?>
                    <select name="<?php echo esc_attr($f->name); ?>">
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
                    >
                    <?php
                    break;

                case 'image':
                    ?>
                    <input
                        type="file"
                        accept="image/*"
                        name="<?php echo esc_attr($f->name); ?>"
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

                if (currentValue === '') {
                    return false;
                }

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

        document.querySelectorAll('.pfb-field[data-conditions]').forEach(field => {
            const rules = JSON.parse(field.dataset.conditions);
            const shouldShow = evaluateRules(rules, formData);

            field.style.display = shouldShow ? '' : 'none';

            // CRITICAL FIX — hidden field submit বন্ধ
            field.querySelectorAll('input, select, textarea').forEach(el => {
                el.disabled = !shouldShow;
            });
        });
    }



    // Change + initial load
    document.addEventListener('change', applyConditions);
    document.addEventListener('DOMContentLoaded', applyConditions);
</script>

