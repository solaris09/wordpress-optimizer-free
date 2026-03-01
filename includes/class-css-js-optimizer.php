<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DW_CSS_JS_Optimizer {

    public function __construct() {
        if ( get_option( 'dw_perf_defer_js' ) ) {
            add_filter( 'script_loader_tag', array( $this, 'defer_js' ), 10, 3 );
        }

        if ( get_option( 'dw_perf_defer_css' ) ) {
            add_filter( 'style_loader_tag', array( $this, 'defer_css' ), 10, 4 );
        }

        if ( get_option( 'dw_perf_preload_lcp_image' ) ) {
            add_action( 'wp_head', array( $this, 'preload_lcp_image' ), 1 );
        }

        if ( get_option( 'dw_perf_browser_cache' ) ) {
            add_action( 'send_headers', array( $this, 'add_cache_headers' ) );
        }
    }

    /**
     * jQuery ve kritik script'ler hariç diğerlerine defer ekle.
     */
    public function defer_js( $tag, $handle, $src ) {
        // Admin, login sayfası ve kritik scriptleri atla
        if ( is_admin() ) {
            return $tag;
        }

        $skip = array( 'jquery', 'jquery-core', 'jquery-migrate' );
        if ( in_array( $handle, $skip, true ) ) {
            return $tag;
        }

        // Zaten defer veya async varsa dokunma
        if ( strpos( $tag, 'defer' ) !== false || strpos( $tag, 'async' ) !== false ) {
            return $tag;
        }

        return str_replace( '<script ', '<script defer ', $tag );
    }

    /**
     * Render-blocking CSS'i async ile yükle (print media trick).
     */
    public function defer_css( $tag, $handle, $href, $media ) {
        if ( is_admin() ) {
            return $tag;
        }

        // Kritik tema CSS'ini atla (impreza ve kritik olanlar)
        $skip = array( 'impreza-main', 'impreza-style', 'wp-block-library' );
        if ( in_array( $handle, $skip, true ) ) {
            return $tag;
        }

        // Async CSS yükleme tekniği
        $async_tag = '<link rel="preload" href="' . esc_url( $href ) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
        $async_tag .= '<noscript>' . $tag . '</noscript>';

        return $async_tag;
    }

    /**
     * LCP görseli için <head> içine preload etiketi ekle.
     */
    public function preload_lcp_image() {
        $lcp_url = get_option( 'dw_perf_lcp_image_url', '' );
        if ( empty( $lcp_url ) ) {
            return;
        }

        $ext      = strtolower( pathinfo( $lcp_url, PATHINFO_EXTENSION ) );
        $mime_map = array(
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
        );
        $type = isset( $mime_map[ $ext ] ) ? $mime_map[ $ext ] : 'image/webp';

        echo '<link rel="preload" fetchpriority="high" as="image" href="' . esc_url( $lcp_url ) . '" type="' . esc_attr( $type ) . '">' . "\n";
    }

    /**
     * Tarayıcı önbelleği için HTTP header'ları ekle.
     */
    public function add_cache_headers() {
        if ( is_admin() ) {
            return;
        }

        header( 'Cache-Control: public, max-age=31536000' );
        header( 'Vary: Accept-Encoding' );
    }
}
