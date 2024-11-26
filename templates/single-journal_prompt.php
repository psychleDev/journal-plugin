<?php
/**
 * Template for displaying single journal prompts
 */

get_header();
 ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <?php
        while (have_posts()) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <!-- <?php the_title('<h1 class="entry-title">', '</h1>'); ?> -->
                </header>

                <div class="entry-content">
                    <?php
                    // the_content();

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'guided-journal'),
                        'after'  => '</div>',
                    ));
                    ?>

                    <?php echo do_shortcode("[journal_entry]"); ?>
                </div>

                <footer class="entry-footer">
                    <?php
                    // Add any custom meta information here
                    ?>
                </footer>
            </article>

            <?php
            // If comments are open or we have at least one comment, load up the comment template.
            if (comments_open() || get_comments_number()) :
                comments_template();
            endif;

        endwhile; // End of the loop.
        ?>

    </main>
</div>

<?php
get_sidebar();
get_footer();
