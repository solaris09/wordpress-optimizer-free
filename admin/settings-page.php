<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DW_Settings_Page {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_filter(
            'plugin_action_links_' . plugin_basename( DW_PERF_PATH . 'dijitalworlds-performance.php' ),
            array( $this, 'add_action_links' )
        );
    }

    /**
     * Eklentiler listesinin hemen altına "Ayarlar" linki ekle.
     */
    public function add_action_links( $links ) {
        $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=dw-performance' ) ) . '">' . __( 'Ayarlar', 'dijitalworlds-performance' ) . '</a>';
        array_unshift( $links, $settings_link );
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
            'dw_perf_defer_js',
            'dw_perf_defer_css',
            'dw_perf_preload_lcp_image',
            'dw_perf_lcp_image_url',
            'dw_perf_fix_lazy_load',
            'dw_perf_fix_meta_desc',
            'dw_perf_fix_link_text',
            'dw_perf_browser_cache',
        );
        foreach ( $options as $option ) {
            register_setting( 'dw_perf_group', $option );
        }
    }

    public function enqueue_assets( $hook ) {
        if ( 'settings_page_dw-performance' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'dw-perf-admin',
            DW_PERF_URL . 'admin/admin.css',
            array(),
            DW_PERF_VERSION
        );

        wp_enqueue_script(
            'dw-perf-admin',
            DW_PERF_URL . 'admin/admin.js',
            array( 'jquery' ),
            DW_PERF_VERSION,
            true
        );

        wp_localize_script( 'dw-perf-admin', 'dwPerf', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'dw_seo_nonce' ),
        ) );
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $saved = false;
        if ( isset( $_POST['dw_perf_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dw_perf_nonce'] ) ), 'dw_perf_save' ) ) {
            $this->save_settings();
            $saved = true;
        }
        ?>
        <div class="wrap dw-wrap">

            <div class="dw-header">
                <h1>🚀 Dijitalworlds Performance</h1>
                <p class="dw-subtitle">dijitalworlds.com için hız & SEO optimizasyonu — v<?php echo esc_html( DW_PERF_VERSION ); ?></p>
            </div>

            <?php if ( $saved ) : ?>
                <div class="notice notice-success dw-notice"><p>✅ Ayarlar kaydedildi!</p></div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field( 'dw_perf_save', 'dw_perf_nonce' ); ?>

                <div class="dw-grid">

                    <!-- PERFORMANS -->
                    <div class="dw-card">
                        <div class="dw-card-header">⚡ Performans</div>
                        <div class="dw-card-body">

                            <label class="dw-toggle">
                                <input type="checkbox" name="dw_perf_defer_js" value="1" <?php checked( get_option( 'dw_perf_defer_js' ), 1 ); ?>>
                                <span class="dw-slider"></span>
                                <span class="dw-label">JavaScript Erteleme (defer)</span>
                            </label>
                            <p class="dw-hint">jQuery hariç JS dosyalarını defer ile yükler. Render-blocking etkisini kaldırır.</p>

                            <label class="dw-toggle">
                                <input type="checkbox" name="dw_perf_defer_css" value="1" <?php checked( get_option( 'dw_perf_defer_css' ), 1 ); ?>>
                                <span class="dw-slider"></span>
                                <span class="dw-label">CSS Async Yükleme</span>
                            </label>
                            <p class="dw-hint">Kritik CSS hariç diğerlerini async yükler. (Impreza ana CSS korunur)</p>

                            <label class="dw-toggle">
                                <input type="checkbox" name="dw_perf_browser_cache" value="1" <?php checked( get_option( 'dw_perf_browser_cache' ), 1 ); ?>>
                                <span class="dw-slider"></span>
                                <span class="dw-label">Tarayıcı Önbelleği (1 Yıl)</span>
                            </label>
                            <p class="dw-hint">Statik dosyalar için Cache-Control header'ı ekler.</p>

                        </div>
                    </div>

                    <!-- LCP -->
                    <div class="dw-card">
                        <div class="dw-card-header">🖼️ LCP Görsel Optimizasyonu</div>
                        <div class="dw-card-body">

                            <label class="dw-toggle">
                                <input type="checkbox" name="dw_perf_preload_lcp_image" value="1" <?php checked( get_option( 'dw_perf_preload_lcp_image' ), 1 ); ?>>
                                <span class="dw-slider"></span>
                                <span class="dw-label">LCP Görselini Preload Et</span>
                            </label>
                            <p class="dw-hint">Ana görselinizi önceden yükler. LCP süresini %40–60 düşürür.</p>

                            <div class="dw-input-group">
                                <label for="dw_lcp_url">LCP Görsel URL'si</label>
                                <input
                                    type="url"
                                    id="dw_lcp_url"
                                    name="dw_perf_lcp_image_url"
                                    value="<?php echo esc_attr( get_option( 'dw_perf_lcp_image_url', '' ) ); ?>"
                                    placeholder="https://dijitalworlds.com/wp-content/uploads/hero.webp"
                                >
                                <small>Ana sayfanızdaki en büyük görselin URL'sini yapıştırın. (Tercihen .webp)</small>
                            </div>

                            <label class="dw-toggle">
                                <input type="checkbox" name="dw_perf_fix_lazy_load" value="1" <?php checked( get_option( 'dw_perf_fix_lazy_load' ), 1 ); ?>>
                                <span class="dw-slider"></span>
                                <span class="dw-label">Lazy Load Düzeltmesi</span>
                            </label>
                            <p class="dw-hint">İlk görselden lazy loading kaldırır, altındakilere ekler.</p>

                        </div>
                    </div>

                    <!-- SEO AYARLARI -->
                    <div class="dw-card">
                        <div class="dw-card-header">🔍 SEO Otomatik Düzeltmeler</div>
                        <div class="dw-card-body">

                            <label class="dw-toggle">
                                <input type="checkbox" name="dw_perf_fix_meta_desc" value="1" <?php checked( get_option( 'dw_perf_fix_meta_desc' ), 1 ); ?>>
                                <span class="dw-slider"></span>
                                <span class="dw-label">Otomatik Meta Description</span>
                            </label>
                            <p class="dw-hint">Yoast/Rank Math yoksa excerpt'ten meta description oluşturur. Skor: +5 puan.</p>

                            <label class="dw-toggle">
                                <input type="checkbox" name="dw_perf_fix_link_text" value="1" <?php checked( get_option( 'dw_perf_fix_link_text' ), 1 ); ?>>
                                <span class="dw-slider"></span>
                                <span class="dw-label">"Read More" Link Zenginleştirme</span>
                            </label>
                            <p class="dw-hint">"READ MORE" butonlarına screen-reader metni ekler.</p>

                        </div>
                    </div>

                </div><!-- .dw-grid -->

                <!-- SEO OPTİMİZER -->
                <div class="dw-card dw-seo-optimizer-card">
                    <div class="dw-card-header">🤖 SEO Optimizer — Tüm Sayfaları Tara & Düzelt</div>
                    <div class="dw-card-body">

                        <p style="margin-top:0;color:#475569;">
                            Sitenizdeki tüm sayfa ve yazıları tarar; eksik meta description, kısa başlık, görsel alt metni gibi sorunları bulur.
                            <strong>Otomatik Düzelt</strong> seçeneğiyle sorunları anında çözer. Sonuçları tabloda görebilir ve her sayfanın meta description'ını manuel olarak düzenleyip kaydedebilirsiniz.
                        </p>

                        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                            <label class="dw-toggle" style="margin-bottom:0;">
                                <input type="checkbox" id="dw-seo-auto-fix" value="1" checked>
                                <span class="dw-slider"></span>
                                <span class="dw-label">Otomatik Düzelt (tarama sırasında)</span>
                            </label>

                            <button type="button" id="dw-seo-run-btn" class="dw-seo-run-btn">
                                🔍 SEO Tara & Optimize Et
                            </button>
                        </div>

                        <div id="dw-seo-report" style="display:none;margin-top:20px;">

                            <div id="dw-seo-summary"></div>

                            <h3 style="margin-top:16px;">📋 Sayfa Raporu</h3>
                            <p style="font-size:12px;color:#94a3b8;margin-top:-8px;">Meta description sütunundaki metinleri düzenleyip <strong>Kaydet</strong> butonuna basarak manuel değişiklik yapabilirsiniz.</p>

                            <div style="overflow-x:auto;">
                                <table class="dw-report-table">
                                    <thead>
                                        <tr>
                                            <th>Sayfa / Yazı</th>
                                            <th>Durum & Sorunlar</th>
                                            <th>Meta Description (Düzenlenebilir)</th>
                                            <th>Hızlı Erişim</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dw-seo-table-body"></tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- DURUM -->
                <div class="dw-card dw-status-card">
                    <div class="dw-card-header">📊 Aktif Ayarlar</div>
                    <div class="dw-card-body dw-status-grid">
                        <?php $this->render_status(); ?>
                    </div>
                </div>

                <div class="dw-actions">
                    <button type="submit" class="dw-btn">💾 Ayarları Kaydet</button>
                </div>

            </form>
        </div><!-- .dw-wrap -->
        <?php
    }

    private function save_settings() {
        $checkboxes = array(
            'dw_perf_defer_js',
            'dw_perf_defer_css',
            'dw_perf_preload_lcp_image',
            'dw_perf_fix_lazy_load',
            'dw_perf_fix_meta_desc',
            'dw_perf_fix_link_text',
            'dw_perf_browser_cache',
        );
        foreach ( $checkboxes as $key ) {
            update_option( $key, isset( $_POST[ $key ] ) ? 1 : 0 );
        }

        if ( isset( $_POST['dw_perf_lcp_image_url'] ) ) {
            update_option( 'dw_perf_lcp_image_url', esc_url_raw( wp_unslash( $_POST['dw_perf_lcp_image_url'] ) ) );
        }
    }

    private function render_status() {
        $items = array(
            array( 'label' => 'JS Defer',    'ok' => (bool) get_option( 'dw_perf_defer_js' ),           'val' => get_option( 'dw_perf_defer_js' ) ? 'Aktif' : 'Kapalı' ),
            array( 'label' => 'CSS Async',   'ok' => (bool) get_option( 'dw_perf_defer_css' ),          'val' => get_option( 'dw_perf_defer_css' ) ? 'Aktif' : 'Kapalı' ),
            array( 'label' => 'LCP Preload', 'ok' => ! empty( get_option( 'dw_perf_lcp_image_url' ) ), 'val' => get_option( 'dw_perf_lcp_image_url' ) ? 'URL Ayarlı ✔' : 'URL Girilmedi' ),
            array( 'label' => 'Lazy Load',   'ok' => (bool) get_option( 'dw_perf_fix_lazy_load' ),      'val' => get_option( 'dw_perf_fix_lazy_load' ) ? 'Aktif' : 'Kapalı' ),
            array( 'label' => 'Meta Desc',   'ok' => defined( 'WPSEO_VERSION' ) || (bool) get_option( 'dw_perf_fix_meta_desc' ), 'val' => defined( 'WPSEO_VERSION' ) ? 'Yoast Aktif' : ( get_option( 'dw_perf_fix_meta_desc' ) ? 'Aktif' : 'Kapalı' ) ),
            array( 'label' => 'Önbellek',    'ok' => (bool) get_option( 'dw_perf_browser_cache' ),      'val' => get_option( 'dw_perf_browser_cache' ) ? 'Aktif' : 'Kapalı' ),
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
}

new DW_Settings_Page();
