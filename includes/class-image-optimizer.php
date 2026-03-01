<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DW_Image_Optimizer {

    public function __construct() {
        if ( get_option( 'dw_perf_fix_lazy_load' ) ) {
            add_filter( 'the_content', array( $this, 'fix_lazy_load' ) );
            add_filter( 'post_thumbnail_html', array( $this, 'fix_lazy_load' ) );
            add_filter( 'wp_get_attachment_image_attributes', array( $this, 'fix_attachment_lazy_load' ), 10, 3 );
        }

        // WebP desteği kontrolü
        add_filter( 'wp_get_attachment_image_src', array( $this, 'maybe_serve_webp' ), 10, 4 );
    }

    /**
     * İçerikteki görsellerde:
     * - LCP olabilecek ilk görselde loading="lazy" kaldır, fetchpriority="high" ekle
     * - Diğerlerine loading="lazy" ekle
     */
    public function fix_lazy_load( $content ) {
        if ( empty( $content ) ) {
            return $content;
        }

        $first = true;

        $content = preg_replace_callback(
            '/<img([^>]+)>/i',
            function ( $matches ) use ( &$first ) {
                $img   = $matches[0];
                $attrs = $matches[1];

                if ( $first ) {
                    // İlk görsel = potansiyel LCP: lazy'i kaldır, fetchpriority ekle
                    $img   = preg_replace( '/\s*loading=["\']lazy["\']/i', '', $img );
                    $img   = preg_replace( '/\s*loading=["\']eager["\']/i', '', $img );

                    if ( strpos( $img, 'fetchpriority' ) === false ) {
                        $img = str_replace( '<img', '<img fetchpriority="high"', $img );
                    }
                    $first = false;
                } else {
                    // Diğer görseller: lazy ekle
                    if ( strpos( $attrs, 'loading=' ) === false ) {
                        $img = str_replace( '<img', '<img loading="lazy"', $img );
                    }
                }

                return $img;
            },
            $content
        );

        return $content;
    }

    /**
     * wp_get_attachment_image ile eklenen görselleri düzelt.
     */
    public function fix_attachment_lazy_load( $attr, $attachment, $size ) {
        // Featured image gibi yapılarda eager bırak
        if ( isset( $attr['loading'] ) && $attr['loading'] === 'lazy' ) {
            // Hero/banner sınıfı varsa lazy kaldır
            if ( isset( $attr['class'] ) && strpos( $attr['class'], 'hero' ) !== false ) {
                unset( $attr['loading'] );
                $attr['fetchpriority'] = 'high';
            }
        }
        return $attr;
    }

    /**
     * Sunucuda .webp versiyonu varsa otomatik olarak WebP sun.
     */
    public function maybe_serve_webp( $image, $attachment_id, $size, $icon ) {
        if ( ! $image ) {
            return $image;
        }

        // WebP desteği var mı?
        $accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';
        if ( strpos( $accept, 'image/webp' ) === false ) {
            return $image;
        }

        $url  = $image[0];
        $path = $this->url_to_path( $url );

        if ( ! $path ) {
            return $image;
        }

        $webp_path = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $path );
        $webp_url  = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $url );

        if ( file_exists( $webp_path ) ) {
            $image[0] = $webp_url;
        }

        return $image;
    }

    /**
     * URL'yi sunucu yoluna çevir.
     */
    private function url_to_path( $url ) {
        $upload_dir = wp_upload_dir();
        $base_url   = $upload_dir['baseurl'];
        $base_path  = $upload_dir['basedir'];

        if ( strpos( $url, $base_url ) === false ) {
            return false;
        }

        return str_replace( $base_url, $base_path, $url );
    }
}
