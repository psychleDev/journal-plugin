<?php
/**
 * Plugin Name: 30-Day Guided Journal
 * Description: A guided journal system with Circle Community SSO integration
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

// Include the main class files directly
require_once GUIDED_JOURNAL_PLUGIN_DIR . 'includes/class-guided-journal.php';
require_once GUIDED_JOURNAL_PLUGIN_DIR . 'includes/class-circle-sso.php';

// Initialize plugin
function guided_journal_init() {
    $plugin = new GuidedJournal\GuidedJournal();
    $plugin->register_post_types();
    $plugin->init();
    return $plugin;
}

// Start the plugin on init to ensure post types are registered early
add_action('init', 'guided_journal_init', 0);

// Activation hook
register_activation_hook(__FILE__, 'guided_journal_activate');
function guided_journal_activate() {
    // Create an instance and register post types
    $plugin = new GuidedJournal\GuidedJournal();
    $plugin->register_post_types();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    global $wpdb;
    
    // Create journal entries table
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
    
    // Set default options
    add_option('guided_journal_test_mode', true);
    add_option('guided_journal_circle_sso_key', '');
    add_option('guided_journal_circle_domain', '');
    
    // Clean up rewrite rules
    flush_rewrite_rules();
}

// Add debug logging temporarily
add_action('template_include', function($template) {
    if (is_singular('journal_prompt')) {
        error_log('Template being loaded: ' . $template);
        error_log('Is singular journal_prompt: true');
        error_log('Current post type: ' . get_post_type());
    }
    return $template;
}, 1);