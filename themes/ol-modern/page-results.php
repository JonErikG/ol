<?php
/**
 * Template Name: Results Page
 * Template for /results/ - Shows detailed event results with user tips and points
 */

get_header();
global $wpdb;
?>

<main id="main" class="site-main">
    <div class="page-wrapper" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
        
        <style>
            .ol-detailed-results-page { }
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
            .ol-points { 
                font-weight: 600; font-size: 1rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; padding: 6px 12px; border-radius: 6px;
                text-align: center; min-width: 45px; display: inline-block;
            }
            .ol-points.zero { background: #ddd; color: #666; }
            .ol-medal-gold { background: #ffe066 !important; color: #1a1a1a; }
            .ol-medal-silver { background: #c0c0c0 !important; color: #1a1a1a; }
            .ol-medal-bronze { background: #cd7f32 !important; color: #1a1a1a; }
            .ol-no-results { text-align: center; padding: 60px; color: #666; font-size: 18px; }
            .ol-results-vertical { display: none; }
            .ol-user-card { background: #f8f9fa; border-radius: 12px; padding: 16px; margin-top: 12px; }
            .ol-user-card.ol-medal-gold { background: #ffe066; }
            .ol-user-card.ol-medal-silver { background: #c0c0c0; }
            .ol-user-card.ol-medal-bronze { background: #cd7f32; }
            .ol-user-card.ol-medal-gold,
            .ol-user-card.ol-medal-silver,
            .ol-user-card.ol-medal-bronze { color: #1a1a1a; }
            .ol-user-card.ol-medal-gold .ol-tipper-name,
            .ol-user-card.ol-medal-silver .ol-tipper-name,
            .ol-user-card.ol-medal-bronze .ol-tipper-name,
            .ol-user-card.ol-medal-gold .ol-user-tip-name,
            .ol-user-card.ol-medal-silver .ol-user-tip-name,
            .ol-user-card.ol-medal-bronze .ol-user-tip-name,
            .ol-user-card.ol-medal-gold .ol-user-tip-meta,
            .ol-user-card.ol-medal-silver .ol-user-tip-meta,
            .ol-user-card.ol-medal-bronze .ol-user-tip-meta { color: #1a1a1a; }
            .ol-user-card-header { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 10px; }
            .ol-user-tip-list { display: flex; flex-direction: column; gap: 8px; }
            .ol-user-tip-row { display: flex; justify-content: space-between; gap: 10px; font-size: 0.95rem; }
            .ol-user-tip-name { color: #333; font-weight: 600; }
            .ol-user-tip-meta { color: #666; }
            .ol-result-table { display: none; }
            .ol-results-vertical { display: block; }
            .ol-top-5-results { background: #f8f9fa; border-radius: 12px; padding: 25px; margin-bottom: 30px; }
            .ol-top-5-results h3 { color: #667eea; font-size: 1.3rem; margin-bottom: 15px; }
            .ol-top-5-list { display: flex; flex-direction: column; gap: 12px; }
            .ol-top-5-item { display: flex; align-items: center; gap: 15px; padding: 12px; background: white; border-radius: 8px; }
            .ol-top-5-position { width: 40px; height: 40px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
            .ol-top-5-position.pos-1 { background: #ffe066; color: #1a1a1a; }
            .ol-top-5-position.pos-2 { background: #c0c0c0; color: #1a1a1a; }
            .ol-top-5-position.pos-3 { background: #cd7f32; color: #1a1a1a; }
            .ol-top-5-name { flex: 1; font-weight: 600; color: #1a1a1a; }
            .ol-top-5-footer { text-align: center; margin-top: 15px; }
            .ol-top-5-footer a { color: #667eea; text-decoration: none; font-size: 0.95rem; }
            .ol-top-5-footer a:hover { text-decoration: underline; }
        </style>

        <div class="ol-detailed-results-page">
            <div class="ol-results-header">
                <h1>📊 Detaljerte Resultater</h1>
                <p>Se hvem som tippet hva og hvor mange poeng de fikk på hver øvelse</p>
            </div>

            <div class="ol-event-results-container">
                <?php
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
                    echo '<div class="ol-no-results">Ingen resultater registrert ennå.</div>';
                } else {
                    foreach ( $events as $event ) {
                        ?>
                        <div class="ol-event-result">
                            <h2><?php echo esc_html( $event->event_name ); ?></h2>
                            <div class="ol-event-info">
                                Dato: <?php echo date( 'd. M Y H:i', strtotime( $event->event_date ) ); ?>
                            </div>

                            <?php
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

                            // Display top 5 results
                            if ( ! empty( $results ) ) {
                                ?>
                                <div class="ol-top-5-results">
                                    <h3>🥇 Top 5 Resultater</h3>
                                    <div class="ol-top-5-list">
                                        <?php
                                        $count = 0;
                                        foreach ( $results as $result ) {
                                            if ( $count >= 5 ) break;
                                            $count++;
                                            $result_name = $result->athlete_name ?? $result->country_name ?? 'N/A';
                                            $pos_class = 'pos-' . intval( $result->position );
                                            ?>
                                            <div class="ol-top-5-item">
                                                <div class="ol-top-5-position <?php echo $pos_class; ?>"><?php echo intval( $result->position ); ?></div>
                                                <div class="ol-top-5-name"><?php echo esc_html( $result_name ); ?></div>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <div class="ol-top-5-footer">
                                        <a href="#ol-full-results-<?php echo $event->id; ?>">👇 Se alle plasseringer</a>
                                    </div>
                                </div>
                                <?php
                            }

                            // Get all tips for this event with user info
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

                            // Sort users by total points (highest first)
                            uasort( $tips_by_user, function( $a, $b ) {
                                return intval( $b['total_points'] ) - intval( $a['total_points'] );
                            } );

                            // Build medal ranks by total points (ties share same medal)
                            $totals = array();
                            foreach ( $tips_by_user as $user_data ) {
                                $totals[] = intval( $user_data['total_points'] );
                            }
                            rsort( $totals );
                            $totals = array_values( array_unique( $totals ) );
                            $rank_by_points = array();
                            foreach ( $totals as $idx => $points_total ) {
                                $rank_by_points[ $points_total ] = $idx + 1;
                            }
                            ?>

                            <table class="ol-result-table">
                                <thead>
                                    <tr>
                                        <th>Plass</th>
                                        <th>Resultat</th>
                                        <?php foreach ( $tips_by_user as $user_id => $user_data ) : 
                                            $user_total_points = intval( $user_data['total_points'] );
                                            $user_rank = isset( $rank_by_points[ $user_total_points ] ) ? $rank_by_points[ $user_total_points ] : 0;
                                            $medal_class = '';
                                            if ( $user_rank === 1 ) {
                                                $medal_class = ' ol-medal-gold';
                                            } elseif ( $user_rank === 2 ) {
                                                $medal_class = ' ol-medal-silver';
                                            } elseif ( $user_rank === 3 ) {
                                                $medal_class = ' ol-medal-bronze';
                                            }
                                        ?>
                                            <th class="<?php echo $medal_class; ?>" style="text-align: center;"><?php echo esc_html( substr( $user_data['display_name'], 0, 15 ) ); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Show results row by row
                                    foreach ( $results as $result ) {
                                        $medal = '';
                                        if ( $result->position == 1 ) {
                                            $medal = '🥇';
                                        } elseif ( $result->position == 2 ) {
                                            $medal = '🥈';
                                        } elseif ( $result->position == 3 ) {
                                            $medal = '🥉';
                                        }
                                        
                                        $result_name = $result->athlete_name ?? $result->country_name ?? 'N/A';
                                        ?>
                                        <tr>
                                            <td class="ol-place-medal"><?php echo $medal; ?> <?php echo intval( $result->position ); ?></td>
                                            <td><strong><?php echo esc_html( $result_name ); ?></strong></td>
                                            <?php
                                            foreach ( $tips_by_user as $user_id => $user_data ) {
                                                $found_tip = false;
                                                $points = 0;
                                                
                                                // Calculate medal class for this user's column
                                                $user_total_points = intval( $user_data['total_points'] );
                                                $user_rank = isset( $rank_by_points[ $user_total_points ] ) ? $rank_by_points[ $user_total_points ] : 0;
                                                $medal_class = '';
                                                if ( $user_rank === 1 ) {
                                                    $medal_class = ' ol-medal-gold';
                                                } elseif ( $user_rank === 2 ) {
                                                    $medal_class = ' ol-medal-silver';
                                                } elseif ( $user_rank === 3 ) {
                                                    $medal_class = ' ol-medal-bronze';
                                                }
                                                
                                                // Find if user tipped this result
                                                foreach ( $user_data['tips'] as $position => $tip ) {
                                                    $tip_matches = false;
                                                    if ( $tip->athlete_id && $tip->athlete_id == $result->athlete_id ) {
                                                        $tip_matches = true;
                                                    } elseif ( $tip->country_id && $tip->country_id == $result->country_id ) {
                                                        $tip_matches = true;
                                                    }
                                                    
                                                    if ( $tip_matches ) {
                                                        $points = intval( $tip->points );
                                                        $found_tip = true;
                                                        $points_class = $points > 0 ? '' : ' zero';
                                                        ?>
                                                        <td class="<?php echo $medal_class; ?>" style="text-align: center; font-size: 0.9rem;">
                                                            ✓ Plass <?php echo intval( $position ); ?><br>
                                                            <span class="ol-points<?php echo $points_class; ?>"><?php echo $points; ?> pts</span>
                                                        </td>
                                                        <?php
                                                        break;
                                                    }
                                                }
                                                
                                                if ( ! $found_tip ) {
                                                    ?>
                                                    <td class="<?php echo $medal_class; ?>" style="text-align: center; color: #ccc;">-</td>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                    <!-- Total points row -->
                                    <tr style="background: #f0f0f0; font-weight: 600;">
                                        <td colspan="2">Total Poeng Denne Øvelsen</td>
                                        <?php
                                        foreach ( $tips_by_user as $user_data ) {
                                            $user_total_points = intval( $user_data['total_points'] );
                                            $user_rank = isset( $rank_by_points[ $user_total_points ] ) ? $rank_by_points[ $user_total_points ] : 0;
                                            $medal_class = '';
                                            if ( $user_rank === 1 ) {
                                                $medal_class = ' ol-medal-gold';
                                            } elseif ( $user_rank === 2 ) {
                                                $medal_class = ' ol-medal-silver';
                                            } elseif ( $user_rank === 3 ) {
                                                $medal_class = ' ol-medal-bronze';
                                            }
                                            ?>
                                            <td style="text-align: center;">
                                                <span class="ol-points<?php echo $medal_class; ?>"><?php echo $user_total_points; ?></span>
                                            </td>
                                            <?php
                                        }
                                        ?>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="ol-results-vertical">
                                <h3 id="ol-full-results-<?php echo $event->id; ?>" style="margin-top: 30px; margin-bottom: 20px; color: #667eea;">📋 Alle Plasseringer & Tips</h3>
                                <?php foreach ( $tips_by_user as $user_data ) : 
                                    $user_total_points = intval( $user_data['total_points'] );
                                    $user_rank = isset( $rank_by_points[ $user_total_points ] ) ? $rank_by_points[ $user_total_points ] : 0;
                                    $medal_class = '';
                                    if ( $user_rank === 1 ) {
                                        $medal_class = ' ol-medal-gold';
                                    } elseif ( $user_rank === 2 ) {
                                        $medal_class = ' ol-medal-silver';
                                    } elseif ( $user_rank === 3 ) {
                                        $medal_class = ' ol-medal-bronze';
                                    }
                                ?>
                                    <div class="ol-user-card<?php echo $medal_class; ?>">
                                        <div class="ol-user-card-header">
                                            <div class="ol-tipper-name"><?php echo esc_html( $user_data['display_name'] ); ?></div>
                                            <div class="ol-user-total">
                                                <span class="ol-points"><?php echo $user_total_points; ?></span>
                                            </div>
                                        </div>
                                        <div class="ol-user-tip-list">
                                            <?php
                                            for ( $pos = 1; $pos <= 5; $pos++ ) {
                                                if ( isset( $user_data['tips'][ $pos ] ) ) {
                                                    $tip = $user_data['tips'][ $pos ];
                                                    $tip_name = $tip->athlete_name ? $tip->athlete_name : ( $tip->country_name ? $tip->country_name : 'N/A' );
                                                    $points = intval( $tip->points );
                                                    $points_class = $points > 0 ? '' : ' zero';
                                                    ?>
                                                    <div class="ol-user-tip-row">
                                                        <div>
                                                            <span class="ol-user-tip-name">Plass <?php echo $pos; ?>:</span>
                                                            <span class="ol-user-tip-meta"><?php echo esc_html( $tip_name ); ?></span>
                                                        </div>
                                                        <span class="ol-points<?php echo $points_class; ?>"><?php echo $points; ?> pts</span>
                                                    </div>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <div class="ol-user-tip-row">
                                                        <div>
                                                            <span class="ol-user-tip-name">Plass <?php echo $pos; ?>:</span>
                                                            <span class="ol-user-tip-meta">-</span>
                                                        </div>
                                                        <span class="ol-points zero">0 pts</span>
                                                    </div>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>

    </div>
</main>

<?php
get_footer();
