<?php
/**
 * Plugin Name: WP Sentinal
 * Plugin URI: https://vr51.com/wp-sentinal
 * Description: Monitors plugin activations/deactivations during WordPress updates and sends email alerts.
 * Version: 1.0.0
 * Author: Lee Hodson
 * Author URI: https://vr51.com
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
        $this->options = get_site_option('wp_sentinal_options', array(
            'email' => get_option('admin_email'),
            'enable_notifications' => 'yes'
        ));
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // Set default options if they don't exist
        if (!get_site_option('wp_sentinal_options')) {
            update_site_option('wp_sentinal_options', array(
                'email' => get_option('admin_email'),
                'enable_notifications' => 'yes'
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
     * Send notification email.
     * 
     * @param array $deactivated_plugins
     */
    private function send_notification_email($deactivated_plugins) {
        $site_name = get_bloginfo('name');
        $domain = parse_url(get_site_url(), PHP_URL_HOST);
        $to = $this->options['email'];
        
        if (empty($deactivated_plugins)) {
            $subject = "WP Sentinal Report | {$domain} | All OK";
            $message = "Hello,\n\n";
            $message .= "WP Sentinal has detected that all plugins remain active after the recent WordPress plugin update.\n\n";
            $message .= "Site: {$site_name} ({$domain})\n";
            $message .= "Status: All plugins remain active\n\n";
            $message .= "This is an automated message from WP Sentinal.\n";
        } else {
            $subject = "WP Sentinal Report | {$domain} | FAIL";
            $message = "Hello,\n\n";
            $message .= "WP Sentinal has detected that one or more plugins have been deactivated during the recent WordPress plugin update.\n\n";
            $message .= "Site: {$site_name} ({$domain})\n";
            $message .= "Status: Some plugins were deactivated\n\n";
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
            
            $message .= "\nThis is an automated message from WP Sentinal.\n";
        }
        
        // Send email
        wp_mail($to, $subject, $message);
    }
}

// Initialize the plugin
function wp_sentinal() {
    return WP_Sentinal::instance();
}

// Start the plugin
wp_sentinal();
