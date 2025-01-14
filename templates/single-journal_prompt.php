<?php
/**
 * Template for displaying single journal prompts
 */

// Add debugging for script loading
add_action('wp_head', function () {
    echo "<!-- Debug: Template loaded -->\n";
});

get_header();

// Get the current day number from the post title
$current_day = intval(get_the_title());

// Get total number of prompts
$total_prompts = wp_count_posts('journal_prompt')->publish;

// Debug information
error_log('Loading journal prompt template for day: ' . $current_day);
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
                                <!-- Debug: Display current post type -->
                                <!-- <?php echo "Current post type: " . get_post_type(); ?> -->
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
                                    'quicktags' => true,
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
                                        <!-- Fallback share button that will be replaced by JS -->
                                        <button class="share-entry contents-toggle">
                                            <span class="dashicons dashicons-share"></span>
                                            <?php _e('Share Entry', 'guided-journal'); ?>
                                        </button>
                                    </div>
                                </div>

                                <div class="save-status">
                                    <span class="status-text"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Debug: Script loading verification -->
                        <script>
                            jQuery(document).ready(function ($) {
                                console.log('Debug: Document ready fired');
                                console.log('Share button container exists:', $('.share-button-container').length > 0);
                                console.log('Share button exists:', $('.share-entry').length > 0);
                                console.log('Sharing script loaded:', typeof journalShare !== 'undefined');
                                console.log('Current URL:', window.location.href);
                                console.log('Post type:', '<?php echo get_post_type(); ?>');
                                console.log('Is singular journal_prompt:', '<?php echo is_singular("journal_prompt"); ?>');

                                // Debug event listener
                                $('.share-entry').on('click', function (e) {
                                    console.log('Share button clicked');
                                    console.log('Event:', e);
                                });
                            });
                        </script>

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

<!-- Debug: Display loaded scripts -->
<div style="display:none;" class="debug-info">
    <?php
    global $wp_scripts;
    echo "<!-- Loaded scripts: \n";
    foreach ($wp_scripts->queue as $script) {
        echo $script . "\n";
    }
    echo "-->";
    ?>
</div>
<!-- Script loading debug -->
<div class="debug-info" style="display:none;">
    <?php
    global $wp_scripts;
    echo "<!-- Enqueued scripts:\n";
    foreach ($wp_scripts->queue as $handle) {
        echo "$handle\n";
        $script = $wp_scripts->registered[$handle];
        echo "  Source: " . $script->src . "\n";
    }
    echo "-->";
    ?>
</div>

<script>
    jQuery(document).ready(function ($) {
        console.log('Scripts loaded:', {
            'sharing_script_loaded': typeof journalShare !== 'undefined',
            'jquery_loaded': typeof jQuery !== 'undefined'
        });
    });
</script>
<?php
// Debug: Display footer action hooks
add_action('wp_footer', function () {
    echo "<!-- Debug: Footer loaded -->\n";
}, 1);

get_footer();
?>