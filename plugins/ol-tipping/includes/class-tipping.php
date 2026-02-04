<?php
/**
 * Tipping Class - Handles tipping logic
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'OL_Tipping_Tipping' ) ) {

class OL_Tipping_Tipping {
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
        $this->init();
    }

    public function init() {
        add_action( 'wp_ajax_submit_tip', array( $this, 'submit_tip' ) );
        add_action( 'wp_ajax_nopriv_submit_tip', array( $this, 'require_login' ) );
        add_action( 'wp_ajax_search_athletes', array( $this, 'search_athletes' ) );
        add_action( 'wp_ajax_nopriv_search_athletes', array( $this, 'require_login' ) );
        add_action( 'wp_ajax_search_countries', array( $this, 'search_countries' ) );
        add_action( 'wp_ajax_nopriv_search_countries', array( $this, 'require_login' ) );
    }

    /**
     * Submit a tip
     */
    public function submit_tip() {
        check_ajax_referer( 'ol-tipping-nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'Du må være innlogget for å tippe' );
        }

        $user_id = get_current_user_id();
        $event_id = intval( $_POST['event_id'] );
        
        // Check if event exists and tipping is still open
        $event = $this->db->get_event( $event_id );
        if ( ! $event ) {
            wp_send_json_error( 'Øvelse ikke funnet' );
        }

        if ( strtotime( $event->tipping_deadline ) < time() ) {
            wp_send_json_error( 'Tippefrist har utgått' );
        }

        // Do not allow multiple tips for the same event
        global $wpdb;
        $tips_table = $wpdb->prefix . 'ol_tips';
        $existing_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $tips_table WHERE user_id = %d AND event_id = %d",
            $user_id,
            $event_id
        ) );

        if ( $existing_count > 0 ) {
            wp_send_json_error( 'Du har allerede tippet på denne øvelsen.' );
        }

        // Check if this is a team event
        $is_team_event = $this->db->is_team_event( $event_id );
        
        // Add new tips
        $count = 0;
        
        if ( $is_team_event ) {
            // Team event - use country_id
            $country_ids = isset( $_POST['country_id'] ) ? array_map( 'intval', (array) $_POST['country_id'] ) : array();
            foreach ( $country_ids as $position => $country_id ) {
                if ( $country_id > 0 ) {
                    $wpdb->insert(
                        $tips_table,
                        array(
                            'user_id' => $user_id,
                            'event_id' => $event_id,
                            'country_id' => $country_id,
                            'position' => $position + 1,
                        ),
                        array( '%d', '%d', '%d', '%d' )
                    );
                    $count++;
                }
            }
        } else {
            // Individual event - use athlete_id
            $athlete_ids = isset( $_POST['athlete_id'] ) ? array_map( 'intval', (array) $_POST['athlete_id'] ) : array();
            foreach ( $athlete_ids as $position => $athlete_id ) {
                if ( $athlete_id > 0 ) {
                    $this->db->add_tip( $user_id, $event_id, $athlete_id, $position + 1 );
                    $count++;
                }
            }
        }

        wp_send_json_success( array(
            'message' => 'Tips lagret! Du tiptet på ' . $count . ' plasser.',
            'count' => $count
        ) );
        
        // Update leaderboard for this user
        $this->db->update_leaderboard( $user_id );
    }

    /**
     * Require login
     */
    public function require_login() {
        wp_send_json_error( 'Du må være innlogget for å tippe' );
    }

    /**
     * Check if user can tip on event
     */
    public function can_user_tip( $event_id ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $event = $this->db->get_event( $event_id );
        if ( ! $event ) {
            return false;
        }

        // Check deadline using WordPress timezone
        $current_timestamp = current_time( 'timestamp' );
        $deadline_timestamp = strtotime( $event->tipping_deadline );
        
        if ( $deadline_timestamp < $current_timestamp ) {
            return false;
        }

        return true;
    }

    /**
     * Get time remaining until deadline
     */
    public function get_time_remaining( $deadline ) {
        $now = time();
        $deadline_time = strtotime( $deadline );

        if ( $deadline_time <= $now ) {
            return null;
        }

        $remaining = $deadline_time - $now;
        return array(
            'seconds' => $remaining,
            'formatted' => $this->format_time_remaining( $remaining ),
        );
    }

    /**
     * Format time remaining
     */
    private function format_time_remaining( $seconds ) {
        $days = floor( $seconds / 86400 );
        $hours = floor( ( $seconds % 86400 ) / 3600 );
        $minutes = floor( ( $seconds % 3600 ) / 60 );
        $secs = $seconds % 60;

        if ( $days > 0 ) {
            return sprintf( '%d dager, %02d timer', $days, $hours );
        } elseif ( $hours > 0 ) {
            return sprintf( '%02d timer, %02d minutter', $hours, $minutes );
        } else {
            return sprintf( '%02d minutter, %02d sekunder', $minutes, $secs );
        }
    }

    /**
     * Search athletes via AJAX
     */
    public function search_athletes() {
        check_ajax_referer( 'ol-tipping-nonce', 'nonce' );

        $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        
        if ( strlen( $search ) < 2 ) {
            wp_send_json_error( 'Skriv minst 2 tegn' );
        }

        $athletes = $this->db->search_athletes( $search );
        
        $results = array();
        foreach ( $athletes as $athlete ) {
            $results[] = array(
                'id' => intval( $athlete->id ),
                'name' => $athlete->name,
                'country' => $athlete->country,
                'display' => $athlete->name . ' (' . $athlete->country . ')',
            );
        }

        wp_send_json_success( $results );
    }

    /**
     * Search countries/teams via AJAX (for team events)
     */
    public function search_countries() {
        check_ajax_referer( 'ol-tipping-nonce', 'nonce' );

        $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        
        if ( strlen( $search ) < 1 ) {
            // Return all countries/teams if no search
            $countries = $this->db->get_countries();
        } else {
            $countries = $this->db->search_countries( $search );
        }
        
        $results = array();
        foreach ( $countries as $country ) {
            // Use team_name (which shows "Norway 2" for second team) or fall back to name
            $team_name = isset( $country->team_name ) ? $country->team_name : $country->name;
            $team_number = isset( $country->team_number ) ? intval( $country->team_number ) : 1;
            
            $results[] = array(
                'id' => intval( $country->id ),
                'name' => $team_name,
                'code' => $country->code,
                'flag' => $country->flag,
                'team_number' => $team_number,
                'display' => $country->flag . ' ' . $team_name,
            );
        }

        wp_send_json_success( $results );
    }
}

} // end if class_exists

