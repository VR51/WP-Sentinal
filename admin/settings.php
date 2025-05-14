<?php
/**
 * WP Sentinal Settings Page
 *
 * @package WP_Sentinal
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="edit.php?action=wp_sentinal_update_settings">
        <?php wp_nonce_field('wp_sentinal_settings_nonce', 'wp_sentinal_settings_nonce'); ?>
        
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Notification Email', 'wp-sentinal'); ?></th>
                <td>
                    <input type="email" name="wp_sentinal_options[email]" value="<?php echo esc_attr($this->options['email']); ?>" class="regular-text" />
                    <p class="description"><?php _e('Email address to receive notifications.', 'wp-sentinal'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Enable Notifications', 'wp-sentinal'); ?></th>
                <td>
                    <select name="wp_sentinal_options[enable_notifications]">
                        <option value="yes" <?php selected($this->options['enable_notifications'], 'yes'); ?>><?php _e('Yes', 'wp-sentinal'); ?></option>
                        <option value="no" <?php selected($this->options['enable_notifications'], 'no'); ?>><?php _e('No', 'wp-sentinal'); ?></option>
                    </select>
                    <p class="description"><?php _e('Enable or disable email notifications.', 'wp-sentinal'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>

<?php
// Process settings update
add_action('network_admin_edit_wp_sentinal_update_settings', function() {
    // Check nonce
    if (!isset($_POST['wp_sentinal_settings_nonce']) || !wp_verify_nonce($_POST['wp_sentinal_settings_nonce'], 'wp_sentinal_settings_nonce')) {
        wp_die(__('Security check failed.', 'wp-sentinal'));
    }
    
    // Save options
    if (isset($_POST['wp_sentinal_options'])) {
        $options = $_POST['wp_sentinal_options'];
        
        // Sanitize email
        if (isset($options['email'])) {
            $options['email'] = sanitize_email($options['email']);
        }
        
        // Sanitize enable_notifications
        if (isset($options['enable_notifications'])) {
            $options['enable_notifications'] = ($options['enable_notifications'] === 'yes') ? 'yes' : 'no';
        }
        
        // Update options
        update_site_option('wp_sentinal_options', $options);
    }
    
    // Redirect back to settings page
    wp_redirect(add_query_arg(array('page' => 'wp-sentinal', 'updated' => 'true'), network_admin_url('settings.php')));
    exit;
});

// Display settings updated message
if (isset($_GET['updated']) && $_GET['updated'] === 'true') {
    add_action('network_admin_notices', function() {
        echo '<div class="updated"><p>' . __('Settings saved.', 'wp-sentinal') . '</p></div>';
    });
}
