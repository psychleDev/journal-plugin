<?php
namespace GuidedJournal;

class GuidedJournalSettings {
    private $options;
    private $default_colors = [
        'background' => '#333333',
        'card_background' => '#1b1b1b',
        'text' => '#ffffff',
        'accent' => '#991B1E',
        'container_background' => '#494949',
        'completed' => '#2E7D32'
    ];

    private $default_fonts = [
        'heading_font' => 'Montserrat',
        'body_font' => 'Open Sans',
        'heading_weight' => '600',
        'body_weight' => '400'
    ];

    private $google_fonts = [
        'Roboto' => ['300', '400', '500', '600', '700'],
        'Open Sans' => ['300', '400', '600', '700'],
        'Lato' => ['300', '400', '700'],
        'Montserrat' => ['400', '500', '600', '700'],
        'Poppins' => ['300', '400', '500', '600', '700'],
        'Raleway' => ['300', '400', '500', '600', '700'],
        'Inter' => ['300', '400', '500', '600', '700'],
        'Playfair Display' => ['400', '500', '600', '700'],
        'Source Sans Pro' => ['300', '400', '600', '700'],
        'Nunito' => ['300', '400', '600', '700']
    ];

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_head', [$this, 'output_custom_styles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_google_fonts']);
        add_action('admin_post_reset_journal_prompts', [$this, 'reset_journal_prompts']);
        add_action('admin_post_reset_journal_entries', [$this, 'reset_journal_entries']);
        add_action('admin_notices', [$this, 'display_reset_notices']);
    }

    public function add_settings_page() {
        add_menu_page(
            'Journal Settings',          // Page title
            'Journal Settings',          // Menu title
            'manage_options',            // Capability
            'journal-settings',          // Menu slug
            [$this, 'render_settings_page'], // Callback function
            'dashicons-admin-appearance', // Icon
            21                          // Position after Journal Prompts
        );

        add_submenu_page(
            'journal-settings',         // Parent slug
            'Typography & Colors',      // Page title
            'Typography & Colors',      // Menu title
            'manage_options',           // Capability
            'journal-settings',         // Menu slug (same as parent to make it the default page)
            [$this, 'render_settings_page'] // Callback function
        );
    }

    public function register_settings() {
        // Register color settings
        register_setting('guided_journal_settings', 'guided_journal_colors', [
            'type' => 'array',
            'default' => $this->default_colors,
            'sanitize_callback' => [$this, 'sanitize_colors']
        ]);

        // Register font settings
        register_setting('guided_journal_settings', 'guided_journal_fonts', [
            'type' => 'array',
            'default' => $this->default_fonts,
            'sanitize_callback' => [$this, 'sanitize_fonts']
        ]);
    }

    public function sanitize_colors($input) {
        $sanitized = [];
        foreach ($this->default_colors as $key => $default) {
            if (isset($input[$key])) {
                $color = preg_replace('/[^A-Fa-f0-9]/', '', $input[$key]);
                $sanitized[$key] = '#' . $color;
            } else {
                $sanitized[$key] = $default;
            }
        }
        return $sanitized;
    }

    public function sanitize_fonts($input) {
        $sanitized = [];
        foreach ($this->default_fonts as $key => $default) {
            if (isset($input[$key]) && !empty($input[$key])) {
                if (strpos($key, 'weight') !== false) {
                    $sanitized[$key] = in_array($input[$key], ['300', '400', '500', '600', '700']) ? 
                        $input[$key] : $default;
                } else {
                    $sanitized[$key] = array_key_exists($input[$key], $this->google_fonts) ? 
                        $input[$key] : $default;
                }
            } else {
                $sanitized[$key] = $default;
            }
        }
        return $sanitized;
    }

    public function enqueue_google_fonts() {
        $fonts = get_option('guided_journal_fonts', $this->default_fonts);
        $font_families = [];

        // Prepare heading font
        if (!empty($fonts['heading_font'])) {
            $font_families[] = str_replace(' ', '+', $fonts['heading_font']) . ':' . $fonts['heading_weight'];
        }

        // Prepare body font if different from heading
        if (!empty($fonts['body_font']) && $fonts['body_font'] !== $fonts['heading_font']) {
            $font_families[] = str_replace(' ', '+', $fonts['body_font']) . ':' . $fonts['body_weight'];
        }

        if (!empty($font_families)) {
            $query_args = array(
                'family' => implode('|', $font_families),
                'display' => 'swap',
            );
            wp_enqueue_style(
                'guided-journal-fonts',
                add_query_arg($query_args, "https://fonts.googleapis.com/css2"),
                array(),
                GUIDED_JOURNAL_VERSION
            );
        }
    }

    public function enqueue_admin_assets($hook) {
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

    public function output_custom_styles() {
        $colors = get_option('guided_journal_colors', $this->default_colors);
        $fonts = get_option('guided_journal_fonts', $this->default_fonts);
        ?>
        <style>
            :root {
                --gj-background: <?php echo esc_html($colors['background']); ?>;
                --gj-card-background: <?php echo esc_html($colors['card_background']); ?>;
                --gj-text: <?php echo esc_html($colors['text']); ?>;
                --gj-accent: <?php echo esc_html($colors['accent']); ?>;
                --gj-container-background: <?php echo esc_html($colors['container_background']); ?>;
                --gj-completed: <?php echo esc_html($colors['completed']); ?>;
                --gj-heading-font: '<?php echo esc_html($fonts['heading_font']); ?>', sans-serif;
                --gj-body-font: '<?php echo esc_html($fonts['body_font']); ?>', sans-serif;
            }

            .journal-container h1,
            .journal-container h2,
            .prompt-grid .day-number,
            .stat-value {
                font-family: var(--gj-heading-font);
                font-weight: <?php echo esc_html($fonts['heading_weight']); ?>;
            }

            body,
            .prompt,
            .stat-label,
            .wp-editor-area {
                font-family: var(--gj-body-font);
                font-weight: <?php echo esc_html($fonts['body_weight']); ?>;
            }
        </style>
        <?php
    }

    public function display_reset_notices() {
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

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $colors = get_option('guided_journal_colors', $this->default_colors);
        $fonts = get_option('guided_journal_fonts', $this->default_fonts);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form action="options.php" method="post">
                <?php settings_fields('guided_journal_settings'); ?>

                <!-- Color Settings Section -->
                <div class="settings-section">
                    <h2>Colors</h2>
                    <table class="form-table">
                        <?php foreach ($this->default_colors as $key => $default): ?>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>
                                    </label>
                                </th>
                                <td>
                                    <div class="color-input-group">
                                        <input type="color" 
                                               id="<?php echo esc_attr($key); ?>"
                                               name="guided_journal_colors[<?php echo esc_attr($key); ?>]"
                                               value="<?php echo esc_attr($colors[$key]); ?>"
                                        >
                                        <input type="text" 
                                               class="color-hex"
                                               value="<?php echo esc_attr($colors[$key]); ?>"
                                               data-color-input="<?php echo esc_attr($key); ?>"
                                        >
                                        <button type="button" 
                                                class="button button-secondary reset-color" 
                                                data-default="<?php echo esc_attr($default); ?>"
                                                data-target="<?php echo esc_attr($key); ?>"
                                        >
                                            Reset
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <!-- Font Settings Section -->
                <div class="settings-section">
                    <h2>Typography</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="heading_font">Heading Font</label>
                            </th>
                            <td>
                                <select id="heading_font" name="guided_journal_fonts[heading_font]">
                                    <?php foreach ($this->google_fonts as $font => $weights): ?>
                                        <option value="<?php echo esc_attr($font); ?>" 
                                                <?php selected($fonts['heading_font'], $font); ?>
                                                style="font-family: '<?php echo esc_attr($font); ?>'">
                                            <?php echo esc_html($font); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="guided_journal_fonts[heading_weight]">
                                    <?php 
                                    $available_weights = $this->google_fonts[$fonts['heading_font']];
                                    foreach ($available_weights as $weight): 
                                    ?>
                                        <option value="<?php echo esc_attr($weight); ?>"
                                                <?php selected($fonts['heading_weight'], $weight); ?>>
                                            <?php echo esc_html($weight); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="body_font">Body Font</label>
                            </th>
                            <td>
                                <select id="body_font" name="guided_journal_fonts[body_font]">
                                    <?php foreach ($this->google_fonts as $font => $weights): ?>
                                        <option value="<?php echo esc_attr($font); ?>"
                                                <?php selected($fonts['body_font'], $font); ?>
                                                style="font-family: '<?php echo esc_attr($font); ?>'">
                                            <?php echo esc_html($font); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="guided_journal_fonts[body_weight]">
                                    <?php 
                                    $available_weights = $this->google_fonts[$fonts['body_font']];
                                    foreach ($available_weights as $weight): 
                                    ?>
                                        <option value="<?php echo esc_attr($weight); ?>"
                                                <?php selected($fonts['body_weight'], $weight); ?>>
                                            <?php echo esc_html($weight); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button('Save Settings'); ?>
            </form>

            <!-- Reset Options Section -->
            <div class="settings-section">
                <h2>Reset Options</h2>
                <p class="description">Use these options with caution. These actions cannot be undone.</p>

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
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Load Google Fonts
            function loadGoogleFont(font) {
                const link = document.createElement('link');
                link.href = `https://fonts.googleapis.com/css2?family=${font.replace(' ', '+')}:wght@400;700&display=swap`;
                link.rel = 'stylesheet';
                document.head.appendChild(link);
            }

            // Initialize font previews
            $('#heading_font, #body_font').each(function() {
                const font = $(this).val();
                loadGoogleFont(font);
                $(this).css('font-family', `"${font}", sans-serif`);
            });

            // Handle font changes
            $('#heading_font, #body_font').on('change', function() {
                const font = $(this).val();
                loadGoogleFont(font);
                $(this).css('font-family', `"${font}", sans-serif`);
            });

            // Handle color resets
            $('.reset-color').on('click', function() {
                const defaultColor = $(this).data('default');
                const targetId = $(this).data('target');
                $(`#${targetId}`).val(defaultColor);
                $(`input[data-color-input="${targetId}"]`).val(defaultColor);
            });

            // Sync color inputs
            $('input[type="color"]').on('input', function() {
                const value = $(this).val();
                $(`input[data-color-input="${$(this).attr('id')}"]`).val(value);
            });

            $('.color-hex').on('input', function() {
                const value = $(this).val();
                const colorId = $(this).data('color-input');
                $(`#${colorId}`).val(value);
            });
        });
        </script>
        <?php
    }

    public function reset_journal_prompts() {
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

    public function reset_journal_entries() {
        if (!current_user_can('manage_options') || !check_admin_referer('reset_journal_entries_nonce', 'reset_entries_nonce')) {
            wp_die('Unauthorized access');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'journal_entries';
        $wpdb->query("TRUNCATE TABLE $table_name");

        // Also truncate the journal stats table if it exists
        $stats_table = $wpdb->prefix . 'journal_stats';
        if($wpdb->get_var("SHOW TABLES LIKE '$stats_table'") == $stats_table) {
            $wpdb->query("TRUNCATE TABLE $stats_table");
        }

        wp_redirect(add_query_arg(
            array('page' => 'journal-settings', 'reset' => 'entries'),
            admin_url('admin.php')
        ));
        exit;
    }
}