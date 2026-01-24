<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;

$form = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pfb_forms WHERE id = %d",
        $form_id
    )
);

if (!$form) {
    echo '<div class="notice notice-error"><p>Invalid form.</p></div>';
    return;
}

$allowed_roles = [];

if (!empty($form->allowed_roles)) {
    $allowed_roles = array_map(
        'trim',
        explode(',', $form->allowed_roles)
    );
}

$allow_user_edit = !empty($form->allow_user_edit);
$access_type     = $form->access_type ?? 'all';
$redirect_type   = $form->redirect_type ?? 'message';
$redirect_page   = intval($form->redirect_page ?? 0);

$roles = wp_roles()->roles;
$pages = get_pages();
?>

<div class="wrap">
    <h1>Form Settings — <?php echo esc_html($form->name); ?></h1>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('pfb_save_form_settings', 'pfb_settings_nonce'); ?>

        <input type="hidden" name="action" value="pfb_save_form_settings">
        <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">

        <table class="form-table">

            <!-- ACCESS TYPE -->
            <tr>
                <th>Who can access this form?</th>
                <td>
                    <select name="access_type">
                        <option value="all" <?php selected($access_type, 'all'); ?>>Everyone</option>
                        <option value="logged_in" <?php selected($access_type, 'logged_in'); ?>>Only logged-in users</option>
                        <option value="guest" <?php selected($access_type, 'guest'); ?>>Only guests</option>
                    </select>
                </td>
            </tr>

            <!-- ROLES -->
            <tr>
                <th>Allowed Roles</th>
                <td>
                    <?php foreach ($roles as $key => $role): ?>
                        <label style="display:block;margin-bottom:6px;">
                            <input type="checkbox"
                                name="allowed_roles[]"
                                value="<?php echo esc_attr($key); ?>"
                                <?php checked(in_array($key, $allowed_roles)); ?>>
                            <?php echo esc_html($role['name']); ?>
                        </label>
                    <?php endforeach; ?>

                    <p class="description">
                        Leave all unchecked = all roles allowed
                    </p>

                </td>
            </tr>

            <!-- REDIRECT -->
            <tr>
                <th>If access denied</th>
                <td>
                    <select name="redirect_type" id="pfb-redirect-type">
                        <option value="message" <?php selected($redirect_type, 'message'); ?>>
                            Show message
                        </option>
                        <option value="login" <?php selected($redirect_type, 'login'); ?>>
                            Redirect to login
                        </option>
                        <option value="page" <?php selected($redirect_type, 'page'); ?>>
                            Redirect to page
                        </option>
                    </select>

                    <div id="pfb-redirect-page-wrap" style="margin-top:12px;">
                        <select name="redirect_page">
                            <option value="">— Select Page —</option>
                            <?php foreach ($pages as $p): ?>
                                <option value="<?php echo $p->ID; ?>"
                                    <?php selected($redirect_page, $p->ID); ?>>
                                    <?php echo esc_html($p->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </td>
            </tr>


            <!-- USER EDIT -->
            <tr>
                <th>User Entry Edit</th>
                <td>
                    <label>
                        <input type="checkbox" name="allow_user_edit" value="1"
                            <?php checked($allow_user_edit); ?>>
                        Allow users to edit their own entries
                    </label>
                </td>
            </tr>

        </table>

        <?php submit_button('Save Settings'); ?>
    </form>




<script>
    document.addEventListener('DOMContentLoaded', function () {
        const access = document.querySelector('[name="access_type"]');
        const roleRow = document.querySelector('input[name="allowed_roles[]"]')?.closest('tr');

        function toggleRoles() {
            roleRow.style.display = (access.value === 'logged_in') ? '' : 'none';
        }

        toggleRoles();
        access.addEventListener('change', toggleRoles);
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const redirectType = document.getElementById('pfb-redirect-type');
    const pageWrap     = document.getElementById('pfb-redirect-page-wrap');

    function toggleRedirectPage() {
        if (redirectType.value === 'page') {
            pageWrap.style.display = 'block';
        } else {
            pageWrap.style.display = 'none';
        }
    }

    // Initial load (edit mode fix)
    toggleRedirectPage();

    // On change
    redirectType.addEventListener('change', toggleRedirectPage);

});
</script>


</div>



