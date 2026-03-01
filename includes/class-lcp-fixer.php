<?php
/**
 * LCP Fixer — Impreza teması için özelleştirilmiş LCP optimizasyonu.
 *
 * Rapordaki sorun:
 * div.vc_column-inner > div.w-image > div.w-image-h > img.attachment-large
 * → loading="lazy" var, fetchpriority="high" yok
 * → Kaynak yükleme gecikmesi: 620 ms
 *
 * Ayrıca:
 * - AppIcon.png = 870.7 KiB (büyük görsel uyarısı)
 * - 2-572x1024.png = 111.8 KiB (WebP ile 33 KiB tasarruf)
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DW_LCP_Fixer {

    public function __construct() {
        // HTML çıktısını tampon (buffer) ile yakala ve düzelt
        if ( get_option( 'dw_perf_fix_lcp_impreza' ) ) {
            add_action( 'template_redirect', array( $this, 'start_buffer' ) );
        }

        // Admin'de büyük görsel uyarısı
        if ( is_admin() ) {
            add_action( 'admin_notices', array( $this, 'large_image_notice' ) );
        }
    }

    /* -------------------------------------------------------
       Output Buffer — tam HTML üzerinde çalış
    ------------------------------------------------------- */
    public function start_buffer() {
        if ( is_admin() || is_feed() ) {
            return;
        }
        ob_start( array( $this, 'process_html' ) );
    }

    public function process_html( $html ) {
        if ( empty( $html ) ) {
            return $html;
        }

        // 1. Impreza w-image-h içindeki ilk görseli bul ve düzelt
        $html = $this->fix_impreza_lcp_image( $html );

        // 2. AppIcon ve büyük PNG'leri tespit et (log için)
        $this->detect_large_images( $html );

        // 3. Preload LCP görseli için <link> ekle (head'e)
        $html = $this->inject_lcp_preload( $html );

        return $html;
    }

    /* -------------------------------------------------------
       Impreza .w-image-h içindeki ilk img'yi düzelt
    ------------------------------------------------------- */
    private function fix_impreza_lcp_image( $html ) {
        // İlk .w-image-h içindeki img'yi bul
        $pattern = '/(class="[^"]*w-image-h[^"]*"[^>]*>[\s\S]*?)(<img[^>]+>)/iU';

        $found = false;
        $html  = preg_replace_callback(
            $pattern,
            function ( $matches ) use ( &$found ) {
                if ( $found ) {
                    return $matches[0];
                }
                $found = true;

                $wrapper = $matches[1];
                $img     = $matches[2];

                // loading="lazy" kaldır
                $img = preg_replace( '/\s*loading=["\']lazy["\']/i', '', $img );
                $img = preg_replace( '/\s*loading=["\']eager["\']/i', '', $img );

                // decoding="async" kaldır (LCP'de senkron olsun)
                $img = preg_replace( '/\s*decoding=["\'][^"\']*["\']/i', '', $img );

                // fetchpriority="high" ekle
                if ( strpos( $img, 'fetchpriority' ) === false ) {
                    $img = str_replace( '<img', '<img fetchpriority="high" decoding="sync"', $img );
                }

                return $wrapper . $img;
            },
            $html
        );

        return $html;
    }

    /* -------------------------------------------------------
       Head'e <link rel="preload"> ekle (manuel URL boşsa otomatik bul)
    ------------------------------------------------------- */
    private function inject_lcp_preload( $html ) {
        $manual_url = get_option( 'dw_perf_lcp_image_url', '' );

        // Manuel URL ayarlanmışsa zaten class-css-js-optimizer hallediyor
        if ( ! empty( $manual_url ) ) {
            return $html;
        }

        // Otomatik: w-image-h içindeki ilk img src'sini bul
        $src = '';
        if ( preg_match( '/class="[^"]*w-image-h[^"]*"[^>]*>[\s\S]*?<img[^>]+src=["\']([^"\']+)["\'][^>]*>/iU', $html, $m ) ) {
            $src = $m[1];
        }

        if ( empty( $src ) ) {
            return $html;
        }

        // WebP versiyonu varsa onu kullan
        $webp_src = preg_replace( '/\.(png|jpg|jpeg)$/i', '.webp', $src );
        $webp_path = $this->url_to_path( $webp_src );
        if ( $webp_path && file_exists( $webp_path ) ) {
            $src = $webp_src;
        }

        $ext      = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
        $mime_map = array(
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
        );
        $type = isset( $mime_map[ $ext ] ) ? $mime_map[ $ext ] : 'image/webp';

        $preload = '<link rel="preload" fetchpriority="high" as="image" href="' . esc_url( $src ) . '" type="' . esc_attr( $type ) . '">' . "\n";

        // </head> öncesine ekle
        $html = str_replace( '</head>', $preload . '</head>', $html );

        return $html;
    }

    /* -------------------------------------------------------
       Büyük görsel tespiti (admin notice için)
    ------------------------------------------------------- */
    private function detect_large_images( $html ) {
        // Sadece debug için, option'a yaz
        if ( ! preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
            return;
        }

        $large = array();
        foreach ( $matches[1] as $url ) {
            $path = $this->url_to_path( $url );
            if ( $path && file_exists( $path ) ) {
                $size = filesize( $path );
                if ( $size > 200 * 1024 ) { // 200 KB üzeri
                    $large[] = array(
                        'url'  => $url,
                        'size' => round( $size / 1024 ) . ' KiB',
                    );
                }
            }
        }

        if ( ! empty( $large ) ) {
            update_option( 'dw_perf_large_images_found', $large );
        }
    }

    /* -------------------------------------------------------
       Admin Notice: Büyük görseller
    ------------------------------------------------------- */
    public function large_image_notice() {
        $screen = get_current_screen();
        if ( ! $screen || 'settings_page_dw-performance' === $screen->id ) {
            return;
        }

        $large = get_option( 'dw_perf_large_images_found', array() );
        if ( empty( $large ) ) {
            return;
        }

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>🖼️ Dijitalworlds Performance:</strong> Sayfanızda büyük boyutlu görseller tespit edildi. WebP\'ye dönüştürmenizi öneririz:</p>';
        echo '<ul style="margin:4px 0 0 20px;list-style:disc;">';
        foreach ( array_slice( $large, 0, 5 ) as $img ) {
            echo '<li><code>' . esc_html( basename( $img['url'] ) ) . '</code> — ' . esc_html( $img['size'] ) . '</li>';
        }
        echo '</ul>';
        echo '<p><a href="' . esc_url( admin_url( 'options-general.php?page=dw-performance' ) ) . '">DW Performance Ayarları →</a></p>';
        echo '</div>';
    }

    private function url_to_path( $url ) {
        $upload_dir = wp_upload_dir();
        $site_url   = site_url();

        if ( strpos( $url, $upload_dir['baseurl'] ) !== false ) {
            return str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
        }
        if ( strpos( $url, $site_url ) !== false ) {
            return str_replace( $site_url, ABSPATH, $url );
        }
        return false;
    }
}
