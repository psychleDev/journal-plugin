<?php
/**
 * Plugin Name: Universal Guided Journal
 * Description: A customizable guided journal system for WordPress with writing stats and progress tracking
 * Version: 2.0
 * Author: Steadfast Creative Solutions LLC.
 * Text Domain: universal-guided-journal
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Define plugin constants only if not already defined
if (!defined('GUIDED_JOURNAL_VERSION')) {
    define('GUIDED_JOURNAL_VERSION', '2.0.0');
}

if (!defined('GUIDED_JOURNAL_PLUGIN_DIR')) {
    define('GUIDED_JOURNAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('GUIDED_JOURNAL_PLUGIN_URL')) {
    define('GUIDED_JOURNAL_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('GUIDED_JOURNAL_DB_VERSION')) {
    define('GUIDED_JOURNAL_DB_VERSION', '2.0');
}

// Include required files
require_once GUIDED_JOURNAL_PLUGIN_DIR . 'includes/class-guided-journal.php';
require_once GUIDED_JOURNAL_PLUGIN_DIR . 'includes/class-journal-roles.php';
require_once GUIDED_JOURNAL_PLUGIN_DIR . 'includes/class-guided-journal-settings.php';
require_once GUIDED_JOURNAL_PLUGIN_DIR . 'includes/class-journal-stats.php';
require_once GUIDED_JOURNAL_PLUGIN_DIR . 'includes/class-guided-journal-sharing.php';

// Store plugin instance
global $guided_journal_plugin;

/**
 * Initialize plugin
 */
function guided_journal_init()
{
    global $guided_journal_plugin;

    // Only initialize once
    if (!isset($guided_journal_plugin)) {
        // Initialize main plugin class
        $guided_journal_plugin = new GuidedJournal\GuidedJournal();
        $guided_journal_plugin->register_post_types();
        $guided_journal_plugin->init();

        // Initialize roles
        $roles = new GuidedJournal\JournalRoles();
        $roles->initialize_roles();

        // Initialize settings
        $settings = new GuidedJournal\GuidedJournalSettings();

        // Initialize sharing
        $sharing = new GuidedJournal\GuidedJournalSharing();
    }

    return $guided_journal_plugin;
}

// Start the plugin on init with priority 5 to ensure it runs before other hooks
add_action('init', 'guided_journal_init', 5);

/**
 * Plugin activation
 */
register_activation_hook(__FILE__, 'guided_journal_activate');
function guided_journal_activate()
{
    // Create database tables
    guided_journal_create_tables();

    // Create an instance and register post types
    $plugin = new GuidedJournal\GuidedJournal();
    $plugin->register_post_types();

    // Initialize roles
    $roles = new GuidedJournal\JournalRoles();
    $roles->create_journal_roles();

    // Initialize sharing tables
    $sharing = new GuidedJournal\GuidedJournalSharing();
    $sharing->activate();

    // Set default options
    guided_journal_set_default_options();

    // Add plugin version to options
    add_option('guided_journal_version', GUIDED_JOURNAL_VERSION);
    add_option('guided_journal_db_version', GUIDED_JOURNAL_DB_VERSION);

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Create required database tables
 */
function guided_journal_create_tables()
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

    // Journal stats table
    $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}journal_stats (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        day_number int(11) NOT NULL,
        word_count int(11) NOT NULL DEFAULT 0,
        time_spent int(11) NOT NULL DEFAULT 0,
        last_modified datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_day (user_id, day_number)
    ) $charset_collate;";

    // Writing streaks table
    $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}journal_streaks (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        streak_start date NOT NULL,
        streak_end date NOT NULL,
        streak_days int(11) NOT NULL DEFAULT 1,
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) $charset_collate;";

    // Share tokens table
    $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}journal_share_tokens (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        entry_day int(11) NOT NULL,
        token varchar(64) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        expires_at datetime NOT NULL,
        views int(11) DEFAULT 0,
        max_views int(11) DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY token (token),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Set default plugin options
 */
function guided_journal_set_default_options()
{
    $default_colors = [
        'background' => '#333333',
        'card_background' => '#1b1b1b',
        'text' => '#ffffff',
        'accent' => '#991B1E',
        'container_background' => '#494949',
        'completed' => '#2E7D32'
    ];

    add_option('guided_journal_colors', $default_colors);
    add_option('guided_journal_autosave_interval', 120); // 2 minutes in seconds
    add_option('guided_journal_min_words', 0); // Minimum words per entry (0 = no minimum)
    add_option('guided_journal_show_stats', 1); // Show stats by default
    add_option('guided_journal_share_expiry_hours', 24); // Default share link expiry
    add_option('guided_journal_share_max_views', 3); // Default maximum views for shared entries
}

/**
 * Plugin deactivation
 */
register_deactivation_hook(__FILE__, 'guided_journal_deactivate');
function guided_journal_deactivate()
{
    // Clear any scheduled events
    wp_clear_scheduled_hook('guided_journal_daily_maintenance');
    wp_clear_scheduled_hook('guided_journal_cleanup_expired_shares');

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin uninstall
 */
register_uninstall_hook(__FILE__, 'guided_journal_uninstall');
function guided_journal_uninstall()
{
    // Only run if explicitly enabled in settings
    if (get_option('guided_journal_delete_data_on_uninstall', false)) {
        global $wpdb;

        // Remove database tables
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}journal_entries");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}journal_stats");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}journal_streaks");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}journal_share_tokens");

        // Remove all plugin options
        delete_option('guided_journal_version');
        delete_option('guided_journal_db_version');
        delete_option('guided_journal_colors');
        delete_option('guided_journal_autosave_interval');
        delete_option('guided_journal_min_words');
        delete_option('guided_journal_show_stats');
        delete_option('guided_journal_delete_data_on_uninstall');
        delete_option('guided_journal_share_expiry_hours');
        delete_option('guided_journal_share_max_views');

        // Remove all journal prompts
        $posts = get_posts([
            'post_type' => 'journal_prompt',
            'numberposts' => -1,
            'post_status' => 'any'
        ]);

        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }
    }
}

