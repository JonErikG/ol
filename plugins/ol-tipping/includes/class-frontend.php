<?php
/**
 * Frontend Class - Handles frontend display
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'OL_Tipping_Frontend' ) ) {

class OL_Tipping_Frontend {
    private static $instance = null;
    private $db;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->db = OL_Tipping_Database::get_instance();
        
        // Auto-seed data if database is empty
        $this->maybe_seed_data();
        
        $this->init();
    }
    
    /**
     * Automatically seed data if database is empty
     */
    private function maybe_seed_data() {
        global $wpdb;
        
        // Check if all required tables exist
        $required_tables = array(
            'ol_events',
            'ol_athletes',
            'ol_countries',
            'ol_tips',
            'ol_results',
            'ol_leaderboard',
        );
        
        $missing_table = false;
        foreach ( $required_tables as $table ) {
            $table_exists = $wpdb->get_var( $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $wpdb->prefix . $table
            ) );
            if ( ! $table_exists ) {
                $missing_table = true;
                break;
            }
        }
        
        if ( $missing_table ) {
            // Tables don't exist - create them
            $this->db->create_tables();
        }
        
        // Check if we have events
        $events_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ol_events" );
        
        if ( $events_count == 0 ) {
            // Seed default data
            $this->db->seed_default_data();
        }
    }

    public function init() {
        add_shortcode( 'ol_tipping', array( $this, 'render_tipping_page' ) );
        add_shortcode( 'ol_event_results', array( $this, 'render_event_results' ) );
        add_shortcode( 'ol_leaderboard', array( $this, 'render_leaderboard' ) );
        add_shortcode( 'ol_events_list', array( $this, 'render_events_list' ) );
        add_shortcode( 'ol_community_tips', array( $this, 'render_community_tips' ) );
        add_shortcode( 'ol_frontpage', array( $this, 'render_frontpage' ) );
        add_shortcode( 'ol_detailed_results', array( $this, 'render_detailed_results' ) );
    }

    /**
     * Render frontpage with next event and quick stats
     */
    public function render_frontpage( $atts ) {
        global $wpdb;
        
        wp_enqueue_style( 'ol-tipping-forside', plugin_dir_url( __FILE__ ) . '../assets/css/tipping-forside.css' );
        
        // Get next upcoming event using WordPress timezone
        $now = current_time( 'mysql' );
        $next_event = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ol_events 
            WHERE event_date > %s 
            ORDER BY event_date ASC 
            LIMIT 1",
            $now
        ) );
        
        // Get stats
        $total_tips = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ol_tips" );
        $total_users = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}ol_tips" );
        $total_events = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ol_events" );
        
        $html = '<div class="ol-frontpage">';
        $html .= '<style>
            .ol-frontpage { padding: 0; }
            .ol-hero-section { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 80px 30px; text-align: center; color: white;
                border-radius: 20px; margin-bottom: 40px;
            }
            .ol-hero-logo-top { text-align: center; margin: 16px 0 10px; }
            .ol-hero-logo-top img { width: 240px; height: auto; max-width: 80vw; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3)); }
            .ol-hero-title { font-size: 48px; margin-bottom: 15px; }
            .ol-hero-subtitle { font-size: 22px; opacity: 0.9; margin-bottom: 30px; }
            .ol-hero-cta { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
            .ol-hero-btn { 
                padding: 18px 40px; border-radius: 50px; text-decoration: none;
                font-weight: 600; font-size: 18px; transition: all 0.3s ease;
            }
            .ol-hero-btn-primary { background: white; color: #667eea; }
            .ol-hero-btn-secondary { background: rgba(255,255,255,0.2); color: white; border: 2px solid white; }
            .ol-hero-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
            
            .ol-stats-section { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-bottom: 50px; }
            .ol-stat-box { 
                background: white; border-radius: 16px; padding: 30px; text-align: center;
                box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            }
            .ol-stat-icon { font-size: 40px; margin-bottom: 15px; }
            .ol-stat-value { font-size: 36px; font-weight: bold; color: #667eea; margin-bottom: 5px; }
            .ol-stat-label { color: #888; font-size: 14px; text-transform: uppercase; }
            
            .ol-next-event-section { 
                background: white; border-radius: 20px; padding: 40px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            }
            .ol-next-event-title { font-size: 28px; color: #1a1a2e; margin-bottom: 20px; text-align: center; }
            .ol-next-event-card {
                background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
                border-radius: 16px; padding: 30px; text-align: center;
                border-left: 5px solid #667eea;
            }
            .ol-next-event-name { font-size: 24px; color: #667eea; margin-bottom: 15px; }
            .ol-next-event-date { font-size: 18px; color: #555; margin-bottom: 20px; }
            .ol-next-event-countdown { 
                background: #667eea; color: white; padding: 15px 30px;
                border-radius: 30px; display: inline-block; font-size: 16px;
            }
            
            @media (max-width: 768px) {
                .ol-hero-title { font-size: 32px; }
                .ol-stats-section { grid-template-columns: 1fr; }
            }
        </style>';
        
        $logo_url = get_template_directory_uri() . '/assets/images/logo.jpg';

        // Hero logo above section
        $html .= '<div class="ol-hero-logo-top"><img src="' . esc_url( $logo_url ) . '" alt="Tipping Ølømpiske lekker" /></div>';

        // Hero section
        $html .= '<div class="ol-hero-section">';
        $html .= '<h1 class="ol-hero-title">🎿 OL Tippekonkurranse 2026</h1>';
        $html .= '<p class="ol-hero-subtitle">Tippe på langrenn i Milano-Cortina og konkurrer mot venner!</p>';
        $html .= '<div class="ol-hero-cta">';
        
        if ( is_user_logged_in() ) {
            $html .= '<a href="' . home_url( '/tipping' ) . '" class="ol-hero-btn ol-hero-btn-primary">🎯 Tippe nå</a>';
            $html .= '<a href="' . home_url( '/leaderboard' ) . '" class="ol-hero-btn ol-hero-btn-secondary">🏆 Se Pultrøyekampen</a>';
        } else {
            $html .= '<a href="' . home_url( '/logg-inn' ) . '" class="ol-hero-btn ol-hero-btn-primary">🔑 Logg inn</a>';
            $html .= '<a href="' . home_url( '/registrering' ) . '" class="ol-hero-btn ol-hero-btn-secondary">📝 Registrer deg</a>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        // Stats section
        $html .= '<div class="ol-stats-section">';
        $html .= '<div class="ol-stat-box"><div class="ol-stat-icon">📝</div><div class="ol-stat-value">' . intval( $total_tips ) . '</div><div class="ol-stat-label">Tips sendt</div></div>';
        $html .= '<div class="ol-stat-box"><div class="ol-stat-icon">👥</div><div class="ol-stat-value">' . intval( $total_users ) . '</div><div class="ol-stat-label">Deltakere</div></div>';
        $html .= '<div class="ol-stat-box"><div class="ol-stat-icon">🏔️</div><div class="ol-stat-value">' . intval( $total_events ) . '</div><div class="ol-stat-label">Øvelser</div></div>';
        $html .= '</div>';
        
        // Next event section
        if ( $next_event ) {
            $html .= '<div class="ol-next-event-section">';
            $html .= '<h2 class="ol-next-event-title">⏳ Neste øvelse</h2>';
            $html .= '<div class="ol-next-event-card">';
            $html .= '<h3 class="ol-next-event-name">' . esc_html( $next_event->event_name ) . '</h3>';
            $html .= '<p class="ol-next-event-date">📅 ' . date( 'd. F Y \k\l H:i', strtotime( $next_event->event_date ) ) . '</p>';
            
            $can_tip = strtotime( $next_event->tipping_deadline ) > time();
            if ( $can_tip ) {
                $html .= '<a href="' . home_url( '/tipping/?event_id=' . intval( $next_event->id ) ) . '" style="text-decoration: none;">';
                $html .= '<p class="ol-next-event-countdown" style="cursor: pointer;">✅ Åpen for tipping - Frist: ' . date( 'd. M H:i', strtotime( $next_event->tipping_deadline ) ) . '</p>';
                $html .= '</a>';
                $html .= '<a href="' . home_url( '/tipping/?event_id=' . intval( $next_event->id ) ) . '" style="display: inline-block; margin-top: 20px; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 30px; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'">🎯 Tippe på denne øvelsen</a>';
            } else {
                $html .= '<p class="ol-next-event-countdown" style="background:#dc3545;">⏱️ Tippefrist utløpt</p>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render tipping page
     */
    public function render_tipping_page( $atts ) {
        $atts = shortcode_atts( array( 'event_id' => 0 ), $atts, 'ol_tipping' );
        
        // Login required styling
        $login_style = '<style>
            .ol-login-required { 
                display: flex; justify-content: center; align-items: center; 
                min-height: 60vh; padding: 40px 20px;
            }
            .ol-login-box { 
                background: white; border-radius: 20px; padding: 50px; 
                text-align: center; max-width: 500px; 
                box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            }
            .ol-login-icon { font-size: 64px; margin-bottom: 20px; }
            .ol-login-box h2 { font-size: 28px; color: #1a1a2e; margin-bottom: 15px; }
            .ol-login-box p { color: #666; margin-bottom: 25px; font-size: 16px; }
            .ol-login-buttons { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-bottom: 20px; }
            .ol-btn { 
                padding: 15px 30px; border-radius: 50px; text-decoration: none; 
                font-weight: 600; font-size: 16px; transition: all 0.3s ease;
            }
            .ol-btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
            .ol-btn-secondary { background: #f0f0f0; color: #333; border: 2px solid #ddd; }
            .ol-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
            .ol-login-info { font-size: 14px; color: #888; }
        </style>';
        
        if ( ! is_user_logged_in() ) {
            return $login_style . '<div class="ol-login-required">
                <div class="ol-login-box">
                    <div class="ol-login-icon">🔐</div>
                    <h2>Logg inn for å tippe</h2>
                    <p>Du må være innlogget for å delta i tippekonkurransen.</p>
                    <div class="ol-login-buttons">
                        <a href="' . home_url( '/logg-inn' ) . '" class="ol-btn ol-btn-primary">🎿 Logg inn</a>
                        <a href="' . home_url( '/registrering' ) . '" class="ol-btn ol-btn-secondary">📝 Registrer deg</a>
                    </div>
                    <p class="ol-login-info">Har du ikke bruker? Registrer deg gratis og bli med i konkurransen!</p>
                </div>
            </div>';
        }

        global $wpdb;
        $user_id = get_current_user_id();

        // Check for event_id in URL or shortcode attribute
        $event_id = 0;
        if ( isset( $_GET['event_id'] ) && intval( $_GET['event_id'] ) > 0 ) {
            $event_id = intval( $_GET['event_id'] );
        } elseif ( $atts['event_id'] > 0 ) {
            $event_id = intval( $atts['event_id'] );
        }

        // Get specific event or next upcoming event
        $now = current_time( 'mysql' );
        
        if ( $event_id > 0 ) {
            $event = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ol_events WHERE id = %d",
                $event_id
            ) );
        } else {
            // Get next event where tipping is still open
            $event = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ol_events 
                WHERE tipping_deadline > %s 
                ORDER BY event_date ASC 
                LIMIT 1",
                $now
            ) );
        }

        if ( ! $event ) {
            // Check if there are ANY events in the database
            $total_events = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ol_events" );
            
            if ( $total_events == 0 ) {
                // No events at all - need to run setup
                return '<div class="ol-no-event-message" style="text-align:center; padding:60px; background:white; border-radius:16px; margin:20px auto; max-width:600px;">
                    <h2 style="color:#1a1a2e;">🎿 Ingen øvelser i databasen</h2>
                    <p style="color:#666;">Øvelser må importeres først. Gå til WordPress Admin → OL Tipping → Øvelser og klikk "Importer OL-øvelser 2026".</p>
                    <a href="' . admin_url( 'admin.php?page=ol-tipping-events' ) . '" style="display:inline-block; margin-top:20px; padding:15px 30px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; text-decoration:none; border-radius:25px; font-weight:600;">Gå til Admin</a>
                </div>';
            }
            
            return '<div class="ol-no-event-message" style="text-align:center; padding:60px; background:white; border-radius:16px; margin:20px auto; max-width:600px;">
                <h2 style="color:#1a1a2e;">🎿 Ingen åpne øvelser</h2>
                <p style="color:#666;">Det er ingen øvelser åpne for tipping akkurat nå.</p>
                <a href="' . home_url( '/events' ) . '" style="display:inline-block; margin-top:20px; padding:15px 30px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; text-decoration:none; border-radius:25px; font-weight:600;">Se alle øvelser</a>
            </div>';
        }

        $tipping = OL_Tipping_Tipping::get_instance();
        $can_tip = $tipping->can_user_tip( $event->id );
        $user_tips = $this->db->get_user_tips( $user_id, $event->id );

        // Store user tips in associative array
        $tips_data = array();
        foreach ( $user_tips as $tip ) {
            $tips_data[ $tip->position ] = (array) $tip;
        }

        // Enqueue AJAX script
        wp_enqueue_script( 'ol-tipping-ajax', plugin_dir_url( __FILE__ ) . '../assets/js/tipping-ajax.js', array( 'jquery' ), '1.0', true );
        wp_localize_script( 'ol-tipping-ajax', 'olTippingAjax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'event_id' => $event->id,
            'nonce' => wp_create_nonce( 'ol-tipping-nonce' ),
        ) );

        // Enqueue CSS
        wp_enqueue_style( 'ol-tipping-forside', plugin_dir_url( __FILE__ ) . '../assets/css/tipping-forside.css' );

        $html = '<div class="ol-tipping-forside">';
        
        // Header with event info
        $html .= '<div class="ol-event-header">';
        $html .= '<h1>🎿 OL Tipping 2026</h1>';
        $html .= '<h2 class="ol-event-name">' . esc_html( $event->event_name ) . '</h2>';
        $html .= '<p class="ol-event-date">📅 ' . date( 'd. F Y', strtotime( $event->event_date ) ) . ' kl ' . date( 'H:i', strtotime( $event->event_date ) ) . '</p>';
        $html .= '<p class="ol-event-location">📍 ' . esc_html( $event->location ) . '</p>';
        
        if ( $can_tip ) {
            $deadline_timestamp = strtotime( $event->tipping_deadline );
            $now = time();
            $diff = $deadline_timestamp - $now;
            $days = floor( $diff / 86400 );
            $hours = floor( ( $diff % 86400 ) / 3600 );
            $minutes = floor( ( $diff % 3600 ) / 60 );
            
            $html .= '<div class="ol-countdown-timer-box">';
            $html .= '<p class="ol-countdown-label">⏳ Tippefrist om:</p>';
            $html .= '<div class="ol-countdown-display">' . $days . ' dager, ' . $hours . ' timer, ' . $minutes . ' min</div>';
            $html .= '</div>';
        } else {
            $html .= '<div class="ol-deadline-passed-notice">';
            $html .= '<p>⏱️ Tippefrist har gått ut for denne øvelsen.</p>';
            $html .= '</div>';
        }
        
        $html .= '</div>';

        if ( $can_tip ) {
            $html .= $this->render_modern_tipping_form( $event, $tips_data );
        } else {
            // Show existing tips if deadline passed
            if ( ! empty( $tips_data ) ) {
                $is_team_event = isset( $event->event_type ) && $event->event_type === 'team';
                $html .= '<div class="ol-your-tips" style="background:white; border-radius:16px; padding:30px; margin-top:20px;">';
                $html .= '<h3 style="color:#1a1a2e; margin-bottom:20px;">Dine tips for denne øvelsen:</h3>';
                foreach ( $tips_data as $pos => $tip ) {
                    $html .= '<div style="padding:10px; background:#f8f9fa; border-radius:8px; margin-bottom:10px;">';
                    if ( $is_team_event ) {
                        $flag = isset( $tip['flag'] ) ? $tip['flag'] : '';
                        $country_name = isset( $tip['country_name'] ) ? $tip['country_name'] : '';
                        $html .= '<strong>' . $pos . '. plass:</strong> ' . esc_html( $flag ) . ' ' . esc_html( $country_name );
                    } else {
                        $html .= '<strong>' . $pos . '. plass:</strong> ' . esc_html( $tip['name'] ) . ' (' . esc_html( $tip['country'] ) . ')';
                    }
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render modern tipping form with AJAX search
     */
    private function render_modern_tipping_form( $event, $tips_data = array() ) {
        // Check if this is a team event
        $is_team_event = isset( $event->event_type ) && $event->event_type === 'team';
        $has_tipped = ! empty( $tips_data );
        $disabled_attr = $has_tipped ? 'disabled' : '';

        // Ensure countries/teams exist for team events
        if ( $is_team_event ) {
            $this->db->create_tables();
            $countries = $this->db->get_countries();
            if ( empty( $countries ) ) {
                $this->db->reseed_teams();
            }
        }
        
        $html = '<div class="ol-tipping-form-container">';
        $html .= '<form id="ol-tipping-form" class="ol-tipping-form" data-event-id="' . intval( $event->id ) . '" data-event-type="' . ( $is_team_event ? 'team' : 'individual' ) . '" data-has-tipped="' . ( $has_tipped ? '1' : '0' ) . '">';
        $html .= wp_nonce_field( 'ol-tipping-nonce', 'nonce', true, false );

        if ( $has_tipped ) {
            $html .= '<div class="ol-team-event-notice" style="background: #e8f5e9; color: #1b5e20; padding: 16px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
                ✅ Du har allerede tippet på denne øvelsen. Tipping kan kun gjøres én gang.
            </div>';
        }

        // Show info for team events
        if ( $is_team_event ) {
            $html .= '<div class="ol-team-event-notice" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; margin-bottom: 25px; text-align: center;">';
            $html .= '<span style="font-size: 28px;">🏅</span>';
            $html .= '<h3 style="margin: 10px 0 5px 0;">Lagøvelse</h3>';
            $html .= '<p style="margin: 0; opacity: 0.9;">Velg hvilke nasjoner du tror kommer på pallen!</p>';
            $html .= '</div>';
        }

        $html .= '<div class="ol-tipping-grid">';
        
        // Render 5 position fields with AJAX search
        for ( $position = 1; $position <= 5; $position++ ) {
            $selected_data = isset( $tips_data[ $position ] ) ? $tips_data[ $position ] : array();
            
            if ( $is_team_event ) {
                // Team event - show country data
                $country_name = isset( $selected_data['country_name'] ) ? $selected_data['country_name'] : '';
                $country_id = isset( $selected_data['country_id'] ) ? $selected_data['country_id'] : '';
                $country_flag = isset( $selected_data['flag'] ) ? $selected_data['flag'] : '';
                
                $html .= '<div class="ol-tipping-position-card">';
                $html .= '<div class="ol-position-label">';
                $html .= '<span class="ol-position-number">' . $position . '.</span>';
                $html .= '<span class="ol-position-text">plass</span>';
                $html .= '</div>';

                $html .= '<div class="ol-search-container">';
                $html .= '<input 
                    type="hidden" 
                    name="country_id[' . $position . ']" 
                    class="ol-country-id" 
                    value="' . esc_attr( $country_id ) . '"
                    data-position="' . $position . '"
                />';

                $html .= '<input 
                    type="text" 
                    class="ol-country-search" 
                    placeholder="Søk etter land..." 
                    autocomplete="off"
                    data-position="' . $position . '"
                    ' . $disabled_attr . '
                />';

                $html .= '<div class="ol-search-results" style="display: none;"></div>';

                // Display selected country
                if ( $country_id ) {
                    $html .= '<div class="ol-selected-country">';
                    $html .= '<div class="ol-country-info">';
                    $html .= '<span class="ol-country-flag">' . esc_html( $country_flag ) . '</span>';
                    $html .= '<span class="ol-country-name">' . esc_html( $country_name ) . '</span>';
                    $html .= '</div>';
                    $html .= '<button type="button" class="ol-remove-country" data-position="' . $position . '">✕</button>';
                    $html .= '</div>';
                }

                $html .= '</div>';
                $html .= '</div>';
            } else {
                // Individual event - show athlete data (existing code)
                $athlete_name = isset( $selected_data['name'] ) ? $selected_data['name'] : '';
                $athlete_id = isset( $selected_data['athlete_id'] ) ? $selected_data['athlete_id'] : '';
                $athlete_country = isset( $selected_data['country'] ) ? $selected_data['country'] : '';

                $html .= '<div class="ol-tipping-position-card">';
                $html .= '<div class="ol-position-label">';
                $html .= '<span class="ol-position-number">' . $position . '.</span>';
                $html .= '<span class="ol-position-text">plass</span>';
                $html .= '</div>';

                $html .= '<div class="ol-search-container">';
                $html .= '<input 
                    type="hidden" 
                    name="athlete_id[' . $position . ']" 
                    class="ol-athlete-id" 
                    value="' . esc_attr( $athlete_id ) . '"
                    data-position="' . $position . '"
                />';

                $html .= '<input 
                    type="text" 
                    class="ol-athlete-search" 
                    placeholder="Søk etter utøver..." 
                    autocomplete="off"
                    data-position="' . $position . '"
                    ' . $disabled_attr . '
                />';

                $html .= '<div class="ol-search-results" style="display: none;"></div>';

                // Display selected athlete
                if ( $athlete_id ) {
                    $html .= '<div class="ol-selected-athlete">';
                    $html .= '<div class="ol-athlete-info">';
                    $html .= '<div class="ol-athlete-name">' . esc_html( $athlete_name ) . '</div>';
                    $html .= '<div class="ol-athlete-country">' . esc_html( $athlete_country ) . '</div>';
                    $html .= '</div>';
                    $html .= '<button type="button" class="ol-remove-athlete" data-position="' . $position . '">✕</button>';
                    $html .= '</div>';
                }

                $html .= '</div>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';

        $html .= '<div class="ol-form-actions">';
        $html .= '<button type="submit" class="ol-submit-btn" ' . $disabled_attr . '>Lagre Tips</button>';
        $html .= '</div>';

        $html .= '<div id="ol-form-message" class="ol-form-message" style="display: none;"></div>';

        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render event results
     */
    public function render_event_results( $atts ) {
        $atts = shortcode_atts(
            array(
                'event_id' => 0,
            ),
            $atts,
            'ol_event_results'
        );

        global $wpdb;
        
        // Get all events with results
        $events_with_results = $wpdb->get_results(
            "SELECT DISTINCT e.*, COUNT(r.id) as result_count 
            FROM {$wpdb->prefix}ol_events e
            INNER JOIN {$wpdb->prefix}ol_results r ON e.id = r.event_id
            GROUP BY e.id
            ORDER BY e.event_date DESC"
        );

        $html = '<div class="ol-results-page">';
        $html .= '<style>
            .ol-results-page { padding: 40px 20px; }
            .ol-results-header { text-align: center; margin-bottom: 40px; }
            .ol-results-header h1 { font-size: 36px; color: #1a1a2e; margin-bottom: 10px; }
            .ol-results-header p { color: #666; font-size: 18px; }
            .ol-results-container { max-width: 900px; margin: 0 auto; }
            .ol-result-event { background: white; border-radius: 16px; padding: 30px; margin-bottom: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
            .ol-result-event h2 { font-size: 24px; color: #667eea; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
            .ol-result-event h2::before { content: "🏆"; }
            .ol-result-item { display: flex; align-items: center; gap: 15px; padding: 15px; border-radius: 10px; margin-bottom: 10px; transition: all 0.3s ease; }
            .ol-result-item:hover { background: #f8f9ff; }
            .ol-result-item.gold { background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%); }
            .ol-result-item.silver { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); }
            .ol-result-item.bronze { background: linear-gradient(135deg, #fff0e6 0%, #ffe4cc 100%); }
            .ol-result-position { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; }
            .ol-result-item.gold .ol-result-position { background: #ffd700; color: #856404; }
            .ol-result-item.silver .ol-result-position { background: #c0c0c0; color: #495057; }
            .ol-result-item.bronze .ol-result-position { background: #cd7f32; color: #fff; }
            .ol-result-item:not(.gold):not(.silver):not(.bronze) .ol-result-position { background: #e9ecef; color: #495057; }
            .ol-result-info { flex: 1; }
            .ol-result-name { font-size: 16px; font-weight: 600; color: #1a1a2e; }
            .ol-result-country { font-size: 14px; color: #888; }
            .ol-result-time { font-family: monospace; font-size: 16px; color: #667eea; font-weight: 600; }
            .ol-no-results { text-align: center; padding: 60px; color: #666; font-size: 18px; }
        </style>';
        
        $html .= '<div class="ol-results-header">';
        $html .= '<h1>📊 Resultater</h1>';
        $html .= '<p>Offisielle resultater fra OL-øvelsene</p>';
        $html .= '</div>';

        $html .= '<div class="ol-results-container">';

        if ( empty( $events_with_results ) ) {
            $html .= '<p class="ol-no-results">Ingen resultater registrert ennå. Resultater vil vises her etter hvert som øvelsene er gjennomført.</p>';
        } else {
            foreach ( $events_with_results as $event ) {
                $results = $this->db->get_event_results( $event->id );
                
                $html .= '<div class="ol-result-event">';
                $html .= '<h2>' . esc_html( $event->event_name ) . '</h2>';
                
                foreach ( $results as $result ) {
                    $pos_class = '';
                    if ( $result->position == 1 ) $pos_class = 'gold';
                    elseif ( $result->position == 2 ) $pos_class = 'silver';
                    elseif ( $result->position == 3 ) $pos_class = 'bronze';
                    
                    $medal = '';
                    if ( $result->position == 1 ) $medal = '🥇';
                    elseif ( $result->position == 2 ) $medal = '🥈';
                    elseif ( $result->position == 3 ) $medal = '🥉';
                    
                    $html .= '<div class="ol-result-item ' . $pos_class . '">';
                    $html .= '<div class="ol-result-position">' . ( $medal ?: $result->position ) . '</div>';
                    $html .= '<div class="ol-result-info">';
                    $html .= '<div class="ol-result-name">' . esc_html( $result->name ) . '</div>';
                    $html .= '<div class="ol-result-country">' . esc_html( $result->country ) . '</div>';
                    $html .= '</div>';
                    $html .= '<div class="ol-result-time">' . esc_html( $result->time ) . '</div>';
                    $html .= '</div>';
                }
                
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render leaderboard
     */
    public function render_leaderboard( $atts ) {
        $leaderboard = $this->db->get_leaderboard( 50 );

        $html = '<div class="ol-leaderboard-page">';
        $html .= '<style>
            .ol-leaderboard-page { padding: 40px 20px; }
            .ol-leaderboard-header { text-align: center; margin-bottom: 40px; }
            .ol-leaderboard-header h1 { font-size: 36px; color: #1a1a2e; margin-bottom: 10px; }
            .ol-leaderboard-header p { color: #666; font-size: 18px; }
            .ol-leaderboard-container { max-width: 900px; margin: 0 auto; }
            .ol-leaderboard-item { 
                display: flex; align-items: center; gap: 20px;
                background: white; border-radius: 12px; padding: 20px 25px;
                margin-bottom: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);
                transition: all 0.3s ease;
            }
            .ol-leaderboard-item:hover { transform: translateX(10px); }
            .ol-leaderboard-item.top-1 { background: #ffe066; }
            .ol-leaderboard-item.top-2 { background: #c0c0c0; color: #1a1a1a; }
            .ol-leaderboard-item.top-3 { background: #cd7f32; color: #1a1a1a; }
            .ol-leaderboard-rank { 
                width: 50px; height: 50px; border-radius: 50%;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; display: flex; align-items: center; justify-content: center;
                font-size: 20px; font-weight: bold;
            }
            .ol-leaderboard-item.top-1 .ol-leaderboard-rank,
            .ol-leaderboard-item.top-2 .ol-leaderboard-rank,
            .ol-leaderboard-item.top-3 .ol-leaderboard-rank {
                background: rgba(0,0,0,0.2);
            }
            .ol-leaderboard-user { flex: 1; }
            .ol-leaderboard-name { font-size: 18px; font-weight: 600; color: #1a1a2e; }
            .ol-leaderboard-label { font-size: 14px; font-weight: 600; margin-top: 4px; }
            .ol-leaderboard-stats { display: flex; gap: 30px; }
            .ol-leaderboard-stat { text-align: center; }
            .ol-leaderboard-stat-value { font-size: 24px; font-weight: bold; color: #667eea; }
            .ol-leaderboard-stat-label { font-size: 12px; color: #888; text-transform: uppercase; }
            .ol-no-leaderboard { text-align: center; padding: 60px; color: #666; font-size: 18px; }
        </style>';
        
        $html .= '<div class="ol-leaderboard-header">';
        $html .= '<h1>🏆 Pultrøyekampen</h1>';
        $html .= '<p>Sammenlagt resultater i kampen om pultrøya og vandrekuken</p>';
        $html .= '</div>';

        $html .= '<div class="ol-leaderboard-container">';

        if ( empty( $leaderboard ) ) {
            $html .= '<p class="ol-no-leaderboard">Ingen tips registrert ennå. Bli den første til å tippe!</p>';
        } else {
            foreach ( $leaderboard as $index => $entry ) {
                $rank = $index + 1;
                $top_class = $rank <= 3 ? ' top-' . $rank : '';
                $medal = '';
                $label = 'totalt uten kunnskap og tippeferdigheter.';
                if ( $rank === 1 ) {
                    $medal = '🥇';
                    $label = 'Pultrøye';
                } elseif ( $rank === 2 ) {
                    $medal = '🥈';
                    $label = 'close but no cigar';
                } elseif ( $rank === 3 ) {
                    $medal = '🥉';
                    $label = 'Har litt å gå på';
                }
                
                $html .= '<div class="ol-leaderboard-item' . $top_class . '">';
                $html .= '<div class="ol-leaderboard-rank">' . ( $medal ?: $rank ) . '</div>';
                $html .= '<div class="ol-leaderboard-user">';
                $html .= '<div class="ol-leaderboard-name">' . esc_html( $entry->display_name ) . '</div>';
                $html .= '<div class="ol-leaderboard-label">' . esc_html( $label ) . '</div>';
                $html .= '</div>';
                $html .= '<div class="ol-leaderboard-stats">';
                $html .= '<div class="ol-leaderboard-stat">';
                $html .= '<div class="ol-leaderboard-stat-value">' . intval( $entry->total_points ) . '</div>';
                $html .= '<div class="ol-leaderboard-stat-label">Poeng</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render events list page
     */
    public function render_events_list() {
        global $wpdb;
        
        $events = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ol_events 
            ORDER BY event_date ASC"
        );

        $user_id = get_current_user_id();
        
        $html = '<div class="ol-events-page">';
        
        // Inline CSS for reliability
        $html .= '<style>
            .ol-events-page { max-width: 1200px; margin: 0 auto; padding: 20px; }
            .ol-events-header { text-align: center; margin-bottom: 40px; }
            .ol-events-header h1 { font-size: 2.5rem; color: #1a1a2e; margin-bottom: 10px; }
            .ol-events-header p { color: #666; font-size: 1.1rem; }
            .ol-events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
            .ol-event-card { 
                background: white; border-radius: 16px; overflow: hidden;
                box-shadow: 0 5px 20px rgba(0,0,0,0.08); transition: all 0.3s ease;
                border: 2px solid #f0f0f0;
            }
            .ol-event-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.15); }
            .ol-event-card.ol-event-open { border-color: #28a745; }
            .ol-event-card.ol-event-closed { border-color: #dc3545; opacity: 0.8; }
            .ol-event-card-header { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 20px; color: white;
            }
            .ol-event-card-header h3 { margin: 0 0 8px; font-size: 1.3rem; }
            .ol-event-status { 
                display: inline-block; padding: 5px 12px; border-radius: 20px;
                font-size: 0.85rem; font-weight: 600;
            }
            .ol-event-open .ol-event-status { background: rgba(40,167,69,0.3); }
            .ol-event-closed .ol-event-status { background: rgba(220,53,69,0.3); }
            .ol-event-card-body { padding: 20px; }
            .ol-event-card-body p { margin: 8px 0; color: #555; }
            .ol-event-card-footer { padding: 15px 20px; background: #f8f9fa; text-align: center; }
            .ol-event-tippe-btn { 
                display: inline-block; padding: 12px 30px; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; text-decoration: none; border-radius: 25px;
                font-weight: 600; transition: all 0.3s ease;
            }
            .ol-event-tippe-btn:hover { transform: scale(1.05); box-shadow: 0 5px 20px rgba(102,126,234,0.4); }
            .ol-user-tip-status { 
                margin-top: 15px; padding: 10px; background: #e8f5e9; 
                border-radius: 8px; font-size: 0.9rem; color: #2e7d32;
            }
            .ol-no-events { 
                text-align: center; padding: 60px; background: white; 
                border-radius: 16px; color: #666;
            }
            @media (max-width: 768px) {
                .ol-events-grid { grid-template-columns: 1fr; }
            }
        </style>';
        
        $html .= '<div class="ol-events-header">';
        $html .= '<h1>🏔️ OL Milano-Cortina 2026</h1>';
        $html .= '<p>Langrenn herrer - Velg en øvelse for å legge inn dine tips</p>';
        $html .= '</div>';

        if ( empty( $events ) ) {
            // Check if tables exist
            $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ol_events'" );
            if ( ! $table_exists ) {
                $html .= '<div class="ol-no-events">';
                $html .= '<h2>⚠️ Database ikke satt opp</h2>';
                $html .= '<p>Pluginen er ikke aktivert riktig. Deaktiver og aktiver pluginen på nytt.</p>';
                $html .= '</div>';
            } else {
                $html .= '<div class="ol-no-events">';
                $html .= '<h2>🎿 Ingen øvelser registrert</h2>';
                $html .= '<p>Øvelser må importeres. <a href="' . admin_url( 'admin.php?page=ol-tipping-events&action=import' ) . '">Klikk her for å importere OL 2026 øvelser</a></p>';
                $html .= '</div>';
            }
        } else {
            $html .= '<div class="ol-events-grid">';
            
            // Current time for comparison
            $current_timestamp = current_time( 'timestamp' );
            
            foreach ( $events as $event ) {
                $deadline_timestamp = strtotime( $event->tipping_deadline );
                $can_tip = $deadline_timestamp > $current_timestamp;
                $status_class = $can_tip ? 'ol-event-open' : 'ol-event-closed';
                $status_text = $can_tip ? '✓ Åpen for tipping' : '✗ Tipping stengt';
                
                // Check if user has already tipped
                $user_has_tipped = false;
                if ( $user_id ) {
                    $tip_count = $wpdb->get_var( $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}ol_tips WHERE user_id = %d AND event_id = %d",
                        $user_id, $event->id
                    ) );
                    $user_has_tipped = $tip_count > 0;
                }
                
                $html .= '<div class="ol-event-card ' . $status_class . '">';
                $html .= '<div class="ol-event-card-header">';
                $html .= '<h3>' . esc_html( $event->event_name ) . '</h3>';
                $html .= '<span class="ol-event-status">' . $status_text . '</span>';
                $html .= '</div>';
                
                $html .= '<div class="ol-event-card-body">';
                $html .= '<p><strong>📅 Dato:</strong> ' . date( 'd. F Y', strtotime( $event->event_date ) ) . '</p>';
                $html .= '<p><strong>⏰ Starttid:</strong> ' . date( 'H:i', strtotime( $event->event_date ) ) . '</p>';
                $html .= '<p><strong>📍 Sted:</strong> ' . esc_html( $event->location ) . '</p>';
                
                if ( $can_tip ) {
                    $html .= '<p><strong>⏳ Tippefrist:</strong> ' . date( 'd. M H:i', strtotime( $event->tipping_deadline ) ) . '</p>';
                }
                
                if ( $user_has_tipped ) {
                    $html .= '<div class="ol-user-tip-status">✅ Du har allerede tippa på denne øvelsen</div>';
                }
                
                $html .= '</div>';
                
                if ( $can_tip ) {
                    $html .= '<div class="ol-event-card-footer">';
                    if ( is_user_logged_in() ) {
                        $btn_text = $user_has_tipped ? 'Endre tips' : 'Tippe nå';
                        $html .= '<a href="' . home_url( '/tipping/?event_id=' . $event->id ) . '" class="ol-event-tippe-btn">🎯 ' . $btn_text . '</a>';
                    } else {
                        $html .= '<a href="' . home_url( '/logg-inn/?redirect_to=' . urlencode( home_url( '/tipping/?event_id=' . $event->id ) ) ) . '" class="ol-event-tippe-btn">🔐 Logg inn for å tippe</a>';
                    }
                    $html .= '</div>';
                }
                
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render community tips
     */
    public function render_community_tips() {
        global $wpdb;

        $html = '<div class="ol-community-tips-page">';
        
        // Inline CSS for reliability
        $html .= '<style>
            .ol-community-tips-page { max-width: 1200px; margin: 0 auto; padding: 20px; }
            .ol-community-header { text-align: center; margin-bottom: 40px; }
            .ol-community-header h1 { font-size: 2.5rem; color: #1a1a2e; margin-bottom: 10px; }
            .ol-community-header p { color: #666; font-size: 1.1rem; }
            .ol-community-events { display: flex; flex-direction: column; gap: 30px; }
            .ol-community-event { 
                background: white; border-radius: 16px; padding: 30px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            }
            .ol-community-event h2 { 
                color: #667eea; font-size: 1.5rem; margin-bottom: 10px;
                padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;
            }
            .ol-tip-count { color: #888; font-size: 0.9rem; margin-bottom: 20px; }
            .ol-position-tips { margin-bottom: 25px; }
            .ol-position-heading { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; padding: 10px 20px; border-radius: 8px;
                font-size: 1rem; margin-bottom: 15px; display: inline-block;
            }
            .ol-community-tip { 
                background: #f8f9fa; border-radius: 8px; padding: 15px;
                margin-bottom: 10px; display: flex; justify-content: space-between;
                align-items: center; flex-wrap: wrap; gap: 10px;
            }
            .ol-tip-header { display: flex; align-items: center; gap: 10px; }
            .ol-tip-header strong { color: #1a1a2e; }
            .ol-tip-country { 
                background: #e0e0e0; padding: 3px 10px; border-radius: 12px;
                font-size: 0.85rem; color: #555;
            }
            .ol-tip-user { font-size: 0.9rem; color: #666; }
            .ol-no-tips { 
                text-align: center; padding: 60px; background: white;
                border-radius: 16px; color: #666;
            }
            @media (max-width: 768px) {
                .ol-community-tip { flex-direction: column; align-items: flex-start; }
            }
        </style>';
        
        $html .= '<div class="ol-community-header">';
        $html .= '<h1>👥 Tippelister</h1>';
        $html .= '<p>Se hva andre tippere har valgt, sortert etter neste øvelse først</p>';
        $html .= '</div>';

        // Get events with tips
        $events = $wpdb->get_results(
            "SELECT DISTINCT e.*, COUNT(t.id) as tip_count 
            FROM {$wpdb->prefix}ol_events e
            LEFT JOIN {$wpdb->prefix}ol_tips t ON e.id = t.event_id
            GROUP BY e.id"
        );

        // Sort so next upcoming is first, then other upcoming, finished last
        $now_ts = current_time( 'timestamp' );
        $upcoming = array();
        $past = array();

        foreach ( $events as $event ) {
            $event_ts = strtotime( $event->event_date );
            if ( $event_ts >= $now_ts ) {
                $upcoming[] = $event;
            } else {
                $past[] = $event;
            }
        }

        usort( $upcoming, function( $a, $b ) {
            return strtotime( $a->event_date ) <=> strtotime( $b->event_date );
        } );
        usort( $past, function( $a, $b ) {
            return strtotime( $b->event_date ) <=> strtotime( $a->event_date );
        } );

        $ordered_events = array();
        if ( ! empty( $upcoming ) ) {
            $ordered_events[] = array_shift( $upcoming ); // next event
            $ordered_events = array_merge( $ordered_events, $upcoming );
        }
        $ordered_events = array_merge( $ordered_events, $past );

        if ( empty( $events ) ) {
            $html .= '<p class="ol-no-tips">Ingen tips registrert ennå.</p>';
        } else {
            $html .= '<div class="ol-community-events">';
            
            foreach ( $ordered_events as $event ) {
                $tips = $wpdb->get_results( $wpdb->prepare(
                    "SELECT t.*, u.display_name, a.name as athlete_name, a.country 
                    FROM {$wpdb->prefix}ol_tips t
                    JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
                    JOIN {$wpdb->prefix}ol_athletes a ON t.athlete_id = a.id
                    WHERE t.event_id = %d
                    ORDER BY t.position ASC, t.created_at DESC",
                    $event->id
                ) );

                $html .= '<div class="ol-community-event">';
                $html .= '<h2>' . esc_html( $event->event_name ) . '</h2>';
                $html .= '<p class="ol-tip-count">' . intval( $event->tip_count ) . ' tips registrert</p>';

                if ( empty( $tips ) ) {
                    $html .= '<p>Ingen tips på denne øvelsen ennå.</p>';
                } else {
                    $grouped_tips = array();
                    foreach ( $tips as $tip ) {
                        if ( ! isset( $grouped_tips[ $tip->position ] ) ) {
                            $grouped_tips[ $tip->position ] = array();
                        }
                        $grouped_tips[ $tip->position ][] = $tip;
                    }

                    foreach ( $grouped_tips as $position => $position_tips ) {
                        $html .= '<div class="ol-position-tips">';
                        $html .= '<h3 class="ol-position-heading">Plass ' . intval( $position ) . '</h3>';
                        
                        foreach ( $position_tips as $tip ) {
                            $html .= '<div class="ol-community-tip">';
                            $html .= '<div class="ol-tip-header">';
                            $html .= '<strong>' . esc_html( $tip->athlete_name ) . '</strong>';
                            $html .= '<span class="ol-tip-country">' . esc_html( $tip->country ) . '</span>';
                            $html .= '</div>';
                            $html .= '<div class="ol-tip-user">';
                            $html .= 'Tippa av: <strong>' . esc_html( $tip->display_name ) . '</strong>';
                            $html .= '</div>';
                            $html .= '</div>';
                        }
                        
                        $html .= '</div>';
                    }
                }

                $html .= '</div>';
            }
            
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render detailed results per event with user tips and points
     */
    public function render_detailed_results() {
        global $wpdb;

        $html = '<div class="ol-detailed-results-page">';
        
        // Inline CSS
        $html .= '<style>
            .ol-detailed-results-page { max-width: 1200px; margin: 0 auto; padding: 20px; }
            .ol-results-header { text-align: center; margin-bottom: 40px; }
            .ol-results-header h1 { font-size: 2.5rem; color: #1a1a2e; margin-bottom: 10px; }
            .ol-results-header p { color: #666; font-size: 1.1rem; }
            .ol-event-results-container { display: flex; flex-direction: column; gap: 40px; }
            .ol-event-result { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
            .ol-event-result h2 { color: #667eea; font-size: 1.5rem; margin-bottom: 5px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; }
            .ol-event-info { color: #888; font-size: 0.9rem; margin-bottom: 20px; }
            .ol-result-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .ol-result-table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
            .ol-result-table th { padding: 15px; text-align: left; font-weight: 600; }
            .ol-result-table td { padding: 15px; border-bottom: 1px solid #f0f0f0; }
            .ol-result-table tbody tr:hover { background: #f8f9fa; }
            .ol-place-medal { font-size: 1.2em; width: 30px; }
            .ol-tipper-name { font-weight: 600; color: #1a1a2e; }
            .ol-tip-list { font-size: 0.9rem; color: #555; }
            .ol-points { 
                font-weight: 600; font-size: 1.1rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; padding: 8px 12px; border-radius: 6px;
                text-align: center; min-width: 50px;
            }
            .ol-points.zero { background: #ddd; color: #666; }
            .ol-no-results { text-align: center; padding: 60px; color: #666; }
            @media (max-width: 768px) {
                .ol-result-table { font-size: 0.9rem; }
                .ol-result-table th, .ol-result-table td { padding: 10px; }
            }
        </style>';
        
        $html .= '<div class="ol-results-header">';
        $html .= '<h1>📊 Detaljerte Resultater</h1>';
        $html .= '<p>Se hvem som tippsatte hva og hvor mange poeng de fikk på hver øvelse</p>';
        $html .= '</div>';

        $html .= '<div class="ol-event-results-container">';

        // Get all events with results
        $events = $wpdb->get_results(
            "SELECT e.*, COUNT(r.id) as result_count 
            FROM {$wpdb->prefix}ol_events e
            LEFT JOIN {$wpdb->prefix}ol_results r ON e.id = r.event_id
            WHERE r.id IS NOT NULL
            GROUP BY e.id
            ORDER BY e.event_date DESC"
        );

        if ( empty( $events ) ) {
            $html .= '<div class="ol-no-results">Ingen resultater registrert ennå.</div>';
        } else {
            foreach ( $events as $event ) {
                $html .= '<div class="ol-event-result">';
                $html .= '<h2>' . esc_html( $event->event_name ) . '</h2>';
                $html .= '<div class="ol-event-info">';
                $html .= 'Dato: ' . date( 'd. M Y H:i', strtotime( $event->event_date ) );
                $html .= '</div>';

                // Get results for this event
                $results = $wpdb->get_results( $wpdb->prepare(
                    "SELECT r.*, a.name as athlete_name, COALESCE(c.display_name, c.name) as country_name, c.flag
                    FROM {$wpdb->prefix}ol_results r
                    LEFT JOIN {$wpdb->prefix}ol_athletes a ON r.athlete_id = a.id
                    LEFT JOIN {$wpdb->prefix}ol_countries c ON r.country_id = c.id
                    WHERE r.event_id = %d
                    ORDER BY r.position ASC",
                    $event->id
                ) );

                // Get all tips for this event
                $tips = $wpdb->get_results( $wpdb->prepare(
                    "SELECT t.*, u.display_name, a.name as athlete_name, COALESCE(c.display_name, c.name) as country_name
                    FROM {$wpdb->prefix}ol_tips t
                    JOIN {$wpdb->users} u ON t.user_id = u.ID
                    LEFT JOIN {$wpdb->prefix}ol_athletes a ON t.athlete_id = a.id
                    LEFT JOIN {$wpdb->prefix}ol_countries c ON t.country_id = c.id
                    WHERE t.event_id = %d
                    ORDER BY u.display_name ASC, t.position ASC",
                    $event->id
                ) );

                // Group tips by user
                $tips_by_user = array();
                $user_totals = array();
                foreach ( $tips as $tip ) {
                    if ( ! isset( $tips_by_user[ $tip->user_id ] ) ) {
                        $tips_by_user[ $tip->user_id ] = array(
                            'display_name' => $tip->display_name,
                            'tips' => array(),
                            'total_points' => 0,
                        );
                    }
                    $tips_by_user[ $tip->user_id ]['tips'][ $tip->position ] = $tip;
                    $tips_by_user[ $tip->user_id ]['total_points'] += intval( $tip->points );
                }

                // Build result display
                $html .= '<table class="ol-result-table">';
                $html .= '<thead><tr>';
                $html .= '<th>Plass</th>';
                $html .= '<th>Resultat</th>';
                
                // Add column headers for each tipper
                foreach ( $tips_by_user as $user_data ) {
                    $html .= '<th style="text-align: center;">' . esc_html( substr( $user_data['display_name'], 0, 12 ) ) . '</th>';
                }
                
                $html .= '</tr></thead>';
                $html .= '<tbody>';

                // Show results row by row with user tips
                foreach ( $results as $result ) {
                    $html .= '<tr>';
                    
                    // Medal for position
                    $medal = '';
                    if ( $result->position == 1 ) {
                        $medal = '🥇';
                    } elseif ( $result->position == 2 ) {
                        $medal = '🥈';
                    } elseif ( $result->position == 3 ) {
                        $medal = '🥉';
                    }
                    
                    $html .= '<td class="ol-place-medal">' . $medal . ' ' . intval( $result->position ) . '</td>';
                    
                    // Actual result
                    $result_name = $result->athlete_name ?? $result->country_name ?? 'N/A';
                    $html .= '<td><strong>' . esc_html( $result_name ) . '</strong></td>';
                    
                    // User tips for this position
                    foreach ( $tips_by_user as $user_id => $user_data ) {
                        $html .= '<td style="text-align: center; font-size: 0.9rem;">';
                        
                        // Find if any user tipped this result
                        $found_tip = false;
                        foreach ( $user_data['tips'] as $position => $tip ) {
                            $tip_matches = false;
                            if ( $tip->athlete_id && $tip->athlete_id == $result->athlete_id ) {
                                $tip_matches = true;
                            } elseif ( $tip->country_id && $tip->country_id == $result->country_id ) {
                                $tip_matches = true;
                            }
                            
                            if ( $tip_matches ) {
                                $points_class = intval( $tip->points ) > 0 ? '' : ' zero';
                                $html .= '✓ Plass ' . intval( $position );
                                $html .= '<br><span class="ol-points' . $points_class . '">' . intval( $tip->points ) . ' pts</span>';
                                $found_tip = true;
                                break;
                            }
                        }
                        
                        if ( ! $found_tip ) {
                            $html .= '<span style="color: #ccc;">-</span>';
                        }
                        
                        $html .= '</td>';
                    }
                    
                    $html .= '</tr>';
                }

                // Total points row
                $html .= '<tr style="background: #f0f0f0; font-weight: 600;">';
                $html .= '<td colspan="2">Total Poeng Denne Øvelsen</td>';
                
                foreach ( $tips_by_user as $user_data ) {
                    $html .= '<td style="text-align: center;"><span class="ol-points">' . intval( $user_data['total_points'] ) . '</span></td>';
                }
                
                $html .= '</tr>';

                $html .= '</tbody>';
                $html .= '</table>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}

} // end if class_exists
