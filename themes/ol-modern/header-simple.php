<?php
/**
 * Header Template
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <header class="site-header">
        <h1><a href="<?php echo home_url(); ?>">
            <img src="<?php echo esc_url( OL_MODERN_URI . '/assets/images/logo.jpg' ); ?>" alt="Tipping Ølømpiske lekker" style="height:40px; width:40px; object-fit:contain; vertical-align:middle;" />
        </a></h1>
        <nav>
            <?php wp_nav_menu( array( 'theme_location' => 'primary', 'fallback_cb' => 'ol_modern_default_menu' ) ); ?>
        </nav>
    </header>
    <main class="site-main">
