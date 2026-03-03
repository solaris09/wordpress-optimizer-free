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
            // Tekil sayfa içeriği
            add_filter( 'the_content', array( $this, 'fix_read_more_links' ) );
            // Excerpt tabanlı "Read more" linkleri
            add_filter( 'excerpt_more', array( $this, 'fix_excerpt_more' ) );
            add_filter( 'the_content_more_link', array( $this, 'fix_content_more_link' ), 10, 2 );
            // Özel template/döngü için JS fallback
            add_action( 'wp_footer', array( $this, 'inject_read_more_fix_js' ), 20 );
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
     * the_content filtresi — tekil sayfalar ve standart döngüler için.
     */
    public function fix_read_more_links( $content ) {
        global $post;
        if ( empty( $post ) ) {
            return $content;
        }

        $weak_texts = array(
            'READ MORE', 'read more', 'Read More',
            'Devamını Oku', 'DEVAMINI OKU',
            'More', 'MORE', 'Details', 'DETAILS',
        );

        $title = esc_attr( get_the_title( $post->ID ) );

        foreach ( $weak_texts as $text ) {
            if ( strpos( $content, '>' . $text . '<' ) !== false ) {
                $content = str_replace(
                    '>' . $text . '<',
                    '><span class="screen-reader-text">' . $title . ' — </span>' . $text . '<',
                    $content
                );
            }
        }

        return $content;
    }

    /**
     * Excerpt sonundaki "[...]" daha fazla linkini düzelt.
     */
    public function fix_excerpt_more( $more ) {
        global $post;
        if ( empty( $post ) ) {
            return $more;
        }
        $title = esc_attr( get_the_title( $post->ID ) );
        return ' <a href="' . esc_url( get_permalink( $post->ID ) ) . '">'
             . '<span class="screen-reader-text">' . $title . ' — </span>'
             . 'Read More</a>';
    }

    /**
     * <!--more--> tag'ından gelen "Continue reading" linkini düzelt.
     */
    public function fix_content_more_link( $more_link, $more_link_text ) {
        global $post;
        if ( empty( $post ) ) {
            return $more_link;
        }
        $title = esc_attr( get_the_title( $post->ID ) );
        return str_replace(
            '>' . $more_link_text . '<',
            '><span class="screen-reader-text">' . $title . ' — </span>' . $more_link_text . '<',
            $more_link
        );
    }

    /**
     * Impreza / us_post_list / WPBakery kolon yapıları için JS fallback.
     * Sayfadaki TÜM zayıf READ MORE linklerini bulup en yakın başlıkla zenginleştirir.
     */
    public function inject_read_more_fix_js() {
        ?>
<script id="dw-read-more-fix">
(function(){
    var WEAK = ['read more','devamını oku','devamini oku','more','details','daha fazla','daha fazlasını oku'];

    /** En yakın DOM üst elementinde h1-h6 ara, max depth kadar yukarı çık */
    function findNearestHeading(el, maxDepth){
        var depth = 0, node = el;
        while(node && depth < maxDepth){
            node = node.parentElement;
            if(!node) break;
            // Önce aynı kapsayıcıdaki önceki kardeşlerde ara
            var prev = node.previousElementSibling;
            while(prev){
                var h = prev.matches('h1,h2,h3,h4,h5,h6') ? prev
                      : prev.querySelector('h1,h2,h3,h4,h5,h6');
                if(h) return h;
                prev = prev.previousElementSibling;
            }
            // Kapsayıcı içinde doğrudan h tag
            var direct = node.querySelector('h1,h2,h3,h4,h5,h6,.entry-title,.w-post-elm.post_title a,.w-blog-entry__title,.usg_post_title_1 a');
            if(direct) return direct;
            depth++;
        }
        return null;
    }

    function fix(){
        document.querySelectorAll('a').forEach(function(a){
            if(a.querySelector('.screen-reader-text')) return;
            var text = (a.textContent||'').trim().toLowerCase();
            if(WEAK.indexOf(text) === -1) return;

            var title = '';

            /* 1. Önce blog kart kapsayıcılarına bak (Impreza grid) */
            var card = a.closest('article,.w-grid-item,.l-grid-item,.w-blog-entry,.post,.entry,[class*="post-item"],[class*="blog-item"],[class*="grid-item"]');
            if(card){
                var h = card.querySelector('h1,h2,h3,h4,h5,h6,.entry-title,.w-post-elm.post_title a,.w-blog-entry__title,.usg_post_title_1 a');
                if(h) title = (h.innerText||h.textContent||'').trim();
            }

            /* 2. WPBakery / Impreza kolon yapısı — DOM'u yukarı tara */
            if(!title){
                var heading = findNearestHeading(a, 6);
                if(heading) title = (heading.innerText||heading.textContent||'').trim();
            }

            /* 3. Son çare: href slug → insan-okunabilir metin */
            if(!title){
                var href = a.getAttribute('href')||'';
                var m = href.replace(/\/$/, '').match(/\/([^\/]+)$/);
                if(m) title = m[1].replace(/-/g,' ');
            }

            if(!title) return;

            var span = document.createElement('span');
            span.className = 'screen-reader-text';
            span.textContent = title + ' \u2014 ';
            a.insertBefore(span, a.firstChild);
        });
    }

    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', fix);
    } else {
        fix();
    }
})();
</script>
        <?php
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
