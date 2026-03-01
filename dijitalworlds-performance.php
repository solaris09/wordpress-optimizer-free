<?php
/**
 * Plugin Name: Dijitalworlds Performance
 * Plugin URI:  https://dijitalworlds.com
 * Description: Siteyi hızlandırmak için CSS/JS erteleme, görsel preload, SEO düzeltmeleri.
 * Version:     1.0.0
 * Author:      Cemal Hekimoğlu
 * Author URI:  https://dijitalworlds.com
 * License:     GPL-2.0+
 * Text Domain: dijitalworlds-performance
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DW_PERF_VERSION', '1.0.0' );
define( 'DW_PERF_PATH', plugin_dir_path( __FILE__ ) );
define( 'DW_PERF_URL', plugin_dir_url( __FILE__ ) );

require_once DW_PERF_PATH . 'includes/class-css-js-optimizer.php';
require_once DW_PERF_PATH . 'includes/class-image-optimizer.php';
require_once DW_PERF_PATH . 'includes/class-seo-fixer.php';
require_once DW_PERF_PATH . 'includes/class-seo-optimizer.php';
require_once DW_PERF_PATH . 'admin/settings-page.php';

/**
 * Eklenti aktif edildiğinde varsayılan ayarları kaydet.
 */
function dw_perf_activate() {
    $defaults = array(
        'defer_js'           => 1,
        'defer_css'          => 1,
        'preload_lcp_image'  => 1,
        'lcp_image_url'      => '',
        'fix_lazy_load'      => 1,
        'fix_meta_desc'      => 1,
        'fix_link_text'      => 0,
        'browser_cache'      => 1,
    );
    foreach ( $defaults as $key => $value ) {
        if ( get_option( 'dw_perf_' . $key ) === false ) {
            update_option( 'dw_perf_' . $key, $value );
        }
    }
}
register_activation_hook( __FILE__, 'dw_perf_activate' );

/**
 * Tüm sınıfları başlat.
 */
function dw_perf_init() {
    new DW_CSS_JS_Optimizer();
    new DW_Image_Optimizer();
    new DW_SEO_Fixer();
    new DW_SEO_Optimizer();
}
add_action( 'plugins_loaded', 'dw_perf_init' );
