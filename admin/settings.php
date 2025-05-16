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
    
    <form method="post" action="<?php echo esc_url(network_admin_url('settings.php?page=wp-sentinal')); ?>">
        <?php wp_nonce_field('wp_sentinal_settings_nonce', 'wp_sentinal_settings_nonce'); ?>
        
        <h2><?php _e('Email Notification Settings', 'wp-sentinal'); ?></h2>
        <table class="form-table">
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
            
            <tr valign="top">
                <th scope="row"><?php _e('Primary Recipient Email', 'wp-sentinal'); ?></th>
                <td>
                    <input type="email" name="wp_sentinal_options[email]" value="<?php echo esc_attr($this->options['email']); ?>" class="regular-text" />
                    <p class="description"><?php _e('Primary email address to receive notifications.', 'wp-sentinal'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Additional Recipients', 'wp-sentinal'); ?></th>
                <td>
                    <textarea name="wp_sentinal_options[additional_recipients]" rows="3" class="large-text"><?php echo esc_textarea(isset($this->options['additional_recipients']) ? $this->options['additional_recipients'] : ''); ?></textarea>
                    <p class="description"><?php _e('Additional email addresses to receive notifications. Add one email per line.', 'wp-sentinal'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Sender Name', 'wp-sentinal'); ?></th>
                <td>
                    <input type="text" name="wp_sentinal_options[sender_name]" value="<?php echo esc_attr(isset($this->options['sender_name']) ? $this->options['sender_name'] : 'WP Sentinal'); ?>" class="regular-text" />
                    <p class="description"><?php _e('Name that will appear as the sender of notification emails.', 'wp-sentinal'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Sender Email', 'wp-sentinal'); ?></th>
                <td>
                    <input type="email" name="wp_sentinal_options[sender_email]" value="<?php echo esc_attr(isset($this->options['sender_email']) ? $this->options['sender_email'] : get_option('admin_email')); ?>" class="regular-text" />
                    <p class="description"><?php _e('Email address that will be used as the sender of notification emails.', 'wp-sentinal'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Email Subject (Success)', 'wp-sentinal'); ?></th>
                <td>
                    <input type="text" name="wp_sentinal_options[subject_success]" value="<?php echo esc_attr(isset($this->options['subject_success']) ? $this->options['subject_success'] : 'WP Sentinal Report | %host% | All OK'); ?>" class="large-text" />
                    <p class="description"><?php _e('Subject line for success emails. Available variables: %host%, %site_name%, %time%, %date%', 'wp-sentinal'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Email Subject (Failure)', 'wp-sentinal'); ?></th>
                <td>
                    <input type="text" name="wp_sentinal_options[subject_failure]" value="<?php echo esc_attr(isset($this->options['subject_failure']) ? $this->options['subject_failure'] : 'WP Sentinal Report | %host% | FAIL'); ?>" class="large-text" />
                    <p class="description"><?php _e('Subject line for failure emails. Available variables: %host%, %site_name%, %time%, %date%', 'wp-sentinal'); ?></p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Content Settings', 'wp-sentinal'); ?></h2>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Include Plugin Comparison Table', 'wp-sentinal'); ?></th>
                <td>
                    <select name="wp_sentinal_options[include_comparison]">
                        <option value="yes" <?php selected(isset($this->options['include_comparison']) ? $this->options['include_comparison'] : 'yes', 'yes'); ?>><?php _e('Yes', 'wp-sentinal'); ?></option>
                        <option value="no" <?php selected(isset($this->options['include_comparison']) ? $this->options['include_comparison'] : 'yes', 'no'); ?>><?php _e('No', 'wp-sentinal'); ?></option>
                    </select>
                    <p class="description"><?php _e('Include a before/after comparison table of plugins in the notification email.', 'wp-sentinal'); ?></p>
                </td>
            </tr>
            
            <?php if (is_multisite()) : ?>
            <tr valign="top">
                <th scope="row"><?php _e('Multisite Table Display', 'wp-sentinal'); ?></th>
                <td>
                    <select name="wp_sentinal_options[multisite_table_display]">
                        <option value="combined" <?php selected(isset($this->options['multisite_table_display']) ? $this->options['multisite_table_display'] : 'combined', 'combined'); ?>><?php _e('Combined table with site information', 'wp-sentinal'); ?></option>
                        <option value="separate" <?php selected(isset($this->options['multisite_table_display']) ? $this->options['multisite_table_display'] : 'combined', 'separate'); ?>><?php _e('Separate table for each site', 'wp-sentinal'); ?></option>
                    </select>
                    <p class="description"><?php _e('For multisite installations, choose how to display plugin information across sites.', 'wp-sentinal'); ?></p>
                </td>
            </tr>
            <?php endif; ?>
            
            <tr valign="top">
                <th scope="row"><?php _e('Include Upgraded Plugins List', 'wp-sentinal'); ?></th>
                <td>
                    <select name="wp_sentinal_options[include_upgraded_list]">
                        <option value="yes" <?php selected(isset($this->options['include_upgraded_list']) ? $this->options['include_upgraded_list'] : 'yes', 'yes'); ?>><?php _e('Yes', 'wp-sentinal'); ?></option>
                        <option value="no" <?php selected(isset($this->options['include_upgraded_list']) ? $this->options['include_upgraded_list'] : 'yes', 'no'); ?>><?php _e('No', 'wp-sentinal'); ?></option>
                    </select>
                    <p class="description"><?php _e('Include a list of only the upgraded plugins in the notification email.', 'wp-sentinal'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Include Upgrade Time', 'wp-sentinal'); ?></th>
                <td>
                    <select name="wp_sentinal_options[include_time]">
                        <option value="yes" <?php selected(isset($this->options['include_time']) ? $this->options['include_time'] : 'yes', 'yes'); ?>><?php _e('Yes', 'wp-sentinal'); ?></option>
                        <option value="no" <?php selected(isset($this->options['include_time']) ? $this->options['include_time'] : 'yes', 'no'); ?>><?php _e('No', 'wp-sentinal'); ?></option>
                    </select>
                    <p class="description"><?php _e('Include the time when the upgrade occurred in the notification email.', 'wp-sentinal'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>

<?php
// Process settings update
if (isset($_POST['wp_sentinal_settings_nonce'])) {
    // Check nonce
    if (!wp_verify_nonce($_POST['wp_sentinal_settings_nonce'], 'wp_sentinal_settings_nonce')) {
        wp_die(__('Security check failed.', 'wp-sentinal'));
    }
    
    // Save options
    if (isset($_POST['wp_sentinal_options'])) {
        $options = $_POST['wp_sentinal_options'];
        
        // Sanitize email
        if (isset($options['email'])) {
            $options['email'] = sanitize_email($options['email']);
        }
        
        // Sanitize sender email
        if (isset($options['sender_email'])) {
            $options['sender_email'] = sanitize_email($options['sender_email']);
        }
        
        // Sanitize sender name
        if (isset($options['sender_name'])) {
            $options['sender_name'] = sanitize_text_field($options['sender_name']);
        }
        
        // Sanitize subject lines
        if (isset($options['subject_success'])) {
            $options['subject_success'] = sanitize_text_field($options['subject_success']);
        }
        
        if (isset($options['subject_failure'])) {
            $options['subject_failure'] = sanitize_text_field($options['subject_failure']);
        }
        
        // Sanitize additional recipients
        if (isset($options['additional_recipients'])) {
            $recipients = explode("\n", $options['additional_recipients']);
            $sanitized_recipients = array();
            
            foreach ($recipients as $recipient) {
                $recipient = trim($recipient);
                if (!empty($recipient) && is_email($recipient)) {
                    $sanitized_recipients[] = sanitize_email($recipient);
                }
            }
            
            $options['additional_recipients'] = implode("\n", $sanitized_recipients);
        }
        
        // Sanitize boolean options
        $boolean_options = array(
            'enable_notifications',
            'include_comparison',
            'include_upgraded_list',
            'include_time'
        );
        
        foreach ($boolean_options as $option) {
            if (isset($options[$option])) {
                $options[$option] = ($options[$option] === 'yes') ? 'yes' : 'no';
            }
        }
        
        // Update options
        update_site_option('wp_sentinal_options', $options);
        
        // Set updated flag
        $updated = true;
    }
}

// Display settings updated message
if (isset($updated) || (isset($_GET['updated']) && $_GET['updated'] === 'true')) {
    echo '<div class="updated"><p>' . __('Settings saved.', 'wp-sentinal') . '</p></div>';
}
