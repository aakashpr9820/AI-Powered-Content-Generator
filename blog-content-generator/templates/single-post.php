<?php
/**
 * The template for displaying single post pages for atdw feeds
 *
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div id="primary" class="site-main bcg-posts-wrapper">
    
    <?php
    while (have_posts()) :
        the_post();
        ?>

        <article id="atdw-post-<?php the_ID(); ?>" class="bcg-post-inner-wrapper">

            <header class="atdw-entry-header">
                <?php the_title( '<h1 class="bcg-post-title">', '</h1>' ); ?>
                <?php the_post_thumbnail('medium', 'thumbnail-class'); ?>
            </header><!-- .atdw-entry-header -->

            <div class="atdw-entry-content">
                <?php
                the_content();

                wp_link_pages(
                    array(
                        'before'   => '<nav class="page-links" aria-label="' . esc_attr__( 'Page', 'atdw-feeds' ) . '">',
                        'after'    => '</nav>',
                        'pagelink' => esc_html__( 'Page %', 'atdw-feeds' ),
                    )
                );
                ?>
            </div><!-- .atdw-entry-content -->

            <?php
                // If comments are open or there is at least one comment, load up the comment template.
                if ( comments_open() || get_comments_number() ) {
                    comments_template();
                }
            ?>

            <?php 
            /**
             * Pagination
            */
           // Previous/next post navigation.
            $twentytwentyone_next = is_rtl() ? twenty_twenty_one_get_icon_svg( 'ui', 'arrow_left' ) : twenty_twenty_one_get_icon_svg( 'ui', 'arrow_right' );
            $twentytwentyone_prev = is_rtl() ? twenty_twenty_one_get_icon_svg( 'ui', 'arrow_right' ) : twenty_twenty_one_get_icon_svg( 'ui', 'arrow_left' );

            $twentytwentyone_next_label     = esc_html__( 'Next post', 'twentytwentyone' );
            $twentytwentyone_previous_label = esc_html__( 'Previous post', 'twentytwentyone' );

            the_post_navigation(
                array(
                    'next_text' => '<p class="meta-nav">' . $twentytwentyone_next_label . $twentytwentyone_next . '</p><p class="post-title">%title</p>',
                    'prev_text' => '<p class="meta-nav">' . $twentytwentyone_prev . $twentytwentyone_previous_label . '</p><p class="post-title">%title</p>',
                )
            );
            ?>

        </article>

    <?php endwhile; ?>

</div>

<?php get_footer();