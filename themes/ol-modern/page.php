<?php
/**
 * Page Template - Handles ALL WordPress pages including front page
 * Each page's shortcode content is rendered based on the actual page content
 */

get_header();

// Get current page info
$current_slug = get_post_field( 'post_name', get_post() );
$page_id = get_the_ID();
$is_front = is_front_page();
?>

<!-- OL Template Debug: slug=<?php echo esc_html( $current_slug ); ?> id=<?php echo intval( $page_id ); ?> is_front=<?php echo $is_front ? 'yes' : 'no'; ?> -->

<style>
.page-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
}
.page-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 50px rgba(0,0,0,0.1);
    min-height: 400px;
}
.page-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 3px solid;
    border-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%) 1;
}
.page-header h1 {
    font-size: 2.5rem;
    color: #1a1a2e;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 15px;
}
.shortcode-content {
    /* Shortcodes render their own styling */
}
</style>

<main id="main" class="site-main">
    <div class="page-wrapper">
        <?php
        while ( have_posts() ) {
            the_post();
            
            // Get the raw post content
            $content = get_the_content();
            
            // Debug: What shortcode is in this page's content?
            // echo '<!-- Page content preview: ' . esc_html( substr( $content, 0, 50 ) ) . ' -->';
            
            // Check which shortcode this page contains and render it
            if ( has_shortcode( $content, 'ol_frontpage' ) && is_front_page() ) {
                // Front page - render the frontpage shortcode
                echo '<div class="shortcode-content">';
                the_content();
                echo '</div>';
            } elseif ( has_shortcode( $content, 'ol_frontpage' ) && ! is_front_page() ) {
                // This page has frontpage shortcode but we're not on front page
                // Show nothing or redirect - this shouldn't happen normally
                echo '<div class="page-card"><p>Denne siden er kun for forsiden.</p></div>';
            } elseif ( 
                has_shortcode( $content, 'ol_tipping' ) ||
                has_shortcode( $content, 'ol_events_list' ) ||
                has_shortcode( $content, 'ol_community_tips' ) ||
                has_shortcode( $content, 'ol_leaderboard' ) ||
                has_shortcode( $content, 'ol_event_results' ) ||
                has_shortcode( $content, 'ol_login_form' ) ||
                has_shortcode( $content, 'ol_register_form' )
            ) {
                // Regular OL shortcode page - render content with shortcode
                echo '<div class="shortcode-content">';
                the_content();
                echo '</div>';
            } elseif ( $current_slug === 'logg-inn' ) {
                echo '<div class="shortcode-content">' . do_shortcode( '[ol_login_form]' ) . '</div>';
            } elseif ( $current_slug === 'registrering' ) {
                echo '<div class="shortcode-content">' . do_shortcode( '[ol_register_form]' ) . '</div>';
            } else {
                // Regular page without OL shortcodes
                ?>
                <div class="page-card">
                    <div class="page-header">
                        <h1><?php the_title(); ?></h1>
                    </div>
                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>
</main>

<?php
get_footer();
