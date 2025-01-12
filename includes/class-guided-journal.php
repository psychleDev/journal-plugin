<?php
namespace GuidedJournal;

use \WP_Query;
use \WP_Error;

class GuidedJournal
{
    private $plugin_path;
    private $test_mode;

    public function __construct()
    {
        $this->plugin_path = GUIDED_JOURNAL_PLUGIN_DIR;
        $this->test_mode = get_option('guided_journal_test_mode', true);
    }

    public function init()
    {
        add_action('init', [$this, 'register_post_types']);
        add_filter('template_include', [$this, 'load_journal_templates'], 99);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_shortcode('journal_grid', [$this, 'render_grid']);
        add_shortcode('journal_entry', [$this, 'render_entry_page']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_save_journal_entry', [$this, 'save_entry']);
        add_action('wp_ajax_get_journal_entries', [$this, 'get_entries']);

        // Journal access control
        add_action('template_redirect', function () {
            global $post;

            if (
                strpos($_SERVER['REQUEST_URI'], '/grid') !== false ||
                strpos($_SERVER['REQUEST_URI'], '/entry') !== false ||
                (is_singular('journal_prompt') && $post)
            ) {
                if (!is_user_logged_in()) {
                    wp_redirect(home_url('/'));
                    exit;
                }

                $user = wp_get_current_user();
                $allowed_roles = ['administrator', 'menoffire', 'ignite30'];
                $has_access = array_intersect($allowed_roles, (array) $user->roles);

                if (empty($has_access)) {
                    wp_redirect(home_url('/'));
                    exit;
                }
            }
        });
    }

    // ... [rest of your existing methods remain the same, just remove the inner content for brevity]
    public function add_admin_menu()
    {
        // Add menu page for Journal Prompts
        add_menu_page(
            __('Journal Prompts', 'guided-journal'),
            __('Journal Prompts', 'guided-journal'),
            'manage_options',
            'guided-journal',
            [$this, 'render_admin_page'],
            'dashicons-book-alt',
            20
        );

        // Add submenu for creating prompts
        add_submenu_page(
            'guided-journal',
            __('Create Prompts', 'guided-journal'),
            __('Create Prompts', 'guided-journal'),
            'manage_options',
            'guided-journal-create',
            [$this, 'render_create_prompts_page']
        );
    }

    public function register_post_types()
    {
        $args = [
            'labels' => [
                'name' => __('Journal Prompts', 'guided-journal'),
                'singular_name' => __('Journal Prompt', 'guided-journal'),
                // ... rest of your labels
            ],
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-book-alt',
            'hierarchical' => false,
            'supports' => [
                'title',
                'editor',
                'author',
                'thumbnail',
                'excerpt',
                'revisions'
            ],
            'has_archive' => true,
            'rewrite' => [
                'slug' => 'journal-prompts',
                'with_front' => true
            ],
            'capability_type' => 'post',
            'query_var' => true,
        ];

        register_post_type('journal_prompt', $args);
    }

    // Keep all your other existing methods...
}