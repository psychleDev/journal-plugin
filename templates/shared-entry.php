<?php
/**
 * Template for displaying shared journal entries
 */

get_header();

$token = get_query_var('share_token');
global $wpdb;
$share_data = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}journal_share_tokens WHERE token = %s",
    $token
));

$entry = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}journal_entries WHERE user_id = %d AND day_number = %d",
    $share_data->user_id,
    $share_data->entry_day
));

$prompt = get_page_by_title($share_data->entry_day, OBJECT, 'journal_prompt');
?>

<div class="container">
    <div class="journal-container shared-entry">
        <div class="shared-entry-header">
            <h2><?php printf(__('Day %d', 'guided-journal'), $share_data->entry_day); ?></h2>
            <div class="shared-info">
                <p class="shared-by">
                    <?php
                    $user = get_userdata($share_data->user_id);
                    printf(
                        __('Shared by %s', 'guided-journal'),
                        esc_html($user->display_name)
                    );
                    ?>
                </p>
            </div>
        </div>

        <div class="prompt"><?php echo wp_kses_post($prompt->post_content); ?></div>
        <div class="entry-content">
            <?php echo wp_kses_post($entry->entry_text); ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>