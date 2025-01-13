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

    public function enqueue_admin_assets($hook)
    {
        if ($hook !== 'toplevel_page_journal-settings') {
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

        // Add completed entry color setting
        register_setting('guided_journal_settings', 'gj_completed_color');

        add_settings_field(
            'gj_completed_color',
            'Completed Entry Color',
            function () {
                $color = get_option('gj_completed_color', '#00FF00'); // Default green
                echo "<input type='color' name='gj_completed_color' value='{$color}'>";
            },
            'guided_journal_settings',
            'guided_journal_settings_section'
        );
    }

    public function sanitize_colors($input)
    {
        $sanitized = [];
        foreach ($this->default_colors as $key => $default) {
            if (isset($input[$key])) {
                // Strip any non-hex characters
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
        $completedColor = get_option('gj_completed_color', '#00FF00');
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
                --gj-completed-color:
                    <?php echo esc_html($completedColor); ?>
                ;
            }
        </style>
        <?php
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="color-settings-preview">
                <h3>Color Settings</h3>
                <p>Customize the appearance of your journal by adjusting the colors below. Changes will be reflected immediately
                    on your site.</p>
            </div>

            <form action="options.php" method="post">
                <?php
                settings_fields('guided_journal_colors');
                do_settings_sections('guided_journal_settings');
                ?>

                <div class="color-settings-grid">
                    <?php
                    $options = get_option('guided_journal_colors', $this->default_colors);
                    $color_fields = [
                        'background' => ['Main Background', 'The main background color of the journal'],
                        'card_background' => ['Card Background', 'Background color for journal entry cards'],
                        'text' => ['Text Color', 'Color for all text content'],
                        'accent' => ['Accent Color', 'Color for buttons and highlights'],
                        'container_background' => ['Container Background', 'Background color for content containers']
                    ];

                    foreach ($color_fields as $key => $labels) {
                        $value = isset($options[$key]) ? $options[$key] : $this->default_colors[$key];
                        ?>
                        <div class="color-field">
                            <h4><?php echo esc_html($labels[0]); ?></h4>
                            <p class="description"> <?php echo esc_html($labels[1]); ?></p>
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
                    <div class="color-field">
                        <h4>Completed Entry Color</h4>
                        <p class="description">Set the color for completed journal entries.</p>
                        <div class="color-inputs">
                            <input type="color" id="gj_completed_color" name="gj_completed_color"
                                value="<?php echo esc_attr(get_option('gj_completed_color', '#00FF00')); ?>">
                            <button type="button" class="button button-secondary reset-color" data-default="#00FF00"
                                data-target="gj_completed_color">Reset</button>
                        </div>
                    </div>
                </div>

                <?php submit_button('Save Color Settings'); ?>
            </form>
        </div>
        <?php
    }
}
