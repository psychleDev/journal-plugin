<?php
namespace GuidedJournal;

use \WP_Query;
use \WP_Error;

class GuidedJournal
{
    private $plugin_path;
    private $stats;

    public function __construct()
    {
        $this->plugin_path = GUIDED_JOURNAL_PLUGIN_DIR;
        $this->stats = new JournalStats();
    }

    public function init()
    {
        add_action('init', [$this, 'register_post_types']);
        add_filter('template_include', [$this, 'load_journal_templates'], 99);
        add_shortcode('journal_grid', [$this, 'render_grid']);
        add_shortcode('journal_entry', [$this, 'render_entry_page']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_save_journal_entry', [$this, 'save_entry']);
        add_action('wp_ajax_get_journal_entries', [$this, 'get_entries']);

        // Add new actions for stats
        add_action('wp_footer', [$this, 'render_notification_container']);
        add_filter('the_content', [$this, 'add_stats_to_entry_page']);

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
        // Only enqueue editor assets on journal entry pages
        if (is_singular('journal_prompt') || strpos($_SERVER['REQUEST_URI'], '/entry') !== false) {
            wp_enqueue_editor();
            wp_enqueue_media();
        }

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

        // Get the total number of prompts
        $max_day = wp_count_posts('journal_prompt')->publish;

        wp_localize_script('guided-journal-script', 'journalAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('journal_nonce'),
            'maxDay' => $max_day
        ]);
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

        // Get stats for current user
        $user_stats = $this->stats->get_user_stats(get_current_user_id());

        // Get completed entries for current user
        global $wpdb;
        $completed_entries = $wpdb->get_col($wpdb->prepare(
            "SELECT day_number FROM {$wpdb->prefix}journal_entries WHERE user_id = %d",
            get_current_user_id()
        ));

        ob_start();
        ?>
        <div class="container">
            <h1><?php _e('Guided Journal', 'guided-journal'); ?></h1>

            <!-- Stats Dashboard -->
            <div class="journal-dashboard">
                <div class="stats-overview">
                    <div class="stat-card">
                        <span class="stat-icon">📝</span>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo count($completed_entries); ?></span>
                                    <span class=" stat-label"><?php _e('Entries Written', 'guided-journal'); ?></span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">🔥</span>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo $user_stats['streak']; ?></span>
                                    <span class=" stat-label"><?php _e('Day Streak', 'guided-journal'); ?></span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">📊</span>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo number_format($user_stats['total_words']); ?></span>
                                    <span class=" stat-label"><?php _e('Total Words', 'guided-journal'); ?></span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">⏱️</span>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo $this->format_time_spent($user_stats['total_time']); ?></span>
                                    <span class=" stat-label"><?php _e('Time Writing', 'guided-journal'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="progress-section">
                    <div class="progress-label">
                        <span><?php _e('Journal Progress', 'guided-journal'); ?></span>
                            <span class="progress-percentage"><?php echo round((count($completed_entries) / wp_count_posts('journal_prompt')->publish) * 100); ?>%</span>
                            </div>
                            <div class=" progress-bar">
                                <div class="progress-fill"
                                    style="width: <?php echo (count($completed_entries) / wp_count_posts('journal_prompt')->publish) * 100; ?>%">
                                </div>
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
                        <a href="<?php the_permalink(); ?>" class="prompt-card <?php echo esc_attr($completed_class); ?>">
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
                <div class=" container">
                        <div class="navigation-top">
                            <a href="/grid" class="contents-toggle">
                                <?php _e('Back to Grid', 'guided-journal'); ?>
                            </a>
                        </div>

                        <div class="journal-container">
                            <h2><?php printf(__('Day %d', 'guided-journal'), $day); ?></h2>

                            <div class="prompt"><?php echo wp_kses_post($prompt); ?></div>

                            <div class="journal-stats"></div>

                            <?php
                            // Initialize WordPress editor
                            $editor_settings = array(
                                'textarea_name' => 'journal-entry',
                                'textarea_rows' => 10,
                                'media_buttons' => true,
                                'tinymce' => array(
                                    'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,undo,redo',
                                    'toolbar2' => '',
                                    'plugins' => 'link,lists,paste',
                                ),
                                'quicktags' => true,
                            );
                            wp_editor($entry, 'journal-entry', $editor_settings);
                            ?>

                            <div class="save-status">All changes saved</div>

                            <div class="navigation">
                                <button class="prev-day" <?php echo ($day <= 1) ? 'disabled' : ''; ?>>
                                    <?php _e('Previous Day', 'guided-journal'); ?>
                                </button>
                                <button class="save-entry">
                                    <?php _e('Save Entry', 'guided-journal'); ?>
                                </button>
                                <button class="next-day" <?php echo ($day >= wp_count_posts('journal_prompt')->publish) ? 'disabled' : ''; ?>>
                                    <?php _e('Next Day', 'guided-journal'); ?>
                                </button>
                            </div>
                        </div>
            </div>
            <?php
            return ob_get_clean();
    }

    public function render_notification_container()
    {
        if (is_singular('journal_prompt') || strpos($_SERVER['REQUEST_URI'], '/entry') !== false) {
            echo '<div id="journal-notification" class="journal-notification"></div>';
        }
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

        $entry_data = [
            'user_id' => $user_id,
            'day_number' => $day,
            'entry_text' => $text,
            'word_count' => intval($_POST['word_count']),
            'time_spent' => intval($_POST['time_spent'])
        ];

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

        // Save stats
        $this->stats->save_entry($entry_data);

        wp_send_json_success([
            'message' => __('Entry saved successfully', 'guided-journal'),
            'stats' => $this->stats->get_user_stats($user_id)
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

    public function add_stats_to_entry_page($content)
    {
        if (!is_singular('journal_prompt') || !is_user_logged_in()) {
            return $content;
        }

        $stats_html = $this->get_stats_html();
        return $content . $stats_html;
    }

    private function get_stats_html()
    {
        $user_stats = $this->stats->get_user_stats(get_current_user_id());

        ob_start();
        ?>
            <div class="journal-stats-summary">
                <h3><?php _e('Your Writing Stats', 'guided-journal'); ?></h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Total Words Written', 'guided-journal'); ?></span>
                        <span class="stat-value"><?php echo number_format($user_stats['total_words']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Writing Streak', 'guided-journal'); ?></span>
                        <span class="stat-value"><?php echo $user_stats['streak']; ?> days</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Average Words per Entry', 'guided-journal'); ?></span>
                        <span class="stat-value"><?php echo $user_stats['avg_words']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php _e('Total Time Writing', 'guided-journal'); ?></span>
                        <span class="stat-value"><?php echo $this->format_time_spent($user_stats['total_time']); ?></span>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
    }

    private function format_time_spent($seconds)
    {
        if ($seconds < 60) {
            return __('Less than a minute', 'guided-journal');
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf(
                _n('%d hour', '%d hours', $hours, 'guided-journal'),
                $hours
            ) . ' ' . sprintf(
                _n('%d minute', '%d minutes', $minutes, 'guided-journal'),
                $minutes
            );
        }

        return sprintf(_n('%d minute', '%d minutes', $minutes, 'guided-journal'), $minutes);
    }

    private function get_completion_percentage()
    {
        $total_prompts = wp_count_posts('journal_prompt')->publish;
        if ($total_prompts === 0) {
            return 0;
        }

        global $wpdb;
        $completed_entries = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT day_number) 
            FROM {$wpdb->prefix}journal_entries 
            WHERE user_id = %d",
            get_current_user_id()
        ));

        return round(($completed_entries / $total_prompts) * 100);
    }

    public function get_journal_stats()
    {
        check_ajax_referer('journal_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to view stats', 'guided-journal'));
        }

        $user_id = get_current_user_id();
        $stats = $this->stats->get_user_stats($user_id);
        $stats['completion_percentage'] = $this->get_completion_percentage();

        wp_send_json_success($stats);
    }

