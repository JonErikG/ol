<?php
/**
 * Template Name: Front Page
 * Front Page Template - Shows the OL frontpage
 */

get_header();
?>

<main id="main" class="site-main front-page">
    <?php
    if ( shortcode_exists( 'ol_frontpage' ) ) {
        echo do_shortcode( '[ol_frontpage]' );
    } else {
        ?>
        <div style="text-align:center; padding:60px; background:white; border-radius:16px; max-width:800px; margin:40px auto;">
            <h1>🎿 Tipping Ølømpiske lekker</h1>
            <p>Tippekonkurransen starter snart!</p>
            <a href="<?php echo home_url( '/tipping' ); ?>" style="display:inline-block; padding:15px 30px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; text-decoration:none; border-radius:25px; margin-top:20px;">Start å tippe</a>
        </div>
        <?php
    }
    ?>
</main>

<?php
get_footer();
