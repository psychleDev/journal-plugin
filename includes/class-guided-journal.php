<?php
namespace GuidedJournal;

use \WP_Query;
use \WP_Error;

class GuidedJournal
{
    private $plugin_path;

    public function __construct()
    {
        $this->plugin_path = GUIDED_JOURNAL_PLUGIN_DIR;
    }

    public function init()
    {
        add_action('init', [$this, 'register_post_types']);
        add_filter('template_include', [$this, 'load_journal_templates'], 99);
        add_shortcode('journal_grid', [$this, 'render_grid']);
        add_shortcode('journal_entry', [$this, 'render_entry_page']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_export_script']);
        add_action('wp_ajax_save_journal_entry', [$this, 'save_entry']);
        add_action('wp_ajax_get_journal_entries', [$this, 'get_entries']);
        add_action('wp_ajax_export_journal_entries', [$this, 'export_entries']);
        add_action('wp_ajax_generate_share_token', [$this, 'generate_share_token']);

        // Basic access control - must be logged in
        add_action('template_redirect', function () {
            if (
                strpos($_SERVER['REQUEST_URI'], '/grid') !== false ||
                strpos($_SERVER['REQUEST_URI'], '/entry') !== false ||
                is_singular('journal_prompt')
            ) {
                if (!is_user_logged_in()) {
                    wp_redirect(wp_login_url(get_permalink()));
                    exit;
                }
            }
        });
    }

    public function register_post_types()
    {
        error_log('Registering post types');

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

        // Add debug logging
        error_log('Post types registered: ' . implode(', ', get_post_types(['public' => true])));
        error_log('Journal prompt post type exists: ' . (post_type_exists('journal_prompt') ? 'yes' : 'no'));

        // Force flush rewrite rules if needed
        if (get_option('guided_journal_flush_rewrite')) {
            error_log('Flushing rewrite rules');
            flush_rewrite_rules();
            delete_option('guided_journal_flush_rewrite');
        }
    }

    public function load_journal_templates($template)
    {
        if (is_singular('journal_prompt')) {
            $theme_template = locate_template('single-journal_prompt.php');

            if ($theme_template) {
                return $theme_template;
            }

            $plugin_template = $this->plugin_path . 'templates/single-journal_prompt.php';

            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    public function enqueue_assets()
    {
        // Debug path information
        $plugin_path = dirname(dirname(__FILE__));
        $plugin_url = plugins_url('', dirname(__FILE__));
        error_log('Plugin absolute path: ' . $plugin_path);
        error_log('Plugin URL: ' . $plugin_url);

        // Current post/page debug
        global $post;
        error_log('Current post ID: ' . ($post ? $post->ID : 'no post'));
        error_log('Current post type: ' . ($post ? $post->post_type : 'no post type'));

        // Enqueue main CSS - use direct path to css folder
        wp_enqueue_style(
            'guided-journal-style',
            $plugin_url . '/assets/css/style.css',
            [],
            GUIDED_JOURNAL_VERSION
        );

        // Enqueue main script
        wp_enqueue_script(
            'guided-journal-script',
            $plugin_url . '/assets/js/script.js',
            ['jquery'],
            GUIDED_JOURNAL_VERSION,
            true
        );

        // Get the total number of prompts
        $max_day = wp_count_posts('journal_prompt')->publish;

        wp_localize_script('guided-journal-script', 'journalAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('journal_nonce'),
            'maxDay' => $max_day
        ]);

        // Force check for journal prompt post type
        global $post;
        $is_journal_prompt = ($post && $post->post_type === 'journal_prompt');
        error_log('Is journal prompt (direct check): ' . ($is_journal_prompt ? 'yes' : 'no'));

        // Enqueue share functionality on journal prompt pages
        if ($is_journal_prompt) {
            error_log('Loading share functionality');

            // Enqueue Dashicons
            wp_enqueue_style('dashicons');

            // Enqueue sharing script with direct path
            wp_enqueue_script(
                'guided-journal-sharing',
                $plugin_url . '/assets/js/sharing.js',
                ['jquery'],
                GUIDED_JOURNAL_VERSION,
                true
            );

            wp_localize_script('guided-journal-sharing', 'journalShare', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('journal_share_nonce'),
                'i18n' => [
                    'copySuccess' => __('Copied!', 'guided-journal'),
                    'copyError' => __('Failed to copy', 'guided-journal'),
                    'generateError' => __('Failed to generate share link', 'guided-journal'),
                    'shareSubject' => __('Check out my journal entry', 'guided-journal'),
                    'shareText' => __('I wanted to share this journal entry with you:', 'guided-journal')
                ]
            ]);

            error_log('Share script enqueued');
        }

        // Only enqueue editor assets on journal entry pages
        if ($is_journal_prompt || strpos($_SERVER['REQUEST_URI'], '/entry') !== false) {
            wp_enqueue_editor();
            wp_enqueue_media();
        }
    }

    public function enqueue_export_script()
    {
        // Get the current page ID
        $page_id = get_queried_object_id();

        // Add debugging
        error_log('Checking export script enqueue. Page ID: ' . $page_id);
        error_log('REQUEST_URI: ' . $_SERVER['REQUEST_URI']);

        // Check if this is the grid page (you'll need to replace 7 with your actual page ID)
        $is_grid_page = ($page_id === 7);

        if (is_singular('journal_prompt') || $is_grid_page) {
            error_log('Enqueueing export script');

            wp_enqueue_script(
                'guided-journal-export',
                GUIDED_JOURNAL_PLUGIN_URL . '/assets/js/export.js',
                ['jquery'],
                GUIDED_JOURNAL_VERSION,
                true
            );

            wp_localize_script('guided-journal-export', 'journalAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('journal_nonce')
            ]);

            // Add debugging to verify script was enqueued
            add_action('wp_footer', function () {
                error_log('Export script enqueued. Verifying in footer.');
                ?>
                <script>
                    console.log('Export script loaded. journalAjax object:', journalAjax);
                </script>
                    <?php
            }, 999);
        }
    }

