<?php
/**
 * SEO Optimizer — tüm sayfa/yazıları tara, sorunları bul, otomatik düzelt, raporla.
 * Manuel düzenleme için de endpoint sağlar.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DW_SEO_Optimizer {

    public function __construct() {
        // Tüm sayfaları tara + düzelt
        add_action( 'wp_ajax_dw_seo_scan', array( $this, 'ajax_scan' ) );
        // Tek bir sayfayı manuel olarak kaydet
        add_action( 'wp_ajax_dw_seo_save_single', array( $this, 'ajax_save_single' ) );
    }

    /* -------------------------------------------------------
       AJAX: Tüm sayfaları tara
    ------------------------------------------------------- */
    public function ajax_scan() {
        check_ajax_referer( 'dw_seo_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Yetki yok.' );
        }

        $auto_fix = isset( $_POST['auto_fix'] ) && '1' === $_POST['auto_fix'];

        $post_types = array( 'page', 'post' );
        $posts = get_posts( array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'post_type',
            'order'          => 'ASC',
        ) );

        $report = array();

        foreach ( $posts as $post ) {
            $issues = array();
            $fixes  = array();

            // ---- 1. Meta Description ----
            $meta_desc = $this->get_stored_meta_desc( $post->ID );
            $has_meta  = ! empty( $meta_desc );

            if ( ! $has_meta ) {
                $generated = $this->generate_meta_desc( $post );
                $issues[]  = 'Meta description eksik';

                if ( $auto_fix && ! empty( $generated ) ) {
                    $this->save_meta_desc( $post->ID, $generated );
                    $fixes[]   = 'Meta description oluşturuldu';
                    $meta_desc = $generated;
                    $has_meta  = true;
                }
            } elseif ( strlen( $meta_desc ) > 160 ) {
                $issues[] = 'Meta description 160 karakterden uzun (' . strlen( $meta_desc ) . ' karakter)';
                if ( $auto_fix ) {
                    $trimmed = substr( $meta_desc, 0, 157 ) . '...';
                    $this->save_meta_desc( $post->ID, $trimmed );
                    $fixes[]   = 'Meta description kısaltıldı';
                    $meta_desc = $trimmed;
                }
            } elseif ( strlen( $meta_desc ) < 50 ) {
                $issues[] = 'Meta description çok kısa (' . strlen( $meta_desc ) . ' karakter)';
            }

            // ---- 2. Başlık uzunluğu ----
            $title     = get_the_title( $post->ID );
            $title_len = strlen( $title );
            if ( $title_len > 60 ) {
                $issues[] = 'Sayfa başlığı 60 karakterden uzun (' . $title_len . ' karakter)';
            } elseif ( $title_len < 20 ) {
                $issues[] = 'Sayfa başlığı çok kısa (' . $title_len . ' karakter)';
            }

            // ---- 3. İçerik uzunluğu ----
            $word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
            if ( $word_count < 300 && 'page' !== $post->post_type ) {
                $issues[] = 'İçerik çok kısa (' . $word_count . ' kelime). 300+ önerilir.';
            }

            // ---- 4. Alt metin kontrolü (featured image) ----
            $thumb_id = get_post_thumbnail_id( $post->ID );
            if ( $thumb_id ) {
                $alt = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
                if ( empty( $alt ) ) {
                    $issues[] = 'Öne çıkan görsel alt metni eksik';
                    if ( $auto_fix ) {
                        update_post_meta( $thumb_id, '_wp_attachment_image_alt', sanitize_text_field( $title ) );
                        $fixes[] = 'Görsel alt metni eklendi: "' . $title . '"';
                    }
                }
            }

            // ---- 5. Slug kontrol ----
            if ( preg_match( '/[A-Z]/', $post->post_name ) ) {
                $issues[] = 'URL (slug) büyük harf içeriyor';
            }
            if ( strlen( $post->post_name ) > 75 ) {
                $issues[] = 'URL (slug) çok uzun (' . strlen( $post->post_name ) . ' karakter)';
            }

            // ---- Durum ----
            if ( empty( $issues ) ) {
                $status = 'ok';
            } elseif ( ! empty( $fixes ) ) {
                $status = 'fixed';
            } else {
                $status = 'warn';
            }

            $report[] = array(
                'id'        => $post->ID,
                'title'     => $title,
                'type'      => $post->post_type,
                'url'       => get_permalink( $post->ID ),
                'edit_url'  => get_edit_post_link( $post->ID ),
                'status'    => $status,
                'issues'    => $issues,
                'fixes'     => $fixes,
                'meta_desc' => $meta_desc,
                'meta_len'  => strlen( $meta_desc ),
                'words'     => $word_count,
            );
        }

        // Özet
        $total  = count( $report );
        $ok     = count( array_filter( $report, fn( $r ) => $r['status'] === 'ok' ) );
        $fixed  = count( array_filter( $report, fn( $r ) => $r['status'] === 'fixed' ) );
        $warn   = count( array_filter( $report, fn( $r ) => $r['status'] === 'warn' ) );

        wp_send_json_success( array(
            'report'  => $report,
            'summary' => array(
                'total'    => $total,
                'ok'       => $ok,
                'fixed'    => $fixed,
                'warn'     => $warn,
                'auto_fix' => $auto_fix,
            ),
        ) );
    }

    /* -------------------------------------------------------
       AJAX: Tek sayfa — kullanıcı manuel kaydet
    ------------------------------------------------------- */
    public function ajax_save_single() {
        check_ajax_referer( 'dw_seo_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Yetki yok.' );
        }

        $post_id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $meta_desc = isset( $_POST['meta_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['meta_desc'] ) ) : '';

        if ( ! $post_id ) {
            wp_send_json_error( 'Geçersiz post ID.' );
        }

        $this->save_meta_desc( $post_id, $meta_desc );

        wp_send_json_success( array(
            'post_id'   => $post_id,
            'meta_desc' => $meta_desc,
            'length'    => strlen( $meta_desc ),
            'message'   => 'Kaydedildi ✅',
        ) );
    }

    /* -------------------------------------------------------
       Yardımcı: Meta desc oku (Yoast → Rank Math → DW → boş)
    ------------------------------------------------------- */
    private function get_stored_meta_desc( $post_id ) {
        // Yoast
        if ( defined( 'WPSEO_VERSION' ) ) {
            $val = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
            if ( ! empty( $val ) ) {
                return $val;
            }
        }
        // Rank Math
        if ( defined( 'RANK_MATH_VERSION' ) ) {
            $val = get_post_meta( $post_id, 'rank_math_description', true );
            if ( ! empty( $val ) ) {
                return $val;
            }
        }
        // DW kendi meta'sı
        return get_post_meta( $post_id, '_dw_meta_description', true );
    }

    /* -------------------------------------------------------
       Yardımcı: Meta desc kaydet
    ------------------------------------------------------- */
    private function save_meta_desc( $post_id, $value ) {
        // Yoast aktifse ona kaydet
        if ( defined( 'WPSEO_VERSION' ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field( $value ) );
            return;
        }
        // Rank Math aktifse
        if ( defined( 'RANK_MATH_VERSION' ) ) {
            update_post_meta( $post_id, 'rank_math_description', sanitize_textarea_field( $value ) );
            return;
        }
        // DW kendi meta'sı
        update_post_meta( $post_id, '_dw_meta_description', sanitize_textarea_field( $value ) );
    }

    /* -------------------------------------------------------
       Yardımcı: Excerpt'ten/içerikten meta desc üret
    ------------------------------------------------------- */
    private function generate_meta_desc( $post ) {
        // 1. Excerpt varsa kullan
        if ( ! empty( $post->post_excerpt ) ) {
            $desc = wp_strip_all_tags( $post->post_excerpt );
            return substr( $desc, 0, 157 ) . ( strlen( $desc ) > 157 ? '...' : '' );
        }
        // 2. İçerikten ilk paragrafı al
        $content = wp_strip_all_tags( $post->post_content );
        $content = preg_replace( '/\s+/', ' ', trim( $content ) );
        if ( strlen( $content ) > 10 ) {
            return substr( $content, 0, 157 ) . '...';
        }
        return '';
    }
}