/**
 * Enqueue styles with custom colors
 */
function enqueue_journal_styles()
{
    $options = get_option('guided_journal_colors', []);

    if (!empty($options)) {
        echo "<style>
            :root {
                --gj-background: " . esc_html($options['background']) . ";
                --gj-card-background: " . esc_html($options['card_background']) . ";
                --gj-text: " . esc_html($options['text']) . ";
                --gj-accent: " . esc_html($options['accent']) . ";
                --gj-container-background: " . esc_html($options['container_background']) . ";
                --gj-completed: " . esc_html($options['completed']) . ";
            }
        </style>";
    }
}
add_action('wp_head', 'enqueue_journal_styles');

/**
 * Daily maintenance tasks
 */
function guided_journal_daily_maintenance()
{
    // Update streaks
    global $wpdb;

    // Get all users with journal entries
    $users = $wpdb->get_col("
        SELECT DISTINCT user_id 
        FROM {$wpdb->prefix}journal_entries
    ");

    foreach ($users as $user_id) {
        // Get the user's stats instance
        $stats = new GuidedJournal\JournalStats();

        // Recalculate and update streak
        $stats->update_user_streak($user_id);
    }

    // Clean up expired share tokens
    $wpdb->query("
        DELETE FROM {$wpdb->prefix}journal_share_tokens 
        WHERE expires_at < NOW() 
        OR (max_views IS NOT NULL AND views >= max_views)
    ");
}
add_action('guided_journal_daily_maintenance', 'guided_journal_daily_maintenance');

// Schedule daily maintenance if not already scheduled
if (!wp_next_scheduled('guided_journal_daily_maintenance')) {
    wp_schedule_event(time(), 'daily', 'guided_journal_daily_maintenance');
}

/**
 * Handle plugin updates
 */
function guided_journal_check_version()
{
    if (get_option('guided_journal_version') !== GUIDED_JOURNAL_VERSION) {
        guided_journal_update();
    }
}
add_action('plugins_loaded', 'guided_journal_check_version');

function guided_journal_update()
{
    $installed_version = get_option('guided_journal_version');

    // Perform any version-specific updates here
    if (version_compare($installed_version, '2.0.0', '<')) {
        // Update to version 2.0.0
        guided_journal_create_tables();
    }

    // Update version in database
    update_option('guided_journal_version', GUIDED_JOURNAL_VERSION);
}