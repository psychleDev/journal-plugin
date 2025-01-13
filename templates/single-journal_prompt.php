<?php
/**
 * Template for displaying single journal prompts
 */

get_header();
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
                    if (is_user_logged_in()) {
                        echo do_shortcode("[journal_entry]");
                    } else {
                        echo '<p>' . sprintf(
                            __('Please <a href="%s">log in</a> to view and write journal entries.', 'guided-journal'),
                            wp_login_url(get_permalink())
                        ) . '</p>';
                    }
                    ?>
                </div>
            </article>
            <?php
        endwhile;
        ?>
    </main>
</div>

<?php
get_footer();