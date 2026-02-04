<?php
/**
 * API Class - Handles REST API endpoints
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'OL_Tipping_API' ) ) {

class OL_Tipping_API {
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
        add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
    }

    /**
     * Register REST API endpoints
     */
    public function register_endpoints() {
        register_rest_route(
            'ol-tipping/v1',
            '/events',
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_events' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'ol-tipping/v1',
            '/athletes',
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_athletes' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'ol-tipping/v1',
            '/event/(?P<id>\d+)',
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_event' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'ol-tipping/v1',
            '/event/(?P<id>\d+)/results',
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_event_results' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'ol-tipping/v1',
            '/leaderboard',
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_leaderboard' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Get events endpoint
     */
    public function get_events( $request ) {
        $events = $this->db->get_events();
        return rest_ensure_response( $events );
    }

    /**
     * Get athletes endpoint
     */
    public function get_athletes( $request ) {
        $athletes = $this->db->get_athletes();
        return rest_ensure_response( $athletes );
    }

    /**
     * Get single event
     */
    public function get_event( $request ) {
        $event_id = intval( $request['id'] );
        $event = $this->db->get_event( $event_id );
        return rest_ensure_response( $event );
    }

    /**
     * Get event results
     */
    public function get_event_results( $request ) {
        $event_id = intval( $request['id'] );
        $results = $this->db->get_event_results( $event_id );
        return rest_ensure_response( $results );
    }

    /**
     * Get leaderboard
     */
    public function get_leaderboard( $request ) {
        $leaderboard = $this->db->get_leaderboard();
        return rest_ensure_response( $leaderboard );
    }
}

} // end if class_exists
