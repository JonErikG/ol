<?php
/**
 * Database Class - Handles all database operations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'OL_Tipping_Database' ) ) {

class OL_Tipping_Database {
    private static $instance = null;
    private $wpdb;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Athletes table
        $athletes_table = $wpdb->prefix . 'ol_athletes';
        $athletes_sql = "CREATE TABLE IF NOT EXISTS $athletes_table (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            country VARCHAR(100) NOT NULL,
            bib_number VARCHAR(50),
            birth_year INT,
            status VARCHAR(50) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY athlete_unique (name, country)
        ) $charset_collate;";

        // Events table
        $events_table = $wpdb->prefix . 'ol_events';
        $events_sql = "CREATE TABLE IF NOT EXISTS $events_table (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_name VARCHAR(255) NOT NULL,
            discipline VARCHAR(100) NOT NULL,
            gender VARCHAR(10) NOT NULL,
            event_type VARCHAR(20) DEFAULT 'individual',
            event_date DATETIME NOT NULL,
            event_time VARCHAR(10),
            location VARCHAR(255),
            tipping_deadline DATETIME,
            status VARCHAR(50) DEFAULT 'open',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY event_unique (event_name, event_date)
        ) $charset_collate;";

        // Countries table (for team events) - supports multiple teams per country
        $countries_table = $wpdb->prefix . 'ol_countries';
        $countries_sql = "CREATE TABLE IF NOT EXISTS $countries_table (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(10) NOT NULL,
            flag VARCHAR(10),
            team_number INT DEFAULT 1,
            display_name VARCHAR(120),
            status VARCHAR(50) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY country_team_unique (code, team_number)
        ) $charset_collate;";

        // Tips table
        $tips_table = $wpdb->prefix . 'ol_tips';
        $tips_sql = "CREATE TABLE IF NOT EXISTS $tips_table (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            event_id BIGINT(20) UNSIGNED NOT NULL,
            athlete_id BIGINT(20) UNSIGNED DEFAULT NULL,
            country_id BIGINT(20) UNSIGNED DEFAULT NULL,
            position INT,
            points INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY tip_unique (user_id, event_id, position),
            FOREIGN KEY (event_id) REFERENCES {$wpdb->prefix}ol_events(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Results table
        $results_table = $wpdb->prefix . 'ol_results';
        $results_sql = "CREATE TABLE IF NOT EXISTS $results_table (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_id BIGINT(20) UNSIGNED NOT NULL,
            athlete_id BIGINT(20) UNSIGNED DEFAULT NULL,
            country_id BIGINT(20) UNSIGNED DEFAULT NULL,
            position INT NOT NULL,
            time VARCHAR(50),
            status VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES {$wpdb->prefix}ol_events(id) ON DELETE CASCADE,
            UNIQUE KEY result_unique (event_id, position)
        ) $charset_collate;";

        // Leaderboard table
        $leaderboard_table = $wpdb->prefix . 'ol_leaderboard';
        $leaderboard_sql = "CREATE TABLE IF NOT EXISTS $leaderboard_table (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            total_points INT DEFAULT 0,
            correct_tips INT DEFAULT 0,
            user_rank INT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY leaderboard_unique (user_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $athletes_sql );
        dbDelta( $events_sql );
        dbDelta( $countries_sql );
        dbDelta( $tips_sql );
        dbDelta( $results_sql );
        dbDelta( $leaderboard_sql );
        
        // Add event_type column if it doesn't exist
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $events_table LIKE 'event_type'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE $events_table ADD COLUMN event_type VARCHAR(20) DEFAULT 'individual' AFTER gender" );
        }
        
        // Add country_id column to tips if it doesn't exist
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $tips_table LIKE 'country_id'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE $tips_table ADD COLUMN country_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER athlete_id" );
        }
        
        // Add country_id column to results if it doesn't exist
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $results_table LIKE 'country_id'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE $results_table ADD COLUMN country_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER athlete_id" );
        }
        
        // Add team columns to countries table if they don't exist
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $countries_table LIKE 'team_number'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE $countries_table ADD COLUMN team_number INT DEFAULT 1 AFTER flag" );
            $wpdb->query( "ALTER TABLE $countries_table ADD COLUMN display_name VARCHAR(120) AFTER team_number" );
            // Drop old unique key and add new one
            $wpdb->query( "ALTER TABLE $countries_table DROP INDEX country_unique" );
            $wpdb->query( "ALTER TABLE $countries_table ADD UNIQUE KEY country_team_unique (code, team_number)" );
        }
        
        // Add user_rank column to leaderboard if it doesn't exist
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $leaderboard_table LIKE 'user_rank'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE $leaderboard_table ADD COLUMN user_rank INT AFTER correct_tips" );
        }

        update_option( 'ol_tipping_db_version', OL_TIPPING_DB_VERSION );
    }

    /**
     * Get athlete
     */
    public function get_athlete( $id ) {
        $athletes_table = $this->wpdb->prefix . 'ol_athletes';
        return $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT * FROM $athletes_table WHERE id = %d",
            $id
        ) );
    }

    /**
     * Get athletes for event
     */
    public function get_athletes( $limit = -1, $offset = 0 ) {
        $athletes_table = $this->wpdb->prefix . 'ol_athletes';
        $sql = "SELECT * FROM $athletes_table WHERE status = 'active' ORDER BY name ASC";
        if ( $limit > 0 ) {
            $sql .= " LIMIT " . intval( $limit ) . " OFFSET " . intval( $offset );
        }
        return $this->wpdb->get_results( $sql );
    }

    /**
     * Search athletes
     */
    public function search_athletes( $search ) {
        $athletes_table = $this->wpdb->prefix . 'ol_athletes';
        $search = sanitize_text_field( $search );
        $search_term = '%' . $this->wpdb->esc_like( $search ) . '%';
        
        $sql = $this->wpdb->prepare(
            "SELECT * FROM $athletes_table 
            WHERE status = 'active' 
            AND (name LIKE %s OR country LIKE %s) 
            ORDER BY name ASC 
            LIMIT 15",
            $search_term,
            $search_term
        );
        
        return $this->wpdb->get_results( $sql );
    }

    /**
     * Add athlete
     */
    public function add_athlete( $name, $country, $bib_number = null, $birth_year = null ) {
        $athletes_table = $this->wpdb->prefix . 'ol_athletes';
        
        // Check if athlete already exists
        $existing = $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT id FROM $athletes_table WHERE name = %s AND country = %s",
            sanitize_text_field( $name ),
            sanitize_text_field( $country )
        ) );
        
        if ( $existing ) {
            return $existing->id;
        }
        
        // Insert new athlete
        $result = $this->wpdb->insert(
            $athletes_table,
            array(
                'name' => sanitize_text_field( $name ),
                'country' => sanitize_text_field( $country ),
                'bib_number' => sanitize_text_field( $bib_number ),
                'birth_year' => intval( $birth_year ),
                'status' => 'active',
            ),
            array( '%s', '%s', '%s', '%d', '%s' )
        );
        
        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Delete all athletes
     */
    public function delete_all_athletes() {
        $athletes_table = $this->wpdb->prefix . 'ol_athletes';
        $tips_table = $this->wpdb->prefix . 'ol_tips';
        $results_table = $this->wpdb->prefix . 'ol_results';
        
        // Delete all tips first (foreign key constraint)
        $this->wpdb->query( "DELETE FROM $tips_table" );
        
        // Delete all results
        $this->wpdb->query( "DELETE FROM $results_table" );
        
        // Delete all athletes
        return $this->wpdb->query( "DELETE FROM $athletes_table" );
    }

    /**
     * Get event
     */
    public function get_event( $id ) {
        $events_table = $this->wpdb->prefix . 'ol_events';
        return $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT * FROM $events_table WHERE id = %d",
            $id
        ) );
    }

    /**
     * Get all events
     */
    public function get_events( $status = null, $limit = -1 ) {
        $events_table = $this->wpdb->prefix . 'ol_events';
        $sql = "SELECT * FROM $events_table";
        if ( $status ) {
            $sql .= $this->wpdb->prepare( " WHERE status = %s", $status );
        }
        $sql .= " ORDER BY event_date ASC";
        if ( $limit > 0 ) {
            $sql .= " LIMIT " . intval( $limit );
        }
        return $this->wpdb->get_results( $sql );
    }

    /**
     * Add event
     */
    public function add_event( $event_data ) {
        $events_table = $this->wpdb->prefix . 'ol_events';
        return $this->wpdb->insert(
            $events_table,
            array(
                'event_name' => sanitize_text_field( $event_data['event_name'] ),
                'discipline' => sanitize_text_field( $event_data['discipline'] ),
                'gender' => sanitize_text_field( $event_data['gender'] ),
                'event_type' => isset( $event_data['event_type'] ) ? sanitize_text_field( $event_data['event_type'] ) : 'individual',
                'event_date' => sanitize_text_field( $event_data['event_date'] ),
                'event_time' => isset( $event_data['event_time'] ) ? sanitize_text_field( $event_data['event_time'] ) : null,
                'location' => isset( $event_data['location'] ) ? sanitize_text_field( $event_data['location'] ) : null,
                'tipping_deadline' => isset( $event_data['tipping_deadline'] ) ? sanitize_text_field( $event_data['tipping_deadline'] ) : null,
                'status' => isset( $event_data['status'] ) ? sanitize_text_field( $event_data['status'] ) : 'open',
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Add tip
     */
    public function add_tip( $user_id, $event_id, $athlete_id, $position ) {
        $tips_table = $this->wpdb->prefix . 'ol_tips';
        return $this->wpdb->insert(
            $tips_table,
            array(
                'user_id' => intval( $user_id ),
                'event_id' => intval( $event_id ),
                'athlete_id' => intval( $athlete_id ),
                'position' => intval( $position ),
            ),
            array( '%d', '%d', '%d', '%d' )
        );
    }

    /**
     * Get user tips for event
     */
    public function get_user_tips( $user_id, $event_id ) {
        $tips_table = $this->wpdb->prefix . 'ol_tips';
        $athletes_table = $this->wpdb->prefix . 'ol_athletes';
        $countries_table = $this->wpdb->prefix . 'ol_countries';
        
        // Check if this is a team event
        if ( $this->is_team_event( $event_id ) ) {
            // Team event - get country/team tips
            return $this->wpdb->get_results( $this->wpdb->prepare(
                "SELECT t.*, COALESCE(c.display_name, c.name) as country_name, c.code, c.flag, c.team_number FROM $tips_table t
                LEFT JOIN $countries_table c ON t.country_id = c.id
                WHERE t.user_id = %d AND t.event_id = %d
                ORDER BY t.position ASC",
                $user_id,
                $event_id
            ) );
        } else {
            // Individual event - get athlete tips
            return $this->wpdb->get_results( $this->wpdb->prepare(
                "SELECT t.*, a.name, a.country FROM $tips_table t
                LEFT JOIN $athletes_table a ON t.athlete_id = a.id
                WHERE t.user_id = %d AND t.event_id = %d
                ORDER BY t.position ASC",
                $user_id,
                $event_id
            ) );
        }
    }

    /**
     * Add or update result
     */
    public function add_result( $event_id, $athlete_id, $position, $time = null, $status = null ) {
        $results_table = $this->wpdb->prefix . 'ol_results';
        $existing = $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT id FROM $results_table WHERE event_id = %d AND athlete_id = %d",
            $event_id,
            $athlete_id
        ) );

        if ( $existing ) {
            return $this->wpdb->update(
                $results_table,
                array(
                    'position' => intval( $position ),
                    'time' => $time ? sanitize_text_field( $time ) : null,
                    'status' => $status ? sanitize_text_field( $status ) : null,
                ),
                array( 'id' => $existing->id ),
                array( '%d', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            return $this->wpdb->insert(
                $results_table,
                array(
                    'event_id' => intval( $event_id ),
                    'athlete_id' => intval( $athlete_id ),
                    'position' => intval( $position ),
                    'time' => $time ? sanitize_text_field( $time ) : null,
                    'status' => $status ? sanitize_text_field( $status ) : null,
                ),
                array( '%d', '%d', '%d', '%s', '%s' )
            );
        }
    }

    /**
     * Get event results
     */
    public function get_event_results( $event_id ) {
        $results_table = $this->wpdb->prefix . 'ol_results';
        $athletes_table = $this->wpdb->prefix . 'ol_athletes';
        return $this->wpdb->get_results( $this->wpdb->prepare(
            "SELECT r.*, a.name, a.country FROM $results_table r
            JOIN $athletes_table a ON r.athlete_id = a.id
            WHERE r.event_id = %d
            ORDER BY r.position ASC",
            $event_id
        ) );
    }

    /**
     * Update leaderboard
     */
    public function update_leaderboard( $user_id ) {
        global $wpdb;
        $tips_table = $wpdb->prefix . 'ol_tips';
        $results_table = $wpdb->prefix . 'ol_results';
        $leaderboard_table = $wpdb->prefix . 'ol_leaderboard';

        // Calculate points
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.id, t.event_id, t.position AS tip_position, r.position AS actual_position
            FROM $tips_table t
            LEFT JOIN $results_table r 
                ON t.event_id = r.event_id 
                AND ( (t.athlete_id IS NOT NULL AND t.athlete_id = r.athlete_id)
                      OR (t.country_id IS NOT NULL AND t.country_id = r.country_id) )
            WHERE t.user_id = %d",
            $user_id
        ) );

        $total_points = 0;
        $correct_tips = 0;

        foreach ( $results as $result ) {
            // Only score if athlete/country exists in result set
            if ( $result->actual_position ) {
                $points = 0;

                // Scoring rules:
                // - Correct winner (tipped position 1 and actual position 1) = 3
                // - Correct name on positions 2-5 (exact position) = 2
                // - Correct name but wrong position (top 5) = 1
                if ( intval( $result->actual_position ) === 1 && intval( $result->tip_position ) === 1 ) {
                    $points = 3;
                } elseif ( intval( $result->actual_position ) >= 2 && intval( $result->actual_position ) <= 5
                    && intval( $result->tip_position ) === intval( $result->actual_position ) ) {
                    $points = 2;
                } elseif ( intval( $result->actual_position ) <= 5 ) {
                    $points = 1;
                }

                if ( $points > 0 ) {
                    $correct_tips++;
                    $total_points += $points;
                }

                $wpdb->update(
                    $tips_table,
                    array( 'points' => $points ),
                    array( 'id' => $result->id ),
                    array( '%d' ),
                    array( '%d' )
                );
            }
        }

        // Update or insert leaderboard
        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT id FROM $leaderboard_table WHERE user_id = %d",
            $user_id
        ) );

        if ( $existing ) {
            $wpdb->update(
                $leaderboard_table,
                array(
                    'total_points' => $total_points,
                    'correct_tips' => $correct_tips,
                ),
                array( 'id' => $existing->id ),
                array( '%d', '%d' ),
                array( '%d' )
            );
        } else {
            $wpdb->insert(
                $leaderboard_table,
                array(
                    'user_id' => $user_id,
                    'total_points' => $total_points,
                    'correct_tips' => $correct_tips,
                ),
                array( '%d', '%d', '%d' )
            );
        }
    }

    /**
     * Get leaderboard
     */
    public function get_leaderboard( $limit = 100 ) {
        $leaderboard_table = $this->wpdb->prefix . 'ol_leaderboard';
        $users_table = $this->wpdb->users;
        return $this->wpdb->get_results(
            "SELECT lb.*, u.display_name, u.user_login FROM $leaderboard_table lb
            JOIN $users_table u ON lb.user_id = u.ID
            ORDER BY lb.total_points DESC, lb.correct_tips DESC
            LIMIT " . intval( $limit )
        );
    }

    /**
     * Seed default data - OL events and athletes
     */
    public function seed_default_data() {
        global $wpdb;
        
        // Check if we already have data
        $events_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ol_events" );
        $athletes_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ol_athletes" );
        
        // Only seed if database is empty
        if ( $events_count > 0 && $athletes_count > 0 ) {
            // Update existing team events to have correct event_type
            $this->update_team_event_types();
            // Ensure countries are seeded
            $this->seed_countries();
            return;
        }
        
        // Seed events
        if ( $events_count == 0 ) {
            $this->seed_events();
        }
        
        // Seed athletes
        if ( $athletes_count == 0 ) {
            $this->seed_athletes();
        }
    }

    /**
     * Update existing team events to have correct event_type
     */
    private function update_team_event_types() {
        global $wpdb;
        $events_table = $wpdb->prefix . 'ol_events';
        
        // Team Sprint and Stafett are team events
        $wpdb->query(
            "UPDATE $events_table 
            SET event_type = 'team' 
            WHERE event_name LIKE '%Team Sprint%' OR event_name LIKE '%Stafett%'"
        );
        
        // All other events are individual (in case not set)
        $wpdb->query(
            "UPDATE $events_table 
            SET event_type = 'individual' 
            WHERE event_type IS NULL OR event_type = ''"
        );
    }

    /**
     * Seed OL 2026 events
     */
    private function seed_events() {
        $events = array(
            array(
                'event_name' => 'Sprint Klassisk Stil - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_type' => 'individual',
                'event_date' => '2026-02-08 12:00:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-08 11:00:00',
            ),
            array(
                'event_name' => '10km Klassisk Stil - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_type' => 'individual',
                'event_date' => '2026-02-10 14:00:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-10 13:00:00',
            ),
            array(
                'event_name' => 'Skiathlon 15km - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_type' => 'individual',
                'event_date' => '2026-02-12 14:30:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-12 13:30:00',
            ),
            array(
                'event_name' => '30km Fri Stil - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_type' => 'individual',
                'event_date' => '2026-02-14 09:00:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-14 08:00:00',
            ),
            array(
                'event_name' => 'Team Sprint Klassisk Stil - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_type' => 'team',
                'event_date' => '2026-02-16 14:00:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-16 13:00:00',
            ),
            array(
                'event_name' => 'Stafett 4x5km - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_type' => 'team',
                'event_date' => '2026-02-18 14:00:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-18 13:00:00',
            ),
            array(
                'event_name' => '50km Fri Stil - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_type' => 'individual',
                'event_date' => '2026-02-20 09:00:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-20 08:00:00',
            ),
        );

        foreach ( $events as $event ) {
            $this->add_event( $event );
        }
        
        // Seed countries for team events
        $this->seed_countries();
    }

    /**
     * Seed athletes
     */
    private function seed_athletes() {
        $athletes = array(
            // Norway
            array( 'Johannes Høsflot Klæbo', 'Norway' ),
            array( 'Simen Hegstad Krüger', 'Norway' ),
            array( 'Harald Østberg Amundsen', 'Norway' ),
            array( 'Erik Valnes', 'Norway' ),
            array( 'Pål Golberg', 'Norway' ),
            array( 'Didrik Tønseth', 'Norway' ),
            array( 'Iver Tildheim Andersen', 'Norway' ),
            array( 'Martin Løwstrøm Nyenget', 'Norway' ),
            array( 'Håvard Solås Taugbøl', 'Norway' ),
            array( 'Sjur Røthe', 'Norway' ),
            array( 'Finn Hågen Krogh', 'Norway' ),
            array( 'Even Northug', 'Norway' ),
            array( 'Thomas Helland Larsen', 'Norway' ),
            array( 'Emil Iversen', 'Norway' ),
            array( 'Hans Christer Holund', 'Norway' ),
            
            // Sweden
            array( 'William Poromaa', 'Sweden' ),
            array( 'Edvin Anger', 'Sweden' ),
            array( 'Calle Halfvarsson', 'Sweden' ),
            array( 'Jens Burman', 'Sweden' ),
            array( 'Marcus Grate', 'Sweden' ),
            array( 'Oskar Svensson', 'Sweden' ),
            array( 'Johan Hägglund', 'Sweden' ),
            array( 'Gustav Berglund', 'Sweden' ),
            
            // Finland
            array( 'Iivo Niskanen', 'Finland' ),
            array( 'Perttu Hyvärinen', 'Finland' ),
            array( 'Lauri Vuorinen', 'Finland' ),
            array( 'Remi Lindholm', 'Finland' ),
            array( 'Joni Mäki', 'Finland' ),
            array( 'Niko Anttola', 'Finland' ),
            
            // Russia
            array( 'Alexander Bolshunov', 'Russia' ),
            array( 'Sergey Ustiugov', 'Russia' ),
            array( 'Artem Maltsev', 'Russia' ),
            array( 'Denis Spitsov', 'Russia' ),
            array( 'Ivan Yakimushkin', 'Russia' ),
            
            // France
            array( 'Lucas Chanavat', 'France' ),
            array( 'Hugo Lapalus', 'France' ),
            array( 'Richard Jouve', 'France' ),
            array( 'Maurice Manificat', 'France' ),
            array( 'Clément Parisse', 'France' ),
            
            // Italy
            array( 'Federico Pellegrino', 'Italy' ),
            array( 'Francesco De Fabiani', 'Italy' ),
            array( 'Giandomenico Salvadori', 'Italy' ),
            array( 'Paolo Ventura', 'Italy' ),
            
            // Germany
            array( 'Friedrich Moch', 'Germany' ),
            array( 'Janosch Brugger', 'Germany' ),
            array( 'Florian Notz', 'Germany' ),
            array( 'Lucas Bögl', 'Germany' ),
            
            // Switzerland
            array( 'Dario Cologna', 'Switzerland' ),
            array( 'Roman Furger', 'Switzerland' ),
            array( 'Jonas Baumann', 'Switzerland' ),
            array( 'Valerio Grond', 'Switzerland' ),
            
            // Austria
            array( 'Mika Vermeulen', 'Austria' ),
            array( 'Benjamin Moser', 'Austria' ),
            
            // USA
            array( 'Gus Schumacher', 'USA' ),
            array( 'JC Schoonmaker', 'USA' ),
            array( 'Ben Ogden', 'USA' ),
            
            // Canada
            array( 'Antoine Cyr', 'Canada' ),
            array( 'Graham Ritchie', 'Canada' ),
            
            // Great Britain
            array( 'Andrew Musgrave', 'Great Britain' ),
            array( 'Andrew Young', 'Great Britain' ),
            
            // Kazakhstan
            array( 'Yevgeniy Velichko', 'Kazakhstan' ),
            
            // Japan
            array( 'Naoto Baba', 'Japan' ),
            array( 'Reo Oba', 'Japan' ),
            
            // Estonia
            array( 'Marko Kilp', 'Estonia' ),
            array( 'Karel Tammjärv', 'Estonia' ),
            
            // Czech Republic
            array( 'Michal Novák', 'Czech Republic' ),
            array( 'Adam Fellner', 'Czech Republic' ),
            
            // Slovenia
            array( 'Miha Šimenc', 'Slovenia' ),
            array( 'Vili Črv', 'Slovenia' ),
            
            // Poland
            array( 'Maciej Staręga', 'Poland' ),
            array( 'Kamil Bury', 'Poland' ),
        );

        foreach ( $athletes as $athlete ) {
            $this->add_athlete( $athlete[0], $athlete[1] );
        }
    }

    /**
     * Seed countries/teams for team events
     * Countries with 2 teams: Norway, Sweden
     */
    private function seed_countries() {
        global $wpdb;
        $countries_table = $wpdb->prefix . 'ol_countries';
        
        // Countries with single team
        $countries = array(
            array( 'Finland', 'FIN', '🇫🇮', 1 ),
            array( 'France', 'FRA', '🇫🇷', 1 ),
            array( 'Italy', 'ITA', '🇮🇹', 1 ),
            array( 'Germany', 'GER', '🇩🇪', 1 ),
            array( 'Switzerland', 'SUI', '🇨🇭', 1 ),
            array( 'Austria', 'AUT', '🇦🇹', 1 ),
            array( 'USA', 'USA', '🇺🇸', 1 ),
            array( 'Canada', 'CAN', '🇨🇦', 1 ),
            array( 'Great Britain', 'GBR', '🇬🇧', 1 ),
            array( 'Japan', 'JPN', '🇯🇵', 1 ),
            array( 'Estonia', 'EST', '🇪🇪', 1 ),
            array( 'Czech Republic', 'CZE', '🇨🇿', 1 ),
            array( 'Slovenia', 'SLO', '🇸🇮', 1 ),
            array( 'Poland', 'POL', '🇵🇱', 1 ),
            array( 'Latvia', 'LAT', '🇱🇻', 1 ),
            array( 'Lithuania', 'LTU', '🇱🇹', 1 ),
            array( 'Kazakhstan', 'KAZ', '🇰🇿', 1 ),
            array( 'China', 'CHN', '🇨🇳', 1 ),
            // Norway with 2 teams
            array( 'Norway', 'NOR', '🇳🇴', 1 ),
            array( 'Norway', 'NOR', '🇳🇴', 2 ),
            // Sweden with 2 teams
            array( 'Sweden', 'SWE', '🇸🇪', 1 ),
            array( 'Sweden', 'SWE', '🇸🇪', 2 ),
        );

        foreach ( $countries as $country ) {
            $team_number = $country[3];
            $display_name = $team_number > 1 ? $country[0] . ' ' . $team_number : $country[0];
            
            $wpdb->replace(
                $countries_table,
                array(
                    'name' => $country[0],
                    'code' => $country[1],
                    'flag' => $country[2],
                    'team_number' => $team_number,
                    'display_name' => $display_name,
                    'status' => 'active',
                ),
                array( '%s', '%s', '%s', '%d', '%s', '%s' )
            );
        }
    }

    /**
     * Get all countries/teams
     */
    public function get_countries() {
        $countries_table = $this->wpdb->prefix . 'ol_countries';
        return $this->wpdb->get_results(
            "SELECT *, COALESCE(display_name, name) as team_name FROM $countries_table WHERE status = 'active' ORDER BY name ASC, team_number ASC"
        );
    }

    /**
     * Get country/team by ID
     */
    public function get_country( $id ) {
        $countries_table = $this->wpdb->prefix . 'ol_countries';
        return $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT *, COALESCE(display_name, name) as team_name FROM $countries_table WHERE id = %d",
            $id
        ) );
    }

    /**
     * Search countries/teams
     */
    public function search_countries( $search ) {
        $countries_table = $this->wpdb->prefix . 'ol_countries';
        $search = sanitize_text_field( $search );
        $search_term = '%' . $this->wpdb->esc_like( $search ) . '%';
        
        return $this->wpdb->get_results( $this->wpdb->prepare(
            "SELECT *, COALESCE(display_name, name) as team_name FROM $countries_table 
            WHERE status = 'active' 
            AND (name LIKE %s OR code LIKE %s OR display_name LIKE %s) 
            ORDER BY name ASC, team_number ASC 
            LIMIT 20",
            $search_term,
            $search_term,
            $search_term
        ) );
    }

    /**
     * Check if event is team event
     */
    public function is_team_event( $event_id ) {
        $events_table = $this->wpdb->prefix . 'ol_events';
        $event_type = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT event_type FROM $events_table WHERE id = %d",
            $event_id
        ) );
        return $event_type === 'team';
    }

    /**
     * Re-seed countries/teams (public method for admin use)
     */
    public function reseed_teams() {
        $this->seed_countries();
    }

    /**
     * Create sample data for testing
     */
    public function create_sample_data() {
        global $wpdb;

        // Ensure migrations are run
        $this->create_tables();

        // Create 5 test users
        $users = array(
            array( 'username' => 'tippern1', 'email' => 'tipper1@example.com', 'display_name' => 'Tipper Ola' ),
            array( 'username' => 'tippern2', 'email' => 'tipper2@example.com', 'display_name' => 'Tipper Kari' ),
            array( 'username' => 'tippern3', 'email' => 'tipper3@example.com', 'display_name' => 'Tipper Per' ),
            array( 'username' => 'tippern4', 'email' => 'tipper4@example.com', 'display_name' => 'Tipper Anna' ),
            array( 'username' => 'tippern5', 'email' => 'tipper5@example.com', 'display_name' => 'Tipper Jonas' ),
        );

        $test_user_ids = array();
        foreach ( $users as $user ) {
            $user_id = username_exists( $user['username'] );
            if ( ! $user_id ) {
                $user_id = wp_create_user( $user['username'], 'password123', $user['email'] );
                wp_update_user( array(
                    'ID' => $user_id,
                    'display_name' => $user['display_name'],
                ) );
            }
            if ( ! is_wp_error( $user_id ) ) {
                $test_user_ids[] = $user_id;
            }
        }

        // Get first two events (should be individual events)
        $events = $wpdb->get_results( 
            $wpdb->prepare(
                "SELECT id, event_type FROM {$wpdb->prefix}ol_events 
                WHERE event_type = %s OR event_type IS NULL 
                LIMIT 2",
                'individual'
            )
        );

        if ( count( $events ) < 2 ) {
            return false; // Not enough events
        }

        // Get some athletes for tipping
        $athletes = $wpdb->get_results( 
            "SELECT id FROM {$wpdb->prefix}ol_athletes LIMIT 10"
        );

        if ( count( $athletes ) < 10 ) {
            return false; // Not enough athletes
        }

        // Varied tips per user - different predictions create scoring variation
        $varied_tips = array(
            // User 1: Tipper Ola - Gets most right
            array( 0, 1, 2, 3, 4 ),  // Positions 1-5: athletes 0,1,2,3,4
            // User 2: Tipper Kari - Some right, some wrong
            array( 1, 0, 3, 2, 4 ),  // Positions 1-5: athletes 1,0,3,2,4 (swapped some)
            // User 3: Tipper Per - Different predictions
            array( 2, 3, 1, 4, 0 ),  // Positions 1-5: athletes 2,3,1,4,0
            // User 4: Tipper Anna - More variation
            array( 3, 4, 0, 1, 2 ),  // Positions 1-5: athletes 3,4,0,1,2
            // User 5: Tipper Jonas - Completely different
            array( 4, 2, 3, 0, 1 ),  // Positions 1-5: athletes 4,2,3,0,1
        );

        // Add tips from each test user to both events with varied predictions
        foreach ( $test_user_ids as $idx => $user_id ) {
            $user_tips = $varied_tips[ $idx ];
            
            foreach ( $events as $event ) {
                // Clear existing tips for this user/event
                $wpdb->query( $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}ol_tips WHERE user_id = %d AND event_id = %d",
                    $user_id,
                    $event->id
                ) );

                // Add 5 tips with varied predictions
                foreach ( $user_tips as $position => $athlete_idx ) {
                    $this->add_tip( $user_id, $event->id, $athletes[ $athlete_idx ]->id, $position + 1 );
                }
            }
        }

        // Add results to both events - actual race results
        // Event 1 results: 0,1,2,3,4 (exact match for User 1)
        $event1_results = array(
            array( 'athlete_id' => $athletes[0]->id, 'position' => 1 ), // User 1: 3 pts, Others: 1 pt
            array( 'athlete_id' => $athletes[1]->id, 'position' => 2 ), // User 1: 2 pts, User 2: 2pts, Others: 1pt
            array( 'athlete_id' => $athletes[2]->id, 'position' => 3 ), // User 1: 2 pts, Others: 1pt
            array( 'athlete_id' => $athletes[3]->id, 'position' => 4 ), // User 1: 2 pts, User 2: 1pt, Others: 1pt
            array( 'athlete_id' => $athletes[4]->id, 'position' => 5 ), // User 1: 2 pts, Others: 1pt
        );

        // Event 2 results: Different order - 1,2,0,3,4 (nobody gets perfect)
        $event2_results = array(
            array( 'athlete_id' => $athletes[1]->id, 'position' => 1 ), // User 1: 1pt, User 2: 3pts, Others: 1pt
            array( 'athlete_id' => $athletes[2]->id, 'position' => 2 ), // User 1: 1pt, User 3: 3pts, Others: 1pt
            array( 'athlete_id' => $athletes[0]->id, 'position' => 3 ), // User 1: 1pt, User 3: 1pt, User 4: 1pt
            array( 'athlete_id' => $athletes[3]->id, 'position' => 4 ), // User 1: 1pt, User 2: 1pt, User 4: 2pts, Others: 1pt
            array( 'athlete_id' => $athletes[4]->id, 'position' => 5 ), // User 1: 2pts, Others: 1pt
        );

        foreach ( $event1_results as $result ) {
            $this->add_result( $events[0]->id, $result['athlete_id'], $result['position'] );
        }

        foreach ( $event2_results as $result ) {
            $this->add_result( $events[1]->id, $result['athlete_id'], $result['position'] );
        }

        // Update leaderboard for all test users
        foreach ( $test_user_ids as $user_id ) {
            $this->update_leaderboard( $user_id );
        }

        // Mark sample data
        update_option( 'ol_tipping_sample_data_created', true );

        return true;
    }

    /**
     * Delete sample data
     */
    public function delete_sample_data() {
        global $wpdb;

        // Delete tips from sample users
        $sample_users = array( 'tippern1', 'tippern2', 'tippern3', 'tippern4', 'tippern5' );
        foreach ( $sample_users as $username ) {
            $user = get_user_by( 'login', $username );
            if ( $user ) {
                // Delete tips
                $wpdb->query( $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}ol_tips WHERE user_id = %d",
                    $user->ID
                ) );

                // Delete leaderboard entry
                $wpdb->query( $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}ol_leaderboard WHERE user_id = %d",
                    $user->ID
                ) );

                // Delete user
                wp_delete_user( $user->ID, false );
            }
        }

        // Delete sample results (keep first 2 events clean by removing results)
        $events = $wpdb->get_results( 
            "SELECT id FROM {$wpdb->prefix}ol_events ORDER BY id ASC LIMIT 2"
        );
        foreach ( $events as $event ) {
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}ol_results WHERE event_id = %d",
                $event->id
            ) );
        }

        // Remove marker
        delete_option( 'ol_tipping_sample_data_created' );

        return true;
    }

    /**
     * Check if sample data exists
     */
    public function sample_data_exists() {
        return (bool) get_option( 'ol_tipping_sample_data_created' );
    }
}

} // end if class_exists
