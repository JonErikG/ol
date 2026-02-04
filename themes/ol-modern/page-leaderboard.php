<?php
/**
 * Template Name: Leaderboard Page
 * Template for /leaderboard/ - Shows leaderboard
 */

get_header();
?>

<main id="main" class="site-main">
    <div class="page-wrapper" style="max-width: 1400px; margin: 0 auto; padding: 40px 20px;">
        <?php
        if ( shortcode_exists( 'ol_leaderboard' ) ) {
            echo do_shortcode( '[ol_leaderboard]' );
        } else {
            echo '<div style="text-align:center; padding:60px; background:white; border-radius:16px;"><h2>⚠️ Plugin ikke aktivert</h2><p>OL Tipping plugin må være aktivert.</p></div>';
        }
        ?>
    </div>
</main>

<?php
get_footer();
