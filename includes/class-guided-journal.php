<?php
namespace GuidedJournal;

use \WP_Query;
use \WP_Error;

class GuidedJournal {
    private $plugin_path;

    public function __construct() {
        $this->plugin_path = GUIDED_JOURNAL_PLUGIN_DIR;
    }

    public function init() {
        add_action('init', [$this, 'register_post_types']);
        add_filter('template_include', [$this, 'load_journal_templates'], 99);
        add_shortcode('journal_grid', [$this, 'render_grid']);
        add_shortcode('journal_entry', [$this, 'render_entry_page']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_export_script']);
        add_action('wp_ajax_save_journal_entry', [$this, 'save_entry']);
        add_action('wp_ajax_get_journal_entries', [$this, 'get_entries']);
        add_action('wp_ajax_export_journal_entries', [$this, 'export_entries']);
    
        // Basic access control - must be logged in
        add_action('template_redirect', function() {
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

    public function register_post_types() {
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

    public function load_journal_templates($template) {
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

    public function enqueue_assets() {
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

    public function render_grid($atts) {
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
            <h1><?php _e('Guided Journal', 'guided-journal'); ?></h1>
    
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
                        <a href="<?php the_permalink(); ?>" class="prompt-card <?php echo esc_attr($completed_class); ?>" data-day="<?php echo esc_attr($number); ?>">
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
    
    private function calculate_streak($user_id) {
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

    public function render_entry_page($atts) {
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
                    'quicktags' => true,
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
                    <button class="next-day" <?php echo ($day >= wp_count_posts('journal_prompt')->publish) ? 'disabled' : ''; ?>>
                        <?php _e('Next Day', 'guided-journal'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_prompt($day) {
        $prompt = get_page_by_path($day, OBJECT, 'journal_prompt');
        return $prompt ? apply_filters('the_content', $prompt->post_content) : sprintf(__('Prompt for day %d', 'guided-journal'), $day);
    }

    private function get_entry($user_id, $day) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT entry_text FROM {$wpdb->prefix}journal_entries 
             WHERE user_id = %d AND day_number = %d",
            $user_id,
            $day
        ));
    }

    public function save_entry() {
        check_ajax_referer('journal_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to save entries', 'guided-journal'));
        }

        $user_id = get_current_user_id();
        $day = intval($_POST['day']);
        $text = wp_kses_post($_POST['text']);

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

        wp_send_json_success([
            'message' => __('Entry saved successfully', 'guided-journal')
        ]);
    }

    public function get_entries() {
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
}