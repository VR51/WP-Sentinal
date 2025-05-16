<?php
/**
 * Plugin Name: WP Sentinal
 * Plugin URI: https://vr51.com/wp-sentinal
 * Description: Monitors plugin activations/deactivations during WordPress updates and sends email alerts.
 * Version: 1.0.0
 * Author: Lee Hodson
 * Author URI: https://vr51.com
 * Donate URI: https://paypal.me/vr51
 * Contributor: Cascade
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-sentinal
 * Domain Path: /languages
 * Network: true
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('WP_SENTINAL_VERSION', '1.0.0');

/**
 * The core plugin class.
 */
class WP_Sentinal {

    /**
     * The single instance of the class.
     */
    protected static $_instance = null;

    /**
     * Store active plugins before update.
     */
    private $active_plugins_before = array();
    
    /**
     * Store plugins being updated.
     */
    private $plugins_being_updated = array();
    
    /**
     * Store update time.
     */
    private $update_time = '';

    /**
     * Settings options.
     */
    private $options = array();

    /**
     * Main WP_Sentinal Instance.
     * 
     * Ensures only one instance of WP_Sentinal is loaded or can be loaded.
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * WP_Sentinal Constructor.
     */
    public function __construct() {
        $this->define_constants();
        $this->init_hooks();
        $this->load_options();
    }