    public function generate_share_token()
    {
        check_ajax_referer('journal_share_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not authorized', 'guided-journal')]);
            return;
        }

        $entry_day = intval($_POST['entry_day']);
        $expiry_hours = isset($_POST['expiry_hours']) ? intval($_POST['expiry_hours']) : 24;
        $max_views = isset($_POST['max_views']) ? intval($_POST['max_views']) : 3;

        global $wpdb;
        $token = wp_generate_password(32, false);
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_hours} hours"));

        $result = $wpdb->insert(
            $wpdb->prefix . 'journal_share_tokens',
            [
                'user_id' => get_current_user_id(),
                'entry_day' => $entry_day,
                'token' => $token,
                'expires_at' => $expires_at,
                'max_views' => $max_views
            ],
            ['%d', '%d', '%s', '%s', '%d']
        );

        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to generate share link', 'guided-journal')]);
        }

        wp_send_json_success(['token' => $token]);
    }

    public function render_grid($atts)
    {
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s</p>',
                __('Please <a href="%s">log in</a> to view your journal.', 'guided-journal'),
                wp_login_url(get_permalink())
            );
        }

        // Get completed entries and stats for current user
        global $wpdb;
        $completed_entries = $wpdb->get_col($wpdb->prepare(
            "SELECT day_number FROM {$wpdb->prefix}journal_entries WHERE user_id = %d",
            get_current_user_id()
        ));

        // Get total words written
        $total_words = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(word_count) FROM {$wpdb->prefix}journal_stats WHERE user_id = %d",
            get_current_user_id()
        ));

        // Calculate streak
        $streak = $this->calculate_streak(get_current_user_id());

        // Calculate completion percentage
        $total_prompts = wp_count_posts('journal_prompt')->publish;
        $completion_percentage = $total_prompts > 0 ? round((count($completed_entries) / $total_prompts) * 100) : 0;

        ob_start();
        ?>
        <div class="container">
            <div class="grid-header">
                <h1><?php _e('Guided Journal', 'guided-journal'); ?></h1>
                <div class="grid-actions">
                    <button class="contents-toggle export-entries">
                        <?php _e('Export Entries', 'guided-journal'); ?>
                    </button>
                </div>
            </div>

            <!-- Stats Dashboard -->
            <div class="journal-dashboard">
                <div class="stats-overview">
                    <div class="stat-card">
                        <span class="stat-icon">📝</span>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo count($completed_entries); ?></span>
                            <span class="stat-label"><?php _e('Entries Written', 'guided-journal'); ?></span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">🔥</span>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo $streak; ?></span>
                            <span class="stat-label"><?php _e('Day Streak', 'guided-journal'); ?></span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">📊</span>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo number_format($total_words); ?></span>
                            <span class="stat-label"><?php _e('Total Words', 'guided-journal'); ?></span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">✅</span>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo $completion_percentage; ?>%</span>
                            <span class="stat-label"><?php _e('Completed', 'guided-journal'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="progress-section">
                    <div class="progress-label">
                        <span><?php _e('Journal Progress', 'guided-journal'); ?></span>
                        <span class="progress-percentage"><?php echo $completion_percentage; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $completion_percentage; ?>%"></div>
                    </div>
                </div>
            </div>

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
                        $formatted_number = sprintf('%02d', $number);
                        $completed_class = in_array($number, $completed_entries) ? 'completed' : '';
                        ?>
                        <a href="<?php the_permalink(); ?>" class="prompt-card <?php echo esc_attr($completed_class); ?>"
                            data-day="<?php echo esc_attr($number); ?>">
                            <span class="day-number"><?php echo esc_html($formatted_number); ?></span>
                        </a>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_entry_page($atts)
    {
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s</p>',
                __('Please <a href="%s">log in</a> to view your journal.', 'guided-journal'),
                wp_login_url(get_permalink())
            );
        }

        ob_start();
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $slug = basename($path);
        $day = str_replace('day-', '', $slug);

        $prompt = $this->get_prompt($day);
        $entry = $this->get_entry(get_current_user_id(), $day);
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

                <?php
                $editor_settings = array(
                    'textarea_name' => 'journal-entry',
                    'textarea_rows' => 20,
                    'editor_height' => 400,
                    'media_buttons' => true,
                    'tinymce' => array(
                        'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,undo,redo',
                        'toolbar2' => '',
                        'plugins' => 'link,lists,paste',
                    ),
                    'quicktags' => true
                );
                wp_editor($entry, 'journal-entry', $editor_settings);
                ?>

                <div class="navigation">
                    <button class="prev-day" <?php echo ($day <= 1) ? 'disabled' : ''; ?>>
                        <?php _e('Previous Day', 'guided-journal'); ?>
                    </button>

                    <button class="save-entry">
                        <?php _e('Save Entry', 'guided-journal'); ?>
                    </button>

                    <button class="next-day" <?php
                    $post_count = wp_count_posts('journal_prompt')->publish;
                    echo ($day >= $post_count) ? 'disabled' : '';
                    ?>>
                        <?php _e('Next Day', 'guided-journal'); ?>
                    </button>

                    <div class="share-button-container"></div>
                </div>

                <div class="save-status">
                    <span class="status-text"></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function calculate_streak($user_id)
    {
        global $wpdb;

        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as entry_date 
            FROM {$wpdb->prefix}journal_entries 
            WHERE user_id = %d 
            ORDER BY created_at DESC",
            $user_id
        ));

        if (empty($entries)) {
            return 0;
        }

        $streak = 1;
        $last_date = strtotime($entries[0]->entry_date);
        $today = strtotime('today');

        // Break streak if no entry today or yesterday
        if ($last_date < strtotime('yesterday')) {
            return 0;
        }

        // Calculate consecutive days
        for ($i = 1; $i < count($entries); $i++) {
            $current_date = strtotime($entries[$i]->entry_date);
            $date_diff = round(($last_date - $current_date) / (60 * 60 * 24));

            if ($date_diff == 1) {
                $streak++;
                $last_date = $current_date;
            } else {
                break;
            }
        }

        return $streak;
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

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to save entries', 'guided-journal'));
        }

        $user_id = get_current_user_id();
        $day = intval($_POST['day']);
        $text = wp_kses_post($_POST['text']);

        global $wpdb;
        $table = $wpdb->prefix . 'journal_entries';

        // Calculate word count for stats
        $word_count = str_word_count(strip_tags($text));

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

        // Update stats
        $stats_table = $wpdb->prefix . 'journal_stats';
        $wpdb->replace(
            $stats_table,
            [
                'user_id' => $user_id,
                'day_number' => $day,
                'word_count' => $word_count,
                'last_modified' => current_time('mysql')
            ],
            ['%d', '%d', '%d', '%s']
        );

        if ($result === false) {
            wp_send_json_error(__('Failed to save entry', 'guided-journal'));
        }

        wp_send_json_success([
            'message' => __('Entry saved successfully', 'guided-journal')
        ]);
    }

    public function get_entries()
    {
        check_ajax_referer('journal_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to view entries', 'guided-journal'));
        }

        global $wpdb;
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT day_number, created_at FROM {$wpdb->prefix}journal_entries 
             WHERE user_id = %d ORDER BY day_number ASC",
            get_current_user_id()
        ));

        wp_send_json_success(['entries' => $entries]);
    }

    public function export_entries()
    {
        // Prevent any output before headers
        ob_clean();

        try {
            // Basic security checks
            if (!check_ajax_referer('journal_nonce', 'nonce', false)) {
                throw new \Exception(__('Invalid security token', 'guided-journal'));
            }

            if (!is_user_logged_in()) {
                throw new \Exception(__('Please log in to export entries', 'guided-journal'));
            }

            $user_id = get_current_user_id();
            global $wpdb;

            // Get entries with error handling
            $entries = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    je.day_number,
                    je.entry_text,
                    je.created_at,
                    jp.post_content as prompt
                 FROM {$wpdb->prefix}journal_entries je 
                 LEFT JOIN {$wpdb->posts} jp 
                    ON jp.post_title = CAST(je.day_number AS CHAR) 
                    AND jp.post_type = 'journal_prompt'
                 WHERE je.user_id = %d 
                 ORDER BY je.day_number ASC",
                $user_id
            ));

            if ($wpdb->last_error) {
                throw new \Exception('Database error: ' . $wpdb->last_error);
            }

            if (empty($entries)) {
                throw new \Exception(__('No entries found to export', 'guided-journal'));
            }

            // Prepare CSV data
            $csv_data = [];

            // Add headers
            $csv_data[] = array(
                __('Day', 'guided-journal'),
                __('Prompt', 'guided-journal'),
                __('Entry', 'guided-journal'),
                __('Date Written', 'guided-journal')
            );

            // Add entries
            foreach ($entries as $entry) {
                $csv_data[] = array(
                    $entry->day_number,
                    wp_strip_all_tags($entry->prompt),
                    wp_strip_all_tags($entry->entry_text),
                    get_date_from_gmt($entry->created_at, get_option('date_format') . ' ' . get_option('time_format'))
                );
            }

            // Get user info and generate filename
            $user = wp_get_current_user();
            $username = sanitize_file_name($user->display_name);
            if (empty(trim($username))) {
                $username = sanitize_file_name($user->user_login);
            }

            // Create filename with date
            $date = current_time('Y-m-d');
            $filename = sprintf(
                'My-Journal-Entries_%s_%s.csv',
                $username,
                $date
            );
            $filename = sanitize_file_name($filename);

            // Clear any previous output and check headers
            if (headers_sent($file, $line)) {
                throw new \Exception("Headers already sent in $file on line $line");
            }

            // Send headers
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: public');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');

            // Create output handle
            $output = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fputs($output, "\xEF\xBB\xBF");

            // Write data
            foreach ($csv_data as $row) {
                fputcsv($output, $row);
            }

            // Close the output stream
            fclose($output);
            exit;

        } catch (\Exception $e) {
            status_header(500);
            wp_send_json_error(array(
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ));
        }
    }

}