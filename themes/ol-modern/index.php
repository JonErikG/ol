<?php
/**
 * Main Template - OL Tipping
 * This is the fallback template that handles routing based on page slug
 */

get_header();

// Get the current page slug
$current_slug = '';
if ( is_page() ) {
    $current_slug = get_post_field( 'post_name', get_post() );
}

// Check if this is the front page
$is_frontpage = is_front_page() || is_home();
?>

<!-- OL Debug: slug=<?php echo esc_html( $current_slug ); ?> is_front=<?php echo $is_frontpage ? 'yes' : 'no'; ?> is_page=<?php echo is_page() ? 'yes' : 'no'; ?> -->

<style>
    .site-main {
        min-height: 50vh;
    }
    
    .page-content {
        background: white;
        border-radius: 16px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .page-title {
        font-size: 2.2rem;
        color: #1a1a2e;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 3px solid;
        border-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%) 1;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .page-title::before {
        content: '🎿';
    }
    
    /* Shortcode content styling */
    .ol-tipping-container,
    .ol-events-container,
    .ol-community-container,
    .ol-leaderboard-container {
        margin-top: 20px;
    }
    
    /* Cards styling */
    .event-card, .tip-card {
        background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
    }
    
    .event-card:hover, .tip-card:hover {
        transform: translateX(5px);
        box-shadow: 0 5px 20px rgba(102,126,234,0.15);
    }
    
    /* Forms */
    input, select, textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        margin-bottom: 15px;
    }
    
    input:focus, select:focus, textarea:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.2);
    }
    
    /* Buttons */
    .btn, button[type="submit"], input[type="submit"] {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 14px 28px;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn:hover, button[type="submit"]:hover, input[type="submit"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102,126,234,0.35);
    }
    
    /* Tables */
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    
    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    tr:hover {
        background: #f8f9ff;
    }
    
    /* Messages */
    .success-message {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .error-message {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    /* Athlete selection */
    .position-selector {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin: 30px 0;
    }
    
    .position-card {
        background: white;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .position-card:hover {
        border-color: #667eea;
    }
    
    .position-card.selected {
        border-color: #28a745;
        background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%);
    }
    
    .position-number {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0 auto 15px;
    }
    
    /* Leaderboard */
    .leaderboard-item {
        display: flex;
        align-items: center;
        padding: 20px;
        background: white;
        border-radius: 12px;
        margin-bottom: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .leaderboard-rank {
        font-size: 1.8rem;
        font-weight: bold;
        color: #667eea;
        width: 60px;
    }
    
    .leaderboard-rank.gold { color: #ffd700; }
    .leaderboard-rank.silver { color: #c0c0c0; }
    .leaderboard-rank.bronze { color: #cd7f32; }
    
    @media (max-width: 768px) {
        .page-content {
            padding: 20px;
        }
        .page-title {
            font-size: 1.5rem;
        }
        .position-selector {
            grid-template-columns: 1fr;
        }
    }
</style>

<main id="main" class="site-main">
    <?php
    if ( have_posts() ) {
        while ( have_posts() ) {
            the_post();
            ?>
            <article class="page-content">
                <h1 class="page-title"><?php the_title(); ?></h1>
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
            <?php
        }
    } else {
        ?>
        <div class="page-content">
            <h1 class="page-title">Velkommen til Tipping Ølømpiske lekker!</h1>
            <p>Bruk menyen ovenfor for å navigere til tippesiden.</p>
            <p><a href="<?php echo home_url('/tipping'); ?>" class="btn">🎿 Start å tippe nå!</a></p>
        </div>
        <?php
    }
    ?>
</main>

<?php
get_footer();
