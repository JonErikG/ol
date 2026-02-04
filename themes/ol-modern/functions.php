<?php
/**
 * Theme Name: OL Modern
 * Theme URI: https://ol-tipping.local
 * Description: Moderne tema for OL tippekonkurranse med parallax og fancy effekter
 * Version: 1.0.0
 * Author: OL Tipping
 * License: GPL2
 * Domain Path: /languages
 * Text Domain: ol-modern
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'OL_MODERN_VERSION', '1.0.0' );
define( 'OL_MODERN_PATH', get_template_directory() );
define( 'OL_MODERN_URI', get_template_directory_uri() );

/**
 * Theme Setup
 */
function ol_modern_setup() {
    load_theme_textdomain( 'ol-modern', OL_MODERN_PATH . '/languages' );
    
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
    add_theme_support( 'comments' );
    
    register_nav_menus( array(
        'primary' => esc_html__( 'Hovedmeny', 'ol-modern' ),
        'footer' => esc_html__( 'Footermeny', 'ol-modern' ),
    ) );
}
add_action( 'after_setup_theme', 'ol_modern_setup' );

/**
 * Create or UPDATE default pages with correct shortcodes
 */
function ol_modern_create_default_pages() {
    $pages = array(
        array(
            'post_title' => 'Hjem',
            'post_name' => 'hjem',
            'post_content' => '[ol_frontpage]',
        ),
        array(
            'post_title' => 'Tippekonkurranse',
            'post_name' => 'tipping',
            'post_content' => '[ol_tipping]',
        ),
        array(
            'post_title' => 'Øvelser',
            'post_name' => 'events',
            'post_content' => '[ol_events_list]',
        ),
        array(
            'post_title' => 'Tippelister',
            'post_name' => 'community-tips',
            'post_content' => '[ol_community_tips]',
        ),
        array(
            'post_title' => 'Resultater',
            'post_name' => 'results',
            'post_content' => '[ol_event_results]',
        ),
        array(
            'post_title' => 'Pultrøyekampen',
            'post_name' => 'leaderboard',
            'post_content' => '[ol_leaderboard]',
        ),
        array(
            'post_title' => 'Logg inn',
            'post_name' => 'logg-inn',
            'post_content' => '[ol_login_form]',
        ),
        array(
            'post_title' => 'Registrering',
            'post_name' => 'registrering',
            'post_content' => '[ol_register_form]',
        ),
        array(
            'post_title' => 'Mistet passord',
            'post_name' => 'mistet-passord',
            'post_content' => '[ol_lost_password_form]',
        ),
    );

    foreach ( $pages as $page ) {
        $existing = get_page_by_path( $page['post_name'] );
        if ( $existing ) {
            // UPDATE existing page with correct shortcode content
            wp_update_post( array(
                'ID' => $existing->ID,
                'post_content' => $page['post_content'],
                'post_title' => $page['post_title'],
            ) );
            
            // Set home page as front page
            if ( $page['post_name'] === 'hjem' ) {
                update_option( 'page_on_front', $existing->ID );
                update_option( 'show_on_front', 'page' );
            }
        } else {
            // Create new page
            $page_id = wp_insert_post( array(
                'post_title' => $page['post_title'],
                'post_name' => $page['post_name'],
                'post_content' => $page['post_content'],
                'post_type' => 'page',
                'post_status' => 'publish',
            ) );
            
            // Set home page as front page
            if ( $page['post_name'] === 'hjem' && $page_id ) {
                update_option( 'page_on_front', $page_id );
                update_option( 'show_on_front', 'page' );
            }
        }
    }
    
    // Flush rewrite rules to ensure URLs work
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'ol_modern_create_default_pages' );

/**
 * Force fix pages - run once via URL parameter
 * Visit: yoursite.com/?ol_fix_pages=1 (as admin)
 */
