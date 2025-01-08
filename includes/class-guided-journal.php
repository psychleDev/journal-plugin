<?php
namespace GuidedJournal;

use \WP_Query;

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

        //-----------------------LANDING PAGE REDIRECT------------------------//
//ADD THIS URL TO Menoffire SUCCESS PAGE https://members.risingfather.com/update-role/?role=menoffire&email=test@test.com

        add_action('template_redirect', function () {
            global $post;

            if (strpos($_SERVER['REQUEST_URI'], '/update-role') !== false) {
                error_log('=== ROLE UPDATE START ===');

                // Get and sanitize query parameters - preserve the + in email
                $role = sanitize_text_field($_GET['role'] ?? '');
                $email = str_replace(' ', '+', $_GET['email'] ?? ''); // Preserve + in email
                $email = sanitize_email($email);

                error_log("Attempting role update - Email: $email, Role: $role");

                // Validate inputs
                if (empty($role) || empty($email)) {
                    error_log('Missing required parameters');
                    wp_die('Missing required parameters');
                }

                // Verify role is valid
                if (!in_array($role, ['menoffire', 'ignite30'])) {
                    error_log("Invalid role specified: $role");
                    wp_die('Invalid role specified');
                }

                // Check if user exists - try both email formats
                $user = get_user_by('email', $email);
                if (!$user) {
                    // Try alternative email format
                    $alt_email = str_replace('+', '', $email);
                    $user = get_user_by('email', $alt_email);
                }

                if ($user) {
                    error_log("Updating existing user role to: $role");
                    // Update existing user's role
                    $user->set_role($role);

                    // Log success
                    error_log("Successfully updated role for user: $email to $role");

                    // Redirect to success page based on role
                    if ($role === 'menoffire') {
                        wp_redirect(home_url('/grid'));
                    } else {
                        wp_redirect(home_url('/'));
                    }
                    exit;
                } else {
                    error_log("User not found with either email format");
                    wp_die('User not found');
                }
                error_log('=== ROLE UPDATE COMPLETE ===');
            }
        });
        //-----------------------LANDING PAGE REDIRECT------------------------//

        // Subscriber redirect to journal
        add_action('template_redirect', function () {
            if (strpos($_SERVER['REQUEST_URI'], '/home/') !== false || is_front_page() || is_home()) {
                $user = wp_get_current_user();
                if (in_array('ignite30', (array) $user->roles)) {
                    wp_redirect(home_url('/grid'));
                    exit;
                }
            }
        });
    }


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
                error_log('Loading plugin template: ' . $plugin_template);
                return $plugin_template;
            } else {
                error_log('Plugin template not found at: ' . $plugin_template);
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
            'public' => true,  // Make the post type publicly accessible
            'publicly_queryable' => true,  // Allow queries on the frontend
            'show_ui' => true,   // Show admin UI
            'show_in_menu' => true,   // Show in admin menu
            'show_in_rest' => true,   // Enable Gutenberg editor
            'menu_position' => 20,     // Position in admin menu
            'menu_icon' => 'dashicons-book-alt',
            'hierarchical' => false,  // Posts, not pages
            'supports' => [
                'title',
                'editor',
                'author',
                'thumbnail',
                'excerpt',
                'revisions'
            ],
            'has_archive' => true,   // Enable archive page
            'rewrite' => [
                'slug' => 'journal-prompts', // URL slug
                'with_front' => true
            ],
            'capability_type' => 'post',
            'query_var' => true,
        ];

        register_post_type('journal_prompt', $args);

        // Flush rewrite rules only on plugin activation
        flush_rewrite_rules();
    }

    public function add_admin_menu()
    {
        // add_options_page(
        //     __('Journal Settings', 'guided-journal'),
        //     __('Journal Settings', 'guided-journal'),
        //     'manage_options',
        //     'guided-journal-settings',
        //     [$this, 'render_settings_page']
        // );
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



    public function render_grid($atts)
    {


        ob_start();
        ?>
        <div class="container">
            <h1><?php _e('30-Day Guided Journal', 'guided-journal'); ?></h1>
            <div class="prompt-grid">

                <?php
                // Create 30 "Day" posts, then loop over each post.
                // The links are to posts, then each post has the entry shortcode on it(possibly?)
                $the_query = new WP_Query([
                    'post_type' => 'journal_prompt',
                    'nopaging' => true,
                    'posts_per_page' => '40',
                    'order' => 'ASC',
                ]); ?>

                <?php if ($the_query->have_posts()): ?>

                    <?php while ($the_query->have_posts()):
                        $the_query->the_post();
                        // $completed = $this->is_entry_completed(get_current_user_id(), $i);
                        ?>
                        <a href="<?php the_permalink(); ?>" class="prompt-card">
                            <span class="day-number"><?php the_title(); ?></span>
                        </a>
                    <?php endwhile; ?>

                    <?php wp_reset_postdata(); ?>

                <?php endif; ?>

            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // Joe: HTML for the Entry FORM
    public function render_entry_page($atts)
    {

        // Method 3: Parse from URL path
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $slug = basename($path); // Gets the last part of the path

        // If you need just the number from "day-1":
        $day = str_replace('day-', '', $slug); // Gets '1'

        // $day = get_query_var('journal_prompt') ? get_query_var('journal_prompt') : 1;
        $day = max(1, min(30, $day)); // Ensure day is between 1 and 30

        $prompt = $this->get_prompt($day);
        $entry = $this->get_entry(get_current_user_id(), $day);

        ob_start();
        ?>
        <div class="container">
            <div class="navigation-top">
                <a href="/grid" class="contents-toggle">
                    <?php _e('Back to Grid', 'guided-journal'); ?>
                </a>
            </div>

            <div class="journal-container">
                <h2><?php printf(__('Day %d', 'guided-journal'), $day); ?></h2>

                <div class="prompt"><?php echo wp_kses_post($prompt); ?></div>

                <textarea id="journal-entry" class="entry-text"><?php echo esc_textarea($entry); ?></textarea>

                <div class="navigation">
                    <button class="prev-day" <?php echo ($day <= 1) ? 'disabled' : ''; ?>>
                        <?php _e('Previous Day', 'guided-journal'); ?>
                    </button>
                    <button class="save-entry">
                        <?php _e('Save Entry', 'guided-journal'); ?>
                    </button>
                    <button class="next-day" <?php echo ($day >= 30) ? 'disabled' : ''; ?>>
                        <?php _e('Next Day', 'guided-journal'); ?>
                    </button>
                </div>

                <!-- <div class="view-entries">
                    <button class="list-toggle">
                        <?php // _e('View All Entries', 'guided-journal'); ?>
                    </button>
                </div>
                
                <div class="entries-list"></div> -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function check_auth()
    {
        return is_user_logged_in();
    }

    private function get_prompt($day)
    {

        // Joe

        $args = [
            'post_type' => 'journal_prompt',
            // 'meta_key' => 'day_number',
            'slug' => $day,
            'posts_per_page' => 1
        ];

        // $prompt = get_posts($args);
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

    private function is_entry_completed($user_id, $day)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}journal_entries 
             WHERE user_id = %d AND day_number = %d",
            $user_id,
            $day
        )) > 0;
    }

    public function save_entry()
    {
        // Verify AJAX nonce
        check_ajax_referer('journal_nonce', 'nonce');

        // Get current user
        $user = wp_get_current_user();

        // Check if user has journal or admin role
        // if (!in_array('menoffire', $user->roles) && !in_array('administrator', $user->roles)) {
        //     wp_send_json_error(__('Unauthorized access', 'guided-journal'));
        // }

        $user_id = get_current_user_id();
        $day = intval($_POST['day']);
        $text = sanitize_textarea_field($_POST['text']);

        global $wpdb;
        $table = $wpdb->prefix . 'journal_entries';

        // Check if entry exists
        $existing_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND day_number = %d",
            $user_id,
            $day
        ));

        if ($existing_entry) {
            // Update existing entry
            $result = $wpdb->update(
                $table,
                ['entry_text' => $text],
                [
                    'user_id' => $user_id,
                    'day_number' => $day
                ],
                ['%s'],
                ['%d', '%d']
            );
        } else {
            // Insert new entry
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

        if ($day === 30) {
            //redirect user to success page.
            wp_redirect(home_url('/journal-challenge-success'));
            exit;
        }
        wp_send_json_success(['message' => __('Entry saved successfully', 'guided-journal')]);
    }

    public function get_entries()
    {
        check_ajax_referer('journal_nonce', 'nonce');

        if (!$this->check_auth()) {
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
