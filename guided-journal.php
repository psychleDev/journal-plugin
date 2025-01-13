<?php
/**
 * Plugin Name: Guided Journal
 * Description: A customizable guided journal system for WordPress
 * Version: 1.0
 * Author: Your Name
 * Text Domain: guided-journal
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Define plugin constants
define('GUIDED_JOURNAL_VERSION', '1.0.0');
define('GUIDED_JOURNAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GUIDED_JOURNAL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once GUIDED_JOURNAL_PLUGIN_DIR . 'includes/class-guided-journal.php';
require_once GUIDED_JOURNAL_PLUGIN_DIR . 'includes/class-journal-roles.php';
require_once GUIDED_JOURNAL_PLUGIN_DIR . 'includes/class-guided-journal-settings.php';

// Initialize plugin
function guided_journal_init()
{
    $plugin = new GuidedJournal\GuidedJournal();
    $plugin->register_post_types();
    $plugin->init();

    // Initialize roles
    $roles = new GuidedJournal\JournalRoles();
    $roles->initialize_roles();

    // Initialize settings
    $settings = new GuidedJournal\GuidedJournalSettings();

    return $plugin;
}

// Start the plugin on init
add_action('init', 'guided_journal_init', 0);

// Activation hook
register_activation_hook(__FILE__, 'guided_journal_activate');
function guided_journal_activate()
{
    // Create an instance and register post types
    $plugin = new GuidedJournal\GuidedJournal();
    $plugin->register_post_types();

    // Initialize roles
    $roles = new GuidedJournal\JournalRoles();
    $roles->create_journal_roles();

    // Flush rewrite rules
    flush_rewrite_rules();

    // Create journal entries table
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
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

// Deactivation hook
register_deactivation_hook(__FILE__, 'guided_journal_deactivate');
function guided_journal_deactivate()
{
    flush_rewrite_rules();
}