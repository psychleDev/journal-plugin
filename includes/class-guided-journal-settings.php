<?php
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
    }

    public function add_settings_page()
    {
        add_submenu_page(
            'guided-journal',
            'Color Settings',
            'Color Settings',
            'manage_options',
            'guided-journal-colors',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings()
    {
        register_setting('guided_journal_colors', 'guided_journal_colors', [
            'type' => 'array',
            'default' => $this->default_colors
        ]);

        add_settings_section(
            'guided_journal_colors_section',
            'Color Scheme Settings',
            [$this, 'render_section_description'],
            'guided_journal_colors'
        );

        $color_fields = [
            'background' => 'Main Background Color',
            'card_background' => 'Card Background Color',
            'text' => 'Text Color',
            'accent' => 'Accent Color',
            'container_background' => 'Container Background Color'
        ];

        foreach ($color_fields as $key => $label) {
            add_settings_field(
                'guided_journal_color_' . $key,
                $label,
                [$this, 'render_color_field'],
                'guided_journal_colors',
                'guided_journal_colors_section',
                ['key' => $key]
            );
        }
    }

    public function render_section_description()
    {
        echo '<p>Customize the color scheme of your guided journal.</p>';
    }

    public function render_color_field($args)
    {
        $options = get_option('guided_journal_colors', $this->default_colors);
        $value = isset($options[$args['key']]) ? $options[$args['key']] : $this->default_colors[$args['key']];
        ?>
        <input type="color" id="guided_journal_color_<?php echo esc_attr($args['key']); ?>"
            name="guided_journal_colors[<?php echo esc_attr($args['key']); ?>]" value="<?php echo esc_attr($value); ?>">
        <button type="button" class="button button-secondary reset-color"
            data-default="<?php echo esc_attr($this->default_colors[$args['key']]); ?>"
            data-target="guided_journal_color_<?php echo esc_attr($args['key']); ?>">
            Reset to Default
        </button>
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
            <form action="options.php" method="post">
                <?php
                settings_fields('guided_journal_colors');
                do_settings_sections('guided_journal_colors');
                submit_button('Save Colors');
                ?>
            </form>
            <script>
                jQuery(document).ready(function ($) {
                    $('.reset-color').on('click', function () {
                        const defaultColor = $(this).data('default');
                        const targetId = $(this).data('target');
                        $('#' + targetId).val(defaultColor);
                    });
                });
            </script>
        </div>
        <?php
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
            }
        </style>
        <?php
    }
}

// Initialize settings
$guided_journal_settings = new GuidedJournalSettings();