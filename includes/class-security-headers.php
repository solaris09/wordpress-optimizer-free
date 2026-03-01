<?php
/**
 * Güvenlik Header'ları.
 *
 * PageSpeed "En İyi Uygulamalar" bölümündeki uyarılar:
 * - CSP (Content Security Policy) yok → Yüksek
 * - HSTS başlığı yok → Yüksek
 * - COOP başlığı yok → Yüksek
 * - X-Frame-Options / frame-ancestors yok → Yüksek
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DW_Security_Headers {

    public function __construct() {
        if ( get_option( 'dw_perf_security_headers' ) ) {
            add_action( 'send_headers', array( $this, 'send_security_headers' ) );
        }

        // .htaccess yönetimi
        register_activation_hook( DW_PERF_PATH . 'dijitalworlds-performance.php', array( $this, 'write_htaccess' ) );
        register_deactivation_hook( DW_PERF_PATH . 'dijitalworlds-performance.php', array( $this, 'remove_htaccess' ) );

        // Ayarlar değişince htaccess'i güncelle
        add_action( 'update_option_dw_perf_security_headers', array( $this, 'maybe_update_htaccess' ), 10, 2 );
        add_action( 'update_option_dw_perf_browser_cache', array( $this, 'maybe_update_htaccess' ), 10, 2 );
    }

    /* -------------------------------------------------------
       PHP Header'ları (dinamik sayfalar için)
    ------------------------------------------------------- */
    public function send_security_headers() {
        if ( is_admin() ) {
            return;
        }

        // HSTS — 1 yıl, subdomainleri dahil et
        header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload' );

        // Clickjacking koruması
        header( 'X-Frame-Options: SAMEORIGIN' );

        // XSS koruması (eski tarayıcılar)
        header( 'X-XSS-Protection: 1; mode=block' );

        // MIME sniffing engelle
        header( 'X-Content-Type-Options: nosniff' );

        // Referrer policy — gizlilik + SEO dengesi
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );

        // COOP — pencere izolasyonu
        header( 'Cross-Origin-Opener-Policy: same-origin-allow-popups' );

        // Permissions Policy — gereksiz API'leri kapat
        header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()' );

        // Content Security Policy — WordPress + Google servisleriyle uyumlu
        $csp = implode( '; ', array(
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://www.google-analytics.com https://accounts.google.com https://static.cloudflareinsights.com https://pagead2.googlesyndication.com https://www.googleadservices.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' data: https://fonts.gstatic.com",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com https://stats.g.doubleclick.net https://cloudflareinsights.com",
            "frame-src https://www.googletagmanager.com https://bid.g.doubleclick.net",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "upgrade-insecure-requests",
        ) );
        header( 'Content-Security-Policy: ' . $csp );
    }

    /* -------------------------------------------------------
       .htaccess yönetimi (statik dosyalar için)
    ------------------------------------------------------- */
    public function write_htaccess() {
        $htaccess_path = ABSPATH . '.htaccess';

        if ( ! file_exists( $htaccess_path ) || ! is_writable( $htaccess_path ) ) {
            return;
        }

        $content    = file_get_contents( $htaccess_path );
        $dw_block   = $this->get_htaccess_block();

        // Zaten eklenmiş mi?
        if ( strpos( $content, '# BEGIN DW Performance' ) !== false ) {
            return;
        }

        // WordPress bloğunun hemen öncesine ekle
        $wp_tag = '# BEGIN WordPress';
        if ( strpos( $content, $wp_tag ) !== false ) {
            $content = str_replace( $wp_tag, $dw_block . "\n" . $wp_tag, $content );
        } else {
            $content = $dw_block . "\n" . $content;
        }

        file_put_contents( $htaccess_path, $content );
    }

    public function remove_htaccess() {
        $htaccess_path = ABSPATH . '.htaccess';

        if ( ! file_exists( $htaccess_path ) || ! is_writable( $htaccess_path ) ) {
            return;
        }

        $content = file_get_contents( $htaccess_path );
        $content = preg_replace( '/# BEGIN DW Performance.*?# END DW Performance\n*/s', '', $content );
        file_put_contents( $htaccess_path, $content );
    }

    public function maybe_update_htaccess( $old, $new ) {
        $this->remove_htaccess();
        if ( get_option( 'dw_perf_security_headers' ) || get_option( 'dw_perf_browser_cache' ) ) {
            $this->write_htaccess();
        }
    }

    private function get_htaccess_block() {
        $cache   = get_option( 'dw_perf_browser_cache' ) ? 1 : 0;
        $headers = get_option( 'dw_perf_security_headers' ) ? 1 : 0;

        $block = "# BEGIN DW Performance\n";
        $block .= "<IfModule mod_headers.c>\n";

        if ( $headers ) {
            $block .= "    Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains; preload\"\n";
            $block .= "    Header always set X-Frame-Options \"SAMEORIGIN\"\n";
            $block .= "    Header always set X-Content-Type-Options \"nosniff\"\n";
            $block .= "    Header always set X-XSS-Protection \"1; mode=block\"\n";
            $block .= "    Header always set Referrer-Policy \"strict-origin-when-cross-origin\"\n";
            $block .= "    Header always set Cross-Origin-Opener-Policy \"same-origin-allow-popups\"\n";
            $block .= "    Header always set Permissions-Policy \"camera=(), microphone=(), geolocation=(), payment=()\"\n";
        }

        if ( $cache ) {
            $block .= "\n";
            $block .= "    # Statik dosyalar için 1 yıl cache\n";
            $block .= "    <FilesMatch \"\\.(css|js|woff2|woff|ttf|otf|eot|svg|webp|avif|png|jpg|jpeg|gif|ico|mp4|webm)$\">\n";
            $block .= "        Header set Cache-Control \"public, max-age=31536000, immutable\"\n";
            $block .= "    </FilesMatch>\n";
            $block .= "\n";
            $block .= "    # HTML için cache'i engelle\n";
            $block .= "    <FilesMatch \"\\.(html|htm|php)$\">\n";
            $block .= "        Header set Cache-Control \"no-cache, no-store, must-revalidate\"\n";
            $block .= "    </FilesMatch>\n";
        }

        $block .= "</IfModule>\n";

        if ( $cache ) {
            $block .= "\n";
            $block .= "<IfModule mod_deflate.c>\n";
            $block .= "    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css application/javascript application/json application/xml\n";
            $block .= "</IfModule>\n";
        }

        $block .= "# END DW Performance\n";

        return $block;
    }
}
