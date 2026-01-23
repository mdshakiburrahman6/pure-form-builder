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

<?php


$pfb_errors = [];

if (!empty($_GET['pfb_errors'])) {
    $pfb_errors = json_decode(
        stripslashes(urldecode($_GET['pfb_errors'])),
        true
    );
}

?>

<form class="pfb-form"
    method="post"
    action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
    enctype="multipart/form-data"
    novalidate
    >


    <input type="hidden" name="action" value="pfb_submit_form">
    <input type="hidden" name="pfb_form_id" value="<?php echo esc_attr($id); ?>">

    <?php wp_nonce_field('pfb_frontend_submit', 'pfb_nonce'); ?>


    <?php foreach ($fields as $f): 
        $has_error = isset($pfb_errors[$f->name]);
    ?>
        <div class="pfb-field <?php echo $has_error ? 'pfb-has-error' : ''; ?>"
            <?php if (!empty($f->rules)): ?>
                data-conditions="<?php echo esc_attr($f->rules); ?>"
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
                            class="<?php echo $has_error ? 'pfb-error-input' : ''; ?>"
                        >
                        <?php
                        break;

                    case 'textarea':
                        ?>
                        <textarea 
                            name="<?php echo esc_attr($f->name); ?>" 
                            <?php echo !empty($f->required) ? 'required' : ''; ?>
                            class="<?php echo $has_error ? 'pfb-error-input' : ''; ?>"
                        ></textarea>
                        <?php
                        break;

                    case 'select':
                        $options = json_decode($f->options, true) ?: [];
                        ?>
                        <select name="<?php echo esc_attr($f->name); ?>" 
                            <?php echo !empty($f->required) ? 'required' : ''; ?> 
                            class="<?php echo $has_error ? 'pfb-error-input' : ''; ?>"
                            >
                            
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
                                    class="<?php echo $has_error ? 'pfb-error-input' : ''; ?>"
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
                            class="<?php echo $has_error ? 'pfb-error-input' : ''; ?>"
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
                            class="<?php echo $has_error ? 'pfb-error-input' : ''; ?>"
                        >
                        <?php
                        break;
                }
                ?>


        </div>
    <?php endforeach; ?>

    <button type="submit">Submit</button>
</form>

<!-- Success Message with sweet alart -->
<?php if (isset($_GET['pfb_success'])) : ?>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });

    Toast.fire({
        icon: 'success',
        title: 'Form submitted successfully!'
    });

});
</script>
<?php endif; ?>

<!-- Error Message with sweet alart -->
<?php if (!empty($pfb_errors)) : ?>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const fields = <?php echo wp_json_encode(array_values($pfb_errors)); ?>;

    const message = fields.join(', ');

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true
    });

    Toast.fire({
        icon: 'error',
        title: message
    });

});
</script>
<?php endif; ?>






<script>
/**
 * 
 * URL clean after success message
 * 
 */
(function () {
    const url = new URL(window.location.href);

    if (url.searchParams.has('pfb_success')) {
        url.searchParams.delete('pfb_success');
        window.history.replaceState({}, document.title, url.pathname);
    }
})();
// URL clean after success message


/**
 * 
 * URL clean after error message
 * 
 */
(function () {
    const url = new URL(window.location.href);

    if (url.searchParams.has('pfb_errors')) {
        url.searchParams.delete('pfb_errors');
        window.history.replaceState({}, document.title, url.pathname);
    }
})();

// URL clean after Error message


// Auto Scroll To First Error
document.addEventListener('DOMContentLoaded', function () {
    const firstError = document.querySelector('.pfb-has-error');
    if (firstError) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

// Error class live remove
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.pfb-error-input').forEach(function (input) {

        input.addEventListener('input', function () {

            // remove red input style
            input.classList.remove('pfb-error-input');

            // remove field wrapper error
            const field = input.closest('.pfb-field');
            if (field) {
                field.classList.remove('pfb-has-error');
            }

        });

        input.addEventListener('change', function () {
            input.dispatchEvent(new Event('input'));
        });

    });

});

// URL clean before user types
document.addEventListener('DOMContentLoaded', function () {

    const url = new URL(window.location.href);

    if (url.searchParams.has('pfb_errors')) {

        document.querySelectorAll('input, textarea, select').forEach(el => {
            el.addEventListener('input', () => {
                url.searchParams.delete('pfb_errors');
                window.history.replaceState({}, document.title, url.pathname);
            }, { once: true });
        });

    }

});

document.addEventListener('input', function (e) {
    const field = e.target;

    if (
        field.closest('.pfb-field') &&
        field.value.trim() !== ''
    ) {
        const wrapper = field.closest('.pfb-field');

        wrapper.classList.remove('pfb-has-error');
        field.classList.remove('pfb-error-input');
    }
});



// Form Handlar
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


