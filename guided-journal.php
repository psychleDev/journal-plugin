<?php
/**
 * Plugin Name: Guided Journal
 * Description: A guided journal system with Circle Community SSO integration
 * Version: 1.0
 * Author: Your Name
 * Text Domain: guided-journal
 */

namespace GuidedJournal;

// Prevent direct access
defined('ABSPATH') || exit;

// Define plugin constants
define('GUIDED_JOURNAL_VERSION', '1.0.0');
define('GUIDED_JOURNAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GUIDED_JOURNAL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes
spl_autoload_register(function ($class) {
    // Check if the class is in our namespace
    if (strpos($class, 'GuidedJournal\\') !== 0) {
        return;
    }

    // Remove namespace from class name
    $class_file = str_replace('GuidedJournal\\', '', $class);
    // Convert class name format to file name format
    $class_file = 'class-' . strtolower(str_replace('_', '-', $class_file)) . '.php';
    // Build the file path
    $file = GUIDED_JOURNAL_PLUGIN_DIR . 'includes/' . $class_file;

    // Include the file if it exists
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Main plugin class
 */
class GuidedJournalPlugin
{
    /**
     * @var GuidedJournalPlugin|null Instance of this class.
     */
    private static $instance = null;

    /**
     * @var GuidedJournal Main journal functionality class
     */
    private $journal;

    /**
     * @var JournalRoles Roles management class
     */
    private $roles;

    /**
     * @var GuidedJournalSettings Settings management class
     */
    private $settings;

    /**
     * Get the singleton instance of this class
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct creation
     */
    private function __construct()
    {
        $this->init();
    }

    /**
     * Initialize the plugin
     */
    private function init()
    {
        // Initialize main functionality
        $this->journal = new GuidedJournal();
        $this->journal->init();

        // Initialize roles management
        $this->roles = new JournalRoles();

        // Initialize settings
        $this->settings = new GuidedJournalSettings();

        // Register activation hook
        register_activation_hook(__FILE__, [$this, 'activate']);

        // Register deactivation hook
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Auth0 user handling
        add_action('auth0_user_login', [$this, 'handle_auth0_login'], 10, 2);
    }

    /**
     * Plugin activation hook
     */
    public function activate()
    {
        // Create database tables
        $this->create_tables();

        // Register post types
        $this->journal->register_post_types();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook
     */
    public function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Create necessary database tables
     */
    private function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Journal entries table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}journal_entries (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            day_number int(11) NOT NULL,
            entry_text longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_day (user_id, day_number)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Handle Auth0 login
     */
    public function handle_auth0_login($user_id, $user_data)
    {
        // Check if Auth0 user is email verified
        if (!isset($user_data->email_verified) || !$user_data->email_verified) {
            wp_redirect(home_url('/verify-email/'));
            exit;
        }
    }

    /**
     * Get the main journal class instance
     */
    public function get_journal()
    {
        return $this->journal;
    }

    /**
     * Get the roles class instance
     */
    public function get_roles()
    {
        return $this->roles;
    }

    /**
     * Get the settings class instance
     */
    public function get_settings()
    {
        return $this->settings;
    }
}

// Initialize the plugin
function guided_journal()
{
    return GuidedJournalPlugin::get_instance();
}

// Start the plugin
guided_journal();