function ol_modern_fix_pages_on_request() {
    if ( isset( $_GET['ol_fix_pages'] ) && $_GET['ol_fix_pages'] === '1' ) {
        if ( current_user_can( 'manage_options' ) ) {
            ol_modern_create_default_pages();
            wp_die( '✅ OL Tipping sider er oppdatert med riktige shortcodes! <a href="' . home_url() . '">Gå til forsiden</a>' );
        }
    }
}
add_action( 'init', 'ol_modern_fix_pages_on_request', 1 );

// Run once on init to ensure pages exist
function ol_modern_ensure_pages() {
    // Only run if not already done this request
    if ( defined( 'OL_PAGES_CHECKED' ) ) {
        return;
    }
    define( 'OL_PAGES_CHECKED', true );
    
    // Check if pages exist
    $tipping_page = get_page_by_path( 'tipping' );
    if ( ! $tipping_page ) {
        ol_modern_create_default_pages();
    }
}
add_action( 'init', 'ol_modern_ensure_pages', 20 );

/**
 * Always ensure login/registration pages exist and flush rewrites
 */
function ol_modern_ensure_auth_pages() {
    $login_page = get_page_by_path( 'logg-inn' );
    $register_page = get_page_by_path( 'registrering' );
    $lost_page = get_page_by_path( 'mistet-passord' );
    
    if ( ! $login_page || ! $register_page || ! $lost_page ) {
        ol_modern_create_default_pages();
        flush_rewrite_rules();
    }
}
add_action( 'wp_loaded', 'ol_modern_ensure_auth_pages' );

/**
 * Force WordPress to use correct template for each page based on slug
 * This bypasses WordPress page content and uses dedicated templates
 */
