<?php
// public/renderer.php
if (!defined('ABSPATH')) exit;

// Renderer for [pfb_form] shortcode
global $wpdb;

// Renderer for [pfb_form] shortcode
$form = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pfb_forms WHERE id = %d",
        $id
    )
);

// Check if editing an existing entry
$is_edit = !empty($entry_id);
$existing_meta = [];

if ($is_edit) {
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT field_name, field_value
             FROM {$wpdb->prefix}pfb_entry_meta
             WHERE entry_id = %d",
            $entry_id
        )
    );

    foreach ($rows as $row) {
        $existing_meta[$row->field_name] = $row->field_value;
    }
}

// Form existence check
if (!$form) {
    echo '<p>Invalid form.</p>';
    return;
}

// ACCESS SETTINGS
$access_type   = $form->access_type ?? 'all';
$allowed_roles = !empty($form->allowed_roles)
    ? array_map('trim', explode(',', $form->allowed_roles))
    : [];

$redirect_type = $form->redirect_type ?? 'message';
$redirect_page = intval($form->redirect_page ?? 0);


$is_logged_in = is_user_logged_in();
$current_user = wp_get_current_user();

// ACCESS TYPE CHECK
$access_error = null;

if ($access_type === 'logged_in' && !is_user_logged_in()) {
    $access_error = pfb_handle_access_denied($redirect_type, $redirect_page);
}

if ($access_type === 'guest' && is_user_logged_in()) {
    $access_error = pfb_handle_access_denied($redirect_type, $redirect_page);
}

// ROLE CHECK — independent of access_type
if (!$access_error && !empty($allowed_roles)) {

    if (!is_user_logged_in()) {
        // roles set but user not logged in
        $access_error = pfb_handle_access_denied($redirect_type, $redirect_page);

    } elseif (empty(array_intersect($allowed_roles, (array) $current_user->roles))) {
        // logged in but wrong role
        $access_error = pfb_handle_access_denied($redirect_type, $redirect_page);
    }
}

// MESSAGE
if ($access_error === 'message') {
    echo '<div class="pfb-access-denied">
        <strong>Access Denied</strong><br>
        You do not have permission to view this form.
    </div>';
    return;
}

// REDIRECT (login / page)
if (is_array($access_error)) {

    add_action('template_redirect', function () use ($access_error) {

        if ($access_error['type'] === 'login') {
            wp_safe_redirect(wp_login_url(get_permalink()));
            exit;
        }

        if ($access_error['type'] === 'page' && !empty($access_error['page'])) {
            wp_safe_redirect(get_permalink($access_error['page']));
            exit;
        }

    });

    return;
}





