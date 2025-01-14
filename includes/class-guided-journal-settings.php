<?php
namespace GuidedJournal;

class GuidedJournalSettings {
    private $options;
    private $demo_mode;
    private $default_colors = [
        'background' => '#333333',
        'card_background' => '#1b1b1b',
        'text' => '#ffffff',
        'accent' => '#991B1E',
        'container_background' => '#494949',
        'completed' => '#2E7D32',
        'button_background' => '#991B1E',
        'button_text' => '#ffffff',
        'button_hover' => '#7a1518',
        'progress_bar_background' => '#494949',
        'progress_bar_fill' => '#2E7D32',
    ];

    private $default_typography = [
        'heading_font' => 'Montserrat',
        'body_font' => 'Open Sans',
        'heading_weight' => '600',
        'body_weight' => '400',
        'heading_size' => '2rem',
        'subheading_size' => '1.5rem',
        'body_size' => '1rem',
        'line_height' => '1.6',
    ];

    private $default_spacing = [
        'card_padding' => '20px',
        'container_padding' => '30px',
        'grid_gap' => '20px',
        'section_spacing' => '40px'
    ];

    private $default_borders = [
        'card_radius' => '10px',
        'button_radius' => '5px',
        'progress_radius' => '10px'
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
        add_action('admin_notices', [$this, 'admin_notices']);

        // Initialize Demo Mode
        $demo_mode_file = GUIDED_JOURNAL_PLUGIN_DIR . 'includes/class-guided-journal-demo-mode.php';
        if (file_exists($demo_mode_file)) {
            require_once $demo_mode_file;
            if (class_exists('GuidedJournal\\GuidedJournalDemoMode')) {
                $this->demo_mode = new GuidedJournalDemoMode();
            }
        }
    }

    public function add_settings_page() {
        add_menu_page(
            'Journal Settings',
            'Journal Settings',
            'manage_options',
            'journal-settings',
            [$this, 'render_settings_page'],
            'dashicons-admin-appearance',
            21
        );

        add_submenu_page(
            'journal-settings',
            'Typography & Colors',
            'Typography & Colors',
            'manage_options',
            'journal-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        // Register color settings
        register_setting('guided_journal_settings', 'guided_journal_colors', [
            'type' => 'array',
            'default' => $this->default_colors,
            'sanitize_callback' => [$this, 'sanitize_colors']
        ]);

        // Register typography settings
        register_setting('guided_journal_settings', 'guided_journal_typography', [
            'type' => 'array',
            'default' => $this->default_typography,
            'sanitize_callback' => [$this, 'sanitize_typography']
        ]);

        // Register spacing settings
        register_setting('guided_journal_settings', 'guided_journal_spacing', [
            'type' => 'array',
            'default' => $this->default_spacing,
            'sanitize_callback' => [$this, 'sanitize_spacing']
        ]);

        // Register border settings
        register_setting('guided_journal_settings', 'guided_journal_borders', [
            'type' => 'array',
            'default' => $this->default_borders,
            'sanitize_callback' => [$this, 'sanitize_borders']
        ]);
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
            'guided-journal-admin-settings',
            GUIDED_JOURNAL_PLUGIN_URL . 'assets/js/admin-settings.js',
            ['jquery'],
            GUIDED_JOURNAL_VERSION,
            true
        );

        // Pass Google Fonts data to JavaScript
        wp_localize_script('guided-journal-admin-settings', 'googleFonts', $this->google_fonts);
    }

