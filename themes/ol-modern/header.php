<?php
/**
 * Header Template - OL Tipping Milano-Cortina 2026
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php wp_head(); ?>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --header-bg: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            --text-light: #ffffff;
            --text-muted: #b8b8d1;
            --accent-gold: #ffd700;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .site-header {
            background: var(--header-bg);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .header-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
        }
        
        .site-branding {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .site-title {
            font-size: 1.8rem;
            margin: 0;
        }
        
        .site-title a {
            color: var(--text-light);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .site-logo {
            width: 92px;
            height: 92px;
            object-fit: contain;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            white-space: nowrap;
            border: 0;
        }
        
        .site-title a:hover {
            color: var(--accent-gold);
        }
        
        .primary-navigation ul {
            display: flex;
            list-style: none;
            gap: 5px;
            margin: 0;
            padding: 0;
        }
        
        .primary-navigation li a {
            color: var(--text-muted);
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: block;
            font-weight: 500;
        }
        
        .primary-navigation li a:hover {
            color: var(--text-light);
            background: rgba(255,255,255,0.1);
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .menu-toggle {
            display: none;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--text-light);
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
        }

        .header-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-welcome {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .btn-login, .btn-logout {
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-login {
            background: var(--primary-gradient);
            color: white;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.1);
            color: var(--text-light);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .btn-login:hover, .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        
        .hero-section {
            background: var(--primary-gradient);
            padding: 80px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="50" font-size="50" opacity="0.1">🎿</text></svg>') repeat;
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 3rem;
            color: white;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-subtitle {
            font-size: 1.4rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 30px;
        }
        
        .hero-countdown {
            display: inline-block;
            background: rgba(0,0,0,0.3);
            padding: 15px 30px;
            border-radius: 50px;
            color: white;
            font-size: 1.1rem;
        }
        
        .site-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 30px;
        }
        
        @media (max-width: 900px) {
            .header-inner {
                flex-direction: row;
                gap: 10px;
                padding: 12px 15px;
            }
            .menu-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .header-menu {
                display: none;
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 10px 0;
            }
            .site-header.menu-open .header-menu {
                display: flex;
            }
            .primary-navigation ul {
                flex-direction: column;
                gap: 8px;
            }
            .primary-navigation li a {
                padding: 10px 12px;
            }
            .header-actions {
                width: 100%;
                justify-content: flex-start;
            }
            .hero-title { font-size: 2rem; }
            .hero-subtitle { font-size: 1.1rem; }
        }
    </style>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <div id="page" class="site">
        <header class="site-header" role="banner">
            <div class="header-inner">
                <div class="site-branding">
                    <h1 class="site-title">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                            <img src="<?php echo esc_url( OL_MODERN_URI . '/assets/images/logo.jpg' ); ?>" alt="Logo" class="site-logo" />
                            <span class="sr-only">Tipping Ølømpiske lekker</span>
                        </a>
                    </h1>
                </div>

                <button class="menu-toggle" aria-expanded="false" aria-controls="primary-menu">
                    ☰ Meny
                </button>

                <div class="header-menu">
                    <nav class="primary-navigation" role="navigation" id="primary-menu">
                        <?php wp_nav_menu( array( 'theme_location' => 'primary', 'fallback_cb' => 'ol_modern_default_menu' ) ); ?>
                    </nav>
                    
                    <div class="header-actions">
                        <?php if ( is_user_logged_in() ) : 
                            $current_user = wp_get_current_user();
                        ?>
                            <span class="user-welcome">Hei, <?php echo esc_html( $current_user->display_name ); ?>!</span>
                            <a href="<?php echo wp_logout_url( home_url() ); ?>" class="btn-logout">Logg ut</a>
                        <?php else : ?>
                            <a href="<?php echo esc_url( home_url( '/logg-inn' ) ); ?>" class="btn-login">Logg inn</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var header = document.querySelector('.site-header');
                var toggle = document.querySelector('.menu-toggle');
                if (!header || !toggle) return;

                toggle.addEventListener('click', function () {
                    var isOpen = header.classList.toggle('menu-open');
                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });
            });
        </script>
        
        <div id="content" class="site-content">
