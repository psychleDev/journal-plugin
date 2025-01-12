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
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p><?php _e('Manage your journal prompts here.', 'guided-journal'); ?></p>
            
            <?php
            // Display existing prompts
            $prompts = new WP_Query([
                'post_type' => 'journal_prompt',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ]);

            if ($prompts->have_posts()) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Day', 'guided-journal'); ?></th>
                            <th><?php _e('Prompt', 'guided-journal'); ?></th>
                            <th><?php _e('Actions', 'guided-journal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($prompts->have_posts()) : $prompts->the_post(); ?>
                            <tr>
                                <td><?php the_title(); ?></td>
                                <td><?php echo wp_trim_words(get_the_content(), 20); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link(); ?>"><?php _e('Edit', 'guided-journal'); ?></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php wp_reset_postdata();
            else :
                echo '<p>' . __('No prompts found.', 'guided-journal') . '</p>';
            endif;
            ?>
        </div>
        <?php
    }

    public function render_create_prompts_page()
    {
        if (isset($_POST['create_prompts']) && check_admin_referer('create_prompts_nonce')) {
            $this->create_prompts();
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field('create_prompts_nonce'); ?>
                <p><?php _e('Click the button below to create 365 journal prompts.', 'guided-journal'); ?></p>
                <input type="submit" name="create_prompts" class="button button-primary" value="<?php esc_attr_e('Create Prompts', 'guided-journal'); ?>">
            </form>
        </div>
        <?php
    }

    private function create_prompts()
    {
        $sample_prompts = [
            "What are you most grateful for today?",
            "What was the biggest challenge you faced recently and how did you handle it?",
            "Write about a goal you're working towards and your next steps.",
            "Reflect on a relationship that's important to you.",
            "What made you smile today?",
            "Describe a moment that made you proud recently.",
            "What would you like to improve about yourself?",
            "Write about someone who inspires you and why.",
            "What are your hopes for the future?",
            "Describe a recent accomplishment and how it made you feel."
        ];

        $created = 0;
        $skipped = 0;

        for ($i = 1; $i <= 365; $i++) {
            // Check if prompt already exists
            $existing = get_page_by_path((string)$i, OBJECT, 'journal_prompt');
            
            if ($existing) {
                $skipped++;
                continue;
            }

            $prompt = $sample_prompts[array_rand($sample_prompts)];
            
            $post_data = array(
                'post_title'    => (string)$i,
                'post_content'  => $prompt,
                'post_status'   => 'publish',
                'post_type'     => 'journal_prompt',
                'post_name'     => (string)$i
            );

            $result = wp_insert_post($post_data);
            
            if (!is_wp_error($result)) {
                $created++;
            }
        }

        add_settings_error(
            'guided_journal_messages',
            'prompts_created',
            sprintf(
                __('Created %d new prompts. Skipped %d existing prompts.', 'guided-journal'),
                $created,
                $skipped
            ),
            'updated'
        );
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
                    'orderby' => 'title',
                    'order' => 'ASC',
                ]); 
                
                if ($the_query->have_posts()): 
                    while ($the_query->have_posts()):
                        $the_query->the_post();
                        ?>
                        <a href="<?php the_permalink(); ?>" class="prompt-card">
                            <span class="day-number"><?php the_title(); ?></span>
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
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $slug = basename($path);
        $day = str_replace('day-', '', $slug);

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
                    <button class="next-day">
                        <?php _e('Next Day', 'guided-journal'); ?>
                    </button>
                </div>
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
        // Verify AJAX nonce
        check_ajax_referer('journal_nonce', 'nonce');

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
                [<?php
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
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p><?php _e('Manage your journal prompts here.', 'guided-journal'); ?></p>
            
            <?php
            // Display existing prompts
            $prompts = new WP_Query([
                'post_type' => 'journal_prompt',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ]);

            if ($prompts->have_posts()) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Day', 'guided-journal'); ?></th>
                            <th><?php _e('Prompt', 'guided-journal'); ?></th>
                            <th><?php _e('Actions', 'guided-journal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($prompts->have_posts()) : $prompts->the_post(); ?>
                            <tr>
                                <td><?php the_title(); ?></td>
                                <td><?php echo wp_trim_words(get_the_content(), 20); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link(); ?>"><?php _e('Edit', 'guided-journal'); ?></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php wp_reset_postdata();
            else :
                echo '<p>' . __('No prompts found.', 'guided-journal') . '</p>';
            endif;
            ?>
        </div>
        <?php
    }

    public function render_create_prompts_page()
    {
        if (isset($_POST['create_prompts']) && check_admin_referer('create_prompts_nonce')) {
            $this->create_prompts();
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field('create_prompts_nonce'); ?>
                <p><?php _e('Click the button below to create 365 journal prompts.', 'guided-journal'); ?></p>
                <input type="submit" name="create_prompts" class="button button-primary" value="<?php esc_attr_e('Create Prompts', 'guided-journal'); ?>">
            </form>
        </div>
        <?php
    }

    private function create_prompts()
    {
        $sample_prompts = [
            "What are you most grateful for today?",
            "What was the biggest challenge you faced recently and how did you handle it?",
            "Write about a goal you're working towards and your next steps.",
            "Reflect on a relationship that's important to you.",
            "What made you smile today?",
            "Describe a moment that made you proud recently.",
            "What would you like to improve about yourself?",
            "Write about someone who inspires you and why.",
            "What are your hopes for the future?",
            "Describe a recent accomplishment and how it made you feel."
        ];

        $created = 0;
        $skipped = 0;

        for ($i = 1; $i <= 365; $i++) {
            // Check if prompt already exists
            $existing = get_page_by_path((string)$i, OBJECT, 'journal_prompt');
            
            if ($existing) {
                $skipped++;
                continue;
            }

            $prompt = $sample_prompts[array_rand($sample_prompts)];
            
            $post_data = array(
                'post_title'    => (string)$i,
                'post_content'  => $prompt,
                'post_status'   => 'publish',
                'post_type'     => 'journal_prompt',
                'post_name'     => (string)$i
            );

            $result = wp_insert_post($post_data);
            
            if (!is_wp_error($result)) {
                $created++;
            }
        }

        add_settings_error(
            'guided_journal_messages',
            'prompts_created',
            sprintf(
                __('Created %d new prompts. Skipped %d existing prompts.', 'guided-journal'),
                $created,
                $skipped
            ),
            'updated'
        );
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
                    'orderby' => 'title',
                    'order' => 'ASC',
                ]); 
                
                if ($the_query->have_posts()): 
                    while ($the_query->have_posts()):
                        $the_query->the_post();
                        ?>
                        <a href="<?php the_permalink(); ?>" class="prompt-card">
                            <span class="day-number"><?php the_title(); ?></span>
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
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $slug = basename($path);
        $day = str_replace('day-', '', $slug);

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
                    <button class="next-day">
                        <?php _e('Next Day', 'guided-journal'); ?>
                    </button>
                </div>
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
        // Verify AJAX nonce
        check_ajax_referer('journal_nonce', 'nonce');

        // Get current user
        $user = wp_get_current_user();

        // Check if user has journal or admin role
        if (!in_array('menoffire', $user->roles) && !in_array('administrator', $user->roles) && !in_array('ignite30', $user->roles)) {
            wp_send_json_error(__('Unauthorized access', 'guided-journal'));
        }

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

    private function get_completed_entries($user_id)
    {
        global $wpdb;
        return $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT day_number FROM {$wpdb->prefix}journal_entries 
             WHERE user_id = %d ORDER BY day_number ASC",
            $user_id
        ));
    }
}
