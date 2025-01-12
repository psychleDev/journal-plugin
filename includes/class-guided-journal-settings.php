<?php
namespace GuidedJournal;

class GuidedJournalSettings
{
    private $options;
    private $default_colors = [
        'background' => '#333333',
        'card_background' => '#1b1b1b',
        'text' => '#ffffff',
        'accent' => '#991B1E',
        'container_background' => '#494949'
    ];

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_head', [$this, 'output_custom_colors']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_settings_page()
    {
        add_menu_page(
            'Journal Settings',          // Page title
            'Journal Settings',          // Menu title
            'manage_options',            // Capability
            'journal-settings',          // Menu slug
            [$this, 'render_settings_page'], // Callback function
            'dashicons-admin-appearance', // Icon
            21                          // Position after Journal Prompts
        );

        // Add Color Settings as the first submenu
        add_submenu_page(
            'journal-settings',         // Parent slug
            'Color Settings',           // Page title
            'Color Settings',           // Menu title
            'manage_options',           // Capability
            'journal-settings',         // Menu slug (same as parent to make it the default page)
            [$this, 'render_settings_page'] // Callback function
        );
    }

    // ... [rest of your existing methods remain the same]
}