function ol_modern_template_include( $template ) {
    // Get current page slug
    $slug = '';
    if ( is_page() ) {
        $slug = get_post_field( 'post_name', get_queried_object_id() );
    }
    
    // Debug comment in HTML
    add_action( 'wp_head', function() use ( $slug ) {
        echo '<!-- OL Modern: slug=' . esc_html( $slug ) . ' is_front=' . ( is_front_page() ? 'yes' : 'no' ) . ' -->' . "\n";
    }, 1 );
    
    // Front page
    if ( is_front_page() || $slug === 'hjem' ) {
        $tpl = get_template_directory() . '/front-page.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }
    
    // Events page
    if ( $slug === 'events' ) {
        $tpl = get_template_directory() . '/page-events.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }
    
    // Tipping page
    if ( $slug === 'tipping' ) {
        $tpl = get_template_directory() . '/page-tipping.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }
    
    // Leaderboard page
    if ( $slug === 'leaderboard' ) {
        $tpl = get_template_directory() . '/page-leaderboard.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }
    
    // Community tips page
    if ( $slug === 'community-tips' ) {
        $tpl = get_template_directory() . '/page-community-tips.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }
    
    // Results page
    if ( $slug === 'results' ) {
        $tpl = get_template_directory() . '/page-results.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }
    
    // Default page template
    if ( is_page() ) {
        $tpl = get_template_directory() . '/page.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }
    
    return $template;
}
add_filter( 'template_include', 'ol_modern_template_include', 99 );

/**
 * Flush rewrite rules on theme switch to ensure URLs work
 */
function ol_modern_flush_rewrites() {
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'ol_modern_flush_rewrites' );

/**
 * Enqueue styles and scripts
 */
function ol_modern_enqueue() {
    wp_enqueue_style( 'ol-modern-style', OL_MODERN_URI . '/assets/css/style.css', array(), OL_MODERN_VERSION );
}
add_action( 'wp_enqueue_scripts', 'ol_modern_enqueue' );

/**
 * Register sidebars
 */
function ol_modern_widgets_init() {
    register_sidebar( array(
        'name'          => esc_html__( 'Primary Sidebar', 'ol-modern' ),
        'id'            => 'primary-sidebar',
        'description'   => esc_html__( 'Main sidebar', 'ol-modern' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'ol_modern_widgets_init' );

/**
 * Default menu if no menu is assigned
 */
function ol_modern_default_menu() {
    $menu_html = '<ul id="primary-menu" class="primary-menu">';
    $menu_html .= '<li><a href="' . esc_url( home_url( '/' ) ) . '">🏠 Hjem</a></li>';
    $menu_html .= '<li><a href="' . esc_url( home_url( '/tipping' ) ) . '">🎿 Tippe</a></li>';
    $menu_html .= '<li><a href="' . esc_url( home_url( '/events' ) ) . '">🏔️ Øvelser</a></li>';
    $menu_html .= '<li><a href="' . esc_url( home_url( '/community-tips' ) ) . '">👥 Tippelister</a></li>';
    $menu_html .= '<li><a href="' . esc_url( home_url( '/leaderboard' ) ) . '">🏆 Pultrøyekampen</a></li>';
    $menu_html .= '<li><a href="' . esc_url( home_url( '/results' ) ) . '">📊 Resultater</a></li>';
    $menu_html .= '<li><a href="' . esc_url( home_url( '/logg-inn' ) ) . '">👤 Logg inn</a></li>';
    $menu_html .= '<li><a href="' . esc_url( home_url( '/registrering' ) ) . '">📝 Registrer deg</a></li>';
    $menu_html .= '</ul>';
    echo $menu_html;
}

/**
 * Block backend for non-admins and hide admin bar
 */
function ol_modern_lock_down_admin() {
    if ( is_admin() && ! current_user_can( 'manage_options' ) && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
        wp_safe_redirect( home_url( '/' ) );
        exit;
    }
}
add_action( 'admin_init', 'ol_modern_lock_down_admin' );

function ol_modern_hide_admin_bar() {
    return false;
}
add_filter( 'show_admin_bar', 'ol_modern_hide_admin_bar' );

/**
 * Reuse logo.jpg as site icon and login logo
 */
function ol_modern_site_icon() {
    $icon_url = esc_url( OL_MODERN_URI . '/assets/images/logo.jpg' );
    echo '<link rel="icon" href="' . $icon_url . '" sizes="any" />';
}
add_action( 'wp_head', 'ol_modern_site_icon' );
add_action( 'login_head', 'ol_modern_site_icon' );

function ol_modern_login_logo() {
    $logo_url = esc_url( OL_MODERN_URI . '/assets/images/logo.jpg' );
    echo '<style>
        .login h1 a { background-image: url(' . $logo_url . '); background-size: contain; width: 180px; height: 180px; }
    </style>';
}
add_action( 'login_enqueue_scripts', 'ol_modern_login_logo' );

/**
 * Frontend login form shortcode
 */
function ol_modern_login_form() {
    if ( is_user_logged_in() ) {
        return '<div class="ol-auth-message">Du er allerede logget inn. <a href="' . esc_url( home_url( '/tipping' ) ) . '">Gå til tipping</a></div>';
    }

    $errors = array();
    $redirect_url = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/tipping' );

    if ( isset( $_POST['ol_login_nonce'] ) && wp_verify_nonce( $_POST['ol_login_nonce'], 'ol_login' ) ) {
        $raw_user = isset( $_POST['ol_login_user'] ) ? wp_unslash( $_POST['ol_login_user'] ) : '';
        $is_email = strpos( $raw_user, '@' ) !== false;
        $user_login = $is_email ? sanitize_email( $raw_user ) : sanitize_user( $raw_user );

        $creds = array(
            'user_login' => $user_login,
            'user_password' => isset( $_POST['ol_login_pass'] ) ? wp_unslash( $_POST['ol_login_pass'] ) : '',
            'remember' => true,
        );

        $user = wp_signon( $creds, false );
        if ( is_wp_error( $user ) ) {
            $errors[] = $user->get_error_message();
        } else {
            if ( isset( $_POST['ol_login_redirect'] ) ) {
                $redirect_url = esc_url_raw( wp_unslash( $_POST['ol_login_redirect'] ) );
            }
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }

    ob_start();
    $logo_url = esc_url( OL_MODERN_URI . '/assets/images/logo.jpg' );
    ?>
    <style>
        .ol-auth-wrapper { max-width: 520px; margin: 0 auto; background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); text-align: center; }
        .ol-auth-logo { width: 120px; height: auto; margin-bottom: 20px; }
        .ol-auth-title { font-size: 28px; margin-bottom: 10px; }
        .ol-auth-fields { display: grid; gap: 15px; text-align: left; }
        .ol-auth-fields label { font-weight: 600; color: #333; }
        .ol-auth-fields input { width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid #ddd; }
        .ol-auth-submit { margin-top: 10px; width: 100%; padding: 14px; border: 0; border-radius: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 700; cursor: pointer; }
        .ol-auth-links { margin-top: 15px; color: #666; }
        .ol-auth-error { background: #ffe8e8; border: 1px solid #f5b5b5; color: #b10f0f; padding: 12px; border-radius: 8px; margin-bottom: 15px; text-align: left; }
    </style>
    <div class="ol-auth-wrapper">
        <img src="<?php echo $logo_url; ?>" alt="Logo" class="ol-auth-logo" />
        <h2 class="ol-auth-title">Logg inn</h2>
        <?php foreach ( $errors as $error ) : ?>
            <div class="ol-auth-error"><?php echo wp_kses_post( $error ); ?></div>
        <?php endforeach; ?>
        <form method="post">
            <div class="ol-auth-fields">
                <label for="ol_login_user">Brukernavn</label>
                <input type="text" id="ol_login_user" name="ol_login_user" required />

                <label for="ol_login_pass">Passord</label>
                <input type="password" id="ol_login_pass" name="ol_login_pass" required />
            </div>
            <?php wp_nonce_field( 'ol_login', 'ol_login_nonce' ); ?>
            <input type="hidden" name="ol_login_redirect" value="<?php echo esc_attr( $redirect_url ); ?>" />
            <button type="submit" class="ol-auth-submit">Logg inn</button>
        </form>
        <div class="ol-auth-links">
            <p><a href="<?php echo esc_url( home_url( '/mistet-passord' ) ); ?>">Mistet passord?</a></p>
            <p>Ingen bruker? <a href="<?php echo esc_url( home_url( '/registrering' ) ); ?>">Registrer deg her</a></p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'ol_login_form', 'ol_modern_login_form' );

/**
 * Frontend lost password form shortcode
 */
function ol_modern_lost_password_form() {
    if ( is_user_logged_in() ) {
        return '<div class="ol-auth-message">Du er allerede logget inn. <a href="' . esc_url( home_url( '/tipping' ) ) . '">Gå til tipping</a></div>';
    }

    $errors = array();
    $success_message = '';

    if ( isset( $_POST['ol_lost_password_nonce'] ) && wp_verify_nonce( $_POST['ol_lost_password_nonce'], 'ol_lost_password' ) ) {
        $user_login = isset( $_POST['ol_lost_user'] ) ? sanitize_text_field( wp_unslash( $_POST['ol_lost_user'] ) ) : '';

        if ( empty( $user_login ) ) {
            $errors[] = 'Skriv inn brukernavn eller e-post.';
        } else {
            $result = retrieve_password( $user_login );
            if ( is_wp_error( $result ) ) {
                $errors[] = $result->get_error_message();
            } else {
                $success_message = 'Hvis brukeren finnes, er en e-post med reset-lenke sendt.';
            }
        }
    }

    ob_start();
    $logo_url = esc_url( OL_MODERN_URI . '/assets/images/logo.jpg' );
    ?>
    <style>
        .ol-auth-wrapper { max-width: 520px; margin: 0 auto; background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); text-align: center; }
        .ol-auth-logo { width: 120px; height: auto; margin-bottom: 20px; }
        .ol-auth-title { font-size: 28px; margin-bottom: 10px; }
        .ol-auth-fields { display: grid; gap: 15px; text-align: left; }
        .ol-auth-fields label { font-weight: 600; color: #333; }
        .ol-auth-fields input { width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid #ddd; }
        .ol-auth-submit { margin-top: 10px; width: 100%; padding: 14px; border: 0; border-radius: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 700; cursor: pointer; }
        .ol-auth-links { margin-top: 15px; color: #666; }
        .ol-auth-error { background: #ffe8e8; border: 1px solid #f5b5b5; color: #b10f0f; padding: 12px; border-radius: 8px; margin-bottom: 15px; text-align: left; }
        .ol-auth-success { background: #e8f8ee; border: 1px solid #9dd8b3; color: #0f6b2f; padding: 12px; border-radius: 8px; margin-bottom: 15px; text-align: left; }
    </style>
    <div class="ol-auth-wrapper">
        <img src="<?php echo $logo_url; ?>" alt="Logo" class="ol-auth-logo" />
        <h2 class="ol-auth-title">Mistet passord</h2>
        <?php if ( $success_message ) : ?>
            <div class="ol-auth-success"><?php echo esc_html( $success_message ); ?></div>
        <?php endif; ?>
        <?php foreach ( $errors as $error ) : ?>
            <div class="ol-auth-error"><?php echo wp_kses_post( $error ); ?></div>
        <?php endforeach; ?>
        <form method="post">
            <div class="ol-auth-fields">
                <label for="ol_lost_user">Brukernavn eller e-post</label>
                <input type="text" id="ol_lost_user" name="ol_lost_user" required />
            </div>
            <?php wp_nonce_field( 'ol_lost_password', 'ol_lost_password_nonce' ); ?>
            <button type="submit" class="ol-auth-submit">Send reset-lenke</button>
        </form>
        <div class="ol-auth-links">
            <p>Har du allerede tilgang? <a href="<?php echo esc_url( home_url( '/logg-inn' ) ); ?>">Logg inn</a></p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'ol_lost_password_form', 'ol_modern_lost_password_form' );

/**
 * Frontend registration form shortcode
 */
function ol_modern_register_form() {
    if ( is_user_logged_in() ) {
        return '<div class="ol-auth-message">Du er allerede registrert. <a href="' . esc_url( home_url( '/tipping' ) ) . '">Gå til tipping</a></div>';
    }

    $errors = array();
    $redirect_url = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/tipping' );

    if ( isset( $_POST['ol_register_nonce'] ) && wp_verify_nonce( $_POST['ol_register_nonce'], 'ol_register' ) ) {
        $username = sanitize_user( wp_unslash( $_POST['ol_reg_user'] ) );
        $display_name = sanitize_text_field( wp_unslash( $_POST['ol_reg_name'] ?? '' ) );
        $email    = sanitize_email( wp_unslash( $_POST['ol_reg_email'] ) );
        $pass     = isset( $_POST['ol_reg_pass'] ) ? wp_unslash( $_POST['ol_reg_pass'] ) : '';
        $confirm  = isset( $_POST['ol_reg_pass_confirm'] ) ? wp_unslash( $_POST['ol_reg_pass_confirm'] ) : '';

        if ( empty( $username ) || empty( $display_name ) || empty( $email ) || empty( $pass ) ) {
            $errors[] = 'Alle felter er påkrevd.';
        }
        if ( $pass !== $confirm ) {
            $errors[] = 'Passordene matcher ikke.';
        }
        if ( username_exists( $username ) ) {
            $errors[] = 'Brukernavnet er allerede i bruk.';
        }
        if ( email_exists( $email ) ) {
            $errors[] = 'E-post er allerede i bruk.';
        }
        if ( $display_name ) {
            global $wpdb;
            $existing_display = $wpdb->get_var( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->users} WHERE display_name = %s",
                $display_name
            ) );
            if ( $existing_display ) {
                $errors[] = 'Navnet er allerede i bruk.';
            }
        }

        if ( empty( $errors ) ) {
            $user_id = wp_create_user( $username, $pass, $email );
            if ( is_wp_error( $user_id ) ) {
                $errors[] = $user_id->get_error_message();
            } else {
                // Set display name to the provided name
                wp_update_user( array(
                    'ID' => $user_id,
                    'display_name' => $display_name,
                ) );
                wp_set_auth_cookie( $user_id );
                if ( isset( $_POST['ol_register_redirect'] ) ) {
                    $redirect_url = esc_url_raw( wp_unslash( $_POST['ol_register_redirect'] ) );
                }
                wp_safe_redirect( $redirect_url );
                exit;
            }
        }
    }

    ob_start();
    $logo_url = esc_url( OL_MODERN_URI . '/assets/images/logo.jpg' );
    ?>
    <style>
        .ol-auth-wrapper { max-width: 520px; margin: 0 auto; background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); text-align: center; }
        .ol-auth-logo { width: 120px; height: auto; margin-bottom: 20px; }
        .ol-auth-title { font-size: 28px; margin-bottom: 10px; }
        .ol-auth-fields { display: grid; gap: 15px; text-align: left; }
        .ol-auth-fields label { font-weight: 600; color: #333; }
        .ol-auth-fields input { width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid #ddd; }
        .ol-auth-submit { margin-top: 10px; width: 100%; padding: 14px; border: 0; border-radius: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 700; cursor: pointer; }
        .ol-auth-links { margin-top: 15px; color: #666; }
        .ol-auth-error { background: #ffe8e8; border: 1px solid #f5b5b5; color: #b10f0f; padding: 12px; border-radius: 8px; margin-bottom: 15px; text-align: left; }
    </style>
    <div class="ol-auth-wrapper">
        <img src="<?php echo $logo_url; ?>" alt="Logo" class="ol-auth-logo" />
        <h2 class="ol-auth-title">Registrer deg</h2>
        <?php foreach ( $errors as $error ) : ?>
            <div class="ol-auth-error"><?php echo wp_kses_post( $error ); ?></div>
        <?php endforeach; ?>
        <form method="post">
            <div class="ol-auth-fields">
                <label for="ol_reg_user">Brukernavn</label>
                <input type="text" id="ol_reg_user" name="ol_reg_user" required />

                <label for="ol_reg_name">Navn</label>
                <input type="text" id="ol_reg_name" name="ol_reg_name" required />

                <label for="ol_reg_email">E-post</label>
                <input type="email" id="ol_reg_email" name="ol_reg_email" required />

                <label for="ol_reg_pass">Passord</label>
                <input type="password" id="ol_reg_pass" name="ol_reg_pass" required />

                <label for="ol_reg_pass_confirm">Bekreft passord</label>
                <input type="password" id="ol_reg_pass_confirm" name="ol_reg_pass_confirm" required />
            </div>
            <?php wp_nonce_field( 'ol_register', 'ol_register_nonce' ); ?>
            <input type="hidden" name="ol_register_redirect" value="<?php echo esc_attr( $redirect_url ); ?>" />
            <button type="submit" class="ol-auth-submit">Registrer deg</button>
        </form>
        <div class="ol-auth-links">
            <p>Har du allerede en konto? <a href="<?php echo esc_url( home_url( '/logg-inn' ) ); ?>">Logg inn her</a></p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'ol_register_form', 'ol_modern_register_form' );

/**
 * Admin notice to setup OL Tipping
 */
function ol_modern_admin_setup_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // Check if setup is needed
    $tipping_page = get_page_by_path( 'tipping' );
    if ( $tipping_page ) {
        return;
    }
    
    // Check for setup action
    if ( isset( $_GET['ol_setup'] ) && $_GET['ol_setup'] === '1' ) {
        ol_modern_create_default_pages();
        
        // Also trigger plugin data seeding
        if ( class_exists( 'OL_Tipping_Database' ) ) {
            $db = OL_Tipping_Database::get_instance();
            $db->create_tables();
            $db->seed_default_data();
        }
        
        echo '<div class="notice notice-success"><p>✅ OL Tipping er nå satt opp! <a href="' . home_url() . '">Gå til forsiden</a></p></div>';
        return;
    }
    
    echo '<div class="notice notice-warning"><p>🎿 <strong>OL Tipping:</strong> Sidene er ikke opprettet ennå. <a href="' . admin_url( '?ol_setup=1' ) . '" class="button button-primary">Sett opp nå</a></p></div>';
}
add_action( 'admin_notices', 'ol_modern_admin_setup_notice' );