    /**
     * Define WP Sentinal Constants.
     */
    private function define_constants() {
        define('WP_SENTINAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('WP_SENTINAL_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Hook into the plugin update process
        add_filter('upgrader_pre_install', array($this, 'before_plugin_update'), 10, 2);
        add_filter('upgrader_post_install', array($this, 'after_plugin_update'), 10, 3);
        
        // Add admin menu
        add_action('network_admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    /**
     * Load plugin options.
     */
    private function load_options() {
        $default_options = array(
            'email' => get_option('admin_email'),
            'enable_notifications' => 'yes',
            'sender_name' => 'WP Sentinal',
            'sender_email' => get_option('admin_email'),
            'subject_success' => 'WP Sentinal Report | %host% | All OK',
            'subject_failure' => 'WP Sentinal Report | %host% | FAIL',
            'additional_recipients' => '',
            'include_comparison' => 'yes',
            'include_upgraded_list' => 'yes',
            'include_time' => 'yes',
            'multisite_table_display' => 'combined'
        );
        
        $this->options = get_site_option('wp_sentinal_options', $default_options);
        
        // Ensure all default options exist
        $this->options = wp_parse_args($this->options, $default_options);
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // Set default options if they don't exist
        if (!get_site_option('wp_sentinal_options')) {
            update_site_option('wp_sentinal_options', array(
                'email' => get_option('admin_email'),
                'enable_notifications' => 'yes',
                'sender_name' => 'WP Sentinal',
                'sender_email' => get_option('admin_email'),
                'subject_success' => 'WP Sentinal Report | %host% | All OK',
                'subject_failure' => 'WP Sentinal Report | %host% | FAIL',
                'additional_recipients' => '',
                'include_comparison' => 'yes',
                'include_upgraded_list' => 'yes',
                'include_time' => 'yes',
                'multisite_table_display' => 'combined'
            ));
        }
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'settings.php',
            __('WP Sentinal Settings', 'wp-sentinal'),
            __('WP Sentinal', 'wp-sentinal'),
            'manage_network_options',
            'wp-sentinal',
            array($this, 'admin_page')
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting('wp_sentinal', 'wp_sentinal_options');
    }

    /**
     * Admin page.
     */
    public function admin_page() {
        include_once WP_SENTINAL_PLUGIN_DIR . 'admin/settings.php';
    }

    /**
     * Before plugin update.
     * 
     * @param bool $return
     * @param array $hook_extra
     * @return bool
     */
    public function before_plugin_update($return, $hook_extra) {
        if (!empty($hook_extra['plugin']) || !empty($hook_extra['plugins'])) {
            // Store active plugins before update
            $this->active_plugins_before = $this->get_active_plugins();
            
            // Store the plugins being updated
            if (!empty($hook_extra['plugin'])) {
                $this->plugins_being_updated = array($hook_extra['plugin']);
            } elseif (!empty($hook_extra['plugins'])) {
                $this->plugins_being_updated = $hook_extra['plugins'];
            }
            
            // Store update time
            $this->update_time = current_time('mysql');
        }
        return $return;
    }

    /**
     * After plugin update.
     * 
     * @param bool $return
     * @param array $hook_extra
     * @param array $result
     * @return bool
     */
    public function after_plugin_update($return, $hook_extra, $result) {
        if (!empty($hook_extra['plugin']) || !empty($hook_extra['plugins'])) {
            // Check if we have stored active plugins
            if (!empty($this->active_plugins_before)) {
                $active_plugins_after = $this->get_active_plugins();
                $this->check_plugin_status($this->active_plugins_before, $active_plugins_after);
            }
        }
        return $return;
    }

    /**
     * Get active plugins.
     * 
     * @return array
     */
    private function get_active_plugins() {
        if (is_multisite()) {
            // Get network active plugins
            $network_plugins = get_site_option('active_sitewide_plugins');
            $network_plugins = $network_plugins ? array_keys($network_plugins) : array();
            
            // Get all sites
            $sites = get_sites();
            $all_active_plugins = array();
            
            foreach ($sites as $site) {
                switch_to_blog($site->blog_id);
                $site_active_plugins = get_option('active_plugins', array());
                
                foreach ($site_active_plugins as $plugin) {
                    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                    $all_active_plugins[$plugin] = array(
                        'name' => $plugin_data['Name'],
                        'sites' => isset($all_active_plugins[$plugin]['sites']) ? $all_active_plugins[$plugin]['sites'] : array(),
                    );
                    $all_active_plugins[$plugin]['sites'][] = $site->blog_id;
                }
                restore_current_blog();
            }
            
            // Add network active plugins
            foreach ($network_plugins as $plugin) {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                $all_active_plugins[$plugin] = array(
                    'name' => $plugin_data['Name'],
                    'network_active' => true
                );
            }
            
            return $all_active_plugins;
        } else {
            // Single site
            $active_plugins = get_option('active_plugins', array());
            $plugins_data = array();
            
            foreach ($active_plugins as $plugin) {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                $plugins_data[$plugin] = array(
                    'name' => $plugin_data['Name']
                );
            }
            
            return $plugins_data;
        }
    }

    /**
     * Check plugin status and send email if needed.
     * 
     * @param array $before
     * @param array $after
     */
    private function check_plugin_status($before, $after) {
        $deactivated_plugins = array();
        
        // Find deactivated plugins
        foreach ($before as $plugin_file => $plugin_data) {
            if (!isset($after[$plugin_file])) {
                // Plugin was active before but not after
                $deactivated_plugins[$plugin_file] = $plugin_data;
            } else if (isset($plugin_data['network_active']) && !isset($after[$plugin_file]['network_active'])) {
                // Plugin was network active before but not after
                $deactivated_plugins[$plugin_file] = $plugin_data;
                $deactivated_plugins[$plugin_file]['deactivation_type'] = 'network';
            } else if (isset($plugin_data['sites']) && isset($after[$plugin_file]['sites'])) {
                // Check if plugin was deactivated on any sites
                $deactivated_sites = array_diff($plugin_data['sites'], $after[$plugin_file]['sites']);
                if (!empty($deactivated_sites)) {
                    $deactivated_plugins[$plugin_file] = $plugin_data;
                    $deactivated_plugins[$plugin_file]['deactivated_sites'] = $deactivated_sites;
                    $deactivated_plugins[$plugin_file]['deactivation_type'] = 'sites';
                }
            }
        }
        
        // Send email notification
        if ($this->options['enable_notifications'] === 'yes') {
            $this->send_notification_email($deactivated_plugins);
        }
    }

    /**
     * Replace variables in a string with their actual values.
     *
     * @param string $text Text with variables
     * @return string Text with variables replaced
     */
    private function replace_variables($text) {
        $site_name = get_bloginfo('name');
        $domain = parse_url(get_site_url(), PHP_URL_HOST);
        $date = date('Y-m-d');
        $time = date('H:i:s');
        
        $variables = array(
            '%host%' => $domain,
            '%site_name%' => $site_name,
            '%date%' => $date,
            '%time%' => $time
        );
        
        return str_replace(array_keys($variables), array_values($variables), $text);
    }
    
    /**
     * Generate plugin comparison table in HTML format for a single site.
     *
     * @param array $before Plugins before update
     * @param array $after Plugins after update
     * @param array $upgraded_plugins List of upgraded plugins
     * @param string $site_name Optional site name for multisite
     * @return string HTML table
     */
    private function generate_comparison_table($before, $after, $upgraded_plugins, $site_name = '') {
        // Add explanatory note above the table
        $table = "<p><em>Note: This table only shows active plugins and plugins with changed status.</em></p>\n";
        
        // Add site name if provided (for multisite)
        if (!empty($site_name)) {
            $table .= "<h4>Site: {$site_name}</h4>\n";
        }
        
        $table .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin-bottom: 20px;'>\n";
        $table .= "<tr style='background-color: #f2f2f2;'><th>Plugin</th><th>Before</th><th>After</th></tr>\n";
        
        // Combine all plugin keys from before and after
        $all_plugins = array_unique(array_merge(array_keys($before), array_keys($after)));
        sort($all_plugins);
        
        $has_rows = false;
        
        foreach ($all_plugins as $plugin_file) {
            $before_status = isset($before[$plugin_file]) ? 'Active' : 'Inactive';
            $after_status = isset($after[$plugin_file]) ? 'Active' : 'Inactive';
            
            // Skip if both statuses are inactive (only show active plugins or plugins with changed status)
            if ($before_status === 'Inactive' && $after_status === 'Inactive') {
                continue;
            }
            
            $has_rows = true;
            $plugin_name = isset($before[$plugin_file]) ? $before[$plugin_file]['name'] : $after[$plugin_file]['name'];
            
            $row_style = in_array($plugin_file, $upgraded_plugins) ? "background-color: #ffffcc;" : "";
            
            $table .= "<tr style='{$row_style}'>";
            $table .= "<td>{$plugin_name} ({$plugin_file})</td>";
            $table .= "<td>{$before_status}</td>";
            $table .= "<td>{$after_status}</td>";
            $table .= "</tr>\n";
        }
        
        if (!$has_rows) {
            $table .= "<tr><td colspan='3' style='text-align: center;'>No active plugins or status changes</td></tr>\n";
        }
        
        $table .= "</table>\n";
        return $table;
    }
    
    /**
     * Generate plugin comparison table for multisite with site information.
     *
     * @param array $before Plugins before update
     * @param array $after Plugins after update
     * @param array $upgraded_plugins List of upgraded plugins
     * @return string HTML table
     */
    private function generate_multisite_combined_table($before, $after, $upgraded_plugins) {
        // Add explanatory note above the table
        $table = "<p><em>Note: This table only shows active plugins and plugins with changed status.</em></p>\n";
        
        $table .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>\n";
        $table .= "<tr style='background-color: #f2f2f2;'><th>Plugin</th><th>Before</th><th>After</th><th>Sites</th></tr>\n";
        
        // Combine all plugin keys from before and after
        $all_plugins = array_unique(array_merge(array_keys($before), array_keys($after)));
        sort($all_plugins);
        
        $has_rows = false;
        
        foreach ($all_plugins as $plugin_file) {
            $before_status = isset($before[$plugin_file]) ? 'Active' : 'Inactive';
            $after_status = isset($after[$plugin_file]) ? 'Active' : 'Inactive';
            
            // Skip if both statuses are inactive (only show active plugins or plugins with changed status)
            if ($before_status === 'Inactive' && $after_status === 'Inactive') {
                continue;
            }
            
            $has_rows = true;
            $plugin_name = isset($before[$plugin_file]) ? $before[$plugin_file]['name'] : $after[$plugin_file]['name'];
            
            // Get site information
            $sites_info = '';
            if (isset($after[$plugin_file]['network_active'])) {
                $sites_info = 'Network Activated';
            } elseif (isset($after[$plugin_file]['sites'])) {
                // Get site names
                $site_names = array();
                foreach ($after[$plugin_file]['sites'] as $blog_id) {
                    $site_details = get_blog_details($blog_id);
                    if ($site_details) {
                        $site_names[] = $site_details->blogname;
                    }
                }
                $sites_info = implode(', ', $site_names);
            }
            
            $row_style = in_array($plugin_file, $upgraded_plugins) ? "background-color: #ffffcc;" : "";
            
            $table .= "<tr style='{$row_style}'>";
            $table .= "<td>{$plugin_name} ({$plugin_file})</td>";
            $table .= "<td>{$before_status}</td>";
            $table .= "<td>{$after_status}</td>";
            $table .= "<td>{$sites_info}</td>";
            $table .= "</tr>\n";
        }
        
        if (!$has_rows) {
            $table .= "<tr><td colspan='4' style='text-align: center;'>No active plugins or status changes</td></tr>\n";
        }
        
        $table .= "</table>\n";
        return $table;
    }
    
    /**
     * Generate separate plugin comparison tables for each site in a multisite network.
     *
     * @param array $before Plugins before update
     * @param array $after Plugins after update
     * @param array $upgraded_plugins List of upgraded plugins
     * @return string HTML tables
     */
    private function generate_multisite_separate_tables($before, $after, $upgraded_plugins) {
        $output = "<p><em>Note: These tables only show active plugins and plugins with changed status.</em></p>\n";
        
        // Network activated plugins
        $output .= "<h4>Network Activated Plugins</h4>\n";
        $network_before = array();
        $network_after = array();
        
        foreach ($before as $plugin_file => $plugin_data) {
            if (isset($plugin_data['network_active'])) {
                $network_before[$plugin_file] = $plugin_data;
            }
        }
        
        foreach ($after as $plugin_file => $plugin_data) {
            if (isset($plugin_data['network_active'])) {
                $network_after[$plugin_file] = $plugin_data;
            }
        }
        
        $output .= $this->generate_comparison_table($network_before, $network_after, $upgraded_plugins);
        
        // Get all sites
        $sites = get_sites();
        
        // Generate tables for each site
        foreach ($sites as $site) {
            $site_details = get_blog_details($site->blog_id);
            $site_name = $site_details ? $site_details->blogname : 'Site ID: ' . $site->blog_id;
            
            $site_before = array();
            $site_after = array();
            
            // Get plugins active on this site before update
            foreach ($before as $plugin_file => $plugin_data) {
                if (isset($plugin_data['sites']) && in_array($site->blog_id, $plugin_data['sites'])) {
                    $site_before[$plugin_file] = $plugin_data;
                }
            }
            
            // Get plugins active on this site after update
            foreach ($after as $plugin_file => $plugin_data) {
                if (isset($plugin_data['sites']) && in_array($site->blog_id, $plugin_data['sites'])) {
                    $site_after[$plugin_file] = $plugin_data;
                }
            }
            
            $output .= $this->generate_comparison_table($site_before, $site_after, $upgraded_plugins, $site_name);
        }
        
        return $output;
    }
    
    /**
     * Send notification email.
     * 
     * @param array $deactivated_plugins
     */
    private function send_notification_email($deactivated_plugins) {
        $site_name = get_bloginfo('name');
        $domain = parse_url(get_site_url(), PHP_URL_HOST);
        
        // Set up recipients
        $to = $this->options['email'];
        $additional_recipients = array();
        
        if (!empty($this->options['additional_recipients'])) {
            $recipients = explode("\n", $this->options['additional_recipients']);
            foreach ($recipients as $recipient) {
                $recipient = trim($recipient);
                if (!empty($recipient) && is_email($recipient)) {
                    $additional_recipients[] = $recipient;
                }
            }
        }
        
        // Set up headers for sender name and email
        $headers = array();
        
        if (!empty($this->options['sender_name']) && !empty($this->options['sender_email'])) {
            $headers[] = 'From: ' . $this->options['sender_name'] . ' <' . $this->options['sender_email'] . '>';
        }
        
        // Add additional recipients as BCC
        foreach ($additional_recipients as $recipient) {
            $headers[] = 'Bcc: ' . $recipient;
        }
        
        // Set content type to HTML if we're including comparison table
        if ($this->options['include_comparison'] === 'yes') {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        }
        
        if (empty($deactivated_plugins)) {
            // Success email
            $subject = $this->replace_variables($this->options['subject_success']);
            
            if ($this->options['include_comparison'] === 'yes') {
                // HTML message
                $message = "<html><body>";
                $message .= "<p>Hello,</p>";
                $message .= "<p>WP Sentinal has detected that all plugins remain active after the recent WordPress plugin update.</p>";
                $message .= "<p><strong>Site:</strong> {$site_name} ({$domain})<br>";
                $message .= "<strong>Status:</strong> All plugins remain active</p>";
                
                // Include upgrade time if enabled
                if ($this->options['include_time'] === 'yes' && !empty($this->update_time)) {
                    $message .= "<p><strong>Update Time:</strong> {$this->update_time}</p>";
                }
                
                // Include upgraded plugins list if enabled
                if ($this->options['include_upgraded_list'] === 'yes' && !empty($this->plugins_being_updated)) {
                    $message .= "<p><strong>Upgraded Plugins:</strong></p><ul>";
                    foreach ($this->plugins_being_updated as $plugin) {
                        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                        $message .= "<li>{$plugin_data['Name']} ({$plugin})</li>";
                    }
                    $message .= "</ul>";
                }
                
                // Include comparison table if enabled
                if (!empty($this->active_plugins_before)) {
                    $message .= "<p><strong>Plugin Status Comparison:</strong></p>";
                    
                    if (is_multisite() && isset($this->options['multisite_table_display'])) {
                        if ($this->options['multisite_table_display'] === 'separate') {
                            $message .= $this->generate_multisite_separate_tables(
                                $this->active_plugins_before, 
                                $this->get_active_plugins(), 
                                $this->plugins_being_updated ?? array()
                            );
                        } else {
                            $message .= $this->generate_multisite_combined_table(
                                $this->active_plugins_before, 
                                $this->get_active_plugins(), 
                                $this->plugins_being_updated ?? array()
                            );
                        }
                    } else {
                        $message .= $this->generate_comparison_table(
                            $this->active_plugins_before, 
                            $this->get_active_plugins(), 
                            $this->plugins_being_updated ?? array()
                        );
                    }
                }
                
                $message .= "<p><em>This is an automated message from WP Sentinal.</em></p>";
                $message .= "</body></html>";
            } else {
                // Plain text message
                $message = "Hello,\n\n";
                $message .= "WP Sentinal has detected that all plugins remain active after the recent WordPress plugin update.\n\n";
                $message .= "Site: {$site_name} ({$domain})\n";
                $message .= "Status: All plugins remain active\n\n";
                
                // Include upgrade time if enabled
                if ($this->options['include_time'] === 'yes' && !empty($this->update_time)) {
                    $message .= "Update Time: {$this->update_time}\n\n";
                }
                
                // Include upgraded plugins list if enabled
                if ($this->options['include_upgraded_list'] === 'yes' && !empty($this->plugins_being_updated)) {
                    $message .= "Upgraded Plugins:\n";
                    foreach ($this->plugins_being_updated as $plugin) {
                        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                        $message .= "- {$plugin_data['Name']} ({$plugin})\n";
                    }
                    $message .= "\n";
                }
                
                $message .= "This is an automated message from WP Sentinal.\n";
            }
        } else {
            // Failure email
            $subject = $this->replace_variables($this->options['subject_failure']);
            
            if ($this->options['include_comparison'] === 'yes') {
                // HTML message
                $message = "<html><body>";
                $message .= "<p>Hello,</p>";
                $message .= "<p>WP Sentinal has detected that one or more plugins have been deactivated during the recent WordPress plugin update.</p>";
                $message .= "<p><strong>Site:</strong> {$site_name} ({$domain})<br>";
                $message .= "<strong>Status:</strong> Some plugins were deactivated</p>";
                
                // Include upgrade time if enabled
                if ($this->options['include_time'] === 'yes' && !empty($this->update_time)) {
                    $message .= "<p><strong>Update Time:</strong> {$this->update_time}</p>";
                }
                
                $message .= "<p><strong>Deactivated Plugins:</strong></p><ul>";
                foreach ($deactivated_plugins as $plugin_file => $plugin_data) {
                    $message .= "<li>{$plugin_data['name']} ({$plugin_file})";
                    
                    if (isset($plugin_data['deactivation_type'])) {
                        if ($plugin_data['deactivation_type'] === 'network') {
                            $message .= " - Network deactivated";
                        } else if ($plugin_data['deactivation_type'] === 'sites') {
                            $message .= " - Deactivated on sites: " . implode(', ', $plugin_data['deactivated_sites']);
                        }
                    }
                    
                    $message .= "</li>";
                }
                $message .= "</ul>";
                
                // Include upgraded plugins list if enabled
                if ($this->options['include_upgraded_list'] === 'yes' && !empty($this->plugins_being_updated)) {
                    $message .= "<p><strong>Upgraded Plugins:</strong></p><ul>";
                    foreach ($this->plugins_being_updated as $plugin) {
                        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                        $message .= "<li>{$plugin_data['Name']} ({$plugin})</li>";
                    }
                    $message .= "</ul>";
                }
                
                // Include comparison table if enabled
                if (!empty($this->active_plugins_before)) {
                    $message .= "<p><strong>Plugin Status Comparison:</strong></p>";
                    
                    if (is_multisite() && isset($this->options['multisite_table_display'])) {
                        if ($this->options['multisite_table_display'] === 'separate') {
                            $message .= $this->generate_multisite_separate_tables(
                                $this->active_plugins_before, 
                                $this->get_active_plugins(), 
                                $this->plugins_being_updated ?? array()
                            );
                        } else {
                            $message .= $this->generate_multisite_combined_table(
                                $this->active_plugins_before, 
                                $this->get_active_plugins(), 
                                $this->plugins_being_updated ?? array()
                            );
                        }
                    } else {
                        $message .= $this->generate_comparison_table(
                            $this->active_plugins_before, 
                            $this->get_active_plugins(), 
                            $this->plugins_being_updated ?? array()
                        );
                    }
                }
                
                $message .= "<p><em>This is an automated message from WP Sentinal.</em></p>";
                $message .= "</body></html>";
            } else {
                // Plain text message
                $message = "Hello,\n\n";
                $message .= "WP Sentinal has detected that one or more plugins have been deactivated during the recent WordPress plugin update.\n\n";
                $message .= "Site: {$site_name} ({$domain})\n";
                $message .= "Status: Some plugins were deactivated\n\n";
                
                // Include upgrade time if enabled
                if ($this->options['include_time'] === 'yes' && !empty($this->update_time)) {
                    $message .= "Update Time: {$this->update_time}\n\n";
                }
                
                $message .= "Deactivated plugins:\n";
                foreach ($deactivated_plugins as $plugin_file => $plugin_data) {
                    $message .= "- {$plugin_data['name']} ({$plugin_file})";
                    
                    if (isset($plugin_data['deactivation_type'])) {
                        if ($plugin_data['deactivation_type'] === 'network') {
                            $message .= " - Network deactivated";
                        } else if ($plugin_data['deactivation_type'] === 'sites') {
                            $message .= " - Deactivated on sites: " . implode(', ', $plugin_data['deactivated_sites']);
                        }
                    }
                    
                    $message .= "\n";
                }
                $message .= "\n";
                
                // Include upgraded plugins list if enabled
                if ($this->options['include_upgraded_list'] === 'yes' && !empty($this->plugins_being_updated)) {
                    $message .= "Upgraded Plugins:\n";
                    foreach ($this->plugins_being_updated as $plugin) {
                        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                        $message .= "- {$plugin_data['Name']} ({$plugin})\n";
                    }
                    $message .= "\n";
                }
                
                $message .= "This is an automated message from WP Sentinal.\n";
            }
        }
        
        // Send email
        wp_mail($to, $subject, $message, $headers);
    }
}

// Initialize the plugin
function wp_sentinal() {
    return WP_Sentinal::instance();
}

// Start the plugin
wp_sentinal();
