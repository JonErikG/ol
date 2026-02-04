<?php
/**
 * Plugin Name: OL Tipping - Cross-Country Skiing
 * Plugin URI: https://ol-tipping.local
 * Description: Tippekonkurranse for OL langrenn Milano-Cortina 2026
 * Version: 1.0.0
 * Author: OL Tipping
 * License: GPL2
 * Domain Path: /languages
 * Text Domain: ol-tipping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'OL_TIPPING_VERSION', '1.0.0' );
define( 'OL_TIPPING_PATH', plugin_dir_path( __FILE__ ) );
define( 'OL_TIPPING_URL', plugin_dir_url( __FILE__ ) );
define( 'OL_TIPPING_DB_VERSION', 1 );

// Load plugin dependencies
if ( file_exists( OL_TIPPING_PATH . 'includes/class-database.php' ) ) {
    require_once OL_TIPPING_PATH . 'includes/class-database.php';
}
if ( file_exists( OL_TIPPING_PATH . 'includes/class-admin.php' ) ) {
    require_once OL_TIPPING_PATH . 'includes/class-admin.php';
}
if ( file_exists( OL_TIPPING_PATH . 'includes/class-frontend.php' ) ) {
    require_once OL_TIPPING_PATH . 'includes/class-frontend.php';
}
if ( file_exists( OL_TIPPING_PATH . 'includes/class-api.php' ) ) {
    require_once OL_TIPPING_PATH . 'includes/class-api.php';
}
if ( file_exists( OL_TIPPING_PATH . 'includes/class-tipping.php' ) ) {
    require_once OL_TIPPING_PATH . 'includes/class-tipping.php';
}

/**
 * Main Plugin Class
 */
class OL_Tipping {
    private static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        error_log( 'OL_Tipping constructor called' );
        $this->init();
    }

    public function init() {
        // Hooks for plugin activation
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // Initialize database immediately
        try {
            OL_Tipping_Database::get_instance();
        } catch ( Exception $e ) {
            error_log( 'OL Tipping Database Init Error: ' . $e->getMessage() );
            return;
        }
        
        // Admin: Initialize early so menu hooks work
        if ( is_admin() ) {
            $this->init_admin_classes();
        }
        
        // Frontend: Register shortcodes immediately (not on hook)
        $this->init_frontend_classes();
        
        // AJAX handlers need to work for both admin and frontend
        $this->init_ajax_handlers();
        
        // Load text domain
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Enqueue styles and scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    public function init_admin_classes() {
        try {
            OL_Tipping_Admin::get_instance();
        } catch ( Exception $e ) {
            error_log( 'OL Tipping Admin Init Error: ' . $e->getMessage() );
        }
    }

    public function init_frontend_classes() {
        try {
            OL_Tipping_Frontend::get_instance();
        } catch ( Exception $e ) {
            error_log( 'OL Tipping Frontend Init Error: ' . $e->getMessage() );
        }
    }
    
    public function init_ajax_handlers() {
        try {
            OL_Tipping_Tipping::get_instance();
        } catch ( Exception $e ) {
            error_log( 'OL Tipping AJAX Init Error: ' . $e->getMessage() );
        }
    }

    public function activate() {
        $db = OL_Tipping_Database::get_instance();
        $db->create_tables();
        
        // Import default events and athletes if database is empty
        $db->seed_default_data();
        
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'ol-tipping', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style( 'ol-tipping-frontend', OL_TIPPING_URL . 'assets/css/frontend.css', array(), OL_TIPPING_VERSION );
        wp_enqueue_style( 'ol-tipping-countdown', OL_TIPPING_URL . 'assets/css/countdown.css', array(), OL_TIPPING_VERSION );
        wp_enqueue_script( 'ol-tipping-countdown', OL_TIPPING_URL . 'assets/js/countdown.js', array( 'jquery' ), OL_TIPPING_VERSION, true );
        wp_enqueue_script( 'ol-tipping-frontend', OL_TIPPING_URL . 'assets/js/frontend.js', array( 'jquery', 'ol-tipping-countdown' ), OL_TIPPING_VERSION, true );
        wp_localize_script( 'ol-tipping-frontend', 'olTippingData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'ol-tipping-nonce' ),
            'userLoggedIn' => is_user_logged_in(),
        ) );
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style( 'ol-tipping-admin', OL_TIPPING_URL . 'assets/css/admin.css', array(), OL_TIPPING_VERSION );
        wp_enqueue_script( 'ol-tipping-admin', OL_TIPPING_URL . 'assets/js/admin.js', array( 'jquery' ), OL_TIPPING_VERSION, true );
    }
}

// Initialize the plugin
OL_Tipping::get_instance();
