<?php
/**
 * Template for displaying content
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-content' ); ?>>
    <header class="entry-header">
        <?php
        if ( is_singular() ) {
            the_title( '<h1 class="entry-title">', '</h1>' );
        } else {
            the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
        }
        ?>
    </header>

    <div class="entry-content">
        <?php
        the_content();
        wp_link_pages( array(
            'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ol-modern' ),
            'after'  => '</div>',
        ) );
        ?>
    </div>

    <?php if ( get_edit_post_link() ) : ?>
    <footer class="entry-footer">
        <?php edit_post_link(); ?>
    </footer>
    <?php endif; ?>
</article>
