<?php
if (!defined('ABSPATH')) exit;

add_shortcode('pfb_form', function ($atts) {

    global $wpdb;

    $atts = shortcode_atts([
        'id' => 0
    ], $atts);

    $form_id = intval($atts['id']);
    if (!$form_id) return '';

    $entry_id = intval($atts['entry_id'] ?? 0);


    $form = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pfb_forms WHERE id = %d",
            $form_id
        )
    );

    if (!$form) {
        return '<p>Invalid form.</p>';
    }

    // ACCESS CHECK (EARLY, SAFE)
    $access_type   = $form->access_type ?? 'all';
    $redirect_type = $form->redirect_type ?? 'message';
    $redirect_page = intval($form->redirect_page ?? 0);

    if ($access_type === 'logged_in' && !is_user_logged_in()) {

        if ($redirect_type === 'login') {
            wp_safe_redirect( wp_login_url( get_permalink() ) );
            exit;
        }

        if ($redirect_type === 'page' && $redirect_page) {
            wp_safe_redirect( get_permalink($redirect_page) );
            exit;
        }
    }

    // Renderer
    $id = $form_id;
    ob_start();
    include PFB_PATH . 'public/renderer.php';
    return ob_get_clean();
});



// For Users 
add_shortcode('pfb_my_entry', 'pfb_render_my_entry');

function pfb_render_my_entry($atts) {

    if (!is_user_logged_in()) {
        return '<p>Please login to view your profile.</p>';
    }

    global $wpdb;

    $form_id = intval($atts['form_id'] ?? 0);
    if (!$form_id) {
        return '<p>Invalid form.</p>';
    }

    $user_id = get_current_user_id();

    // Check existing entry
    $entry_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}pfb_entries 
             WHERE form_id = %d AND user_id = %d",
            $form_id,
            $user_id
        )
    );

    // If no entry → show form
    if (!$entry_id) {
        return do_shortcode('[pfb_form id="'.$form_id.'"]');
    }

    // View / Edit mode
    if (isset($_GET['edit']) && $_GET['edit'] == 1) {
        return do_shortcode('[pfb_form id="'.$form_id.'" edit="1" entry_id="'.$entry_id.'"]');
    }

    // Default → show entry details
    return pfb_render_entry_view($entry_id, $form_id);
    
}


// Entry details view function
if (!function_exists('pfb_render_entry_view')) {

    function pfb_render_entry_view($entry_id, $form_id = null) {

        global $wpdb;

        // If form_id not passed (admin view)
        if (!$form_id) {
            $form_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT form_id FROM {$wpdb->prefix}pfb_entries WHERE id=%d",
                    $entry_id
                )
            );
        }

        if (!$form_id) {
            return '<p>Invalid entry.</p>';
        }

        $meta = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.field_value, f.label, f.type
                 FROM {$wpdb->prefix}pfb_entry_meta m
                 INNER JOIN {$wpdb->prefix}pfb_fields f 
                    ON m.field_name = f.name
                 WHERE m.entry_id = %d
                 ORDER BY f.sort_order ASC",
                $entry_id
            )
        );

        ob_start();
        ?>
        <div class="pfb-entry-view">
            <h3>My Profile</h3>

            <div class="pfb-profile-card">

                <?php foreach ($meta as $m): 
                    if (empty($m->field_value)) continue;
                ?>

                    <?php if ($m->type === 'image'): ?>
                    <div class="pfb-profile-image">
                        <img src="<?php echo esc_url($m->field_value); ?>" alt="">
                        <a class="pfb-download-btn" href="<?php echo esc_url($m->field_value); ?>" download>
                        ⬇ Download Image
                        </a>
                    </div>

                    <?php else: ?>
                    <div class="pfb-info-card">
                        <div class="pfb-label"><?php echo esc_html($m->label); ?></div>
                        <div class="pfb-value"><?php echo esc_html($m->field_value); ?></div>
                    </div>
                    <?php endif; ?>

                <?php endforeach; ?>

            </div>

            <a class="pfb-edit-btn" href="<?php echo esc_url(add_query_arg('edit', 1)); ?>">
                ✏️ Edit Profile
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
}




// View / Edit mode
if (isset($_GET['edit']) && $_GET['edit'] == 1) {
    return do_shortcode(
        '[pfb_form id="'.$form_id.'" edit="1" entry_id="'.$entry_id.'"]'
    );
}