    public function enqueue_google_fonts() {
        $typography = get_option('guided_journal_typography', $this->default_typography);
        $fonts_to_load = array();

        // Add heading font
        if (!empty($typography['heading_font'])) {
            $fonts_to_load[] = array(
                'font' => $typography['heading_font'],
                'weight' => $typography['heading_weight']
            );
        }

        // Add body font if different from heading
        if (!empty($typography['body_font']) && $typography['body_font'] !== $typography['heading_font']) {
            $fonts_to_load[] = array(
                'font' => $typography['body_font'],
                'weight' => $typography['body_weight']
            );
        }

        if (!empty($fonts_to_load)) {
            $font_families = array();
            foreach ($fonts_to_load as $font) {
                $font_families[] = str_replace(' ', '+', $font['font']) . ':' . $font['weight'];
            }

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

    public function output_custom_styles() {
        $colors = get_option('guided_journal_colors', $this->default_colors);
        $typography = get_option('guided_journal_typography', $this->default_typography);
        $spacing = get_option('guided_journal_spacing', $this->default_spacing);
        $borders = get_option('guided_journal_borders', $this->default_borders);
        ?>
        <style>
            :root {
                /* Colors */
                --gj-background: <?php echo esc_html($colors['background']); ?>;
                --gj-card-background: <?php echo esc_html($colors['card_background']); ?>;
                --gj-text: <?php echo esc_html($colors['text']); ?>;
                --gj-accent: <?php echo esc_html($colors['accent']); ?>;
                --gj-container-background: <?php echo esc_html($colors['container_background']); ?>;
                --gj-completed: <?php echo esc_html($colors['completed']); ?>;
                --gj-button-background: <?php echo esc_html($colors['button_background']); ?>;
                --gj-button-text: <?php echo esc_html($colors['button_text']); ?>;
                --gj-button-hover: <?php echo esc_html($colors['button_hover']); ?>;
                --gj-progress-background: <?php echo esc_html($colors['progress_bar_background']); ?>;
                --gj-progress-fill: <?php echo esc_html($colors['progress_bar_fill']); ?>;

                /* Typography */
                --gj-heading-font: "<?php echo esc_html($typography['heading_font']); ?>", sans-serif;
                --gj-body-font: "<?php echo esc_html($typography['body_font']); ?>", sans-serif;
                --gj-heading-size: <?php echo esc_html($typography['heading_size']); ?>;
                --gj-subheading-size: <?php echo esc_html($typography['subheading_size']); ?>;
                --gj-body-size: <?php echo esc_html($typography['body_size']); ?>;
                --gj-line-height: <?php echo esc_html($typography['line_height']); ?>;

                /* Spacing */
                --gj-card-padding: <?php echo esc_html($spacing['card_padding']); ?>;
                --gj-container-padding: <?php echo esc_html($spacing['container_padding']); ?>;
                --gj-grid-gap: <?php echo esc_html($spacing['grid_gap']); ?>;
                --gj-section-spacing: <?php echo esc_html($spacing['section_spacing']); ?>;

                /* Borders */
                --gj-card-radius: <?php echo esc_html($borders['card_radius']); ?>;
                --gj-button-radius: <?php echo esc_html($borders['button_radius']); ?>;
                --gj-progress-radius: <?php echo esc_html($borders['progress_radius']); ?>;
            }
        </style>
        <?php
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $colors = get_option('guided_journal_colors', $this->default_colors);
        $typography = get_option('guided_journal_typography', $this->default_typography);
        $spacing = get_option('guided_journal_spacing', $this->default_spacing);
        $borders = get_option('guided_journal_borders', $this->default_borders);

        include GUIDED_JOURNAL_PLUGIN_DIR . 'templates/admin/settings-page.php';
    }

    /**
     * Sanitize color settings
     */
    public function sanitize_colors($input) {
        $sanitized = [];
        foreach ($this->default_colors as $key => $default) {
            if (isset($input[$key])) {
                $color = sanitize_hex_color($input[$key]);
                $sanitized[$key] = $color ? $color : $default;
            } else {
                $sanitized[$key] = $default;
            }
        }
        return $sanitized;
    }

    /**
     * Sanitize typography settings
     */
    public function sanitize_typography($input) {
        $sanitized = [];
        
        // Sanitize fonts
        $sanitized['heading_font'] = isset($input['heading_font']) && array_key_exists($input['heading_font'], $this->google_fonts) 
            ? $input['heading_font'] 
            : $this->default_typography['heading_font'];
        
        $sanitized['body_font'] = isset($input['body_font']) && array_key_exists($input['body_font'], $this->google_fonts) 
            ? $input['body_font'] 
            : $this->default_typography['body_font'];

        // Sanitize weights
        $heading_weights = $this->google_fonts[$sanitized['heading_font']];
        $sanitized['heading_weight'] = isset($input['heading_weight']) && in_array($input['heading_weight'], $heading_weights)
            ? $input['heading_weight']
            : $this->default_typography['heading_weight'];

        $body_weights = $this->google_fonts[$sanitized['body_font']];
        $sanitized['body_weight'] = isset($input['body_weight']) && in_array($input['body_weight'], $body_weights)
            ? $input['body_weight']
            : $this->default_typography['body_weight'];

        // Sanitize sizes and line height
        $sanitized['heading_size'] = isset($input['heading_size']) ? $this->sanitize_css_value($input['heading_size'], $this->default_typography['heading_size']) : $this->default_typography['heading_size'];
        $sanitized['subheading_size'] = isset($input['subheading_size']) ? $this->sanitize_css_value($input['subheading_size'], $this->default_typography['subheading_size']) : $this->default_typography['subheading_size'];
        $sanitized['body_size'] = isset($input['body_size']) ? $this->sanitize_css_value($input['body_size'], $this->default_typography['body_size']) : $this->default_typography['body_size'];
        $sanitized['line_height'] = isset($input['line_height']) ? $this->sanitize_css_value($input['line_height'], $this->default_typography['line_height']) : $this->default_typography['line_height'];

        return $sanitized;
    }

    /**
     * Sanitize spacing settings
     */
    public function sanitize_spacing($input) {
        $sanitized = [];
        foreach ($this->default_spacing as $key => $default) {
            $sanitized[$key] = isset($input[$key]) ? $this->sanitize_css_value($input[$key], $default) : $default;
        }
        return $sanitized;
    }

    /**
     * Sanitize border settings
     */
    public function sanitize_borders($input) {
        $sanitized = [];
        foreach ($this->default_borders as $key => $default) {
            $sanitized[$key] = isset($input[$key]) ? $this->sanitize_css_value($input[$key], $default) : $default;
        }
        return $sanitized;
    }

    /**
     * Validate CSS value (px, rem, em, %)
     */
    private function sanitize_css_value($value, $default) {
        // Remove any whitespace
        $value = trim($value);
        
        // Check if empty
        if (empty($value)) {
            return $default;
        }

        // Validate numeric values with units
        if (preg_match('/^(\d*\.?\d+)(px|rem|em|%|vh|vw)$/', $value)) {
            return $value;
        }

// Validate unitless values (like line-height)
if (preg_match('/^\d*\.?\d+$/', $value)) {
    return $value;
}

return $default;
}

private function render_color_input($key, $label, $colors) {
?>
<div class="color-input-group">
    <label><?php echo esc_html($label); ?></label>
    <div class="color-inputs">
        <input type="color" 
               name="guided_journal_colors[<?php echo esc_attr($key); ?>]" 
               value="<?php echo esc_attr($colors[$key]); ?>">
        <input type="text" 
               class="color-hex-value"
               value="<?php echo esc_attr($colors[$key]); ?>"
               data-color-input="<?php echo esc_attr($key); ?>">
        <button type="button" 
                class="button button-secondary reset-color" 
                data-default="<?php echo esc_attr($this->default_colors[$key]); ?>"
                data-target="<?php echo esc_attr($key); ?>">
            <?php _e('Reset', 'guided-journal'); ?>
        </button>
    </div>
</div>
<?php
}

public function admin_notices() {
if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Journal style settings updated successfully.', 'guided-journal'); ?></p>
    </div>
    <?php
}
}

public function render_preview_section() {
?>
<div class="settings-section preview-section">
    <h2><?php _e('Preview', 'guided-journal'); ?></h2>
    <div class="style-preview">
        <div class="preview-card">
            <h3><?php _e('Sample Card', 'guided-journal'); ?></h3>
            <p><?php _e('This is how your cards and text will look with the current settings.', 'guided-journal'); ?></p>
            <button class="preview-button"><?php _e('Sample Button', 'guided-journal'); ?></button>
            <div class="preview-progress">
                <div class="preview-progress-bar" style="width: 75%"></div>
            </div>
        </div>
    </div>
</div>
<?php
}

public function render_typography_section($typography) {
?>
<div class="settings-section">
    <h2><?php _e('Typography', 'guided-journal'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Heading Font', 'guided-journal'); ?></th>
            <td>
                <select name="guided_journal_typography[heading_font]">
                    <?php foreach ($this->google_fonts as $font => $weights): ?>
                        <option value="<?php echo esc_attr($font); ?>" 
                                <?php selected($typography['heading_font'], $font); ?>>
                            <?php echo esc_html($font); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="guided_journal_typography[heading_weight]">
                    <?php foreach ($this->google_fonts[$typography['heading_font']] as $weight): ?>
                        <option value="<?php echo esc_attr($weight); ?>"
                                <?php selected($typography['heading_weight'], $weight); ?>>
                            <?php echo esc_html($weight); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" 
                       name="guided_journal_typography[heading_size]" 
                       value="<?php echo esc_attr($typography['heading_size']); ?>"
                       class="small-text"
                       placeholder="2rem">
                <p class="description"><?php _e('Font size should include units (px, rem, em)', 'guided-journal'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Body Font', 'guided-journal'); ?></th>
            <td>
                <select name="guided_journal_typography[body_font]">
                    <?php foreach ($this->google_fonts as $font => $weights): ?>
                        <option value="<?php echo esc_attr($font); ?>"
                                <?php selected($typography['body_font'], $font); ?>>
                            <?php echo esc_html($font); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="guided_journal_typography[body_weight]">
                    <?php foreach ($this->google_fonts[$typography['body_font']] as $weight): ?>
                        <option value="<?php echo esc_attr($weight); ?>"
                                <?php selected($typography['body_weight'], $weight); ?>>
                            <?php echo esc_html($weight); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" 
                       name="guided_journal_typography[body_size]" 
                       value="<?php echo esc_attr($typography['body_size']); ?>"
                       class="small-text"
                       placeholder="1rem">
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Line Height', 'guided-journal'); ?></th>
            <td>
                <input type="text" 
                       name="guided_journal_typography[line_height]" 
                       value="<?php echo esc_attr($typography['line_height']); ?>"
                       class="small-text"
                       placeholder="1.6">
                <p class="description"><?php _e('Line height can be unitless (1.6) or include units', 'guided-journal'); ?></p>
            </td>
        </tr>
    </table>
</div>
<?php
}

public function render_spacing_section($spacing) {
?>
<div class="settings-section">
    <h2><?php _e('Spacing', 'guided-journal'); ?></h2>
    <table class="form-table">
        <?php
        $spacing_fields = [
            'card_padding' => __('Card Padding', 'guided-journal'),
            'container_padding' => __('Container Padding', 'guided-journal'),
            'grid_gap' => __('Grid Gap', 'guided-journal'),
            'section_spacing' => __('Section Spacing', 'guided-journal')
        ];
        foreach ($spacing_fields as $key => $label):
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <input type="text" 
                       name="guided_journal_spacing[<?php echo esc_attr($key); ?>]" 
                       value="<?php echo esc_attr($spacing[$key]); ?>"
                       class="regular-text"
                       placeholder="<?php echo esc_attr($this->default_spacing[$key]); ?>">
                <p class="description"><?php _e('Include units (px, rem, em)', 'guided-journal'); ?></p>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php
}

public function render_borders_section($borders) {
?>
<div class="settings-section">
    <h2><?php _e('Borders & Radius', 'guided-journal'); ?></h2>
    <table class="form-table">
        <?php
        $border_fields = [
            'card_radius' => __('Card Border Radius', 'guided-journal'),
            'button_radius' => __('Button Border Radius', 'guided-journal'),
            'progress_radius' => __('Progress Bar Border Radius', 'guided-journal')
        ];
        foreach ($border_fields as $key => $label):
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <input type="text" 
                       name="guided_journal_borders[<?php echo esc_attr($key); ?>]" 
                       value="<?php echo esc_attr($borders[$key]); ?>"
                       class="regular-text"
                       placeholder="<?php echo esc_attr($this->default_borders[$key]); ?>">
                <p class="description"><?php _e('Include units (px, rem, em)', 'guided-journal'); ?></p>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php
}
}