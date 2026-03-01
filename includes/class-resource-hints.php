<?php
/**
 * Resource Hints: Preconnect, DNS-prefetch, Font-display swap, GTM geciktirilmiş yükleme.
 *
 * PageSpeed Insights sorunları:
 * - "Oluşturmayı engelleyen istekler" — Google Fonts 1.5 sn gecikme
 * - "3. taraf kodu yükleme performansını etkiliyor" — GTM 201 ms
 * - "Yazı tipi görüntüleme" — font-display swap eksik
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DW_Resource_Hints {

    public function __construct() {
        if ( get_option( 'dw_perf_preconnect' ) ) {
            // Preconnect + DNS-prefetch en erken önce gelsin
            add_action( 'wp_head', array( $this, 'add_preconnect_tags' ), 1 );
            // WP'nin kendi resource-hints sistemine de ekle
            add_filter( 'wp_resource_hints', array( $this, 'filter_resource_hints' ), 10, 2 );
        }

        if ( get_option( 'dw_perf_font_display_swap' ) ) {
            add_action( 'wp_head', array( $this, 'add_font_display_swap' ), 2 );
        }

        if ( get_option( 'dw_perf_delay_gtm' ) ) {
            // GTM script'ini DOM'dan kaldır, yerine geciktirilmiş loader ekle
            add_action( 'wp_head', array( $this, 'inject_gtm_delay_loader' ), 99 );
            add_filter( 'script_loader_tag', array( $this, 'remove_gtm_from_head' ), 10, 3 );
        }

        if ( get_option( 'dw_perf_delay_gsi' ) ) {
            add_filter( 'script_loader_tag', array( $this, 'delay_gsi_script' ), 10, 3 );
        }
    }

    /* -------------------------------------------------------
       1. Preconnect + DNS-prefetch
    ------------------------------------------------------- */
    public function add_preconnect_tags() {
        // Maksimum 4 preconnect: fazlası PageSpeed uyarısı verir
        $origins = array(
            // Google Fonts — 2 aşamalı preconnect (LCP için kritik)
            array( 'url' => 'https://fonts.googleapis.com', 'crossorigin' => false ),
            array( 'url' => 'https://fonts.gstatic.com',   'crossorigin' => true  ),
            // Google Tag Manager
            array( 'url' => 'https://www.googletagmanager.com', 'crossorigin' => false ),
            // Google Analytics
            array( 'url' => 'https://www.google-analytics.com', 'crossorigin' => false ),
            // Cloudflare Insights kaldırıldı: preconnect >4 uyarısı veriyordu
        );

        foreach ( $origins as $origin ) {
            $co = $origin['crossorigin'] ? ' crossorigin' : '';
            echo '<link rel="preconnect" href="' . esc_url( $origin['url'] ) . '"' . $co . '>' . "\n";
            echo '<link rel="dns-prefetch" href="' . esc_url( $origin['url'] ) . '">' . "\n";
        }
    }

    public function filter_resource_hints( $hints, $relation_type ) {
        if ( 'preconnect' === $relation_type ) {
            $hints[] = array( 'href' => 'https://fonts.googleapis.com' );
            $hints[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' );
        }
        return $hints;
    }

    /* -------------------------------------------------------
       2. Font-display: swap — CSS override ile
    ------------------------------------------------------- */
    public function add_font_display_swap() {
        ?>
        <style id="dw-font-display">
        /* Dijitalworlds Performance: font-display swap */
        @font-face { font-display: swap; }

        /* Google Fonts yüklenmeden önce system font fallback */
        body, .w-btn, .w-iconbox-title, .w-nav-item {
            font-synthesis: weight style;
        }
        </style>
        <?php

        // Google Fonts URL'lerine &display=swap ekle
        add_filter( 'style_loader_src', array( $this, 'add_display_swap_to_fonts' ), 10, 2 );
    }

    public function add_display_swap_to_fonts( $src, $handle ) {
        if ( strpos( $src, 'fonts.googleapis.com' ) !== false ) {
            if ( strpos( $src, 'display=' ) === false ) {
                $src = add_query_arg( 'display', 'swap', $src );
            } else {
                $src = preg_replace( '/display=([^&]+)/', 'display=swap', $src );
            }
        }
        return $src;
    }

    /* -------------------------------------------------------
       3. GTM Geciktirilmiş Yükleme
       Strateji: Kullanıcı ilk etkileşimde (scroll, click, keydown,
       touchstart) veya 4 sn sonra GTM yükle.
       PageSpeed'de 3. taraf engeli = 201 ms kurtarır.
    ------------------------------------------------------- */
    public function remove_gtm_from_head( $tag, $handle, $src ) {
        // GTM script'ini tamamen engelle, kendi loader'ımız yükleyecek
        if (
            strpos( $src, 'googletagmanager.com/gtag/js' ) !== false ||
            strpos( $src, 'googletagmanager.com/gtm.js' ) !== false
        ) {
            return ''; // Orijinal script çıktısını sil
        }
        return $tag;
    }

    public function inject_gtm_delay_loader() {
        $gtm_id = get_option( 'dw_perf_gtm_id', '' );
        if ( empty( $gtm_id ) ) {
            return;
        }

        $gtm_id = esc_js( $gtm_id );
        ?>
        <script id="dw-gtm-delay">
        /* Dijitalworlds Performance: GTM Geciktirilmiş Yükleme */
        (function() {
            var gtmLoaded = false;

            function loadGTM() {
                if (gtmLoaded) return;
                gtmLoaded = true;

                // dataLayer başlat
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({'gtm.start': new Date().getTime(), event: 'gtm.js'});

                // GTM script yükle
                var s = document.createElement('script');
                s.async = true;
                s.src = 'https://www.googletagmanager.com/gtm.js?id=<?php echo esc_js( $gtm_id ); ?>';
                document.head.appendChild(s);
            }

            // İlk kullanıcı etkileşiminde yükle
            var events = ['scroll', 'click', 'keydown', 'touchstart', 'mousemove'];
            events.forEach(function(event) {
                window.addEventListener(event, function handler() {
                    loadGTM();
                    events.forEach(function(e) {
                        window.removeEventListener(e, handler);
                    });
                }, {once: true, passive: true});
            });

            // Maksimum 4 saniye bekle, sonra yükle
            setTimeout(loadGTM, 4000);
        })();
        </script>
        <?php
    }

    /* -------------------------------------------------------
       4. Google Sign-In (GSI) Gecikmeli Yükleme
       /gsi/client = 92 KiB, 71 KiB kullanılmıyor
    ------------------------------------------------------- */
    public function delay_gsi_script( $tag, $handle, $src ) {
        if ( strpos( $src, 'accounts.google.com/gsi/client' ) !== false ) {
            // Lazy yükle: intersectionobserver ile veya etkileşimde
            $tag = str_replace( '<script ', '<script defer ', $tag );
        }
        return $tag;
    }
}
