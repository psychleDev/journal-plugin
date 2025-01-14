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
                <tr>
                    <th scope="row">Background</th>
                    <td>
                        <div class="color-setting">
                            <input type="color" 
                                   name="guided_journal_colors[background]" 
                                   value="<?php echo esc_attr($colors['background']); ?>">
                            <input type="text" 
                                   class="color-hex" 
                                   value="<?php echo esc_attr($colors['background']); ?>">
                            <button type="button" class="button reset-color">Reset</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Card Background</th>
                    <td>
                        <div class="color-setting">
                            <input type="color" 
                                   name="guided_journal_colors[card_background]" 
                                   value="<?php echo esc_attr($colors['card_background']); ?>">
                            <input type="text" 
                                   class="color-hex" 
                                   value="<?php echo esc_attr($colors['card_background']); ?>">
                            <button type="button" class="button reset-color">Reset</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Text</th>
                    <td>
                        <div class="color-setting">
                            <input type="color" 
                                   name="guided_journal_colors[text]" 
                                   value="<?php echo esc_attr($colors['text']); ?>">
                            <input type="text" 
                                   class="color-hex" 
                                   value="<?php echo esc_attr($colors['text']); ?>">
                            <button type="button" class="button reset-color">Reset</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Accent</th>
                    <td>
                        <div class="color-setting">
                            <input type="color" 
                                   name="guided_journal_colors[accent]" 
                                   value="<?php echo esc_attr($colors['accent']); ?>">
                            <input type="text" 
                                   class="color-hex" 
                                   value="<?php echo esc_attr($colors['accent']); ?>">
                            <button type="button" class="button reset-color">Reset</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Container Background</th>
                    <td>
                        <div class="color-setting">
                            <input type="color" 
                                   name="guided_journal_colors[container_background]" 
                                   value="<?php echo esc_attr($colors['container_background']); ?>">
                            <input type="text" 
                                   class="color-hex" 
                                   value="<?php echo esc_attr($colors['container_background']); ?>">
                            <button type="button" class="button reset-color">Reset</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Completed State</th>
                    <td>
                        <div class="color-setting">
                            <input type="color" 
                                   name="guided_journal_colors[completed]" 
                                   value="<?php echo esc_attr($colors['completed']); ?>">
                            <input type="text" 
                                   class="color-hex" 
                                   value="<?php echo esc_attr($colors['completed']); ?>">
                            <button type="button" class="button reset-color">Reset</button>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Button Colors -->
            <h3>Button Colors</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Button Background</th>
                    <td>
                        <div class="color-setting">
                            <input type="color" 
                                   name="guided_journal_colors[button_background]" 
                                   value="<?php echo esc_attr($colors['button_background']); ?>">
                            <input type="text" 
                                   class="color-hex" 
                                   value="<?php echo esc_attr($colors['button_background']); ?>">
                            <button type="button" class="button reset-color">Reset</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Button Text</th>
                    <td>
                        <div class="color-setting">
                            <input type="color" 
                                   name="guided_journal_colors[button_text]" 
                                   value="<?php echo esc_attr($colors['button_text']); ?>">
                            <input type="text" 
                                   class="color-hex" 
                                   value="<?php echo esc_attr($colors['button_text']); ?>">
                            <button type="button" class="button reset-color">Reset</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Button Hover</th>
                    <td>
                        <div class="color-setting">
                            <input type="color" 
                                   name="guided_journal_colors[button_hover]" 
                                   value="<?php echo esc_attr($colors['button_hover']); ?>">
                            <input type="text" 
                                   class="color-hex" 
                                   value="<?php echo esc_attr($colors['button_hover']); ?>">
                            <button type="button" class="button reset-color">Reset</button>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Progress Bar Colors -->
            <h3>Progress Bar Colors</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Progress Bar Background</th>
                    <td>
                        <div class="color-setting">
                            <input type="color" 
                                   name="guided_journal_colors[progress_bar_background]" 
                                   value="<?php echo esc_attr($colors['progress_bar_background']); ?>">
                            <input type="text" 
                                   class="color-hex" 
                                   value="<?php echo esc_attr($colors['progress_bar_background']); ?>">
                            <button type="button" class="button reset-color">Reset</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Progress Bar Fill</th>
                    <td>
                        <div class="color-setting">
                            <input type="color" 
                                   name="guided_journal_colors[progress_bar_fill]" 
                                   value="<?php echo esc_attr($colors['progress_bar_fill']); ?>">
                            <input type="text" 
                                   class="color-hex" 
                                   value="<?php echo esc_attr($colors['progress_bar_fill']); ?>">
                            <button type="button" class="button reset-color">Reset</button>
                        </div>
                    </td>
                </tr>
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
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" style="display: inline-block; margin-right: 20px;">
                <?php wp_nonce_field('reset_journal_prompts_nonce', 'reset_prompts_nonce'); ?>
                <input type="hidden" name="action" value="reset_journal_prompts">
                <input type="submit" class="button button-secondary" value="<?php esc_attr_e('Reset All Prompts', 'guided-journal'); ?>"
                    onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete all journal prompts? This action cannot be undone.', 'guided-journal'); ?>');">
            </form>

            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" style="display: inline-block;">
                <?php wp_nonce_field('reset_journal_entries_nonce', 'reset_entries_nonce'); ?>
                <input type="hidden" name="action" value="reset_journal_entries">
                <input type="submit" class="button button-secondary" value="<?php esc_attr_e('Reset All Journal Entries', 'guided-journal'); ?>"
                    onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete all journal entries? This action cannot be undone.', 'guided-journal'); ?>');">
            </form>
        </div>
    </div>
</div>