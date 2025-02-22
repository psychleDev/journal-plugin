<?php
/**
 * Template for displaying single journal prompts
 */

get_header();

// Get the current day number from the post title
$current_day = intval(get_the_title());

// Get total number of prompts
$total_prompts = wp_count_posts('journal_prompt')->publish;

?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <?php
        while (have_posts()):
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="entry-content">
                    <?php
                    if (is_user_logged_in()):
                        ?>
                        <div class="container">
                            <div class="navigation-top">
                                <a href="/grid" class="contents-toggle">
                                    <?php _e('Back to Grid', 'guided-journal'); ?>
                                </a>
                            </div>

                            <div class="journal-container">
                                <h2><?php printf(__('Day %d', 'guided-journal'), $current_day); ?></h2>

                                <div class="prompt"><?php the_content(); ?></div>

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
                                    'quicktags' => true
                                );

                                // Get existing entry content
                                global $wpdb;
                                $entry_content = $wpdb->get_var($wpdb->prepare(
                                    "SELECT entry_text FROM {$wpdb->prefix}journal_entries 
                                     WHERE user_id = %d AND day_number = %d",
                                    get_current_user_id(),
                                    $current_day
                                ));

                                wp_editor($entry_content, 'journal-entry', $editor_settings);
                                ?>

                                <div class="navigation">
                                    <button class="prev-day" <?php echo ($current_day <= 1) ? 'disabled' : ''; ?>>
                                        <?php _e('Previous Day', 'guided-journal'); ?>
                                    </button>

                                    <button class="save-entry">
                                        <?php _e('Save Entry', 'guided-journal'); ?>
                                    </button>

                                    <button class="next-day" <?php echo ($current_day >= $total_prompts) ? 'disabled' : ''; ?>>
                                        <?php _e('Next Day', 'guided-journal'); ?>
                                    </button>

                                    <div class="share-button-container">
                                        <button class="share-entry contents-toggle">
                                            <span class="dashicons dashicons-share"></span>
                                            <?php _e('Share Entry', 'guided-journal'); ?>
                                        </button>
                                        <div class="share-popup" style="display: none;">
                                            <div class="share-content">
                                                <h3><?php _e('Share Entry', 'guided-journal'); ?></h3>
                                                <div class="share-link-container">
                                                    <input type="text" class="share-link" readonly>
                                                    <button class="copy-link contents-toggle">
                                                        <span class="dashicons dashicons-clipboard"></span>
                                                        <?php _e('Copy', 'guided-journal'); ?>
                                                    </button>
                                                </div>
                                                <div class="share-options">
                                                    <button class="email-share contents-toggle">
                                                        <span class="dashicons dashicons-email"></span>
                                                        <?php _e('Email', 'guided-journal'); ?>
                                                    </button>
                                                    <button class="twitter-share contents-toggle">
                                                        <span class="dashicons dashicons-twitter"></span>
                                                        <?php _e('Twitter', 'guided-journal'); ?>
                                                    </button>
                                                </div>
                                                <div class="share-info">
                                                    <?php _e('Link expires in 24 hours and can be viewed up to 3 times', 'guided-journal'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="save-status">
                                    <span class="status-text"></span>
                                </div>
                            </div>
                        </div>
                        <?php
                    else:
                        echo '<p>' . sprintf(
                            __('Please <a href="%s">log in</a> to view and write journal entries.', 'guided-journal'),
                            wp_login_url(get_permalink())
                        ) . '</p>';
                    endif;
                    ?>
                </div>
            </article>
            <?php
        endwhile;
        ?>
    </main>
</div>

<?php get_footer(); ?>