/**
 * // User Entries View
 * $id shortcode 
 * [pfb_form id="2"]
 * 
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

    <?php if (!empty($entry_id)): ?>
        <input type="hidden" name="entry_id" value="<?php echo esc_attr($entry_id); ?>">
    <?php endif; ?>


    <input type="hidden" name="action" value="pfb_submit_form">
    <input type="hidden" name="pfb_form_id" value="<?php echo esc_attr($id); ?>">

    <?php wp_nonce_field('pfb_frontend_submit', 'pfb_nonce'); ?>


    <?php foreach ($fields as $f): 
        $has_error = isset($pfb_errors[$f->name]);
    ?>
        <div class="pfb-field <?php echo $has_error ? 'pfb-has-error' : ''; ?>"
            <?php if (!empty($f->rules)): ?>
                data-rules="<?php echo esc_attr($f->rules); ?>"
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
                       <?php
                            $value = ($is_edit && isset($existing_meta[$f->name]))
                                ? esc_attr($existing_meta[$f->name])
                                : '';
                            ?>

                            <input
                                type="<?php echo esc_attr($f->type); ?>"
                                name="<?php echo esc_attr($f->name); ?>"
                                value="<?php echo $value; ?>"
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
                        ><?php
                            echo esc_textarea($existing_meta[$f->name] ?? '');
                        ?></textarea>

                        <?php
                        break;

                    case 'select':
                        $options = json_decode($f->options, true) ?: [];
                        ?>
                        <select name="<?php echo esc_attr($f->name); ?>" 
                            <?php echo !empty($f->required) ? 'required' : ''; ?> 
                            class="<?php echo $has_error ? 'pfb-error-input' : ''; ?>"
                            >
                            
                            <option value="" <?php selected(empty($existing_meta[$f->name])); ?>>
                                Select
                            </option>
                            <?php foreach ($options as $opt): ?>
                                <option value="<?php echo esc_attr($opt); ?>"
                                    <?php selected(($existing_meta[$f->name] ?? '') === $opt); ?>
                                >
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
                                    <?php checked(($existing_meta[$f->name] ?? '') === $opt); ?>
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
                            <div class="pfb-file-wrap">
                                <?php
                                    $types = !empty($f->file_types) ? $f->file_types : '';
                                    $max   = !empty($f->max_size) ? $f->max_size : 0;
                                    $min   = !empty($f->min_size) ? $f->min_size : 0;

                                    ?>

                                    <input
                                    type="file"
                                    accept="image/*"
                                    name="<?php echo esc_attr($f->name); ?>"
                                    class="pfb-file-input <?php echo $has_error ? 'pfb-error-input' : ''; ?>"
                                    data-preview="pfb-preview-<?php echo esc_attr($f->name); ?>"
                                    data-types="<?php echo esc_attr($types); ?>"
                                    data-max="<?php echo esc_attr($max); ?>"
                                    data-min="<?php echo esc_attr($min); ?>"
                                >


                                <div class="pfb-preview" id="pfb-preview-<?php echo esc_attr($f->name); ?>">
                                    <?php if ($is_edit && !empty($existing_meta[$f->name])): ?>
                                        <div class="pfb-image-preview existing">
                                            <img src="<?php echo esc_url($existing_meta[$f->name]); ?>" />
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (in_array($f->type, ['image','file'])): ?>
                                <?php if (!empty($f->file_types) || !empty($f->max_size)): ?>
                                    <small class="pfb-file-hint" style="display:block; margin-top:6px; color:#666;">
                                        <?php if (!empty($f->file_types)): ?>
                                            Allowed: <?php echo esc_html($f->file_types); ?>
                                        <?php endif; ?>

                                        <?php if (!empty($f->max_size)): ?>
                                            <?php if (!empty($f->file_types)) echo ' | '; ?>
                                            Max size: <?php echo esc_html($f->max_size); ?>MB
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php
                    break;

                }
                ?>


        </div>
    <?php endforeach; ?>

    <button type="submit">
        <?php echo $is_edit ? 'Update Profile' : 'Submit'; ?>
    </button>

</form>


<!-- User Entries View-->
 <?php 
    // $is_view = isset($_GET['pfb_view'], $_GET['entry_id']);
    // $entry_id = $is_view ? intval($_GET['entry_id']) : 0;

    // if ($is_view) {

    //     if (!pfb_user_can_edit_entry($entry_id, $id)) {
    //         echo '<div class="pfb-access-denied">You cannot view this entry.</div>';
    //         return;
    //     }

    //     $rows = $wpdb->get_results(
    //         $wpdb->prepare(
    //             "SELECT field_name, field_value
    //             FROM {$wpdb->prefix}pfb_entry_meta
    //             WHERE entry_id = %d",
    //             $entry_id
    //         )
    //     );

    //     echo '<div class="pfb-entry-view">';
    //     echo '<h3>Your Submission</h3>';

    //     foreach ($rows as $row) {
    //         echo '<p><strong>' . esc_html($row->field_name) . '</strong>: ';
    //         echo esc_html($row->field_value) . '</p>';
    //     }

    //     // EDIT BUTTON
    //     echo '<a class="pfb-edit-btn" href="' . esc_url(
    //         add_query_arg(
    //             ['pfb_edit' => 1, 'entry_id' => $entry_id],
    //             get_permalink()
    //         )
    //     ) . '">Edit Entry</a>';

    //     echo '</div>';
    //     return;
    // }
 ?>


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

    if (e.target.type === 'file') return;

    const field = e.target.closest('.pfb-field');
    if (!field) return;

    if (e.target.value.trim() !== '') {
        field.classList.remove('pfb-has-error');
        e.target.classList.remove('pfb-error-input');
    }

});



document.addEventListener('click', function (e) {

    if (!e.target.classList.contains('pfb-remove-file')) return;

    const wrap = e.target.closest('.pfb-file-wrap');

    // reset file input
    const input = wrap.querySelector('input[type="file"]');
    input.value = '';

    // remove preview
    wrap.querySelector('.pfb-preview').innerHTML = '';

    // FIX — remove error UI
    const fieldWrapper = wrap.closest('.pfb-field');
    if (fieldWrapper) {
        fieldWrapper.classList.remove('pfb-has-error');
    }

    input.classList.remove('pfb-error-input');
});


</script>
<script>
document.addEventListener('change', function (e) {

    if (!e.target.classList.contains('pfb-file-input')) return;

    const input = e.target;
    const field = input.closest('.pfb-field');
    const previewBox = document.getElementById(input.dataset.preview);

    // reset UI
    input.classList.remove('pfb-error-input');
    field.classList.remove('pfb-has-error');
    if (previewBox) previewBox.innerHTML = '';

    if (!input.files || !input.files[0]) return;

    const file = input.files[0];
    const ext = file.name.split('.').pop().toLowerCase();
    const sizeMB = file.size / (1024 * 1024);

    const allowed = (input.dataset.types || '')
        .split(',')
        .map(t => t.trim().toLowerCase())
        .filter(Boolean);

    const maxMB = parseFloat(input.dataset.max || 0);
    const minMB = parseFloat(input.dataset.min || 0);

    let error = '';

    // invalid extension
    if (allowed.length && !allowed.includes(ext)) {
        error = `Invalid file type. Allowed: ${allowed.join(', ')}`;
    }

    // max size
    if (!error && maxMB && sizeMB > maxMB) {
        error = `File size exceeds ${maxMB} MB`;
    }

    // min size
    if (!error && minMB && sizeMB < minMB) {
        error = `File size must be at least ${minMB} MB`;
    }

    // INVALID → STOP
    if (error) {

        input.value = '';
        input.classList.add('pfb-error-input');
        field.classList.add('pfb-has-error');

        Swal.fire({
            icon: 'error',
            title: 'Invalid File',
            text: error,
        });

        return;
    }

    // VALID IMAGE → PREVIEW
    if (file.type.startsWith('image/') && previewBox) {

        const reader = new FileReader();
        reader.onload = function (ev) {
            previewBox.innerHTML = `
                <div class="pfb-image-preview">
                    <img src="${ev.target.result}" />
                    <button type="button" class="pfb-remove-file">✕</button>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }

});
</script>
