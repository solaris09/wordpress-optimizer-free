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

    public function add_action_links( $links ) {
        $url = admin_url( 'options-general.php?page=dw-performance' );
        array_unshift( $links, '<a href="' . esc_url( $url ) . '">Ayarlar</a>' );
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

                <!-- ROW 1: Performans + LCP + SEO -->
                <div class="dw-grid">

                    <!-- PERFORMANS -->
                    <div class="dw-card">
                        <div class="dw-card-header">⚡ Performans</div>
                        <div class="dw-card-body">

                            <?php $this->toggle( 'dw_perf_defer_js', 'JavaScript Erteleme (defer)', 'jQuery hariç tüm scriptlere defer ekler. Render-blocking kaldırır.' ); ?>
                            <?php $this->toggle( 'dw_perf_defer_css', 'CSS Async Yükleme', 'Kritik CSS hariç stil dosyalarını async yükler.' ); ?>
                            <?php $this->toggle( 'dw_perf_browser_cache', 'Tarayıcı Önbelleği', 'Cache-Control + .htaccess ile 1 yıl cache ekler.' ); ?>

                        </div>
                    </div>

                    <!-- LCP -->
                    <div class="dw-card">
                        <div class="dw-card-header">🖼️ LCP Görsel Optimizasyonu</div>
                        <div class="dw-card-body">

                            <?php $this->toggle( 'dw_perf_preload_lcp_image', 'LCP Görselini Preload Et', 'Ana görsel için &lt;link rel="preload"&gt; ekler.' ); ?>

                            <div class="dw-input-group">
                                <label for="dw_lcp_url">LCP Görsel URL'si (boş bırakırsan otomatik bulur)</label>
                                <input type="url" id="dw_lcp_url" name="dw_perf_lcp_image_url"
                                    value="<?php echo esc_attr( get_option( 'dw_perf_lcp_image_url', '' ) ); ?>"
                                    placeholder="https://dijitalworlds.com/wp-content/uploads/2026/01/1-kopyasi-473x1024.png">
                                <small>Rapordaki LCP elementi: <code>1-kopyasi-473x1024.png</code> — WebP versiyonunu gir</small>
                            </div>

                            <?php $this->toggle( 'dw_perf_fix_lcp_impreza', 'Impreza Tema LCP Düzeltici', 'w-image-h içindeki ilk görselden lazy kaldırır, fetchpriority="high" ekler. 620 ms gecikmeyi çözer.' ); ?>
                            <?php $this->toggle( 'dw_perf_fix_lazy_load', 'Genel Lazy Load Yönetimi', 'İçerikteki ilk görselde lazy kaldırır, altındakilere ekler.' ); ?>

                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="dw-card">
                        <div class="dw-card-header">🔍 SEO Düzeltmeleri</div>
                        <div class="dw-card-body">

                            <?php $this->toggle( 'dw_perf_fix_meta_desc', 'Otomatik Meta Description', 'Yoast/Rank Math yoksa excerpt\'ten üretir. SEO +5 puan.' ); ?>
                            <?php $this->toggle( 'dw_perf_fix_link_text', '"Read More" Link Zenginleştirme', 'READ MORE butonlarına screen-reader metni ekler.' ); ?>

                        </div>
                    </div>

                </div><!-- .dw-grid -->

                <!-- ROW 2: Resource Hints + Güvenlik -->
                <div class="dw-grid">

                    <!-- RESOURCE HINTS -->
                    <div class="dw-card">
                        <div class="dw-card-header">🔗 Resource Hints & Font Optimizasyonu</div>
                        <div class="dw-card-body">

                            <?php $this->toggle( 'dw_perf_preconnect', 'Preconnect + DNS-prefetch', 'Google Fonts, GTM, Analytics, Cloudflare için preconnect. Google Fonts gecikmesini ~1.5 sn düşürür.' ); ?>
                            <?php $this->toggle( 'dw_perf_font_display_swap', 'Font-Display: Swap', 'Google Fonts URL\'lerine display=swap ekler. Yazı tipi yüklenirken metin görünür kalır. Layout shift\'i önler.' ); ?>

                        </div>
                    </div>

                    <!-- GTM GECİKME -->
                    <div class="dw-card">
                        <div class="dw-card-header">📊 3. Taraf Kod Optimizasyonu</div>
                        <div class="dw-card-body">

                            <?php $this->toggle( 'dw_perf_delay_gtm', 'GTM Geciktirilmiş Yükleme', 'GTM\'yi kullanıcı etkileşimine (scroll, click) kadar bekletir. Ana thread\'den 201 ms + 50 KiB kurtarır.' ); ?>

                            <div class="dw-input-group">
                                <label for="dw_gtm_id">GTM Container ID</label>
                                <input type="text" id="dw_gtm_id" name="dw_perf_gtm_id"
                                    value="<?php echo esc_attr( get_option( 'dw_perf_gtm_id', '' ) ); ?>"
                                    placeholder="GTM-XXXXXXX">
                                <small>Raporda görülen GTM ID: <code>GT-PBNXLLRL</code> — buraya gir</small>
                            </div>

                            <?php $this->toggle( 'dw_perf_delay_gsi', 'Google Sign-In (GSI) Erteleme', '/gsi/client = 92 KiB. Kullanılmayan 71 KiB JS tasarrufu.' ); ?>

                        </div>
                    </div>

                    <!-- GÜVENLİK -->
                    <div class="dw-card">
                        <div class="dw-card-header">🔒 Güvenlik Başlıkları</div>
                        <div class="dw-card-body">

                            <?php $this->toggle( 'dw_perf_security_headers', 'Güvenlik Header\'ları Ekle', 'HSTS, X-Frame-Options, CSP, COOP, Referrer-Policy, Permissions-Policy. PageSpeed "En İyi Uygulamalar" uyarılarını giderir.' ); ?>

                            <div class="dw-info-box">
                                <strong>Eklenen header'lar:</strong>
                                <ul>
                                    <li><code>Strict-Transport-Security</code> — HSTS, 1 yıl</li>
                                    <li><code>X-Frame-Options: SAMEORIGIN</code> — Clickjacking</li>
                                    <li><code>Content-Security-Policy</code> — XSS koruması</li>
                                    <li><code>Cross-Origin-Opener-Policy</code> — COOP</li>
                                    <li><code>Referrer-Policy</code> — Gizlilik</li>
                                    <li><code>Permissions-Policy</code> — API kısıtlaması</li>
                                </ul>
                                <small>⚠️ CSP, mevcut eklentilerle çakışabilir. Sorun çıkarsa kapatın.</small>
                            </div>

                        </div>
                    </div>

                </div><!-- .dw-grid row 2 -->

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
                                <span class="dw-label">Otomatik Düzelt</span>
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
                                            <th>Erişim</th>
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
                    <div class="dw-card-header">📊 Aktif Ayarlar</div>
                    <div class="dw-card-body dw-status-grid">
                        <?php $this->render_status(); ?>
                    </div>
                </div>

                <div class="dw-actions">
                    <button type="submit" class="dw-btn">💾 Ayarları Kaydet</button>
                    <span style="font-size:12px;color:#94a3b8;">v<?php echo esc_html( DW_PERF_VERSION ); ?></span>
                </div>

            </form>
        </div>
        <?php
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
        $gtm_id = get_option( 'dw_perf_gtm_id', '' );
        $items  = array(
            array( 'label' => 'JS Defer',       'ok' => (bool) get_option( 'dw_perf_defer_js' ),          'val' => get_option( 'dw_perf_defer_js' ) ? 'Aktif' : 'Kapalı' ),
            array( 'label' => 'CSS Async',      'ok' => (bool) get_option( 'dw_perf_defer_css' ),         'val' => get_option( 'dw_perf_defer_css' ) ? 'Aktif' : 'Kapalı' ),
            array( 'label' => 'LCP Preload',    'ok' => (bool) get_option( 'dw_perf_preload_lcp_image' ), 'val' => get_option( 'dw_perf_preload_lcp_image' ) ? 'Aktif' : 'Kapalı' ),
            array( 'label' => 'LCP Impreza',    'ok' => (bool) get_option( 'dw_perf_fix_lcp_impreza' ),   'val' => get_option( 'dw_perf_fix_lcp_impreza' ) ? 'Aktif' : 'Kapalı' ),
            array( 'label' => 'Preconnect',     'ok' => (bool) get_option( 'dw_perf_preconnect' ),        'val' => get_option( 'dw_perf_preconnect' ) ? 'Aktif' : 'Kapalı' ),
            array( 'label' => 'Font Swap',      'ok' => (bool) get_option( 'dw_perf_font_display_swap' ), 'val' => get_option( 'dw_perf_font_display_swap' ) ? 'Aktif' : 'Kapalı' ),
            array( 'label' => 'GTM Gecikme',    'ok' => ! empty( $gtm_id ) && get_option( 'dw_perf_delay_gtm' ), 'val' => ( ! empty( $gtm_id ) && get_option( 'dw_perf_delay_gtm' ) ) ? $gtm_id : ( get_option( 'dw_perf_delay_gtm' ) ? 'ID Girilmedi' : 'Kapalı' ) ),
            array( 'label' => 'Güvenlik',       'ok' => (bool) get_option( 'dw_perf_security_headers' ),  'val' => get_option( 'dw_perf_security_headers' ) ? 'Aktif' : 'Kapalı' ),
            array( 'label' => 'Önbellek',       'ok' => (bool) get_option( 'dw_perf_browser_cache' ),     'val' => get_option( 'dw_perf_browser_cache' ) ? 'Aktif' : 'Kapalı' ),
            array( 'label' => 'Meta Desc',      'ok' => defined( 'WPSEO_VERSION' ) || (bool) get_option( 'dw_perf_fix_meta_desc' ), 'val' => defined( 'WPSEO_VERSION' ) ? 'Yoast Aktif' : ( get_option( 'dw_perf_fix_meta_desc' ) ? 'Aktif' : 'Kapalı' ) ),
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
