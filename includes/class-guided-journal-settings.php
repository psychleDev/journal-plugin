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
        'container_background' => '#494949',
        'completed' => '#2E7D32'
    ];

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_head', [$this, 'output_custom_colors']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_post_reset_journal_prompts', [$this, 'reset_journal_prompts']);
        add_action('admin_post_reset_journal_entries', [$this, 'reset_journal_entries']);
        add_action('admin_notices', [$this, 'display_reset_notices']);
    }

    public function enqueue_admin_assets($hook)
    {
        if ('toplevel_page_journal-settings' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'guided-journal-admin',
            GUIDED_JOURNAL_PLUGIN_URL . 'assets/css/admin.css',
            [],
            GUIDED_JOURNAL_VERSION
        );

        wp_enqueue_script(
            'guided-journal-admin',
            GUIDED_JOURNAL_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            GUIDED_JOURNAL_VERSION,
            true
        );
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

    public function register_settings()
    {
        register_setting('guided_journal_colors', 'guided_journal_colors', [
            'type' => 'array',
            'default' => $this->default_colors,
            'sanitize_callback' => [$this, 'sanitize_colors']
        ]);
    }

    public function sanitize_colors($input)
    {
        $sanitized = [];
        foreach ($this->default_colors as $key => $default) {
            if (isset($input[$key])) {
                // Strip any non-hex characters and ensure it starts with #
                $color = preg_replace('/[^A-Fa-f0-9]/', '', $input[$key]);
                $sanitized[$key] = '#' . $color;
            } else {
                $sanitized[$key] = $default;
            }
        }
        return $sanitized;
    }

    public function output_custom_colors()
    {
        $options = get_option('guided_journal_colors', $this->default_colors);
        ?>
        <style>
            :root {
                --gj-background:
                    <?php echo esc_html($options['background']); ?>
                ;
                --gj-card-background:
                    <?php echo esc_html($options['card_background']); ?>
                ;
                --gj-text:
                    <?php echo esc_html($options['text']); ?>
                ;
                --gj-accent:
                    <?php echo esc_html($options['accent']); ?>
                ;
                --gj-container-background:
                    <?php echo esc_html($options['container_background']); ?>
                ;
                --gj-completed:
                    <?php echo esc_html($options['completed']); ?>
                ;
            }
        </style>
        <?php
    }

    public function display_reset_notices()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'journal-settings') {
            return;
        }

        if (isset($_GET['reset'])) {
            $type = sanitize_text_field($_GET['reset']);
            $message = '';
            $class = 'notice notice-success';

            switch ($type) {
                case 'prompts':
                    $message = 'All journal prompts have been successfully deleted.';
                    break;
                case 'entries':
                    $message = 'All journal entries have been successfully cleared.';
                    break;
            }

            if ($message) {
                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
            }
        }
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <!-- Color Settings Section -->
            <div class="color-settings-preview">
                <h3>Color Settings</h3>
                <p>Customize the appearance of your journal by adjusting the colors below. Changes will be reflected immediately
                    on your site.</p>
            </div>

            <form action="options.php" method="post">
                <?php settings_fields('guided_journal_colors'); ?>

                <div class="color-settings-grid">
                    <?php
                    $options = get_option('guided_journal_colors', $this->default_colors);
                    $color_fields = [
                        'background' => ['Main Background', 'The main background color of the journal'],
                        'card_background' => ['Card Background', 'Background color for journal entry cards'],
                        'text' => ['Text Color', 'Color for all text content'],
                        'accent' => ['Accent Color', 'Color for buttons and highlights'],
                        'container_background' => ['Container Background', 'Background color for content containers'],
                        'completed' => ['Completed Color', 'Background color for completed journal entries']
                    ];

                    foreach ($color_fields as $key => $labels) {
                        $value = isset($options[$key]) ? $options[$key] : $this->default_colors[$key];
                        ?>
                        <div class="color-field">
                            <h4><?php echo esc_html($labels[0]); ?></h4>
                            <p class="description"><?php echo esc_html($labels[1]); ?></p>
                            <div class="color-inputs">
                                <input type="color" id="guided_journal_color_<?php echo esc_attr($key); ?>"
                                    name="guided_journal_colors[<?php echo esc_attr($key); ?>]"
                                    value="<?php echo esc_attr($value); ?>">
                                <input type="text" value="<?php echo esc_attr($value); ?>" class="color-hex-value"
                                    data-color-input="guided_journal_color_<?php echo esc_attr($key); ?>">
                                <button type="button" class="button button-secondary reset-color"
                                    data-default="<?php echo esc_attr($this->default_colors[$key]); ?>"
                                    data-target="guided_journal_color_<?php echo esc_attr($key); ?>">
                                    Reset
                                </button>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <?php submit_button('Save Color Settings'); ?>
            </form>

            <!-- Reset Options Section -->
            <div class="color-settings-preview" style="margin-top: 40px;">
                <h3>Reset Options</h3>
                <p>Use these options with caution. These actions cannot be undone.</p>
            </div>

            <div class="reset-options">
                <form action="<?php echo admin_url('admin-post.php'); ?>" method="post"
                    style="display: inline-block; margin-right: 20px;">
                    <?php wp_nonce_field('reset_journal_prompts_nonce', 'reset_prompts_nonce'); ?>
                    <input type="hidden" name="action" value="reset_journal_prompts">
                    <input type="submit" class="button button-secondary" value="Reset All Prompts"
                        onclick="return confirm('Are you sure you want to delete all journal prompts? This action cannot be undone.');">
                </form>

                <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" style="display: inline-block;">
                    <?php wp_nonce_field('reset_journal_entries_nonce', 'reset_entries_nonce'); ?>
                    <input type="hidden" name="action" value="reset_journal_entries">
                    <input type="submit" class="button button-secondary" value="Reset All Journal Entries"
                        onclick="return confirm('Are you sure you want to delete all journal entries? This action cannot be undone.');">
                </form>
            </div>
        </div>
        <?php
    }

    public function reset_journal_prompts()
    {
        if (!current_user_can('manage_options') || !check_admin_referer('reset_journal_prompts_nonce', 'reset_prompts_nonce')) {
            wp_die('Unauthorized access');
        }

        $args = array(
            'post_type' => 'journal_prompt',
            'posts_per_page' => -1,
            'post_status' => 'any',
        );

        $prompts = get_posts($args);

        foreach ($prompts as $prompt) {
            wp_delete_post($prompt->ID, true);
        }

        wp_redirect(add_query_arg(
            array('page' => 'journal-settings', 'reset' => 'prompts'),
            admin_url('admin.php')
        ));
        exit;
    }

    public function reset_journal_entries()
    {
        if (!current_user_can('manage_options') || !check_admin_referer('reset_journal_entries_nonce', 'reset_entries_nonce')) {
            wp_die('Unauthorized access');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'journal_entries';
        $wpdb->query("TRUNCATE TABLE $table_name");

        wp_redirect(add_query_arg(
            array('page' => 'journal-settings', 'reset' => 'entries'),
            admin_url('admin.php')
        ));
        exit;
    }
}