<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DW_Settings_Page {

    /** @var string */
    private $lang;

    /** @var array */
    private $s;

    public function __construct() {
        $this->lang = get_option( 'dw_perf_language', 'tr' );
        $this->s    = $this->get_strings();

        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_dw_clear_cache', array( $this, 'ajax_clear_cache' ) );
        add_filter(
            'plugin_action_links_' . plugin_basename( DW_PERF_PATH . 'dijitalworlds-performance.php' ),
            array( $this, 'add_action_links' )
        );
    }

    public function add_action_links( $links ) {
        $url   = admin_url( 'options-general.php?page=dw-performance' );
        $label = 'en' === $this->lang ? 'Settings' : 'Ayarlar';
        array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>' );
        return $links;
    }

    public function add_menu() {
        add_options_page(
            'Dijitalworlds Performance',
            'DW Performance',
            'manage_options',
            'dw-performance',
            array( $this, 'render_page' )
        );
    }

    public function register_settings() {
        $options = array(
            'dw_perf_defer_js', 'dw_perf_defer_css',
            'dw_perf_preload_lcp_image', 'dw_perf_lcp_image_url',
            'dw_perf_fix_lazy_load', 'dw_perf_fix_meta_desc',
            'dw_perf_fix_link_text', 'dw_perf_browser_cache',
            'dw_perf_preconnect', 'dw_perf_font_display_swap',
            'dw_perf_delay_gtm', 'dw_perf_gtm_id', 'dw_perf_delay_gsi',
            'dw_perf_security_headers', 'dw_perf_fix_lcp_impreza',
            'dw_perf_language',
        );
        foreach ( $options as $o ) {
            register_setting( 'dw_perf_group', $o );
        }
    }

    public function enqueue_assets( $hook ) {
        if ( 'settings_page_dw-performance' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'dw-perf-admin', DW_PERF_URL . 'admin/admin.css', array(), DW_PERF_VERSION );
        wp_enqueue_script( 'dw-perf-admin', DW_PERF_URL . 'admin/admin.js', array( 'jquery' ), DW_PERF_VERSION, true );
        wp_localize_script( 'dw-perf-admin', 'dwPerf', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'dw_seo_nonce' ),
            'lang'     => $this->lang,
            'strings'  => array(
                'scanning'       => $this->s['scanning'],
                'scan_btn'       => $this->s['seo_run_btn'],
                'save'           => $this->s['save'],
                'saving'         => $this->s['saving'],
                'saved'          => $this->s['saved'],
                'err_generic'    => $this->s['err_generic'],
                'err_ajax'       => $this->s['err_ajax'],
                'err_save'       => $this->s['err_save'],
                'fixed_badge'    => $this->s['fixed_badge'],
                'ok_badge'       => $this->s['ok_badge'],
                'warn_badge'     => $this->s['warn_badge'],
                'words'          => $this->s['words'],
                'auto_fixed'     => $this->s['auto_fixed'],
                'ok_label'       => $this->s['ok_label'],
                'warn_label'     => $this->s['warn_label'],
                'fixed_label'    => $this->s['fixed_label'],
                'total_label'    => $this->s['total_label'],
                'pages'          => $this->s['pages'],
                'edit_link'      => $this->s['edit_link'],
                'clear_cache'        => $this->s['clear_cache_btn'],
                'clearing_cache'     => $this->s['clearing_cache'],
                'cache_cleared'      => $this->s['cache_cleared'],
                'cache_error'        => $this->s['cache_error'],
                'cache_result_title' => $this->s['cache_result_title'],
                'cache_varnish_found'=> $this->s['cache_varnish_found'],
                'cache_varnish_none' => $this->s['cache_varnish_none'],
            ),
        ) );
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Dil değişikliği işle
        if ( isset( $_POST['dw_lang_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dw_lang_nonce'] ) ), 'dw_lang_switch' ) ) {
            $new_lang = ( isset( $_POST['dw_lang'] ) && 'en' === $_POST['dw_lang'] ) ? 'en' : 'tr';
            update_option( 'dw_perf_language', $new_lang );
            wp_safe_redirect( admin_url( 'options-general.php?page=dw-performance' ) );
            exit;
        }

        $saved = false;
        if ( isset( $_POST['dw_perf_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dw_perf_nonce'] ) ), 'dw_perf_save' ) ) {
            $this->save_settings();
            $saved = true;
        }

        $s = $this->s;
        ?>
        <div class="wrap dw-wrap">

            <div class="dw-header">
                <div class="dw-header-top">
                    <div>
                        <h1>🚀 Dijitalworlds Performance</h1>
                        <p class="dw-subtitle"><?php echo esc_html( $s['subtitle'] ); ?></p>
                    </div>
                    <div class="dw-lang-switcher">
                        <span class="dw-lang-label">🌐</span>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field( 'dw_lang_switch', 'dw_lang_nonce' ); ?>
                            <input type="hidden" name="dw_lang" value="tr">
                            <button type="submit" class="dw-lang-btn<?php echo ( 'tr' === $this->lang ) ? ' dw-lang-active' : ''; ?>">🇹🇷 TR</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field( 'dw_lang_switch', 'dw_lang_nonce' ); ?>
                            <input type="hidden" name="dw_lang" value="en">
                            <button type="submit" class="dw-lang-btn<?php echo ( 'en' === $this->lang ) ? ' dw-lang-active' : ''; ?>">🇬🇧 EN</button>
                        </form>
                    </div>
                </div>
            </div>

            <?php if ( $saved ) : ?>
                <div class="notice notice-success dw-notice"><p><?php echo esc_html( $s['saved_notice'] ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field( 'dw_perf_save', 'dw_perf_nonce' ); ?>

                <!-- ROW 1 -->
                <div class="dw-grid">

                    <!-- PERFORMANS -->
                    <div class="dw-card">
                        <div class="dw-card-header"><?php echo esc_html( $s['card_perf'] ); ?></div>
                        <div class="dw-card-body">
                            <?php $this->toggle( 'dw_perf_defer_js', $s['defer_js_label'], $s['defer_js_hint'] ); ?>
                            <?php $this->toggle( 'dw_perf_defer_css', $s['defer_css_label'], $s['defer_css_hint'] ); ?>
                            <?php $this->toggle( 'dw_perf_browser_cache', $s['browser_cache_label'], $s['browser_cache_hint'] ); ?>
                        </div>
                    </div>

                    <!-- LCP -->
                    <div class="dw-card">
                        <div class="dw-card-header"><?php echo esc_html( $s['card_lcp'] ); ?></div>
                        <div class="dw-card-body">
                            <?php $this->toggle( 'dw_perf_preload_lcp_image', $s['preload_lcp_label'], $s['preload_lcp_hint'] ); ?>

                            <div class="dw-input-group">
                                <label for="dw_lcp_url"><?php echo esc_html( $s['lcp_url_label'] ); ?></label>
                                <input type="url" id="dw_lcp_url" name="dw_perf_lcp_image_url"
                                    value="<?php echo esc_attr( get_option( 'dw_perf_lcp_image_url', '' ) ); ?>"
                                    placeholder="https://dijitalworlds.com/wp-content/uploads/2026/01/1-kopyasi-473x1024.png">
                                <small><?php echo wp_kses( $s['lcp_url_small'], array( 'code' => array() ) ); ?></small>
                            </div>

                            <?php $this->toggle( 'dw_perf_fix_lcp_impreza', $s['fix_lcp_label'], $s['fix_lcp_hint'] ); ?>
                            <?php $this->toggle( 'dw_perf_fix_lazy_load', $s['lazy_label'], $s['lazy_hint'] ); ?>
                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="dw-card">
                        <div class="dw-card-header"><?php echo esc_html( $s['card_seo'] ); ?></div>
                        <div class="dw-card-body">
                            <?php $this->toggle( 'dw_perf_fix_meta_desc', $s['meta_desc_label'], $s['meta_desc_hint'] ); ?>
                            <?php $this->toggle( 'dw_perf_fix_link_text', $s['link_text_label'], $s['link_text_hint'] ); ?>
                        </div>
                    </div>

                </div><!-- .dw-grid row 1 -->

                <!-- ROW 2 -->
                <div class="dw-grid">

                    <!-- RESOURCE HINTS -->
                    <div class="dw-card">
                        <div class="dw-card-header"><?php echo esc_html( $s['card_hints'] ); ?></div>
                        <div class="dw-card-body">
                            <?php $this->toggle( 'dw_perf_preconnect', $s['preconnect_label'], $s['preconnect_hint'] ); ?>
                            <?php $this->toggle( 'dw_perf_font_display_swap', $s['font_swap_label'], $s['font_swap_hint'] ); ?>
                        </div>
                    </div>

                    <!-- GTM -->
                    <div class="dw-card">
                        <div class="dw-card-header"><?php echo esc_html( $s['card_3rd'] ); ?></div>
                        <div class="dw-card-body">
                            <?php $this->toggle( 'dw_perf_delay_gtm', $s['gtm_label'], $s['gtm_hint'] ); ?>

                            <div class="dw-input-group">
                                <label for="dw_gtm_id"><?php echo esc_html( $s['gtm_id_label'] ); ?></label>
                                <input type="text" id="dw_gtm_id" name="dw_perf_gtm_id"
                                    value="<?php echo esc_attr( get_option( 'dw_perf_gtm_id', '' ) ); ?>"
                                    placeholder="GTM-XXXXXXX">
                                <small><?php echo wp_kses( $s['gtm_id_small'], array( 'code' => array() ) ); ?></small>
                            </div>

                            <?php $this->toggle( 'dw_perf_delay_gsi', $s['gsi_label'], $s['gsi_hint'] ); ?>
                        </div>
                    </div>

                    <!-- GÜVENLİK -->
                    <div class="dw-card">
                        <div class="dw-card-header"><?php echo esc_html( $s['card_security'] ); ?></div>
                        <div class="dw-card-body">
                            <?php $this->toggle( 'dw_perf_security_headers', $s['sec_label'], $s['sec_hint'] ); ?>

                            <div class="dw-info-box">
                                <strong><?php echo esc_html( $s['sec_headers_title'] ); ?></strong>
                                <ul>
                                    <li><code>Strict-Transport-Security</code> — HSTS</li>
                                    <li><code>X-Frame-Options: SAMEORIGIN</code></li>
                                    <li><code>Content-Security-Policy</code></li>
                                    <li><code>Cross-Origin-Opener-Policy</code></li>
                                    <li><code>Referrer-Policy</code></li>
                                    <li><code>Permissions-Policy</code></li>
                                </ul>
                                <small><?php echo esc_html( $s['sec_warning'] ); ?></small>
                            </div>
                        </div>
                    </div>

                </div><!-- .dw-grid row 2 -->

                <!-- SEO OPTİMİZER -->
                <div class="dw-card dw-seo-optimizer-card">
                    <div class="dw-card-header"><?php echo esc_html( $s['card_seo_optimizer'] ); ?></div>
                    <div class="dw-card-body">

                        <p style="margin-top:0;color:#475569;">
                            <?php echo wp_kses( $s['seo_desc'], array( 'strong' => array() ) ); ?>
                        </p>

                        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                            <label class="dw-toggle" style="margin-bottom:0;">
                                <input type="checkbox" id="dw-seo-auto-fix" value="1" checked>
                                <span class="dw-slider"></span>
                                <span class="dw-label"><?php echo esc_html( $s['seo_auto_fix'] ); ?></span>
                            </label>
                            <button type="button" id="dw-seo-run-btn" class="dw-seo-run-btn">
                                <?php echo esc_html( $s['seo_run_btn'] ); ?>
                            </button>
                        </div>

                        <div id="dw-seo-report" style="display:none;margin-top:20px;">
                            <div id="dw-seo-summary"></div>
                            <h3 style="margin-top:16px;"><?php echo esc_html( $s['seo_report_title'] ); ?></h3>
                            <p style="font-size:12px;color:#94a3b8;margin-top:-8px;">
                                <?php echo wp_kses( $s['seo_report_desc'], array( 'strong' => array() ) ); ?>
                            </p>
                            <div style="overflow-x:auto;">
                                <table class="dw-report-table">
                                    <thead>
                                        <tr>
                                            <th><?php echo esc_html( $s['th_page'] ); ?></th>
                                            <th><?php echo esc_html( $s['th_status'] ); ?></th>
                                            <th><?php echo esc_html( $s['th_meta'] ); ?></th>
                                            <th><?php echo esc_html( $s['th_access'] ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="dw-seo-table-body"></tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- DURUM PANELİ -->
                <div class="dw-card dw-status-card">
                    <div class="dw-card-header"><?php echo esc_html( $s['card_status'] ); ?></div>
                    <div class="dw-card-body dw-status-grid">
                        <?php $this->render_status(); ?>
                    </div>
                </div>

                <div class="dw-actions">
                    <button type="submit" class="dw-btn"><?php echo esc_html( $s['save_btn'] ); ?></button>
                    <button type="button" id="dw-clear-cache-btn" class="dw-btn-danger">
                        🗑️ <?php echo esc_html( $s['clear_cache_btn'] ); ?>
                    </button>
                    <span style="font-size:12px;color:#94a3b8;">v<?php echo esc_html( DW_PERF_VERSION ); ?></span>
                </div>
                <div id="dw-cache-result" style="display:none;" class="dw-cache-result"></div>

            </form>
        </div>
        <?php
    }

    /* -------------------------------------------------------
       AJAX: Tüm önbelleği temizle
    ------------------------------------------------------- */
    public function ajax_clear_cache() {
        check_ajax_referer( 'dw_seo_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Yetki yok.' );
        }

        $cleared = array();

        // 1. WordPress object cache
        wp_cache_flush();
        $cleared[] = 'WordPress Object Cache';

        // 2. WordPress transientleri temizle
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'" );
        $cleared[] = 'Transients';

        // 3. WP Super Cache
        if ( function_exists( 'wp_cache_clean_cache' ) ) {
            global $file_prefix;
            wp_cache_clean_cache( $file_prefix, true );
            $cleared[] = 'WP Super Cache';
        }

        // 4. W3 Total Cache
        if ( function_exists( 'w3tc_pgcache_flush' ) ) {
            w3tc_pgcache_flush();
            $cleared[] = 'W3 Total Cache';
        }

        // 5. WP Rocket
        if ( function_exists( 'rocket_clean_domain' ) ) {
            rocket_clean_domain();
            $cleared[] = 'WP Rocket';
        }

        // 6. LiteSpeed Cache
        if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
            do_action( 'litespeed_purge_all' );
            $cleared[] = 'LiteSpeed Cache';
        }

        // 7. WP Fastest Cache
        if ( isset( $GLOBALS['wp_fastest_cache'] ) && method_exists( $GLOBALS['wp_fastest_cache'], 'deleteCache' ) ) {
            $GLOBALS['wp_fastest_cache']->deleteCache( true );
            $cleared[] = 'WP Fastest Cache';
        }

        // 8. Autoptimize
        if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
            autoptimizeCache::clearall();
            $cleared[] = 'Autoptimize';
        }

        // 9. Varnish Cache
        // 9a. "Varnish HTTP Purge" eklentisi (Mika Epstein)
        if ( class_exists( 'VarnishPurger' ) ) {
            do_action( 'wpvarnish_purge_url', home_url( '/' ) );
            $cleared[] = 'Varnish (eklenti)';
        }
        // 9b. Varnish HTTP Purge eklentisinin purge_url hook'u
        if ( has_action( 'vhp_purge_url' ) ) {
            do_action( 'vhp_purge_url', home_url( '/' ) );
        }
        // 9c. Doğrudan HTTP PURGE isteği (BAN yöntemi)
        $varnish_host = defined( 'VHP_VARNISH_IP' ) ? VHP_VARNISH_IP : '127.0.0.1';
        $varnish_port = defined( 'VHP_VARNISH_PORT' ) ? VHP_VARNISH_PORT : 6081;
        $purge_url    = 'http://' . $varnish_host . ':' . $varnish_port . '/';
        $response     = wp_remote_request( $purge_url, array(
            'method'  => 'PURGE',
            'headers' => array(
                'Host'           => wp_parse_url( home_url(), PHP_URL_HOST ),
                'X-Purge-Method' => 'regex',
                'X-Purge-Regex'  => '.*',
            ),
            'timeout'    => 5,
            'sslverify'  => false,
        ) );
        if ( ! is_wp_error( $response ) ) {
            $code = wp_remote_retrieve_response_code( $response );
            if ( in_array( $code, array( 200, 204, 400, 301, 302 ), true ) ) {
                $cleared[] = 'Varnish (HTTP PURGE)';
            }
        }

        // 10. Rewrite kurallarını yenile
        flush_rewrite_rules( false );

        wp_send_json_success( array( 'cleared' => $cleared ) );
    }

    private function save_settings() {
        $checkboxes = array(
            'dw_perf_defer_js', 'dw_perf_defer_css',
            'dw_perf_preload_lcp_image', 'dw_perf_fix_lazy_load',
            'dw_perf_fix_meta_desc', 'dw_perf_fix_link_text',
            'dw_perf_browser_cache', 'dw_perf_preconnect',
            'dw_perf_font_display_swap', 'dw_perf_delay_gtm',
            'dw_perf_delay_gsi', 'dw_perf_security_headers',
            'dw_perf_fix_lcp_impreza',
        );
        foreach ( $checkboxes as $key ) {
            update_option( $key, isset( $_POST[ $key ] ) ? 1 : 0 );
        }

        if ( isset( $_POST['dw_perf_lcp_image_url'] ) ) {
            update_option( 'dw_perf_lcp_image_url', esc_url_raw( wp_unslash( $_POST['dw_perf_lcp_image_url'] ) ) );
        }
        if ( isset( $_POST['dw_perf_gtm_id'] ) ) {
            update_option( 'dw_perf_gtm_id', sanitize_text_field( wp_unslash( $_POST['dw_perf_gtm_id'] ) ) );
        }

        // Güvenlik ayarı değişince htaccess'i yenile
        $sec = new DW_Security_Headers();
        $sec->remove_htaccess();
        if ( get_option( 'dw_perf_security_headers' ) || get_option( 'dw_perf_browser_cache' ) ) {
            $sec->write_htaccess();
        }
    }

    private function toggle( $option, $label, $hint ) {
        ?>
        <label class="dw-toggle">
            <input type="checkbox" name="<?php echo esc_attr( $option ); ?>" value="1" <?php checked( get_option( $option ), 1 ); ?>>
            <span class="dw-slider"></span>
            <span class="dw-label"><?php echo esc_html( $label ); ?></span>
        </label>
        <p class="dw-hint"><?php echo wp_kses( $hint, array( 'code' => array(), 'strong' => array() ) ); ?></p>
        <?php
    }

    private function render_status() {
        $s      = $this->s;
        $gtm_id = get_option( 'dw_perf_gtm_id', '' );
        $items  = array(
            array( 'label' => 'JS Defer',    'ok' => (bool) get_option( 'dw_perf_defer_js' ),          'val' => get_option( 'dw_perf_defer_js' ) ? $s['status_active'] : $s['status_off'] ),
            array( 'label' => 'CSS Async',   'ok' => (bool) get_option( 'dw_perf_defer_css' ),         'val' => get_option( 'dw_perf_defer_css' ) ? $s['status_active'] : $s['status_off'] ),
            array( 'label' => 'LCP Preload', 'ok' => (bool) get_option( 'dw_perf_preload_lcp_image' ), 'val' => get_option( 'dw_perf_preload_lcp_image' ) ? $s['status_active'] : $s['status_off'] ),
            array( 'label' => 'LCP Impreza', 'ok' => (bool) get_option( 'dw_perf_fix_lcp_impreza' ),   'val' => get_option( 'dw_perf_fix_lcp_impreza' ) ? $s['status_active'] : $s['status_off'] ),
            array( 'label' => 'Preconnect',  'ok' => (bool) get_option( 'dw_perf_preconnect' ),        'val' => get_option( 'dw_perf_preconnect' ) ? $s['status_active'] : $s['status_off'] ),
            array( 'label' => 'Font Swap',   'ok' => (bool) get_option( 'dw_perf_font_display_swap' ), 'val' => get_option( 'dw_perf_font_display_swap' ) ? $s['status_active'] : $s['status_off'] ),
            array(
                'label' => 'GTM',
                'ok'    => ! empty( $gtm_id ) && get_option( 'dw_perf_delay_gtm' ),
                'val'   => ( ! empty( $gtm_id ) && get_option( 'dw_perf_delay_gtm' ) ) ? $gtm_id : ( get_option( 'dw_perf_delay_gtm' ) ? $s['status_no_id'] : $s['status_off'] ),
            ),
            array( 'label' => 'Security',    'ok' => (bool) get_option( 'dw_perf_security_headers' ),  'val' => get_option( 'dw_perf_security_headers' ) ? $s['status_active'] : $s['status_off'] ),
            array( 'label' => 'Cache',       'ok' => (bool) get_option( 'dw_perf_browser_cache' ),     'val' => get_option( 'dw_perf_browser_cache' ) ? $s['status_active'] : $s['status_off'] ),
            array(
                'label' => 'Meta Desc',
                'ok'    => defined( 'WPSEO_VERSION' ) || (bool) get_option( 'dw_perf_fix_meta_desc' ),
                'val'   => defined( 'WPSEO_VERSION' ) ? $s['status_yoast'] : ( get_option( 'dw_perf_fix_meta_desc' ) ? $s['status_active'] : $s['status_off'] ),
            ),
        );

        foreach ( $items as $item ) {
            $class = $item['ok'] ? 'dw-status-ok' : 'dw-status-off';
            $icon  = $item['ok'] ? '✅' : '⭕';
            echo '<div class="dw-status-item ' . esc_attr( $class ) . '">';
            echo '<span class="dw-status-icon">' . $icon . '</span>';
            echo '<span class="dw-status-label">' . esc_html( $item['label'] ) . '</span>';
            echo '<span class="dw-status-val">' . esc_html( $item['val'] ) . '</span>';
            echo '</div>';
        }
    }

    /* -------------------------------------------------------
       Dil Stringleri
    ------------------------------------------------------- */
    private function get_strings() {
        $tr = array(
            'subtitle'           => 'dijitalworlds.com için hız & SEO optimizasyonu — v' . DW_PERF_VERSION,
            'saved_notice'       => '✅ Ayarlar kaydedildi!',
            // Kart başlıkları
            'card_perf'          => '⚡ Performans',
            'card_lcp'           => '🖼️ LCP Görsel Optimizasyonu',
            'card_seo'           => '🔍 SEO Düzeltmeleri',
            'card_hints'         => '🔗 Resource Hints & Font Optimizasyonu',
            'card_3rd'           => '📊 3. Taraf Kod Optimizasyonu',
            'card_security'      => '🔒 Güvenlik Başlıkları',
            'card_seo_optimizer' => '🤖 SEO Optimizer — Tüm Sayfaları Tara & Düzelt',
            'card_status'        => '📊 Aktif Ayarlar',
            // Toggle label & hint
            'defer_js_label'     => 'JavaScript Erteleme (defer)',
            'defer_js_hint'      => 'jQuery hariç tüm scriptlere defer ekler. Render-blocking kaldırır.',
            'defer_css_label'    => 'CSS Async Yükleme',
            'defer_css_hint'     => 'Kritik CSS hariç stil dosyalarını async yükler.',
            'browser_cache_label'=> 'Tarayıcı Önbelleği',
            'browser_cache_hint' => 'Cache-Control + .htaccess ile 1 yıl cache ekler.',
            'preload_lcp_label'  => 'LCP Görselini Preload Et',
            'preload_lcp_hint'   => 'Ana görsel için &lt;link rel="preload"&gt; ekler.',
            'lcp_url_label'      => 'LCP Görsel URL\'si (boş bırakırsan otomatik bulur)',
            'lcp_url_small'      => 'Rapordaki LCP elementi: <code>1-kopyasi-473x1024.png</code> — WebP versiyonunu gir',
            'fix_lcp_label'      => 'Impreza Tema LCP Düzeltici',
            'fix_lcp_hint'       => 'w-image-h içindeki ilk görselden lazy kaldırır, fetchpriority="high" ekler. 620 ms gecikmeyi çözer.',
            'lazy_label'         => 'Genel Lazy Load Yönetimi',
            'lazy_hint'          => 'İçerikteki ilk görselde lazy kaldırır, altındakilere ekler.',
            'meta_desc_label'    => 'Otomatik Meta Description',
            'meta_desc_hint'     => 'Yoast/Rank Math yoksa excerpt\'ten üretir. SEO +5 puan.',
            'link_text_label'    => '"Read More" Link Zenginleştirme',
            'link_text_hint'     => 'READ MORE butonlarına screen-reader metni ekler.',
            'preconnect_label'   => 'Preconnect + DNS-prefetch',
            'preconnect_hint'    => 'Google Fonts, GTM, Analytics için preconnect. Google Fonts gecikmesini ~1.5 sn düşürür.',
            'font_swap_label'    => 'Font-Display: Swap',
            'font_swap_hint'     => 'Google Fonts URL\'lerine display=swap ekler. Yazı tipi yüklenirken metin görünür kalır. Layout shift\'i önler.',
            'gtm_label'          => 'GTM Geciktirilmiş Yükleme',
            'gtm_hint'           => 'GTM\'yi kullanıcı etkileşimine (scroll, click) kadar bekletir. Ana thread\'den 201 ms + 50 KiB kurtarır.',
            'gtm_id_label'       => 'GTM Container ID',
            'gtm_id_small'       => 'Raporda görülen GTM ID: <code>GTM-PR6342</code> — buraya gir',
            'gsi_label'          => 'Google Sign-In (GSI) Erteleme',
            'gsi_hint'           => '/gsi/client = 92 KiB. Kullanılmayan 71 KiB JS tasarrufu.',
            'sec_label'          => 'Güvenlik Header\'ları Ekle',
            'sec_hint'           => 'HSTS, X-Frame-Options, CSP, COOP, Referrer-Policy, Permissions-Policy. PageSpeed "En İyi Uygulamalar" uyarılarını giderir.',
            'sec_headers_title'  => 'Eklenen header\'lar:',
            'sec_warning'        => '⚠️ CSP, mevcut eklentilerle çakışabilir. Sorun çıkarsa kapatın.',
            // SEO Optimizer
            'seo_desc'           => 'Sitenizdeki tüm sayfa ve yazıları tarar; eksik meta description, kısa başlık, görsel alt metni gibi sorunları bulur. <strong>Otomatik Düzelt</strong> seçeneğiyle sorunları anında çözer. Sonuçları tabloda görebilir ve her sayfanın meta description\'ını manuel olarak düzenleyip kaydedebilirsiniz.',
            'seo_auto_fix'       => 'Otomatik Düzelt',
            'seo_run_btn'        => '🔍 SEO Tara & Optimize Et',
            'seo_report_title'   => '📋 Sayfa Raporu',
            'seo_report_desc'    => 'Meta description sütunundaki metinleri düzenleyip <strong>Kaydet</strong> butonuna basarak manuel değişiklik yapabilirsiniz.',
            'th_page'            => 'Sayfa / Yazı',
            'th_status'          => 'Durum & Sorunlar',
            'th_meta'            => 'Meta Description (Düzenlenebilir)',
            'th_access'          => 'Erişim',
            // Eylemler
            'save_btn'           => '💾 Ayarları Kaydet',
            'clear_cache_btn'    => 'Tüm Önbelleği Temizle',
            'clearing_cache'     => 'Temizleniyor...',
            'cache_cleared'      => '✅ Önbellek temizlendi!',
            'cache_error'        => 'Önbellek temizlenirken hata oluştu.',
            'cache_result_title' => 'Temizlenen önbellekler:',
            'cache_varnish_found'=> '🟢 Varnish Önbelleği bulundu ve temizlendi',
            'cache_varnish_none' => '🔴 Varnish Önbelleği bulunamadı (sunucu yanıt vermedi)',
            'save'               => 'Kaydet',
            'saving'             => 'Kaydediliyor...',
            'saved'              => '✅ Kaydedildi',
            'scanning'           => 'Taranıyor...',
            // Durum paneli
            'status_active'      => 'Aktif',
            'status_off'         => 'Kapalı',
            'status_no_id'       => 'ID Girilmedi',
            'status_yoast'       => 'Yoast Aktif',
            // JS strings
            'err_generic'        => 'Bilinmeyen hata.',
            'err_ajax'           => 'AJAX isteği başarısız. Lütfen sayfayı yenileyip tekrar deneyin.',
            'err_save'           => 'Kayıt hatası',
            'fixed_badge'        => '🔧 Düzeltildi',
            'ok_badge'           => '✅ İyi',
            'warn_badge'         => '⚠️ Uyarı',
            'words'              => 'kelime',
            'auto_fixed'         => 'otomatik düzeltildi',
            'ok_label'           => 'Sorunsuz',
            'warn_label'         => 'Uyarı',
            'fixed_label'        => 'Düzeltildi',
            'total_label'        => 'Toplam',
            'pages'              => 'sayfa',
            'edit_link'          => 'WP Düzenle →',
        );

        $en = array(
            'subtitle'           => 'Speed & SEO optimization for dijitalworlds.com — v' . DW_PERF_VERSION,
            'saved_notice'       => '✅ Settings saved!',
            // Card headers
            'card_perf'          => '⚡ Performance',
            'card_lcp'           => '🖼️ LCP Image Optimization',
            'card_seo'           => '🔍 SEO Fixes',
            'card_hints'         => '🔗 Resource Hints & Font Optimization',
            'card_3rd'           => '📊 3rd Party Code Optimization',
            'card_security'      => '🔒 Security Headers',
            'card_seo_optimizer' => '🤖 SEO Optimizer — Scan & Fix All Pages',
            'card_status'        => '📊 Active Settings',
            // Toggle label & hint
            'defer_js_label'     => 'JavaScript Defer',
            'defer_js_hint'      => 'Adds defer to all scripts except jQuery. Eliminates render-blocking.',
            'defer_css_label'    => 'CSS Async Loading',
            'defer_css_hint'     => 'Loads non-critical stylesheets asynchronously.',
            'browser_cache_label'=> 'Browser Cache',
            'browser_cache_hint' => 'Adds 1-year cache via Cache-Control + .htaccess.',
            'preload_lcp_label'  => 'Preload LCP Image',
            'preload_lcp_hint'   => 'Adds &lt;link rel="preload"&gt; for the hero image.',
            'lcp_url_label'      => 'LCP Image URL (leave empty for auto-detection)',
            'lcp_url_small'      => 'LCP element from report: <code>1-kopyasi-473x1024.png</code> — enter WebP version',
            'fix_lcp_label'      => 'Impreza Theme LCP Fixer',
            'fix_lcp_hint'       => 'Removes lazy from first image in .w-image-h, adds fetchpriority="high". Fixes 620 ms delay.',
            'lazy_label'         => 'General Lazy Load Management',
            'lazy_hint'          => 'Removes lazy from first image in content, adds it to images below the fold.',
            'meta_desc_label'    => 'Auto Meta Description',
            'meta_desc_hint'     => 'Generates from excerpt if Yoast/Rank Math is absent. SEO +5 points.',
            'link_text_label'    => '"Read More" Link Enrichment',
            'link_text_hint'     => 'Adds screen-reader text to READ MORE buttons.',
            'preconnect_label'   => 'Preconnect + DNS-prefetch',
            'preconnect_hint'    => 'Preconnect for Google Fonts, GTM, Analytics. Reduces Google Fonts delay by ~1.5s.',
            'font_swap_label'    => 'Font-Display: Swap',
            'font_swap_hint'     => 'Adds display=swap to Google Fonts URLs. Text stays visible while font loads. Prevents layout shift.',
            'gtm_label'          => 'GTM Delayed Loading',
            'gtm_hint'           => 'Delays GTM until user interaction (scroll, click). Saves 201 ms + 50 KiB from main thread.',
            'gtm_id_label'       => 'GTM Container ID',
            'gtm_id_small'       => 'GTM ID from report: <code>GTM-PR6342</code> — enter it here',
            'gsi_label'          => 'Google Sign-In (GSI) Defer',
            'gsi_hint'           => '/gsi/client = 92 KiB. Saves 71 KiB of unused JS.',
            'sec_label'          => 'Add Security Headers',
            'sec_hint'           => 'HSTS, X-Frame-Options, CSP, COOP, Referrer-Policy, Permissions-Policy. Fixes PageSpeed "Best Practices" warnings.',
            'sec_headers_title'  => 'Headers added:',
            'sec_warning'        => '⚠️ CSP may conflict with existing plugins. Disable if issues arise.',
            // SEO Optimizer
            'seo_desc'           => 'Scans all pages and posts; finds issues like missing meta descriptions, short titles, and image alt text. <strong>Auto Fix</strong> resolves issues instantly. View results in the table and manually edit each page\'s meta description.',
            'seo_auto_fix'       => 'Auto Fix',
            'seo_run_btn'        => '🔍 Scan & Optimize SEO',
            'seo_report_title'   => '📋 Page Report',
            'seo_report_desc'    => 'Edit texts in the meta description column and click <strong>Save</strong> to make manual changes.',
            'th_page'            => 'Page / Post',
            'th_status'          => 'Status & Issues',
            'th_meta'            => 'Meta Description (Editable)',
            'th_access'          => 'Access',
            // Actions
            'save_btn'           => '💾 Save Settings',
            'clear_cache_btn'    => 'Clear All Cache',
            'clearing_cache'     => 'Clearing...',
            'cache_cleared'      => '✅ Cache cleared!',
            'cache_error'        => 'An error occurred while clearing cache.',
            'cache_result_title' => 'Cleared caches:',
            'cache_varnish_found'=> '🟢 Varnish Cache found and cleared',
            'cache_varnish_none' => '🔴 Varnish Cache not found (server did not respond)',
            'save'               => 'Save',
            'saving'             => 'Saving...',
            'saved'              => '✅ Saved',
            'scanning'           => 'Scanning...',
            // Status panel
            'status_active'      => 'Active',
            'status_off'         => 'Off',
            'status_no_id'       => 'No ID entered',
            'status_yoast'       => 'Yoast Active',
            // JS strings
            'err_generic'        => 'Unknown error.',
            'err_ajax'           => 'AJAX request failed. Please refresh and try again.',
            'err_save'           => 'Save error',
            'fixed_badge'        => '🔧 Fixed',
            'ok_badge'           => '✅ Good',
            'warn_badge'         => '⚠️ Warning',
            'words'              => 'words',
            'auto_fixed'         => 'auto-fixed',
            'ok_label'           => 'OK',
            'warn_label'         => 'Warning',
            'fixed_label'        => 'Fixed',
            'total_label'        => 'Total',
            'pages'              => 'pages',
            'edit_link'          => 'Edit in WP →',
        );

        return 'en' === $this->lang ? $en : $tr;
    }
}

new DW_Settings_Page();
