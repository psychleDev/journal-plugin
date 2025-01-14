<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form action="options.php" method="post">
        <?php settings_fields('guided_journal_settings'); ?>

        <!-- Colors Section -->
        <div class="settings-section">
            <h2>Colors</h2>
            
            <!-- Theme Colors -->
            <h3>Theme Colors</h3>
            <table class="form-table">
                <?php
                $theme_colors = [
                    'background' => __('Background', 'guided-journal'),
                    'card_background' => __('Card Background', 'guided-journal'),
                    'text' => __('Text', 'guided-journal'),
                    'accent' => __('Accent', 'guided-journal'),
                    'container_background' => __('Container Background', 'guided-journal'),
                    'completed' => __('Completed State', 'guided-journal')
                ];

                foreach ($theme_colors as $key => $label) {
                    ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($label); ?></th>
                        <td>
                            <div class="color-setting">
                                <input type="color" 
                                       name="guided_journal_colors[<?php echo esc_attr($key); ?>]" 
                                       value="<?php echo esc_attr($colors[$key]); ?>">
                                <input type="text" 
                                       class="color-hex" 
                                       value="<?php echo esc_attr($colors[$key]); ?>">
                                <button type="button" class="button reset-color" 
                                        data-default="<?php echo esc_attr($this->default_colors[$key]); ?>">
                                    <?php _e('Reset', 'guided-journal'); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>

            <!-- Button Colors -->
            <h3>Button Colors</h3>
            <table class="form-table">
                <?php
                $button_colors = [
                    'button_background' => __('Button Background', 'guided-journal'),
                    'button_text' => __('Button Text', 'guided-journal'),
                    'button_hover' => __('Button Hover', 'guided-journal')
                ];

                foreach ($button_colors as $key => $label) {
                    ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($label); ?></th>
                        <td>
                            <div class="color-setting">
                                <input type="color" 
                                       name="guided_journal_colors[<?php echo esc_attr($key); ?>]" 
                                       value="<?php echo esc_attr($colors[$key]); ?>">
                                <input type="text" 
                                       class="color-hex" 
                                       value="<?php echo esc_attr($colors[$key]); ?>">
                                <button type="button" class="button reset-color" 
                                        data-default="<?php echo esc_attr($this->default_colors[$key]); ?>">
                                    <?php _e('Reset', 'guided-journal'); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>

            <!-- Progress Bar Colors -->
            <h3>Progress Bar Colors</h3>
            <table class="form-table">
                <?php
                $progress_colors = [
                    'progress_bar_background' => __('Progress Bar Background', 'guided-journal'),
                    'progress_bar_fill' => __('Progress Bar Fill', 'guided-journal')
                ];

                foreach ($progress_colors as $key => $label) {
                    ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($label); ?></th>
                        <td>
                            <div class="color-setting">
                                <input type="color" 
                                       name="guided_journal_colors[<?php echo esc_attr($key); ?>]" 
                                       value="<?php echo esc_attr($colors[$key]); ?>">
                                <input type="text" 
                                       class="color-hex" 
                                       value="<?php echo esc_attr($colors[$key]); ?>">
                                <button type="button" class="button reset-color" 
                                        data-default="<?php echo esc_attr($this->default_colors[$key]); ?>">
                                    <?php _e('Reset', 'guided-journal'); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
        </div>

        <!-- Typography Section -->
        <div class="settings-section">
            <h2>Typography</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Heading Font</th>
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
                        <p class="description">Font size should include units (px, rem, em)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Body Font</th>
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
            </table>
        </div>

        <?php submit_button(__('Save Settings', 'guided-journal')); ?>
    </form>

    <!-- Demo Mode Section -->
    <?php if (isset($this->demo_mode)): ?>
    <div class="settings-section">
        <?php $this->demo_mode->render_demo_section(); ?>
    </div>
    <?php endif; ?>

    <!-- Reset Options Section -->
    <div class="settings-section">
        <h2><?php _e('Reset Options', 'guided-journal'); ?></h2>
        <p class="description"><?php _e('Use these options with caution. These actions cannot be undone.', 'guided-journal'); ?></p>

        <div class="reset-options">
            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" style="display: inline-block; margin-right: 20px;">
                <?php wp_nonce_field('reset_journal_prompts_nonce', 'reset_prompts_nonce'); ?>
                <input type="hidden" name="action" value="reset_journal_prompts">
                <input type="submit" class="button button-secondary" value="<?php _e('Reset All Prompts', 'guided-journal'); ?>"
                    onclick="return confirm('<?php _e('Are you sure you want to delete all journal prompts? This action cannot be undone.', 'guided-journal'); ?>');">
            </form>

            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" style="display: inline-block;">
                <?php wp_nonce_field('reset_journal_entries_nonce', 'reset_entries_nonce'); ?>
                <input type="hidden" name="action" value="reset_journal_entries">
                <input type="submit" class="button button-secondary" value="<?php _e('Reset All Journal Entries', 'guided-journal'); ?>"
                    onclick="return confirm('<?php _e('Are you sure you want to delete all journal entries? This action cannot be undone.', 'guided-journal'); ?>');">
            </form>
        </div>
    </div>
</div>