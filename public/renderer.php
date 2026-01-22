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
    <div class="pfb-field"
         <?php if (!empty($f->rules)): ?>
             data-rules='<?php echo esc_attr($f->rules); ?>'
         <?php endif; ?>
    >

        <label><?php echo esc_html($f->label); ?></label>

        <?php if ($f->type === 'text'): ?>
            <input type="text" name="<?php echo esc_attr($f->name); ?>">
        <?php endif; ?>

        <?php if ($f->type === 'select'): ?>
            <select name="<?php echo esc_attr($f->name); ?>">
                <option value="">Select</option>

                <?php
                $options = json_decode($f->options, true) ?: [];
                foreach ($options as $opt):
                ?>
                    <option value="<?php echo esc_attr($opt); ?>">
                        <?php echo esc_html($opt); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

    </div>
<?php endforeach; ?>

<button type="submit">Submit</button>
</form>
