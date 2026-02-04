<?php
/**
 * Footer Template - OL Tipping Milano-Cortina 2026
 */
?>
        </div><!-- .site-content -->
    </div><!-- .site -->
    
    <footer class="site-footer">
        <style>
            .site-footer {
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
                color: #b8b8d1;
                padding: 60px 30px 30px;
                margin-top: 60px;
            }
            
            .footer-inner {
                max-width: 1400px;
                margin: 0 auto;
            }
            
            .footer-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 40px;
                margin-bottom: 40px;
            }
            
            .footer-section h3 {
                color: #ffffff;
                font-size: 1.2rem;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .footer-section p {
                line-height: 1.8;
                font-size: 0.95rem;
            }
            
            .footer-menu {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .footer-menu li {
                margin-bottom: 12px;
            }
            
            .footer-menu a {
                color: #b8b8d1;
                text-decoration: none;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .footer-menu a:hover {
                color: #667eea;
                padding-left: 5px;
            }
            
            .footer-bottom {
                border-top: 1px solid rgba(255,255,255,0.1);
                padding-top: 30px;
                text-align: center;
                font-size: 0.9rem;
            }
            
            .footer-bottom p {
                margin: 0;
            }
            
            @media (max-width: 768px) {
                .footer-grid {
                    grid-template-columns: 1fr;
                    text-align: center;
                }
                .footer-menu a {
                    justify-content: center;
                }
            }
        </style>
        
        <div class="footer-inner">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>🎿 OL Tippekonkurranse</h3>
                    <p>Tippe på resultater fra Milano-Cortina 2026 langrenn og konkurrér mot andre håpløse tippere i Wørld Cøp</p>
                </div>
                
                <div class="footer-section">
                    <h3>📍 Navigasjon</h3>
                    <ul class="footer-menu">
                        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">🏠 Hjem</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/tipping' ) ); ?>">🎿 Tippe</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/events' ) ); ?>">🏔️ Øvelser</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/community-tips' ) ); ?>">👥 Andres Tips</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/leaderboard' ) ); ?>">🏆 Leaderboard</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>ℹ️ Om OL 2026</h3>
                    <p>
                        <strong>📍 Sted:</strong> Milano-Cortina, Italia<br>
                        <strong>📅 Dato:</strong> 6-22. februar 2026<br>
                        <strong>⛷️ Sport:</strong> Langrenn<br>
                        <strong>🎯 Øvelser:</strong> 7 renn
                    </p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date( 'Y' ); ?> OL Tippekonkurranse • Laget med ❤️ for skielskarar 🏔️Cøpirights allj ræights reserved O wannabe.</p>
            </div>
        </div>
    </footer>
    
    <?php wp_footer(); ?>
</body>
</html>
