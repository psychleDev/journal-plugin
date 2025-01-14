<?php
/**
 * Template for displaying share link errors
 */

get_header();

$error = get_query_var('share_error');
?>

<div class="container">
    <div class="journal-container share-error">
        <h2><?php _e('Share Link Error', 'guided-journal'); ?></h2>
        <div class="error-message">
            <?php echo esc_html($error->get_error_message()); ?>
        </div>
        <p class="error-help">
            <?php _e('Please request a new share link from the journal entry owner.', 'guided-journal'); ?>
        </p>
    </div>
</div>

<?php get_footer(); ?>