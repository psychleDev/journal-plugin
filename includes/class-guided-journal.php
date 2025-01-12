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

    /**
     * Load journal templates
     */
    public function load_journal_templates($template)
    {
        if (is_singular('journal_prompt')) {
            // First try to find the template in the theme
            $theme_template = locate_template('single-journal_prompt.php');

            if ($theme_template) {
                return $theme_template;
            }

            // If not found in theme, use plugin template
            $plugin_template = $this->plugin_path . 'templates/single-journal_prompt.php';

            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    public function register_post_types()
    {
        $args = [
            'labels' => [
                'name' => __('Journal Prompts', 'guided-journal'),
                'singular_name' => __('Journal Prompt', 'guided-journal'),
                'add_new' => __('Add New', 'guided-journal'),
                'add_new_item' => __('Add New Journal Prompt', 'guided-journal'),
                'edit_item' => __('Edit Journal Prompt', 'guided-journal'),
                'new_item' => __('New Journal Prompt', 'guided-journal'),
                'view_item' => __('View Journal Prompt', 'guided-journal'),
                'search_items' => __('Search Journal Prompts', 'guided-journal'),
                'not_found' => __('No journal prompts found', 'guided-journal'),
                'not_found_in_trash' => __('No journal prompts found in trash', 'guided-journal'),
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

    public function enqueue_assets()
    {
        wp_enqueue_style(
            'guided-journal-style',
            GUIDED_JOURNAL_PLUGIN_URL . 'assets/css/style.css',
            [],
            GUIDED_JOURNAL_VERSION
        );

        wp_enqueue_script(
            'guided-journal-script',
            GUIDED_JOURNAL_PLUGIN_URL . 'assets/js/script.js',
            ['jquery'],
            GUIDED_JOURNAL_VERSION,
            true
        );

        wp_localize_script('guided-journal-script', 'journalAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('journal_nonce'),
        ]);
    }

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

    public function render_admin_page()
    {
        include($this->plugin_path . 'templates/admin-page.php');
    }

    public function render_create_prompts_page()
    {
        include($this->plugin_path . 'templates/create-prompts-page.php');
    }

    public function render_grid($atts)
    {
        ob_start();
        ?>
        <div class="container">
            <h1><?php _e('Guided Journal', 'guided-journal'); ?></h1>
            <div class="prompt-grid">
                <?php
                $the_query = new WP_Query([
                    'post_type' => 'journal_prompt',
                    'nopaging' => true,
                    'orderby' => 'title_num',
                    'meta_key' => 'title_num',
                    'orderby' => 'meta_value_num',
                    'order' => 'ASC',
                    'posts_per_page' => -1
                ]);

                if ($the_query->have_posts()):
                    while ($the_query->have_posts()):
                        $the_query->the_post();
                        $number = intval(get_the_title());
                        $formatted_number = sprintf('%02d', $number); // Pad with zeros
                        ?>
                        <a href="<?php the_permalink(); ?>" class="prompt-card">
                            <span class="day-number"><?php echo esc_html($formatted_number); ?></span>
                        </a>
                    <?php endwhile;
                    wp_reset_postdata();
                endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_entry_page($atts)
    {
        ob_start();
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $slug = basename($path);
        $day = str_replace('day-', '', $slug);

        $prompt = $this->get_prompt($day);
        $entry = $this->get_entry(get_current_user_id(), $day);

        include($this->plugin_path . 'templates/entry-page.php');
        return ob_get_clean();
    }

    private function get_prompt($day)
    {
        $prompt = get_page_by_path($day, OBJECT, 'journal_prompt');
        return $prompt ? apply_filters('the_content', $prompt->post_content) : sprintf(__('Prompt for day %d', 'guided-journal'), $day);
    }

    private function get_entry($user_id, $day)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT entry_text FROM {$wpdb->prefix}journal_entries 
             WHERE user_id = %d AND day_number = %d",
            $user_id,
            $day
        ));
    }

    public function save_entry()
    {
        check_ajax_referer('journal_nonce', 'nonce');

        $user = wp_get_current_user();
        if (!in_array('menoffire', $user->roles) && !in_array('administrator', $user->roles) && !in_array('ignite30', $user->roles)) {
            wp_send_json_error(__('Unauthorized access', 'guided-journal'));
        }

        $user_id = get_current_user_id();
        $day = intval($_POST['day']);
        $text = sanitize_textarea_field($_POST['text']);

        global $wpdb;
        $table = $wpdb->prefix . 'journal_entries';

        $existing_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND day_number = %d",
            $user_id,
            $day
        ));

        if ($existing_entry) {
            $result = $wpdb->update(
                $table,
                ['entry_text' => $text],
                ['user_id' => $user_id, 'day_number' => $day],
                ['%s'],
                ['%d', '%d']
            );
        } else {
            $result = $wpdb->insert(
                $table,
                [
                    'user_id' => $user_id,
                    'day_number' => $day,
                    'entry_text' => $text
                ],
                ['%d', '%d', '%s']
            );
        }

        if ($result === false) {
            wp_send_json_error(__('Failed to save entry', 'guided-journal'));
        }

        wp_send_json_success(['message' => __('Entry saved successfully', 'guided-journal')]);
    }

    public function get_entries()
    {
        check_ajax_referer('journal_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Unauthorized access', 'guided-journal'));
        }

        global $wpdb;
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT day_number, created_at FROM {$wpdb->prefix}journal_entries 
             WHERE user_id = %d ORDER BY day_number ASC",
            get_current_user_id()
        ));

        wp_send_json_success(['entries' => $entries]);
    }
}