    private function validate_entry_data($data)
    {
        $errors = [];

        if (empty($data['day']) || !is_numeric($data['day'])) {
            $errors[] = __('Invalid day number', 'guided-journal');
        }

        if (empty($data['text'])) {
            $errors[] = __('Entry text cannot be empty', 'guided-journal');
        }

        if (!isset($data['word_count']) || !is_numeric($data['word_count'])) {
            $errors[] = __('Invalid word count', 'guided-journal');
        }

        if (!isset($data['time_spent']) || !is_numeric($data['time_spent'])) {
            $errors[] = __('Invalid time spent', 'guided-journal');
        }

        return empty($errors) ? true : $errors;
    }

    private function sanitize_entry_data($data)
    {
        return [
            'day' => intval($data['day']),
            'text' => wp_kses_post($data['text']),
            'word_count' => intval($data['word_count']),
            'time_spent' => intval($data['time_spent'])
        ];
    }

    public function get_user_journal_data()
    {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $user_id = get_current_user_id();

        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, s.word_count, s.time_spent 
            FROM {$wpdb->prefix}journal_entries e 
            LEFT JOIN {$wpdb->prefix}journal_stats s 
            ON e.user_id = s.user_id AND e.day_number = s.day_number 
            WHERE e.user_id = %d 
            ORDER BY e.day_number ASC",
            $user_id
        ));

        if (!$entries) {
            return false;
        }

        $stats = $this->stats->get_user_stats($user_id);

        return [
            'entries' => $entries,
            'stats' => $stats,
            'completion_percentage' => $this->get_completion_percentage()
        ];
    }
}