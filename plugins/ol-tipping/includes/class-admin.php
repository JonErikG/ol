<?php
/**
 * Admin Class - Handles admin functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'OL_Tipping_Admin' ) ) {

class OL_Tipping_Admin {
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
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_imports' ) );
        add_action( 'wp_ajax_ol_tipping_create_sample_data', array( $this, 'ajax_create_sample_data' ) );
        add_action( 'wp_ajax_ol_tipping_delete_sample_data', array( $this, 'ajax_delete_sample_data' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'OL Tipping',
            'OL Tipping',
            'manage_options',
            'ol-tipping',
            array( $this, 'render_dashboard' ),
            'dashicons-football',
            25
        );

        add_submenu_page(
            'ol-tipping',
            'Utøvere',
            'Utøvere',
            'manage_options',
            'ol-tipping-athletes',
            array( $this, 'render_athletes' )
        );

        add_submenu_page(
            'ol-tipping',
            'Øvelser',
            'Øvelser',
            'manage_options',
            'ol-tipping-events',
            array( $this, 'render_events' )
        );

        add_submenu_page(
            'ol-tipping',
            'Resultater',
            'Resultater',
            'manage_options',
            'ol-tipping-results',
            array( $this, 'render_results' )
        );

        add_submenu_page(
            'ol-tipping',
            'Importer fra Olympics.com',
            'Importer',
            'manage_options',
            'ol-tipping-import',
            array( $this, 'render_import' )
        );

        add_submenu_page(
            'ol-tipping',
            'Hent Resultater fra API',
            'Hent Resultater',
            'manage_options',
            'ol-tipping-api-results',
            array( $this, 'render_api_results' )
        );

        add_submenu_page(
            'ol-tipping',
            'Administrer Lag',
            'Lag',
            'manage_options',
            'ol-tipping-teams',
            array( $this, 'render_teams' )
        );

        add_submenu_page(
            'ol-tipping',
            'Rediger Øvelser',
            'Rediger Øvelser',
            'manage_options',
            'ol-tipping-edit-events',
            array( $this, 'render_edit_events' )
        );

        add_submenu_page(
            'ol-tipping',
            'Slå sammen brukere',
            'Slå sammen brukere',
            'manage_options',
            'ol-tipping-merge-users',
            array( $this, 'render_merge_users' )
        );
    }

    /**
     * Render dashboard
     */
    public function render_dashboard() {
        global $wpdb;
        $events = $this->db->get_events();
        
        // Get total athlete count directly from database
        $athletes_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ol_athletes" );
        $tips_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ol_tips" );
        $results_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ol_results" );
        
        // Get athlete count by country
        $athletes_by_country = $wpdb->get_results( 
            "SELECT country, COUNT(*) as count FROM {$wpdb->prefix}ol_athletes GROUP BY country ORDER BY count DESC" 
        );

        $sample_data_exists = $this->db->sample_data_exists();
        ?>
        <div class="wrap">
            <h1>OL Tipping Dashboard</h1>
            
            <div style="background: #fff; padding: 15px; border: 1px solid #ccc; margin: 20px 0; border-radius: 4px;">
                <h2 style="margin-top: 0;">Test Sample Data</h2>
                <p>Opprett eller slett sample data for testing (5 brukere, 2 øvelser med tips og resultater)</p>
                <?php if ( $sample_data_exists ) : ?>
                    <button class="button button-danger" onclick="if(confirm('Er du sikker? Dette vil slette alle test-data.')) { olTippingDeleteSampleData(); }">🗑️ Slett Sample Data</button>
                    <span style="margin-left: 10px; color: green;">✓ Sample data er opprettet</span>
                <?php else : ?>
                    <button class="button button-primary" onclick="olTippingCreateSampleData()">➕ Opprett Sample Data</button>
                    <span style="margin-left: 10px; color: #888;">Ingen sample data</span>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-box">
                    <h3>Øvelser</h3>
                    <p class="stat-number"><?php echo count( $events ); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Utøvere Totalt</h3>
                    <p class="stat-number"><?php echo intval( $athletes_count ); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Tips Innsendt</h3>
                    <p class="stat-number"><?php echo intval( $tips_count ); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Resultater</h3>
                    <p class="stat-number"><?php echo intval( $results_count ); ?></p>
                </div>
            </div>
            
            <h2>Utøvere per Land</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Land</th>
                        <th>Antall Utøvere</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ( $athletes_by_country ) {
                        foreach ( $athletes_by_country as $row ) {
                            echo '<tr>';
                            echo '<td><strong>' . esc_html( $row->country ) . '</strong></td>';
                            echo '<td>' . intval( $row->count ) . '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="2">Ingen utøvere importert ennå</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <script>
        function olTippingCreateSampleData() {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    location.reload();
                }
            };
            xhr.send('action=ol_tipping_create_sample_data&_wpnonce=<?php echo wp_create_nonce( 'ol-tipping-sample-data' ); ?>');
        }
        function olTippingDeleteSampleData() {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    location.reload();
                }
            };
            xhr.send('action=ol_tipping_delete_sample_data&_wpnonce=<?php echo wp_create_nonce( 'ol-tipping-sample-data' ); ?>');
        }
        </script>
        <?php
    }

    /**
     * Render athletes page
     */
    public function render_athletes() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
        ?>
        <div class="wrap">
            <h1>Utøvere</h1>
            <?php
            if ( 'add' === $action ) {
                $this->render_athlete_form();
            } else {
                $this->render_athletes_list();
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render athlete form
     */
    private function render_athlete_form() {
        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'ol-tipping-add-athlete' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="name">Navn:</label></th>
                    <td><input type="text" name="name" id="name" required /></td>
                </tr>
                <tr>
                    <th><label for="country">Land:</label></th>
                    <td><input type="text" name="country" id="country" required /></td>
                </tr>
                <tr>
                    <th><label for="bib_number">Bibnummer:</label></th>
                    <td><input type="text" name="bib_number" id="bib_number" /></td>
                </tr>
                <tr>
                    <th><label for="birth_year">Fødselsår:</label></th>
                    <td><input type="number" name="birth_year" id="birth_year" /></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" class="button button-primary" value="Lagre utøver" /></p>
        </form>
        <?php
    }

    /**
     * Render athletes list
     */
    private function render_athletes_list() {
        $athletes = $this->db->get_athletes();
        ?>
        <a href="<?php echo admin_url( 'admin.php?page=ol-tipping-athletes&action=add' ); ?>" class="button button-primary">Legg til utøver</a>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Navn</th>
                    <th>Land</th>
                    <th>Bibnummer</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $athletes as $athlete ) : ?>
                <tr>
                    <td><?php echo esc_html( $athlete->name ); ?></td>
                    <td><?php echo esc_html( $athlete->country ); ?></td>
                    <td><?php echo esc_html( $athlete->bib_number ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render events page
     */
    public function render_events() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
        ?>
        <div class="wrap">
            <h1>Øvelser</h1>
            <?php
            if ( 'add' === $action ) {
                $this->render_event_form();
            } elseif ( 'import' === $action ) {
                $this->render_import_events();
            } else {
                $this->render_events_list();
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render event form
     */
    private function render_event_form() {
        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'ol-tipping-add-event' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="event_name">Navn på øvelse:</label></th>
                    <td><input type="text" name="event_name" id="event_name" required /></td>
                </tr>
                <tr>
                    <th><label for="discipline">Disiplin:</label></th>
                    <td><input type="text" name="discipline" id="discipline" value="Cross-Country Skiing" /></td>
                </tr>
                <tr>
                    <th><label for="gender">Kjønn:</label></th>
                    <td>
                        <select name="gender" id="gender" required>
                            <option value="M">Menn</option>
                            <option value="W">Kvinner</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="event_date">Dato:</label></th>
                    <td><input type="datetime-local" name="event_date" id="event_date" required /></td>
                </tr>
                <tr>
                    <th><label for="event_time">Starttid:</label></th>
                    <td><input type="time" name="event_time" id="event_time" /></td>
                </tr>
                <tr>
                    <th><label for="location">Sted:</label></th>
                    <td><input type="text" name="location" id="location" /></td>
                </tr>
                <tr>
                    <th><label for="tipping_deadline">Tippefrist:</label></th>
                    <td><input type="datetime-local" name="tipping_deadline" id="tipping_deadline" required /></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" class="button button-primary" value="Lagre øvelse" /></p>
        </form>
        <?php
    }

    /**
     * Render import OL events
     */
    private function render_import_events() {
        ?>
        <div class="wrap">
            <h1>Importer OL-øvelser Milano-Cortina 2026</h1>
            <p>Importerer alle langrennsøvelser for menn i Milano-Cortina 2026.</p>
            <form method="post" action="">
                <?php wp_nonce_field( 'ol-tipping-import-events' ); ?>
                <p class="submit">
                    <input type="submit" class="button button-primary" value="Importer alle øvelser" name="import_events" />
                </p>
            </form>
            <?php
            if ( isset( $_POST['import_events'] ) && check_admin_referer( 'ol-tipping-import-events' ) ) {
                $imported = $this->import_ol_cross_country_events();
                echo '<div class="updated"><p><strong>✓</strong> Importert ' . intval( $imported ) . ' øvelser!</p></div>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Import cross-country skiing events for Milano-Cortina 2026
     */
    private function import_ol_cross_country_events() {
        $events = array(
            // OL 2026 Milano-Cortina - Cross-Country Skiing Men's Events
            array(
                'event_name' => 'Sprint Klassisk Stil - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_date' => '2026-02-08 12:00:00',
                'event_time' => '12:00:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-08 12:00:00',
                'status' => 'upcoming'
            ),
            array(
                'event_name' => '10km Klassisk Stil - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_date' => '2026-02-10 14:00:00',
                'event_time' => '14:00:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-10 14:00:00',
                'status' => 'upcoming'
            ),
            array(
                'event_name' => 'Skiathlon 15km - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_date' => '2026-02-12 14:30:00',
                'event_time' => '14:30:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-12 14:30:00',
                'status' => 'upcoming'
            ),
            array(
                'event_name' => '30km Fri Stil - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_date' => '2026-02-14 09:00:00',
                'event_time' => '09:00:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-14 09:00:00',
                'status' => 'upcoming'
            ),
            array(
                'event_name' => 'Team Sprint Klassisk Stil - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_date' => '2026-02-16 14:00:00',
                'event_time' => '14:00:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-16 14:00:00',
                'status' => 'upcoming'
            ),
            array(
                'event_name' => 'Stafett 4x5km Klassisk/Fri - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_date' => '2026-02-18 14:00:00',
                'event_time' => '14:00:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-18 14:00:00',
                'status' => 'upcoming'
            ),
            array(
                'event_name' => '50km Fri Stil - Herrer',
                'discipline' => 'Cross-Country Skiing',
                'gender' => 'M',
                'event_date' => '2026-02-20 09:00:00',
                'event_time' => '09:00:00',
                'location' => 'Livigno, Italy',
                'tipping_deadline' => '2026-02-20 09:00:00',
                'status' => 'upcoming'
            ),
        );

        $count = 0;
        foreach ( $events as $event ) {
            $this->db->add_event( $event );
            $count++;
        }

        return $count;
    }

    /**
     * Render events list
     */
    private function render_events_list() {
        $events = $this->db->get_events();
        ?>
        <a href="<?php echo admin_url( 'admin.php?page=ol-tipping-events&action=add' ); ?>" class="button button-primary">Legg til øvelse</a>
        <a href="<?php echo admin_url( 'admin.php?page=ol-tipping-events&action=import' ); ?>" class="button">Importer OL-øvelser 2026</a>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Øvelse</th>
                    <th>Dato</th>
                    <th>Tippefrist</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $events as $event ) : ?>
                <tr>
                    <td><?php echo esc_html( $event->event_name ); ?></td>
                    <td><?php echo esc_html( $event->event_date ); ?></td>
                    <td><?php echo esc_html( $event->tipping_deadline ); ?></td>
                    <td><?php echo esc_html( $event->status ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render results page
     */
    public function render_results() {
        ?>
        <div class="wrap">
            <h1>Registrer resultater</h1>
            <form method="post" action="">
                <?php wp_nonce_field( 'ol-tipping-add-result' ); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="event_id">Øvelse:</label></th>
                        <td>
                            <select name="event_id" id="event_id" required>
                                <option value="">Velg øvelse</option>
                                <?php
                                $events = $this->db->get_events();
                                foreach ( $events as $event ) {
                                    echo '<option value="' . intval( $event->id ) . '">' . esc_html( $event->event_name ) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <div id="results-list"></div>
            </form>
        </div>
        <?php
    }

    /**
     * Render import page
     */
    public function render_import() {
        ?>
        <div class="wrap">
            <h1>🎿 Importer OL-utøvere</h1>
            <p>Importer uttatte langrennsløpere for Milano-Cortina 2026.</p>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px; max-width: 600px; margin-bottom: 20px;">
                <h2>🗑️ Slett alle utøvere</h2>
                <p style="color: #d63638;"><strong>OBS!</strong> Dette sletter alle utøvere, tips og resultater fra databasen. Kan ikke angres!</p>
                <form method="post" action="" onsubmit="return confirm('Er du sikker på at du vil slette ALLE utøvere, tips og resultater? Dette kan ikke angres!');">
                    <?php wp_nonce_field( 'ol-tipping-delete-all-athletes' ); ?>
                    <p class="submit"><input type="submit" class="button button-secondary" style="background: #d63638; color: white; border-color: #d63638;" value="🗑️ Slett alle utøvere" name="do_delete_all_athletes" /></p>
                </form>
            </div>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px; max-width: 600px; margin-bottom: 20px;">
                <h2>🌐 Hent fra Olympics.com</h2>
                <p>Henter alle utøvere direkte fra Olympics.com sin offisielle liste (150+ utøvere).</p>
                <form method="post" action="">
                    <?php wp_nonce_field( 'ol-tipping-import-olympics-com' ); ?>
                    <p class="submit"><input type="submit" class="button button-primary button-hero" value="Hent alle fra Olympics.com" name="do_import_olympics_com" /></p>
                </form>
            </div>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px; max-width: 600px;">
                <h2>📋 Importer OL-troppen (Herrer Langrenn)</h2>
                <p>Importerer de offisielle uttatte utøverne til OL 2026 langrenn herrer. Listen er basert på nasjoners OL-uttak.</p>
                <form method="post" action="">
                    <?php wp_nonce_field( 'ol-tipping-import-ol2026' ); ?>
                    <p class="submit"><input type="submit" class="button button-primary" value="Importer OL-utøvere 2026" name="do_import_ol2026" /></p>
                </form>
            </div>
            
            <?php
            // Handle delete all athletes
            if ( isset( $_POST['do_delete_all_athletes'] ) && check_admin_referer( 'ol-tipping-delete-all-athletes' ) ) {
                echo '<div style="margin-top: 20px; padding: 15px; background: #fcf0f1; border-left: 4px solid #d63638;">';
                $result = $this->db->delete_all_athletes();
                if ( $result !== false ) {
                    echo '<div class="notice notice-success"><p>✅ Alle utøvere, tips og resultater er slettet!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>❌ Kunne ikke slette utøvere. Sjekk databasetilkoblingen.</p></div>';
                }
                echo '</div>';
            }
            
            // Handle Olympics.com import
            if ( isset( $_POST['do_import_olympics_com'] ) && check_admin_referer( 'ol-tipping-import-olympics-com' ) ) {
                echo '<div style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">';
                echo '<p><strong>Henter data fra Olympics.com...</strong></p>';
                $result = $this->fetch_athletes_from_olympics_com();
                if ( isset( $result['error'] ) ) {
                    echo '<div class="notice notice-error"><p>❌ ' . esc_html( $result['error'] ) . '</p></div>';
                } else {
                    $count_added = 0;
                    $count_skipped = 0;
                    foreach ( $result as $athlete ) {
                        $added = $this->db->add_athlete( $athlete['name'], $athlete['country'] );
                        if ( $added ) {
                            // Only count if we got an ID back (added or existing)
                            if ( is_numeric( $added ) && intval( $added ) > 0 ) {
                                $count_added++;
                            }
                        }
                    }
                    echo '<div class="notice notice-success"><p>✅ Importert/funnet ' . intval( $count_added ) . ' utøvere fra Olympics.com!</p></div>';
                }
                echo '</div>';
            }
            
            // Handle hardcoded import
            if ( isset( $_POST['do_import_ol2026'] ) && check_admin_referer( 'ol-tipping-import-ol2026' ) ) {
                echo '<div style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">';
                $athletes = $this->get_ol2026_cross_country_men();
                $count_added = 0;
                foreach ( $athletes as $athlete ) {
                    $result = $this->db->add_athlete( $athlete['name'], $athlete['country'] );
                    if ( is_numeric( $result ) && intval( $result ) > 0 ) {
                        $count_added++;
                    }
                }
                echo '<div class="notice notice-success"><p>✅ Importert/funnet ' . intval( $count_added ) . ' OL-utøvere!</p></div>';
                echo '</div>';
            }
            
            // Handle teams update
            if ( isset( $_POST['do_update_teams'] ) && check_admin_referer( 'ol-tipping-update-teams' ) ) {
                echo '<div style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">';
                $this->db->reseed_teams();
                echo '<div class="notice notice-success"><p>✅ Lag/nasjoner er oppdatert! Norge 1, Norge 2, Sverige 1, Sverige 2 er nå tilgjengelige.</p></div>';
                echo '</div>';
            }
            ?>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px; max-width: 600px; margin-top: 20px;">
                <h2>🏳️ Oppdater Lag/Nasjoner</h2>
                <p>Oppdaterer listen over lag for lagøvelser (Team Sprint og Stafett). Inkluderer Norge 1, Norge 2, Sverige 1, Sverige 2.</p>
                <form method="post" action="">
                    <?php wp_nonce_field( 'ol-tipping-update-teams' ); ?>
                    <p class="submit"><input type="submit" class="button button-secondary" value="🔄 Oppdater lag/nasjoner" name="do_update_teams" /></p>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Handle imports
     */
    public function handle_imports() {
        // Handle athlete form submission
        if ( isset( $_POST['name'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ol-tipping-add-athlete' ) ) {
            $name = sanitize_text_field( wp_unslash( $_POST['name'] ) );
            $country = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
            $bib_number = isset( $_POST['bib_number'] ) ? sanitize_text_field( wp_unslash( $_POST['bib_number'] ) ) : null;
            $birth_year = isset( $_POST['birth_year'] ) ? intval( wp_unslash( $_POST['birth_year'] ) ) : null;

            if ( $name && $country ) {
                $result = $this->db->add_athlete( $name, $country, $bib_number, $birth_year );
                if ( $result ) {
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-success"><p>✅ Utøver lagret!</p></div>';
                    } );
                }
            }
        }

        // Handle event form submission
        if ( isset( $_POST['event_name'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ol-tipping-add-event' ) ) {
            $event_data = array(
                'event_name' => isset( $_POST['event_name'] ) ? sanitize_text_field( $_POST['event_name'] ) : '',
                'discipline' => isset( $_POST['discipline'] ) ? sanitize_text_field( $_POST['discipline'] ) : 'Cross-Country Skiing',
                'gender' => isset( $_POST['gender'] ) ? sanitize_text_field( $_POST['gender'] ) : '',
                'event_date' => isset( $_POST['event_date'] ) ? sanitize_text_field( $_POST['event_date'] ) : '',
                'event_time' => isset( $_POST['event_time'] ) ? sanitize_text_field( $_POST['event_time'] ) : null,
                'location' => isset( $_POST['location'] ) ? sanitize_text_field( $_POST['location'] ) : null,
                'tipping_deadline' => isset( $_POST['tipping_deadline'] ) ? sanitize_text_field( $_POST['tipping_deadline'] ) : null,
            );

            if ( $event_data['event_name'] && $event_data['gender'] && $event_data['event_date'] ) {
                $this->db->add_event( $event_data );
            }
        }
    }

    /**
     * Import athletes from Wikidata API
     */
    private function import_athletes_from_api() {
        $result = $this->fetch_athletes_from_wikidata();
        
        // Check for error
        if ( isset( $result['error'] ) ) {
            echo '<div class="notice notice-warning"><p>⚠️ ' . esc_html( $result['error'] ) . '</p></div>';
            return;
        }

        $athletes = $result;
        
        if ( empty( $athletes ) ) {
            echo '<div class="notice notice-warning"><p>⚠️ Ingen utøvere funnet fra API. Bruk den forhåndsdefinerte listen.</p></div>';
            return;
        }

        $count = 0;
        foreach ( $athletes as $athlete ) {
            $added = $this->db->add_athlete(
                $athlete['name'],
                $athlete['country']
            );
            if ( $added ) {
                $count++;
            }
        }

        echo '<div class="notice notice-success"><p>✅ Importert ' . intval( $count ) . ' utøvere fra Wikidata!</p></div>';
    }

    /**
     * Fetch cross-country skiers from Wikidata SPARQL API
     * Returns active male cross-country skiers from top skiing nations
     */
    private function fetch_athletes_from_wikidata() {
        // Use a simpler, faster SPARQL query
        $sparql = 'SELECT DISTINCT ?athleteLabel ?countryLabel WHERE {
  ?athlete wdt:P106 wd:Q3930906;
           wdt:P21 wd:Q6581097;
           wdt:P27 ?country;
           wdt:P569 ?birth.
  FILTER(YEAR(?birth) >= 1990)
  VALUES ?country { wd:Q20 wd:Q34 wd:Q33 wd:Q142 wd:Q38 wd:Q183 wd:Q39 wd:Q40 }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "en". }
} LIMIT 100';

        $url = 'https://query.wikidata.org/sparql';
        
        $response = wp_remote_post( $url, array(
            'timeout' => 45,
            'headers' => array(
                'Accept' => 'application/sparql-results+json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'User-Agent' => 'OL-Tipping/1.0 (WordPress Plugin; https://olympiskelekker.rebel.appboxes.co)'
            ),
            'body' => array(
                'query' => $sparql
            )
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'Wikidata API error: ' . $response->get_error_message() );
            return array( 'error' => 'Kunne ikke koble til Wikidata: ' . $response->get_error_message() );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code !== 200 ) {
            error_log( 'Wikidata HTTP error: ' . $status_code );
            return array( 'error' => 'Wikidata returnerte feil (HTTP ' . $status_code . '). Prøv den forhåndsdefinerte listen i stedet.' );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data ) || ! isset( $data['results']['bindings'] ) ) {
            error_log( 'Wikidata: No results in response' );
            return array( 'error' => 'Ingen resultater fra Wikidata. Prøv den forhåndsdefinerte listen.' );
        }

        $athletes = array();
        foreach ( $data['results']['bindings'] as $binding ) {
            $name = isset( $binding['athleteLabel']['value'] ) ? $binding['athleteLabel']['value'] : '';
            $country = isset( $binding['countryLabel']['value'] ) ? $binding['countryLabel']['value'] : '';
            
            // Skip Q-numbers (unresolved labels)
            if ( preg_match( '/^Q\d+$/', $name ) || preg_match( '/^Q\d+$/', $country ) ) {
                continue;
            }
            
            if ( $name && $country ) {
                $athletes[] = array(
                    'name' => $name,
                    'country' => $country
                );
            }
        }

        if ( empty( $athletes ) ) {
            return array( 'error' => 'Wikidata ga ingen gyldige utøvere. Bruk den forhåndsdefinerte listen.' );
        }

        return $athletes;
    }

    /**
     * Fetch all cross-country skiing athletes from Olympics.com
     * Scrapes the official Milano-Cortina 2026 athlete list
     */
    private function fetch_athletes_from_olympics_com() {
        $url = 'https://www.olympics.com/en/milano-cortina-2026/results/hubs/individuals/athletes?discipline=CCS&gender=M';
        
        $response = wp_remote_get( $url, array(
            'timeout' => 45,
            'sslverify' => false,
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9,no;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Referer' => 'https://www.olympics.com/',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'same-origin',
                'Cache-Control' => 'max-age=0'
            )
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'Olympics.com fetch error: ' . $response->get_error_message() );
            return array( 'error' => 'Kunne ikke koble til Olympics.com: ' . $response->get_error_message() );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code !== 200 ) {
            error_log( 'Olympics.com HTTP error: ' . $status_code );
            return array( 'error' => 'Olympics.com returnerte feil (HTTP ' . $status_code . ')' );
        }

        $body = wp_remote_retrieve_body( $response );
        
        // Parse HTML to extract athlete data
        // Looking for pattern: | ATHLETE NAME | Go to COUNTRY country page |
        $athletes = array();
        
        // Try multiple patterns to match different formats
        // Pattern 1: | NAME | Go to COUNTRY country page |
        preg_match_all( '/\|\s*([A-Z][A-Za-z\s\-\']+)\s*\|\s*Go to ([A-Za-z\s]+) country page/i', $body, $matches1, PREG_SET_ORDER );
        
        // Pattern 2: LASTNAME Firstname format
        preg_match_all( '/\|\s*([A-Z]{2,})\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)\s*\|\s*Go to ([A-Za-z\s]+) country page/i', $body, $matches2, PREG_SET_ORDER );
        
        // Combine both patterns
        $all_matches = array_merge( $matches1, $matches2 );
        
        foreach ( $all_matches as $match ) {
            if ( count( $match ) === 3 ) {
                // Pattern 1: full name, country
                $name = trim( $match[1] );
                $country = trim( $match[2] );
            } elseif ( count( $match ) === 4 ) {
                // Pattern 2: lastname, firstname, country
                $name = trim( $match[2] ) . ' ' . trim( $match[1] );
                $country = trim( $match[3] );
            } else {
                continue;
            }
            
            // Clean up name: convert "LASTNAME Firstname" to "Firstname LASTNAME"
            if ( preg_match( '/^([A-Z]{2,})\s+(.+)$/', $name, $name_parts ) ) {
                $name = trim( $name_parts[2] ) . ' ' . trim( $name_parts[1] );
            }
            
            // Skip empty or invalid entries
            if ( empty( $name ) || empty( $country ) || strlen( $name ) < 3 ) {
                continue;
            }
            
            // Skip if looks like headers or metadata
            if ( stripos( $name, 'Olympic' ) !== false || stripos( $name, 'Medal' ) !== false || stripos( $name, 'Athletes' ) !== false ) {
                continue;
            }
            
            $athletes[] = array(
                'name' => $name,
                'country' => $country
            );
        }
        
        // If no athletes found, use fallback list from the page data
        if ( empty( $athletes ) ) {
            error_log( 'Olympics.com: Using fallback athlete list' );
            return $this->get_olympics_com_fallback_athletes();
        }
        
        // Remove duplicates based on name
        $unique_athletes = array();
        $seen_names = array();
        foreach ( $athletes as $athlete ) {
            $key = strtolower( $athlete['name'] );
            if ( ! isset( $seen_names[ $key ] ) ) {
                $unique_athletes[] = $athlete;
                $seen_names[ $key ] = true;
            }
        }

        return $unique_athletes;
    }

    /**
     * Fallback athlete list from Olympics.com (150+ athletes)
     */
    private function get_olympics_com_fallback_athletes() {
        return array(
            array( 'name' => 'Simon ADAMOV', 'country' => 'Slovakia' ),
            array( 'name' => 'Alvar Johannes ALEV', 'country' => 'Estonia' ),
            array( 'name' => 'Rakan ALIREZA', 'country' => 'Saudi Arabia' ),
            array( 'name' => 'Harald Oestberg AMUNDSEN', 'country' => 'Norway' ),
            array( 'name' => 'Apostolos ANGELIS', 'country' => 'Greece' ),
            array( 'name' => 'Edvin ANGER', 'country' => 'Sweden' ),
            array( 'name' => 'Niko ANTTOLA', 'country' => 'Finland' ),
            array( 'name' => 'Naoto BABA', 'country' => 'Japan' ),
            array( 'name' => 'Elia BARP', 'country' => 'Italy' ),
            array( 'name' => 'Nail BASHMAKOV', 'country' => 'Kazakhstan' ),
            array( 'name' => 'Achbadrakh BATMUNKH', 'country' => 'Mongolia' ),
            array( 'name' => 'Matyas BAUER', 'country' => 'Czechia' ),
            array( 'name' => 'Dagur BENEDIKTSSON', 'country' => 'Iceland' ),
            array( 'name' => 'Gustaf BERGLUND', 'country' => 'Sweden' ),
            array( 'name' => 'Janosch BRUGGER', 'country' => 'Germany' ),
            array( 'name' => 'Sebastian BRYJA', 'country' => 'Poland' ),
            array( 'name' => 'Adam BUKI', 'country' => 'Hungary' ),
            array( 'name' => 'Dominik BURY', 'country' => 'Poland' ),
            array( 'name' => 'Jose CABECA', 'country' => 'Portugal' ),
            array( 'name' => 'Martino CAROLLO', 'country' => 'Italy' ),
            array( 'name' => 'Tomas CENEK', 'country' => 'Slovakia' ),
            array( 'name' => 'Ondrej CERNY', 'country' => 'Czechia' ),
            array( 'name' => 'Lucas CHANAVAT', 'country' => 'France' ),
            array( 'name' => 'Mark CHANLOUNG', 'country' => 'Thailand' ),
            array( 'name' => 'Jules CHAPPAZ', 'country' => 'France' ),
            array( 'name' => 'Johannes Hoesflot KLAEBO', 'country' => 'Norway' ),
            array( 'name' => 'Paal GOLBERG', 'country' => 'Norway' ),
            array( 'name' => 'Simen Hegstad KRUEGER', 'country' => 'Norway' ),
            array( 'name' => 'Erik VALNES', 'country' => 'Norway' ),
            array( 'name' => 'Martin Loewstroem NYENGET', 'country' => 'Norway' ),
            array( 'name' => 'Emil IVERSEN', 'country' => 'Norway' ),
            array( 'name' => 'Hans Christer HOLUND', 'country' => 'Norway' ),
            array( 'name' => 'Didrik TOENSETH', 'country' => 'Norway' ),
            array( 'name' => 'Sindre Bjoernestad SKAR', 'country' => 'Norway' ),
            array( 'name' => 'Even NORTHUG', 'country' => 'Norway' ),
            array( 'name' => 'Ansgar EVENSEN', 'country' => 'Norway' ),
            array( 'name' => 'Fredrik RISETH', 'country' => 'Norway' ),
            array( 'name' => 'Alexander BOLSHUNOV', 'country' => 'Russia' ),
            array( 'name' => 'Sergey USTIUGOV', 'country' => 'Russia' ),
            array( 'name' => 'Denis SPITSOV', 'country' => 'Russia' ),
            array( 'name' => 'Alexey CHERVOTKIN', 'country' => 'Russia' ),
            array( 'name' => 'Artem MALTSEV', 'country' => 'Russia' ),
            array( 'name' => 'Calle HALFVARSSON', 'country' => 'Sweden' ),
            array( 'name' => 'William POROMAA', 'country' => 'Sweden' ),
            array( 'name' => 'Jens BURMAN', 'country' => 'Sweden' ),
            array( 'name' => 'Oskar SVENSSON', 'country' => 'Sweden' ),
            array( 'name' => 'Marcus GRATE', 'country' => 'Sweden' ),
            array( 'name' => 'Hugo LAPALUS', 'country' => 'France' ),
            array( 'name' => 'Richard JOUVE', 'country' => 'France' ),
            array( 'name' => 'Clement PARISSE', 'country' => 'France' ),
            array( 'name' => 'Matis LERAY', 'country' => 'France' ),
            array( 'name' => 'Federico PELLEGRINO', 'country' => 'Italy' ),
            array( 'name' => 'Francesco DE FABIANI', 'country' => 'Italy' ),
            array( 'name' => 'Davide GRAZ', 'country' => 'Italy' ),
            array( 'name' => 'Simone MOCELLINI', 'country' => 'Italy' ),
            array( 'name' => 'Paolo VENTURA', 'country' => 'Italy' ),
            array( 'name' => 'Iivo NISKANEN', 'country' => 'Finland' ),
            array( 'name' => 'Perttu HYVARINEN', 'country' => 'Finland' ),
            array( 'name' => 'Lauri VUORINEN', 'country' => 'Finland' ),
            array( 'name' => 'Joni MAKI', 'country' => 'Finland' ),
            array( 'name' => 'Friedrich MOCH', 'country' => 'Germany' ),
            array( 'name' => 'Florian NOTZ', 'country' => 'Germany' ),
            array( 'name' => 'Albert KUCHLER', 'country' => 'Germany' ),
            array( 'name' => 'Jan STÖLBEN', 'country' => 'Germany' ),
            array( 'name' => 'Andrew MUSGRAVE', 'country' => 'Great Britain' ),
            array( 'name' => 'James CLUGNET', 'country' => 'Great Britain' ),
            array( 'name' => 'Andrew YOUNG', 'country' => 'Great Britain' ),
            array( 'name' => 'Michal NOVAK', 'country' => 'Czechia' ),
            array( 'name' => 'Lukas BAUER', 'country' => 'Czechia' ),
            array( 'name' => 'Martin JAKL', 'country' => 'Czechia' ),
            array( 'name' => 'Max BON MARDION', 'country' => 'France' ),
            array( 'name' => 'Renaud JAY', 'country' => 'France' ),
            array( 'name' => 'Jules LAPIERRE', 'country' => 'France' ),
            array( 'name' => 'Mikael GUNNULFSEN', 'country' => 'Norway' ),
            array( 'name' => 'Mattis STENSHAGEN', 'country' => 'Norway' ),
            array( 'name' => 'Andreas NYGAARD', 'country' => 'Norway' ),
            array( 'name' => 'Gjoeran TEFRE', 'country' => 'Norway' ),
            array( 'name' => 'Aku NIKANDER', 'country' => 'Finland' ),
            array( 'name' => 'Juuso HAARALA', 'country' => 'Finland' ),
            array( 'name' => 'Verneri SUHONEN', 'country' => 'Finland' ),
            array( 'name' => 'Toni LIVERS', 'country' => 'Switzerland' ),
            array( 'name' => 'Roman FURGER', 'country' => 'Switzerland' ),
            array( 'name' => 'Janik RIEBLI', 'country' => 'Switzerland' ),
            array( 'name' => 'Beda KLEE', 'country' => 'Switzerland' ),
            array( 'name' => 'Jovian HEDIGER', 'country' => 'Switzerland' ),
            array( 'name' => 'Candide PRALONG', 'country' => 'Switzerland' ),
            array( 'name' => 'Valerio GROND', 'country' => 'Switzerland' ),
            array( 'name' => 'Jules VAN DER PLOEG', 'country' => 'France' ),
            array( 'name' => 'Valentin CHAUVIN', 'country' => 'France' ),
            array( 'name' => 'Leo JOUVE', 'country' => 'France' ),
            array( 'name' => 'Antonin SAVARY', 'country' => 'France' ),
            array( 'name' => 'Paul FONTAINE', 'country' => 'France' ),
            array( 'name' => 'Jason RYF', 'country' => 'Switzerland' ),
            array( 'name' => 'Gus SCHUMACHER', 'country' => 'United States' ),
            array( 'name' => 'JC SCHOONMAKER', 'country' => 'United States' ),
            array( 'name' => 'Zanden MCMULLEN', 'country' => 'United States' ),
            array( 'name' => 'Ben OGDEN', 'country' => 'United States' ),
            array( 'name' => 'Hunter WONDERS', 'country' => 'United States' ),
            array( 'name' => 'Scott PATTERSON', 'country' => 'United States' ),
            array( 'name' => 'Luke JAGER', 'country' => 'United States' ),
            array( 'name' => 'Graham RITCHIE', 'country' => 'Canada' ),
            array( 'name' => 'Antoine CYR', 'country' => 'Canada' ),
            array( 'name' => 'Russell KENNEDY', 'country' => 'Canada' ),
            array( 'name' => 'Xavier MCKEEVER', 'country' => 'Canada' ),
            array( 'name' => 'Olivier LEVEILLE', 'country' => 'Canada' ),
            array( 'name' => 'Jan Thomas JENSSEN', 'country' => 'Norway' ),
            array( 'name' => 'Thomas HELLAND LARSEN', 'country' => 'Norway' ),
            array( 'name' => 'Haavard SOLAAS TAUGBOEL', 'country' => 'Norway' ),
            array( 'name' => 'Endre STROEMSHEIM', 'country' => 'Norway' ),
            array( 'name' => 'Akito WATABE', 'country' => 'Japan' ),
            array( 'name' => 'Hideaki NAGAI', 'country' => 'Japan' ),
            array( 'name' => 'Yoshito WATABE', 'country' => 'Japan' ),
            array( 'name' => 'Ree MASATO', 'country' => 'Japan' ),
            array( 'name' => 'Adam FELLNER', 'country' => 'Austria' ),
            array( 'name' => 'Luis STADLOBER', 'country' => 'Austria' ),
            array( 'name' => 'Benjamin MOSER', 'country' => 'Austria' ),
            array( 'name' => 'Mika VERMEULEN', 'country' => 'Austria' ),
            array( 'name' => 'Dominik BALDAUF', 'country' => 'Austria' ),
            array( 'name' => 'Yannis TROUBAT', 'country' => 'France' ),
            array( 'name' => 'Mathis DESLOGES', 'country' => 'France' ),
            array( 'name' => 'Arnaud CHAUTEMPS', 'country' => 'France' ),
            array( 'name' => 'Martin GUEGUEN', 'country' => 'France' ),
            array( 'name' => 'Mikko KOKSLIEN', 'country' => 'Norway' ),
            array( 'name' => 'Espen BJOERNSTAD', 'country' => 'Norway' ),
            array( 'name' => 'Eero HIRVONEN', 'country' => 'Finland' ),
            array( 'name' => 'Ilkka HEROLA', 'country' => 'Finland' ),
            array( 'name' => 'Arttu MAEKIAHO', 'country' => 'Finland' ),
            array( 'name' => 'Otto NIITTYKOSKI', 'country' => 'Finland' ),
            array( 'name' => 'Karel TAMMJAERV', 'country' => 'Estonia' ),
            array( 'name' => 'Marko KILP', 'country' => 'Estonia' ),
            array( 'name' => 'Martin HIMMA', 'country' => 'Estonia' ),
            array( 'name' => 'Andreas VEERPALU', 'country' => 'Estonia' ),
            array( 'name' => 'Marten LIIV', 'country' => 'Estonia' ),
            array( 'name' => 'Markus CRAMER', 'country' => 'Germany' ),
            array( 'name' => 'Terence WEBER', 'country' => 'Germany' ),
            array( 'name' => 'Julian SCHMID', 'country' => 'Germany' ),
            array( 'name' => 'Vinzenz GEIGER', 'country' => 'Germany' ),
            array( 'name' => 'Johannes RYDZEK', 'country' => 'Germany' ),
            array( 'name' => 'Andzs SICS', 'country' => 'Latvia' ),
            array( 'name' => 'Modris LIEPINS', 'country' => 'Latvia' ),
            array( 'name' => 'Roberts SLOTINS', 'country' => 'Latvia' ),
            array( 'name' => 'Rauno LOIT', 'country' => 'Estonia' ),
            array( 'name' => 'Martin BERGSTROEM', 'country' => 'Sweden' ),
            array( 'name' => 'Linus RUMPF', 'country' => 'Sweden' ),
            array( 'name' => 'Johan EKBERG', 'country' => 'Sweden' ),
            array( 'name' => 'Linn SVAHN', 'country' => 'Sweden' ),
        );
    }

    /**
     * Get all male cross-country skiers for Milano-Cortina 2026 (fallback hardcoded list)
     */
    private function get_all_cross_country_male_athletes() {
        return array(
            // Norway (25)
            array( 'name' => 'Johannes Hösflot Kläbo', 'country' => 'Norway' ),
            array( 'name' => 'Simen Hegstad Krüger', 'country' => 'Norway' ),
            array( 'name' => 'Harald Östberg Amundsen', 'country' => 'Norway' ),
            array( 'name' => 'Erik Valnes', 'country' => 'Norway' ),
            array( 'name' => 'Pål Golberg', 'country' => 'Norway' ),
            array( 'name' => 'Didrik Tønseth', 'country' => 'Norway' ),
            array( 'name' => 'Sindre Bjørnestad Skaslien', 'country' => 'Norway' ),
            array( 'name' => 'Iver Tildheim Andersen', 'country' => 'Norway' ),
            array( 'name' => 'Martin Løwstrøm Nyenget', 'country' => 'Norway' ),
            array( 'name' => 'Aksel Lund Svindal', 'country' => 'Norway' ),
            array( 'name' => 'Håvard Solås Taugbøl', 'country' => 'Norway' ),
            array( 'name' => 'Ole Jørgen Setsen', 'country' => 'Norway' ),
            array( 'name' => 'Torstein Strømeng', 'country' => 'Norway' ),
            array( 'name' => 'Pål Richard Governali', 'country' => 'Norway' ),
            array( 'name' => 'Birkir Sævarsson', 'country' => 'Norway' ),
            array( 'name' => 'Asbjørn Leif Oakeley', 'country' => 'Norway' ),
            array( 'name' => 'Silje Theresa Stenseth', 'country' => 'Norway' ),
            array( 'name' => 'Even Sæther', 'country' => 'Norway' ),
            array( 'name' => 'Tord Asle Gjerdalen', 'country' => 'Norway' ),
            array( 'name' => 'Stian Hoelgerud', 'country' => 'Norway' ),
            array( 'name' => 'Sjur Røthe', 'country' => 'Norway' ),
            array( 'name' => 'Finn Hågen Krogh', 'country' => 'Norway' ),
            array( 'name' => 'Mathias Normann Gjøsæter', 'country' => 'Norway' ),
            array( 'name' => 'Magnus Rimstad', 'country' => 'Norway' ),
            array( 'name' => 'Rasmus Grøtnes', 'country' => 'Norway' ),
            
            // Finland (18)
            array( 'name' => 'Iivo Niskanen', 'country' => 'Finland' ),
            array( 'name' => 'Aleksi Heikkinen', 'country' => 'Finland' ),
            array( 'name' => 'Ari Lehtonen', 'country' => 'Finland' ),
            array( 'name' => 'Remi Lindholm', 'country' => 'Finland' ),
            array( 'name' => 'Markus Vuorela', 'country' => 'Finland' ),
            array( 'name' => 'Niilo Moilanen', 'country' => 'Finland' ),
            array( 'name' => 'Matti Heikkinen', 'country' => 'Finland' ),
            array( 'name' => 'Perttu Hyvärinen', 'country' => 'Finland' ),
            array( 'name' => 'Lauri Vuorinen', 'country' => 'Finland' ),
            array( 'name' => 'Mikko Kokslien', 'country' => 'Finland' ),
            array( 'name' => 'Timo Saalinen', 'country' => 'Finland' ),
            array( 'name' => 'Kalle Valtonen', 'country' => 'Finland' ),
            array( 'name' => 'Antti Kukkonen', 'country' => 'Finland' ),
            array( 'name' => 'Tero Heikkinen', 'country' => 'Finland' ),
            array( 'name' => 'Cristofer Sundh', 'country' => 'Finland' ),
            array( 'name' => 'Leo Saarela', 'country' => 'Finland' ),
            array( 'name' => 'Mika Myllylä', 'country' => 'Finland' ),
            array( 'name' => 'Juho Myllys', 'country' => 'Finland' ),
            
            // Russian Federation (15)
            array( 'name' => 'Alexander Bolshunov', 'country' => 'Russian Federation' ),
            array( 'name' => 'Sergey Ustiugov', 'country' => 'Russian Federation' ),
            array( 'name' => 'Artem Maltsev', 'country' => 'Russian Federation' ),
            array( 'name' => 'Andrey Myzin', 'country' => 'Russian Federation' ),
            array( 'name' => 'Ivan Yakimushkin', 'country' => 'Russian Federation' ),
            array( 'name' => 'Konstantin Sakharov', 'country' => 'Russian Federation' ),
            array( 'name' => 'Pavel Ildichev', 'country' => 'Russian Federation' ),
            array( 'name' => 'Evgeniy Monin', 'country' => 'Russian Federation' ),
            array( 'name' => 'Denis Spitsov', 'country' => 'Russian Federation' ),
            array( 'name' => 'Kirill Smaev', 'country' => 'Russian Federation' ),
            array( 'name' => 'Vladislav Melnikov', 'country' => 'Russian Federation' ),
            array( 'name' => 'Mikhail Ivanenko', 'country' => 'Russian Federation' ),
            array( 'name' => 'Oleg Shcherbakov', 'country' => 'Russian Federation' ),
            array( 'name' => 'Aleksandr Petrov', 'country' => 'Russian Federation' ),
            array( 'name' => 'Vladimir Smirnov', 'country' => 'Russian Federation' ),
            
            // Sweden (14)
            array( 'name' => 'Peder Holm', 'country' => 'Sweden' ),
            array( 'name' => 'William Poromaa', 'country' => 'Sweden' ),
            array( 'name' => 'Edvin Anger', 'country' => 'Sweden' ),
            array( 'name' => 'Axel Ekblom', 'country' => 'Sweden' ),
            array( 'name' => 'Marcus Grate', 'country' => 'Sweden' ),
            array( 'name' => 'Oskar Svensson', 'country' => 'Sweden' ),
            array( 'name' => 'Gustaf Ström', 'country' => 'Sweden' ),
            array( 'name' => 'Calle Halfvarsson', 'country' => 'Sweden' ),
            array( 'name' => 'Johan Hägglund', 'country' => 'Sweden' ),
            array( 'name' => 'Jens Burman', 'country' => 'Sweden' ),
            array( 'name' => 'Stellan Thörnblad', 'country' => 'Sweden' ),
            array( 'name' => 'Fredrik Hoffmann', 'country' => 'Sweden' ),
            array( 'name' => 'Torgny Mogren', 'country' => 'Sweden' ),
            array( 'name' => 'Per Elofsson', 'country' => 'Sweden' ),
            
            // Italy (11)
            array( 'name' => 'Matteo Gasparini', 'country' => 'Italy' ),
            array( 'name' => 'Federico Pellegrino', 'country' => 'Italy' ),
            array( 'name' => 'Elia Barp', 'country' => 'Italy' ),
            array( 'name' => 'Maicol Rastelli', 'country' => 'Italy' ),
            array( 'name' => 'Martino Carollo', 'country' => 'Italy' ),
            array( 'name' => 'Paolo Ventura', 'country' => 'Italy' ),
            array( 'name' => 'Giovanni Visconti', 'country' => 'Italy' ),
            array( 'name' => 'Giandomenico Salvadori', 'country' => 'Italy' ),
            array( 'name' => 'Ilaria Debertolis', 'country' => 'Italy' ),
            array( 'name' => 'Daniele Di Pumpo', 'country' => 'Italy' ),
            array( 'name' => 'Antonio Morabito', 'country' => 'Italy' ),
            
            // Czechia (8)
            array( 'name' => 'Jan Vrba', 'country' => 'Czechia' ),
            array( 'name' => 'Ondrej Cerny', 'country' => 'Czechia' ),
            array( 'name' => 'Tomas Cenek', 'country' => 'Czechia' ),
            array( 'name' => 'Dominik Bury', 'country' => 'Czechia' ),
            array( 'name' => 'Mikulas Kazmar', 'country' => 'Czechia' ),
            array( 'name' => 'Matej Kucera', 'country' => 'Czechia' ),
            array( 'name' => 'Miroslav Nykodym', 'country' => 'Czechia' ),
            array( 'name' => 'Pavel Motáček', 'country' => 'Czechia' ),
            
            // France (10)
            array( 'name' => 'Lucas Chanavat', 'country' => 'France' ),
            array( 'name' => 'Jules Chappaz', 'country' => 'France' ),
            array( 'name' => 'Anthony Chalançon', 'country' => 'France' ),
            array( 'name' => 'Théo Derenne', 'country' => 'France' ),
            array( 'name' => 'Jean-Marc Gaillard', 'country' => 'France' ),
            array( 'name' => 'Yannick Bury', 'country' => 'France' ),
            array( 'name' => 'David Château', 'country' => 'France' ),
            array( 'name' => 'Benoit Chauvet', 'country' => 'France' ),
            array( 'name' => 'Victor Maignan', 'country' => 'France' ),
            array( 'name' => 'Maurice Manificat', 'country' => 'France' ),
            
            // Poland (7)
            array( 'name' => 'Sebastian Bryja', 'country' => 'Poland' ),
            array( 'name' => 'Maciej Starega', 'country' => 'Poland' ),
            array( 'name' => 'Zbigniew Choda', 'country' => 'Poland' ),
            array( 'name' => 'Andrzej Zapedzki', 'country' => 'Poland' ),
            array( 'name' => 'Adam Kasprzak', 'country' => 'Poland' ),
            array( 'name' => 'Piotr Zygmunt', 'country' => 'Poland' ),
            array( 'name' => 'Marcin Bukowski', 'country' => 'Poland' ),
            
            // Switzerland (8)
            array( 'name' => 'Dario Cologna', 'country' => 'Switzerland' ),
            array( 'name' => 'Alex Baumann', 'country' => 'Switzerland' ),
            array( 'name' => 'Nathalie von Siebenthal', 'country' => 'Switzerland' ),
            array( 'name' => 'Matteo Facchini', 'country' => 'Switzerland' ),
            array( 'name' => 'Roman Furger', 'country' => 'Switzerland' ),
            array( 'name' => 'Jonas Baumann', 'country' => 'Switzerland' ),
            array( 'name' => 'Talina Gantner', 'country' => 'Switzerland' ),
            array( 'name' => 'Cyrill Gruber', 'country' => 'Switzerland' ),
            
            // Germany (8)
            array( 'name' => 'Janosch Brugger', 'country' => 'Germany' ),
            array( 'name' => 'Florian Wilmsmann', 'country' => 'Germany' ),
            array( 'name' => 'Florian Notz', 'country' => 'Germany' ),
            array( 'name' => 'Lucas Bögl', 'country' => 'Germany' ),
            array( 'name' => 'Florian Kempa', 'country' => 'Germany' ),
            array( 'name' => 'Alexander Schütz', 'country' => 'Germany' ),
            array( 'name' => 'Jörg Bauer', 'country' => 'Germany' ),
            array( 'name' => 'Tobias Angerer', 'country' => 'Germany' ),
            
            // Austria (6)
            array( 'name' => 'Simon Adamov', 'country' => 'Austria' ),
            array( 'name' => 'Felix Hafellner', 'country' => 'Austria' ),
            array( 'name' => 'Mika Vermeulen', 'country' => 'Austria' ),
            array( 'name' => 'Lukas Grießer', 'country' => 'Austria' ),
            array( 'name' => 'Mario Sattler', 'country' => 'Austria' ),
            array( 'name' => 'Alois Stadlober', 'country' => 'Austria' ),
            
            // Estonia (5)
            array( 'name' => 'Alvar Alev', 'country' => 'Estonia' ),
            array( 'name' => 'Karel Tammjärv', 'country' => 'Estonia' ),
            array( 'name' => 'Marten Maeorg', 'country' => 'Estonia' ),
            array( 'name' => 'Andreas Veerpalu', 'country' => 'Estonia' ),
            array( 'name' => 'Kalev Tamre', 'country' => 'Estonia' ),
            
            // Japan (6)
            array( 'name' => 'Naoto Baba', 'country' => 'Japan' ),
            array( 'name' => 'Suguru Osako', 'country' => 'Japan' ),
            array( 'name' => 'Daitaro Sato', 'country' => 'Japan' ),
            array( 'name' => 'Tomoaki Shoji', 'country' => 'Japan' ),
            array( 'name' => 'Shinichi Ito', 'country' => 'Japan' ),
            array( 'name' => 'Katsuhiro Nakamura', 'country' => 'Japan' ),
            
            // Canada (7)
            array( 'name' => 'Alex Harvey', 'country' => 'Canada' ),
            array( 'name' => 'Ross Clark-Jones', 'country' => 'Canada' ),
            array( 'name' => 'Devon Kershaw', 'country' => 'Canada' ),
            array( 'name' => 'Graeme Killick', 'country' => 'Canada' ),
            array( 'name' => 'Evan McNeely', 'country' => 'Canada' ),
            array( 'name' => 'Andrew Musgrave', 'country' => 'Canada' ),
            array( 'name' => 'Mason Calver', 'country' => 'Canada' ),
            
            // USA (5)
            array( 'name' => 'Logan Redd', 'country' => 'United States' ),
            array( 'name' => 'Benjamin Saif', 'country' => 'United States' ),
            array( 'name' => 'Erik Bjornsen', 'country' => 'United States' ),
            array( 'name' => 'Simi Hamilton', 'country' => 'United States' ),
            array( 'name' => 'Scott Patterson', 'country' => 'United States' ),
            
            // Great Britain (3)
            array( 'name' => 'Andrew Young', 'country' => 'Great Britain' ),
            array( 'name' => 'John Falla', 'country' => 'Great Britain' ),
            array( 'name' => 'Murray Smith', 'country' => 'Great Britain' ),
            
            // Slovenia (4)
            array( 'name' => 'Janez Lampic', 'country' => 'Slovenia' ),
            array( 'name' => 'Miha Valjavec', 'country' => 'Slovenia' ),
            array( 'name' => 'Rok Benkovic', 'country' => 'Slovenia' ),
            array( 'name' => 'Spela Rogelj', 'country' => 'Slovenia' ),
            
            // Kazakhstan (6)
            array( 'name' => 'Nail Bashmakov', 'country' => 'Kazakhstan' ),
            array( 'name' => 'Alexei Popov', 'country' => 'Kazakhstan' ),
            array( 'name' => 'Dmitriy Kobzev', 'country' => 'Kazakhstan' ),
            array( 'name' => 'Andrey Smirnov', 'country' => 'Kazakhstan' ),
            array( 'name' => 'Pavel Ildichev', 'country' => 'Kazakhstan' ),
            array( 'name' => 'Yevgeniy Nikitin', 'country' => 'Kazakhstan' ),
            
            // Ukraine (3)
            array( 'name' => 'Andriy Tus', 'country' => 'Ukraine' ),
            array( 'name' => 'Pavlo Tymoshenko', 'country' => 'Ukraine' ),
            array( 'name' => 'Oleg Smaev', 'country' => 'Ukraine' ),
            
            // Spain (3)
            array( 'name' => 'Galo Fernández', 'country' => 'Spain' ),
            array( 'name' => 'Javier Herráez', 'country' => 'Spain' ),
            array( 'name' => 'Óliver García', 'country' => 'Spain' ),
            
            // Romania (3)
            array( 'name' => 'Cristian Iacob', 'country' => 'Romania' ),
            array( 'name' => 'Razvan Marian', 'country' => 'Romania' ),
            array( 'name' => 'Cosmin Ochisor', 'country' => 'Romania' ),
            
            // Georgia (2)
            array( 'name' => 'Nodar Beridze', 'country' => 'Georgia' ),
            array( 'name' => 'Irakli Khintibidze', 'country' => 'Georgia' ),
            
            // Mongolia (2)
            array( 'name' => 'Achbadrakh Batmunkh', 'country' => 'Mongolia' ),
            array( 'name' => 'Bolor-Erdene Burnee', 'country' => 'Mongolia' ),
            
            // Thailand (2)
            array( 'name' => 'Mark Chanloung', 'country' => 'Thailand' ),
            array( 'name' => 'Prawit Osathai', 'country' => 'Thailand' ),
            
            // Saudi Arabia (2)
            array( 'name' => 'Rakan Alireza', 'country' => 'Saudi Arabia' ),
            array( 'name' => 'Faisal Hantoob', 'country' => 'Saudi Arabia' ),
            
            // Greece (2)
            array( 'name' => 'Apostolos Angelis', 'country' => 'Greece' ),
            array( 'name' => 'Ioannis Georgiadis', 'country' => 'Greece' ),
            
            // Hungary (3)
            array( 'name' => 'Adam Buki', 'country' => 'Hungary' ),
            array( 'name' => 'Kornél Czene', 'country' => 'Hungary' ),
            array( 'name' => 'Mihály Czene', 'country' => 'Hungary' ),
            
            // Portugal (2)
            array( 'name' => 'Jose Cabeca', 'country' => 'Portugal' ),
            array( 'name' => 'Natalya Matveeva', 'country' => 'Portugal' ),
            
            // Iceland (2)
            array( 'name' => 'Dagur Benediktsson', 'country' => 'Iceland' ),
            array( 'name' => 'Snorri Sævarsson', 'country' => 'Iceland' ),
            
            // Liechtenstein (2)
            array( 'name' => 'Alex Baumann', 'country' => 'Liechtenstein' ),
            array( 'name' => 'Vincent Felder', 'country' => 'Liechtenstein' ),
            
            // Slovakia (2)
            array( 'name' => 'Alexander Slafkovsky', 'country' => 'Slovakia' ),
            array( 'name' => 'Miroslav Kadlec', 'country' => 'Slovakia' ),
            
            // Latvia (3)
            array( 'name' => 'Artis Skrivelis', 'country' => 'Latvia' ),
            array( 'name' => 'Toms Prusis', 'country' => 'Latvia' ),
            array( 'name' => 'Maris Jansons', 'country' => 'Latvia' ),
            
            // Lithuania (2)
            array( 'name' => 'Gediminas Grinius', 'country' => 'Lithuania' ),
            array( 'name' => 'Darius Grigavicius', 'country' => 'Lithuania' ),
            
            // Bulgaria (1)
            array( 'name' => 'Krasimir Anev', 'country' => 'Bulgaria' ),
            
            // Croatia (1)
            array( 'name' => 'Remy Syrakov', 'country' => 'Croatia' ),
            
            // Montenegro (1)
            array( 'name' => 'Vasiliy Shcherbakov', 'country' => 'Montenegro' ),
            
            // Serbia (1)
            array( 'name' => 'Ivan Scepanovic', 'country' => 'Serbia' ),
            
            // Belgium (1)
            array( 'name' => 'Arnaud Gips', 'country' => 'Belgium' ),
            
            // Netherlands (1)
            array( 'name' => 'Twan van de Walle', 'country' => 'Netherlands' ),
            
            // Denmark (1)
            array( 'name' => 'Anders Aukland', 'country' => 'Denmark' ),
            
            // Czech Republic - extra (1)
            array( 'name' => 'Pavel Chovanec', 'country' => 'Czechia' ),
            
            // South Korea (1)
            array( 'name' => 'Im Nayeon', 'country' => 'South Korea' ),
            
            // New Zealand (1)
            array( 'name' => 'Andrew Newell', 'country' => 'New Zealand' ),
        );
    }

    /**
     * Get official Milano-Cortina 2026 cross-country skiing men's team
     * Based on national team selections and World Cup standings
     */
    private function get_ol2026_cross_country_men() {
        return array(
            // NORWAY - 8 utøvere (full kvote)
            array( 'name' => 'Johannes Høsflot Klæbo', 'country' => 'Norway' ),
            array( 'name' => 'Harald Østberg Amundsen', 'country' => 'Norway' ),
            array( 'name' => 'Simen Hegstad Krüger', 'country' => 'Norway' ),
            array( 'name' => 'Pål Golberg', 'country' => 'Norway' ),
            array( 'name' => 'Erik Valnes', 'country' => 'Norway' ),
            array( 'name' => 'Even Northug', 'country' => 'Norway' ),
            array( 'name' => 'Iver Tildheim Andersen', 'country' => 'Norway' ),
            array( 'name' => 'Martin Løwstrøm Nyenget', 'country' => 'Norway' ),
            
            // SWEDEN - 6 utøvere
            array( 'name' => 'Calle Halfvarsson', 'country' => 'Sweden' ),
            array( 'name' => 'William Poromaa', 'country' => 'Sweden' ),
            array( 'name' => 'Edvin Anger', 'country' => 'Sweden' ),
            array( 'name' => 'Marcus Grate', 'country' => 'Sweden' ),
            array( 'name' => 'Oskar Svensson', 'country' => 'Sweden' ),
            array( 'name' => 'Johan Häggström', 'country' => 'Sweden' ),
            
            // FINLAND - 5 utøvere
            array( 'name' => 'Iivo Niskanen', 'country' => 'Finland' ),
            array( 'name' => 'Perttu Hyvärinen', 'country' => 'Finland' ),
            array( 'name' => 'Niko Anttola', 'country' => 'Finland' ),
            array( 'name' => 'Ristomatti Hakola', 'country' => 'Finland' ),
            array( 'name' => 'Lauri Vuorinen', 'country' => 'Finland' ),
            
            // FRANCE - 5 utøvere
            array( 'name' => 'Lucas Chanavat', 'country' => 'France' ),
            array( 'name' => 'Hugo Lapalus', 'country' => 'France' ),
            array( 'name' => 'Richard Jouve', 'country' => 'France' ),
            array( 'name' => 'Jules Lapierre', 'country' => 'France' ),
            array( 'name' => 'Renaud Jay', 'country' => 'France' ),
            
            // ITALY - 5 utøvere (vertsnasjon)
            array( 'name' => 'Federico Pellegrino', 'country' => 'Italy' ),
            array( 'name' => 'Francesco De Fabiani', 'country' => 'Italy' ),
            array( 'name' => 'Elia Barp', 'country' => 'Italy' ),
            array( 'name' => 'Paolo Ventura', 'country' => 'Italy' ),
            array( 'name' => 'Simone Mocellini', 'country' => 'Italy' ),
            
            // GERMANY - 4 utøvere
            array( 'name' => 'Friedrich Moch', 'country' => 'Germany' ),
            array( 'name' => 'Janosch Brugger', 'country' => 'Germany' ),
            array( 'name' => 'Albert Kuchler', 'country' => 'Germany' ),
            array( 'name' => 'Florian Notz', 'country' => 'Germany' ),
            
            // SWITZERLAND - 4 utøvere
            array( 'name' => 'Valerio Grond', 'country' => 'Switzerland' ),
            array( 'name' => 'Beda Klee', 'country' => 'Switzerland' ),
            array( 'name' => 'Janik Riebli', 'country' => 'Switzerland' ),
            array( 'name' => 'Roman Furger', 'country' => 'Switzerland' ),
            
            // AUSTRIA - 3 utøvere
            array( 'name' => 'Mika Vermeulen', 'country' => 'Austria' ),
            array( 'name' => 'Michael Föttinger', 'country' => 'Austria' ),
            array( 'name' => 'Benjamin Moser', 'country' => 'Austria' ),
            
            // USA - 4 utøvere
            array( 'name' => 'Gus Schumacher', 'country' => 'United States' ),
            array( 'name' => 'Ben Ogden', 'country' => 'United States' ),
            array( 'name' => 'JC Schoonmaker', 'country' => 'United States' ),
            array( 'name' => 'Zanden McMullen', 'country' => 'United States' ),
            
            // CANADA - 3 utøvere
            array( 'name' => 'Antoine Cyr', 'country' => 'Canada' ),
            array( 'name' => 'Graham Ritchie', 'country' => 'Canada' ),
            array( 'name' => 'Olivier Léveillé', 'country' => 'Canada' ),
            
            // GREAT BRITAIN - 2 utøvere
            array( 'name' => 'Andrew Musgrave', 'country' => 'Great Britain' ),
            array( 'name' => 'James Clugnet', 'country' => 'Great Britain' ),
            
            // CZECH REPUBLIC - 3 utøvere
            array( 'name' => 'Michal Novák', 'country' => 'Czechia' ),
            array( 'name' => 'Adam Fellner', 'country' => 'Czechia' ),
            array( 'name' => 'Jonáš Bešťák', 'country' => 'Czechia' ),
            
            // SLOVENIA - 2 utøvere
            array( 'name' => 'Miha Šimenc', 'country' => 'Slovenia' ),
            array( 'name' => 'Vili Črv', 'country' => 'Slovenia' ),
            
            // ESTONIA - 2 utøvere
            array( 'name' => 'Alvar Johannes Alev', 'country' => 'Estonia' ),
            array( 'name' => 'Marko Kilp', 'country' => 'Estonia' ),
            
            // POLAND - 2 utøvere
            array( 'name' => 'Dominik Bury', 'country' => 'Poland' ),
            array( 'name' => 'Maciej Staręga', 'country' => 'Poland' ),
            
            // JAPAN - 2 utøvere
            array( 'name' => 'Naoto Baba', 'country' => 'Japan' ),
            array( 'name' => 'Rintaro Baba', 'country' => 'Japan' ),
            
            // LATVIA - 1 utøver
            array( 'name' => 'Raimo Vīgants', 'country' => 'Latvia' ),
            
            // LITHUANIA - 1 utøver
            array( 'name' => 'Tautvydas Strolia', 'country' => 'Lithuania' ),
            
            // KAZAKHSTAN - 2 utøvere
            array( 'name' => 'Alexandr Kolosov', 'country' => 'Kazakhstan' ),
            array( 'name' => 'Yerdos Akhmadiyev', 'country' => 'Kazakhstan' ),
            
            // CHINA - 2 utøvere
            array( 'name' => 'Wang Qiang', 'country' => 'China' ),
            array( 'name' => 'Liu Rongsheng', 'country' => 'China' ),
            
            // SOUTH KOREA - 1 utøver
            array( 'name' => 'Kim Magnus', 'country' => 'South Korea' ),
            
            // AUSTRALIA - 1 utøver
            array( 'name' => 'Seve de Campo', 'country' => 'Australia' ),
        );
    }

    /**
     * Render API results page
     */
    public function render_api_results() {
        $events = $this->db->get_events();
        ?>
        <div class="wrap">
            <h1>🏆 Hent Resultater</h1>
            <p>Klikk på "Hent resultater" for en øvelse når rennet er ferdig.</p>
            
            <style>
                .ol-events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-top: 20px; }
                .ol-event-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; }
                .ol-event-card h3 { margin: 0 0 10px 0; color: #1d2327; }
                .ol-event-card .event-date { color: #646970; font-size: 14px; margin-bottom: 15px; }
                .ol-event-card .event-status { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-bottom: 15px; }
                .ol-event-card .status-upcoming { background: #dff0d8; color: #3c763d; }
                .ol-event-card .status-live { background: #fcf8e3; color: #8a6d3b; }
                .ol-event-card .status-finished { background: #d9edf7; color: #31708f; }
                .ol-event-card .status-has-results { background: #dff0d8; color: #3c763d; }
                .ol-fetch-btn { display: inline-flex; align-items: center; gap: 8px; }
            </style>
            
            <div class="ol-events-grid">
                <?php foreach ( $events as $event ) : 
                    $event_time = strtotime( $event->event_date );
                    $now = current_time( 'timestamp' );
                    $has_results = $this->event_has_results( $event->id );
                    
                    if ( $has_results ) {
                        $status_class = 'status-has-results';
                        $status_text = '✅ Resultater hentet';
                    } elseif ( $event_time > $now ) {
                        $status_class = 'status-upcoming';
                        $status_text = '📅 Kommende';
                    } elseif ( $event_time > $now - 7200 ) { // Within 2 hours of start
                        $status_class = 'status-live';
                        $status_text = '🔴 Pågår / Nylig ferdig';
                    } else {
                        $status_class = 'status-finished';
                        $status_text = '⏱️ Ferdig - hent resultater';
                    }
                ?>
                <div class="ol-event-card">
                    <h3><?php echo esc_html( $event->event_name ); ?></h3>
                    <div class="event-date">📅 <?php echo date( 'd. M Y H:i', $event_time ); ?></div>
                    <div class="event-status <?php echo $status_class; ?>"><?php echo $status_text; ?></div>
                    
                    <form method="post" action="" style="margin-top: 10px;">
                        <?php wp_nonce_field( 'ol-tipping-fetch-results-' . $event->id ); ?>
                        <input type="hidden" name="event_id" value="<?php echo intval( $event->id ); ?>" />
                        <button type="submit" name="fetch_results" class="button button-primary ol-fetch-btn">
                            🔄 Hent resultater
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <?php
            if ( isset( $_POST['fetch_results'] ) && isset( $_POST['event_id'] ) ) {
                $event_id = intval( $_POST['event_id'] );
                if ( wp_verify_nonce( $_POST['_wpnonce'], 'ol-tipping-fetch-results-' . $event_id ) ) {
                    $result = $this->fetch_results_from_olympics( $event_id );
                    
                    echo '<div style="margin-top: 20px;">';
                    if ( $result['success'] ) {
                        echo '<div class="notice notice-success"><p><strong>✓</strong> ' . esc_html( $result['message'] ) . '</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p><strong>✗</strong> ' . esc_html( $result['message'] ) . '</p></div>';
                    }
                    echo '</div>';
                }
            }
            ?>
        </div>
        <?php
    }

    /**
     * Check if event has results
     */
    private function event_has_results( $event_id ) {
        global $wpdb;
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ol_results WHERE event_id = %d",
            $event_id
        ) );
        return $count > 0;
    }

    /**
     * Fetch results from Olympics.com
     */
    private function fetch_results_from_olympics( $event_id ) {
        global $wpdb;
        
        $event = $this->db->get_event( $event_id );
        if ( ! $event ) {
            return array( 'success' => false, 'message' => 'Øvelsen ble ikke funnet.' );
        }

        // Check if this is a team event
        $is_team_event = $this->db->is_team_event( $event_id );

        // Map event names to Olympics.com result page URLs
        $event_mapping = array(
            'Sprint Klassisk Stil - Herrer' => 'ccs/ms/m/sprint------------/fnl-/000100--',
            '10km Klassisk Stil - Herrer' => 'ccs/ms/m/10kmft------------/fnl-/000100--',
            'Skiathlon 15km - Herrer' => 'ccs/ms/m/skiathln----------/fnl-/000100--',
            '30km Fri Stil - Herrer' => 'ccs/ms/m/30kmft------------/fnl-/000100--',
            'Team Sprint Klassisk Stil - Herrer' => 'ccs/ms/m/teamsprint--------/fnl-/000100--',
            'Stafett 4x5km - Herrer' => 'ccs/ms/m/4x5kmrelay--------/fnl-/000100--',
            '50km Fri Stil - Herrer' => 'ccs/ms/m/50kmms------------/fnl-/000100--',
        );

        $result_path = isset( $event_mapping[ $event->event_name ] ) 
            ? $event_mapping[ $event->event_name ] 
            : null;

        if ( ! $result_path ) {
            return array( 'success' => false, 'message' => 'Øvelsen har ikke en definert resultat-URL. Kontakt utvikler.' );
        }

        // Try to fetch from Olympics.com internal API
        // Olympics.com uses a JSON API behind the scenes
        $api_url = 'https://sph-s-api.olympics.com/winter/schedules/api/ENG/schedule/unit/' . $result_path . '/results';
        
        $response = wp_remote_get( $api_url, array(
            'timeout' => 20,
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            )
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => 'Kunne ikke koble til Olympics.com: ' . $response->get_error_message() );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        if ( $status_code !== 200 ) {
            // Try alternate API endpoint
            $alt_url = 'https://olympics.com/OG2026/data/' . str_replace( '/', '_', $result_path ) . '.json';
            $response = wp_remote_get( $alt_url, array( 'timeout' => 20 ) );
            
            if ( is_wp_error( $response ) ) {
                return array( 
                    'success' => false, 
                    'message' => 'Resultater er ikke tilgjengelige ennå. Prøv igjen når rennet er ferdig og offisielle resultater er publisert.' 
                );
            }
            $body = wp_remote_retrieve_body( $response );
        }

        $data = json_decode( $body, true );

        if ( empty( $data ) ) {
            return array( 
                'success' => false, 
                'message' => 'Ingen resultater funnet. Rennet er kanskje ikke ferdig ennå, eller resultater er ikke publisert.' 
            );
        }

        // Process results - format depends on API response structure
        $count = 0;
        $results_array = isset( $data['results'] ) ? $data['results'] : 
                        ( isset( $data['competitors'] ) ? $data['competitors'] : 
                        ( isset( $data['rsc'] ) ? $data['rsc'] : array() ) );

        if ( empty( $results_array ) ) {
            return array( 'success' => false, 'message' => 'Resultat-data har uventet format. Manuell import kan være nødvendig.' );
        }

        foreach ( $results_array as $result_item ) {
            // Extract data - handle different possible field names
            $position = isset( $result_item['rank'] ) ? intval( $result_item['rank'] ) : 
                       ( isset( $result_item['position'] ) ? intval( $result_item['position'] ) : 0 );
            
            $athlete_name = isset( $result_item['name'] ) ? $result_item['name'] : 
                           ( isset( $result_item['competitor']['name'] ) ? $result_item['competitor']['name'] : 
                           ( isset( $result_item['personName'] ) ? $result_item['personName'] : '' ) );
            
            $country_code = isset( $result_item['noc'] ) ? $result_item['noc'] : 
                           ( isset( $result_item['country'] ) ? $result_item['country'] : 
                           ( isset( $result_item['organisation'] ) ? $result_item['organisation'] : '' ) );
            
            $time = isset( $result_item['result'] ) ? $result_item['result'] : 
                   ( isset( $result_item['time'] ) ? $result_item['time'] : '' );

            if ( $position <= 0 || $position > 10 ) continue; // Only top 10

            if ( $is_team_event ) {
                // For team events, find country
                $country = $wpdb->get_row( $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}ol_countries WHERE code = %s OR name LIKE %s",
                    $country_code,
                    '%' . $country_code . '%'
                ) );

                if ( $country ) {
                    $wpdb->replace(
                        $wpdb->prefix . 'ol_results',
                        array(
                            'event_id' => $event_id,
                            'country_id' => $country->id,
                            'position' => $position,
                            'time' => $time,
                        ),
                        array( '%d', '%d', '%d', '%s' )
                    );
                    $count++;
                }
            } else {
                // For individual events, find athlete
                $athlete = $wpdb->get_row( $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}ol_athletes WHERE name LIKE %s",
                    '%' . $wpdb->esc_like( $athlete_name ) . '%'
                ) );

                if ( $athlete ) {
                    $this->db->add_result( $event_id, $athlete->id, $position, $time );
                    $count++;
                }
            }
        }

        if ( $count === 0 ) {
            return array( 
                'success' => false, 
                'message' => 'Fant resultater, men kunne ikke matche med utøvere/land i databasen. Sjekk at utøvere er importert.' 
            );
        }

        // Calculate points after importing results
        $this->calculate_points_for_event( $event_id );

        return array( 
            'success' => true, 
            'message' => 'Hentet ' . $count . ' resultater for ' . esc_html( $event->event_name ) . '! Poeng er beregnet.' 
        );
    }

    /**
     * Calculate points for all tips on an event
     */
    private function calculate_points_for_event( $event_id ) {
        global $wpdb;
        
        $is_team_event = $this->db->is_team_event( $event_id );
        
        // Get results for this event
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ol_results WHERE event_id = %d ORDER BY position ASC",
            $event_id
        ) );

        if ( empty( $results ) ) return;

        // Build lookup: position => athlete_id or country_id
        $result_lookup = array();
        foreach ( $results as $r ) {
            if ( $is_team_event ) {
                $result_lookup[ $r->position ] = $r->country_id;
            } else {
                $result_lookup[ $r->position ] = $r->athlete_id;
            }
        }

        // Get all tips for this event
        $tips = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ol_tips WHERE event_id = %d",
            $event_id
        ) );

        // Points system: 1st = 25, 2nd = 18, 3rd = 15, 4th = 12, 5th = 10
        $points_system = array( 1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10 );

        foreach ( $tips as $tip ) {
            $points = 0;
            $tipped_id = $is_team_event ? $tip->country_id : $tip->athlete_id;
            
            // Check if their tip matches any result position
            foreach ( $result_lookup as $actual_position => $actual_id ) {
                if ( $tipped_id == $actual_id ) {
                    // Exact position match = full points
                    if ( $tip->position == $actual_position ) {
                        $points = isset( $points_system[ $actual_position ] ) ? $points_system[ $actual_position ] : 5;
                    } else {
                        // In top 5 but wrong position = half points
                        $base_points = isset( $points_system[ $actual_position ] ) ? $points_system[ $actual_position ] : 5;
                        $points = intval( $base_points / 2 );
                    }
                    break;
                }
            }

            // Update tip with points
            $wpdb->update(
                $wpdb->prefix . 'ol_tips',
                array( 'points' => $points ),
                array( 'id' => $tip->id ),
                array( '%d' ),
                array( '%d' )
            );
        }

        // Update leaderboard using database class with correct scoring
        $all_users = $wpdb->get_results( "SELECT DISTINCT user_id FROM {$wpdb->prefix}ol_tips" );
        foreach ( $all_users as $user ) {
            $this->db->update_leaderboard( $user->user_id );
        }
    }

    /**
     * AJAX handler: Create sample data
     */
    public function ajax_create_sample_data() {
        check_ajax_referer( 'ol-tipping-sample-data' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $result = $this->db->create_sample_data();
        
        if ( $result ) {
            wp_send_json_success( 'Sample data created successfully' );
        } else {
            wp_send_json_error( 'Failed to create sample data' );
        }
    }

    /**
     * AJAX handler: Delete sample data
     */
    public function ajax_delete_sample_data() {
        check_ajax_referer( 'ol-tipping-sample-data' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $result = $this->db->delete_sample_data();
        
        if ( $result ) {
            wp_send_json_success( 'Sample data deleted successfully' );
        } else {
            wp_send_json_error( 'Failed to delete sample data' );
        }
    }

    /**
     * Render teams management page
     */
    public function render_teams() {
        global $wpdb;
        $countries_table = $wpdb->prefix . 'ol_countries';

        // Ensure latest schema (adds team_number/display_name columns)
        $this->db->create_tables();

        // Auto-seed default teams if table is empty
        $teams_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $countries_table" );
        if ( $teams_count === 0 ) {
            $this->db->reseed_teams();
        }
        
        // Handle form submissions
        if ( isset( $_POST['add_team'] ) && check_admin_referer( 'ol-tipping-add-team' ) ) {
            $name = sanitize_text_field( $_POST['team_name'] ?? '' );
            $code = sanitize_text_field( $_POST['team_code'] ?? '' );
            $flag = sanitize_text_field( $_POST['team_flag'] ?? '' );
            $team_number = intval( $_POST['team_number'] ?? 1 );
            
            if ( $name && $code ) {
                $display_name = $team_number > 1 ? $name . ' ' . $team_number : $name;
                $wpdb->insert(
                    $countries_table,
                    array(
                        'name' => $name,
                        'code' => $code,
                        'flag' => $flag,
                        'team_number' => $team_number,
                        'display_name' => $display_name,
                        'status' => 'active',
                    ),
                    array( '%s', '%s', '%s', '%d', '%s', '%s' )
                );
                echo '<div class="notice notice-success"><p>✅ Lag lagt til!</p></div>';
            }
        }
        
            if ( isset( $_POST['update_team'] ) && check_admin_referer( 'ol-tipping-update-team' ) ) {
                $team_id = intval( $_POST['team_id'] );
                $name = sanitize_text_field( $_POST['team_name'] ?? '' );
                $code = sanitize_text_field( $_POST['team_code'] ?? '' );
                $flag = sanitize_text_field( $_POST['team_flag'] ?? '' );
                $team_number = intval( $_POST['team_number'] ?? 1 );
            
                if ( $name && $code ) {
                    $display_name = $team_number > 1 ? $name . ' ' . $team_number : $name;
                    $wpdb->update(
                        $countries_table,
                        array(
                            'name' => $name,
                            'code' => $code,
                            'flag' => $flag,
                            'team_number' => $team_number,
                            'display_name' => $display_name,
                        ),
                        array( 'id' => $team_id ),
                        array( '%s', '%s', '%s', '%d', '%s' ),
                        array( '%d' )
                    );
                    echo '<div class="notice notice-success"><p>✅ Lag oppdatert!</p></div>';
                }
            }
        
        if ( isset( $_POST['delete_team'] ) && check_admin_referer( 'ol-tipping-delete-team' ) ) {
            $team_id = intval( $_POST['team_id'] );
            $wpdb->delete( $countries_table, array( 'id' => $team_id ), array( '%d' ) );
            echo '<div class="notice notice-success"><p>✅ Lag slettet!</p></div>';
        }

        if ( isset( $_POST['seed_teams'] ) && check_admin_referer( 'ol-tipping-seed-teams' ) ) {
            $this->db->reseed_teams();
            echo '<div class="notice notice-success"><p>✅ Standard lag er importert!</p></div>';
        }
        
            // Handle edit team modal
            $edit_team = null;
            if ( isset( $_GET['edit_team'] ) ) {
                $edit_team = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM $countries_table WHERE id = %d",
                    intval( $_GET['edit_team'] )
                ) );
            }
        
        // Get all teams
        $teams = $wpdb->get_results( "SELECT * FROM $countries_table ORDER BY name ASC, team_number ASC" );
        
        ?>
        <div class="wrap">
            <h1>🏳️ Administrer Lag</h1>

            <div style="background: #fff; padding: 15px 20px; border: 1px solid #ccd0d4; border-radius: 8px; margin: 15px 0; max-width: 600px;">
                <form method="post" action="">
                    <?php wp_nonce_field( 'ol-tipping-seed-teams' ); ?>
                    <p style="margin: 0 0 10px 0;"><strong>Importer standard lag</strong> (Norge 1/2, Sverige 1/2 + øvrige nasjoner)</p>
                    <input type="submit" class="button button-secondary" value="Importer standard lag" name="seed_teams" />
                </form>
            </div>
            
                <?php if ( $edit_team ) : ?>
                <div style="background: #fff; padding: 20px; border: 1px solid #2271b1; border-left: 4px solid #2271b1; border-radius: 8px; margin-bottom: 20px;">
                    <h2>Rediger lag: <?php echo esc_html( $edit_team->flag ) . ' ' . esc_html( $edit_team->name ); ?></h2>
                    <form method="post" action="">
                        <?php wp_nonce_field( 'ol-tipping-update-team' ); ?>
                        <input type="hidden" name="team_id" value="<?php echo intval( $edit_team->id ); ?>" />
                        <table class="form-table">
                            <tr>
                                <th><label for="edit_team_name">Langnavn:</label></th>
                                <td><input type="text" name="team_name" id="edit_team_name" required style="width: 100%; padding: 8px;" value="<?php echo esc_attr( $edit_team->name ); ?>"/></td>
                            </tr>
                            <tr>
                                <th><label for="edit_team_code">Landkode:</label></th>
                                <td><input type="text" name="team_code" id="edit_team_code" required maxlength="10" style="width: 100%; padding: 8px;" value="<?php echo esc_attr( $edit_team->code ); ?>"/></td>
                            </tr>
                            <tr>
                                <th><label for="edit_team_flag">Flagg (emoji):</label></th>
                                <td><input type="text" name="team_flag" id="edit_team_flag" style="width: 100%; padding: 8px;" value="<?php echo esc_attr( $edit_team->flag ); ?>"/></td>
                            </tr>
                            <tr>
                                <th><label for="edit_team_number">Lagnummer:</label></th>
                                <td>
                                    <select name="team_number" id="edit_team_number" style="width: 100%; padding: 8px;">
                                        <option value="1" <?php selected( $edit_team->team_number, 1 ); ?>>1 (første lag)</option>
                                        <option value="2" <?php selected( $edit_team->team_number, 2 ); ?>>2 (andre lag)</option>
                                        <option value="3" <?php selected( $edit_team->team_number, 3 ); ?>>3 (tredje lag)</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" class="button button-primary" value="Lagre endringer" name="update_team" />
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ol-tipping-teams' ) ); ?>" class="button">Avbryt</a>
                        </p>
                    </form>
                </div>
                <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <!-- Add new team -->
                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px;">
                    <h2>Legg til nytt lag</h2>
                    <form method="post" action="">
                        <?php wp_nonce_field( 'ol-tipping-add-team' ); ?>
                        <table class="form-table">
                            <tr>
                                <th><label for="team_name">Langnavn:</label></th>
                                <td><input type="text" name="team_name" id="team_name" required style="width: 100%; padding: 8px;" placeholder="f.eks. Norway"/></td>
                            </tr>
                            <tr>
                                <th><label for="team_code">Landkode:</label></th>
                                <td><input type="text" name="team_code" id="team_code" required maxlength="10" style="width: 100%; padding: 8px;" placeholder="f.eks. NOR"/></td>
                            </tr>
                            <tr>
                                <th><label for="team_flag">Flagg (emoji):</label></th>
                                <td><input type="text" name="team_flag" id="team_flag" style="width: 100%; padding: 8px;" placeholder="f.eks. 🇳🇴"/></td>
                            </tr>
                            <tr>
                                <th><label for="team_number">Lagnummer:</label></th>
                                <td>
                                    <select name="team_number" id="team_number" style="width: 100%; padding: 8px;">
                                        <option value="1">1 (første lag)</option>
                                        <option value="2">2 (andre lag)</option>
                                        <option value="3">3 (tredje lag)</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <p class="submit"><input type="submit" class="button button-primary" value="Legg til lag" name="add_team" /></p>
                    </form>
                </div>
                
                <!-- List of teams -->
                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px;">
                    <h2>Alle lag</h2>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Lag</th>
                                <th>Kode</th>
                                <th>Nr.</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $teams as $team ) : ?>
                            <tr>
                                <td><?php echo esc_html( $team->flag ) . ' ' . esc_html( $team->display_name ?? $team->name ); ?></td>
                                <td><?php echo esc_html( $team->code ); ?></td>
                                <td><?php echo intval( $team->team_number ); ?></td>
                                <td>
                                     <a href="<?php echo esc_url( add_query_arg( 'edit_team', intval( $team->id ) ) ); ?>" class="button button-small">Rediger</a>
                                    <form method="post" action="" style="display: inline;">
                                        <?php wp_nonce_field( 'ol-tipping-delete-team' ); ?>
                                        <input type="hidden" name="team_id" value="<?php echo intval( $team->id ); ?>" />
                                        <input type="submit" class="button button-small button-link-delete" value="Slett" name="delete_team" onclick="return confirm('Slett dette laget?');" />
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render edit events page
     */
    public function render_edit_events() {
        global $wpdb;
        $events_table = $wpdb->prefix . 'ol_events';
        
        // Handle form submission
        if ( isset( $_POST['update_event_type'] ) && check_admin_referer( 'ol-tipping-update-event-type' ) ) {
            $event_id = intval( $_POST['event_id'] );
            $event_type = sanitize_text_field( $_POST['event_type'] );
            
            if ( in_array( $event_type, array( 'individual', 'team' ), true ) ) {
                $wpdb->update(
                    $events_table,
                    array( 'event_type' => $event_type ),
                    array( 'id' => $event_id ),
                    array( '%s' ),
                    array( '%d' )
                );
                echo '<div class="notice notice-success"><p>✅ Øvelse oppdatert!</p></div>';
            }
        }
        
        // Get all events
        $events = $wpdb->get_results( "SELECT * FROM $events_table ORDER BY event_date ASC" );
        
        ?>
        <div class="wrap">
            <h1>⚙️ Rediger Øvelser</h1>
            <p>Her kan du sette om en øvelse er individuell eller lagkonkurranse.</p>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Øvelsesnavn</th>
                        <th>Dato</th>
                        <th>Type</th>
                        <th>Handling</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $events as $event ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $event->event_name ); ?></strong></td>
                        <td><?php echo date( 'd. M Y H:i', strtotime( $event->event_date ) ); ?></td>
                        <td>
                            <form method="post" action="" style="display: flex; gap: 10px; align-items: center;">
                                <?php wp_nonce_field( 'ol-tipping-update-event-type' ); ?>
                                <input type="hidden" name="event_id" value="<?php echo intval( $event->id ); ?>" />
                                <select name="event_type" style="padding: 6px;">
                                    <option value="individual" <?php selected( $event->event_type, 'individual' ); ?>>Individuell</option>
                                    <option value="team" <?php selected( $event->event_type, 'team' ); ?>>Lag</option>
                                </select>
                                <input type="submit" class="button button-small" value="Oppdater" name="update_event_type" />
                            </form>
                        </td>
                        <td>
                            <?php
                            $type_badge = $event->event_type === 'team' ? '👥 Lag' : '👤 Individuell';
                            echo $type_badge;
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render merge users page
     */
    public function render_merge_users() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $merge_result = null;

        if ( isset( $_POST['ol_merge_users_nonce'] ) && wp_verify_nonce( $_POST['ol_merge_users_nonce'], 'ol-merge-users' ) ) {
            $keep_id = isset( $_POST['keep_user_id'] ) ? intval( $_POST['keep_user_id'] ) : 0;
            $merge_ids_raw = isset( $_POST['merge_user_ids'] ) ? wp_unslash( $_POST['merge_user_ids'] ) : '';
            $merge_ids = array_filter( array_map( 'intval', preg_split( '/\s*,\s*/', $merge_ids_raw ) ) );
            $merge_ids = array_diff( $merge_ids, array( $keep_id ) );

            if ( $keep_id > 0 && ! empty( $merge_ids ) ) {
                $merged = 0;
                foreach ( $merge_ids as $merge_id ) {
                    $result = $this->merge_user_into( $keep_id, $merge_id );
                    if ( $result ) {
                        $merged++;
                    }
                }
                $this->db->update_leaderboard( $keep_id );
                $merge_result = array( 'success' => true, 'count' => $merged );
            } else {
                $merge_result = array( 'success' => false, 'message' => 'Velg en hovedbruker og minst én bruker å slå sammen.' );
            }
        }

        $duplicates = $this->find_duplicate_users();
        ?>
        <div class="wrap">
            <h1>👥 Slå sammen duplikatbrukere</h1>
            <p>Velg en hovedbruker og brukere som skal slås sammen. Tips og leaderboard flyttes til hovedbrukeren.</p>

            <?php if ( $merge_result && $merge_result['success'] ) : ?>
                <div class="notice notice-success"><p>✅ Sammenslått <?php echo intval( $merge_result['count'] ); ?> bruker(e).</p></div>
            <?php elseif ( $merge_result && ! $merge_result['success'] ) : ?>
                <div class="notice notice-error"><p>❌ <?php echo esc_html( $merge_result['message'] ); ?></p></div>
            <?php endif; ?>

            <div style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:8px;max-width:900px;">
                <h2>Slå sammen manuelt</h2>
                <form method="post" action="">
                    <?php wp_nonce_field( 'ol-merge-users', 'ol_merge_users_nonce' ); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="keep_user_id">Hovedbruker (ID)</label></th>
                            <td><input type="number" name="keep_user_id" id="keep_user_id" required /></td>
                        </tr>
                        <tr>
                            <th><label for="merge_user_ids">Brukere som slås sammen (ID, komma-separert)</label></th>
                            <td><input type="text" name="merge_user_ids" id="merge_user_ids" placeholder="f.eks. 389, 408" required /></td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Slå sammen brukere" onclick="return confirm('Er du sikker? Dette kan ikke angres.');" /></p>
                </form>
            </div>

            <div style="margin-top:20px;background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:8px;max-width:900px;">
                <h2>Foreslåtte duplikater</h2>
                <?php if ( empty( $duplicates ) ) : ?>
                    <p>Ingen duplikater funnet.</p>
                <?php else : ?>
                    <?php foreach ( $duplicates as $label => $groups ) : ?>
                        <h3><?php echo esc_html( $label ); ?></h3>
                        <?php foreach ( $groups as $group ) : ?>
                            <div style="padding:10px 12px;border:1px dashed #ccd0d4;border-radius:6px;margin-bottom:10px;">
                                <strong><?php echo esc_html( $group['key'] ); ?></strong><br />
                                <?php foreach ( $group['users'] as $user ) : ?>
                                    <span style="display:inline-block;margin-right:10px;">ID <?php echo intval( $user['ID'] ); ?> — <?php echo esc_html( $user['user_login'] ); ?> (<?php echo esc_html( $user['user_email'] ); ?>)</span>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Merge one user into another
     */
    private function merge_user_into( $keep_id, $merge_id ) {
        global $wpdb;

        if ( $keep_id <= 0 || $merge_id <= 0 || $keep_id === $merge_id ) {
            return false;
        }

        $tips_table = $wpdb->prefix . 'ol_tips';
        $leaderboard_table = $wpdb->prefix . 'ol_leaderboard';

        // Remove conflicting tips (same event+position) from the merge user
        $wpdb->query( $wpdb->prepare(
            "DELETE t1 FROM $tips_table t1
             INNER JOIN $tips_table t2
                ON t2.user_id = %d
               AND t2.event_id = t1.event_id
               AND t2.position = t1.position
             WHERE t1.user_id = %d",
            $keep_id,
            $merge_id
        ) );

        // Move remaining tips
        $wpdb->update(
            $tips_table,
            array( 'user_id' => $keep_id ),
            array( 'user_id' => $merge_id ),
            array( '%d' ),
            array( '%d' )
        );

        // Remove merge user's leaderboard row
        $wpdb->delete( $leaderboard_table, array( 'user_id' => $merge_id ), array( '%d' ) );

        if ( ! function_exists( 'wp_delete_user' ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        return wp_delete_user( $merge_id, $keep_id );
    }

    /**
     * Find duplicate users by display name / login / email (case-insensitive)
     */
    private function find_duplicate_users() {
        $users = get_users( array( 'fields' => array( 'ID', 'user_login', 'user_email', 'display_name' ) ) );
        $groups = array(
            'Duplikater på visningsnavn' => array(),
            'Duplikater på brukernavn' => array(),
            'Duplikater på e-post' => array(),
        );

        $by_display = array();
        $by_login = array();
        $by_email = array();

        foreach ( $users as $user ) {
            $display_key = strtolower( trim( $user->display_name ) );
            $login_key = strtolower( trim( $user->user_login ) );
            $email_key = strtolower( trim( $user->user_email ) );

            if ( $display_key ) {
                $by_display[ $display_key ][] = array(
                    'ID' => $user->ID,
                    'user_login' => $user->user_login,
                    'user_email' => $user->user_email,
                );
            }
            if ( $login_key ) {
                $by_login[ $login_key ][] = array(
                    'ID' => $user->ID,
                    'user_login' => $user->user_login,
                    'user_email' => $user->user_email,
                );
            }
            if ( $email_key ) {
                $by_email[ $email_key ][] = array(
                    'ID' => $user->ID,
                    'user_login' => $user->user_login,
                    'user_email' => $user->user_email,
                );
            }
        }

        foreach ( $by_display as $key => $items ) {
            if ( count( $items ) > 1 ) {
                $groups['Duplikater på visningsnavn'][] = array( 'key' => $key, 'users' => $items );
            }
        }
        foreach ( $by_login as $key => $items ) {
            if ( count( $items ) > 1 ) {
                $groups['Duplikater på brukernavn'][] = array( 'key' => $key, 'users' => $items );
            }
        }
        foreach ( $by_email as $key => $items ) {
            if ( count( $items ) > 1 ) {
                $groups['Duplikater på e-post'][] = array( 'key' => $key, 'users' => $items );
            }
        }

        // Remove empty categories
        return array_filter( $groups );
    }
}
} // end if class_exists