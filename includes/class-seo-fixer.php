<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DW_SEO_Fixer {

    public function __construct() {
        if ( get_option( 'dw_perf_fix_meta_desc' ) ) {
            add_action( 'wp_head', array( $this, 'inject_meta_description' ), 5 );
        }

        if ( get_option( 'dw_perf_fix_link_text' ) ) {
            add_filter( 'the_content', array( $this, 'fix_read_more_links' ) );
        }

        // Admin bar'da SEO uyarıları göster
        add_action( 'wp_footer', array( $this, 'show_seo_warnings' ) );
    }

    /**
     * Meta description yoksa otomatik oluştur.
     * Yoast veya Rank Math aktifse müdahale etme.
     */
    public function inject_meta_description() {
        // Yoast veya Rank Math varsa çık
        if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
            return;
        }

        global $post;

        $description = '';

        if ( is_singular() && ! empty( $post ) ) {
            // Önce excerpt'e bak
            $description = get_the_excerpt( $post->ID );
            if ( empty( $description ) ) {
                $description = wp_trim_words( strip_tags( $post->post_content ), 25, '' );
            }
        } elseif ( is_front_page() || is_home() ) {
            $description = get_bloginfo( 'description' );
            if ( empty( $description ) ) {
                $description = 'Dijitalworlds - Yenilikçi iOS uygulamaları ve teknoloji çözümleri.';
            }
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $term        = get_queried_object();
            $description = ! empty( $term->description ) ? $term->description : $term->name;
        }

        if ( empty( $description ) ) {
            return;
        }

        $description = wp_strip_all_tags( $description );
        $description = substr( $description, 0, 160 );

        echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";

        // Open Graph description
        echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
    }

    /**
     * "READ MORE" gibi zayıf link metinlerini post başlığıyla zenginleştir.
     */
    public function fix_read_more_links( $content ) {
        global $post;

        $weak_texts = array(
            'READ MORE',
            'read more',
            'Read More',
            'Devamını Oku',
            'DEVAMINI OKU',
            'More',
            'MORE',
            'Details',
            'DETAILS',
        );

        foreach ( $weak_texts as $text ) {
            if ( strpos( $content, '>' . $text . '<' ) !== false && ! empty( $post ) ) {
                $title   = esc_attr( get_the_title( $post->ID ) );
                $content = str_replace(
                    '>' . $text . '<',
                    '><span class="screen-reader-text">' . $title . ' - </span>' . $text . '<',
                    $content
                );
            }
        }

        return $content;
    }

    /**
     * Sayfa footer'ında (sadece admin için) SEO sorunlarını listele.
     */
    public function show_seo_warnings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $warnings = array();

        // Meta description kontrolü
        if ( ! defined( 'WPSEO_VERSION' ) && ! defined( 'RANK_MATH_VERSION' ) ) {
            global $post;
            if ( is_singular() && ! empty( $post ) && empty( get_the_excerpt( $post->ID ) ) ) {
                $warnings[] = '⚠️ Bu sayfaya excerpt (meta description) eklenmemiş.';
            }
        }

        if ( empty( $warnings ) ) {
            return;
        }

        echo '<div id="dw-seo-warnings" style="position:fixed;bottom:40px;right:20px;background:#1d1d1d;color:#fff;padding:12px 16px;border-radius:8px;font-size:13px;z-index:9999;max-width:320px;box-shadow:0 4px 12px rgba(0,0,0,.4);">';
        echo '<strong style="display:block;margin-bottom:6px;color:#f0c040;">🔍 Dijitalworlds SEO</strong>';
        foreach ( $warnings as $w ) {
            echo '<div style="margin-top:4px;">' . esc_html( $w ) . '</div>';
        }
        echo '</div>';
    }
}
