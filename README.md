# Dijitalworlds Performance — WordPress Plugin

> **Dijitalworlds.com** için özel geliştirilmiş, hız & SEO optimizasyon eklentisi.
> Custom WordPress performance & SEO optimization plugin for dijitalworlds.com.

## 👤 Yazar / Author

**Cemal Hekimoğlu**
- Web: [dijitalworlds.com](https://dijitalworlds.com)
- GitHub: [@solaris09](https://github.com/solaris09)

## 📄 Lisans / License

Bu proje **Apache License 2.0** lisansı ile yayınlanmıştır.
This project is licensed under the **Apache License 2.0**.

> Kodu özgürce kullanabilir, değiştirebilir ve dağıtabilirsiniz.
> Tek şart: Orijinal yazar adını (Cemal Hekimoğlu) ve lisans bilgisini koruyun.
>
> You are free to use, modify, and distribute this code.
> Only requirement: Keep the original author name (Cemal Hekimoğlu) and license notice.

[![License](https://img.shields.io/badge/License-Apache_2.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)

---

## 🇹🇷 Türkçe

### Ne Yapar?

Bu eklenti, PageSpeed Insights raporundaki sorunları otomatik olarak çözmek için geliştirilmiştir:

| Özellik | Açıklama |
|---|---|
| ⚡ JS Defer | jQuery hariç tüm script'lere `defer` ekler, render-blocking etkisini kaldırır |
| ⚡ CSS Async | Kritik CSS hariç stil dosyalarını async olarak yükler |
| 🖼️ LCP Preload | Ana sayfa hero görselinize `fetchpriority="high"` + `<link rel="preload">` ekler |
| 🖼️ Lazy Load Düzeltme | İlk görselden lazy'i kaldırır, alt görsellere otomatik ekler |
| 🔍 Meta Description | Yoast/Rank Math yoksa excerpt'ten otomatik meta description oluşturur |
| 🔍 Read More Zenginleştirme | "READ MORE" gibi zayıf link metinlerini SEO'ya uygun hale getirir |
| 🤖 SEO Optimizer | Tüm sayfa/yazıları tarar, sorunları raporlar, otomatik düzeltir |
| 💾 Tarayıcı Önbelleği | `Cache-Control: max-age=31536000` header'ı ekler |

### SEO Optimizer Nasıl Çalışır?

1. WordPress Admin → **Ayarlar → DW Performance** sayfasına git
2. **SEO Optimizer** kartında **"Otomatik Düzelt"** seçeneğini aç/kapat
3. **"SEO Tara & Optimize Et"** butonuna bas
4. Tüm sayfa ve yazılar taranır, sonuçlar tabloda listelenir
5. Her satırda **meta description'ı düzenleyip** "Kaydet" butonuyla kaydedebilirsin
6. **"WP Düzenle →"** linki ile doğrudan WP editörüne geçebilirsin

### Kurulum

1. `dijitalworlds-performance` klasörünü `/wp-content/plugins/` içine kopyala
2. WordPress Admin → **Eklentiler** sayfasında aktif et
3. Eklentinin hemen altındaki **"Ayarlar"** linkine tıkla
4. LCP görsel URL'sini gir ve tercihleri kaydet

### Gereksinimler

- WordPress 5.8+
- PHP 7.4+

---

## 🇬🇧 English

### What Does It Do?

This plugin was built to automatically fix issues found in PageSpeed Insights reports:

| Feature | Description |
|---|---|
| ⚡ JS Defer | Adds `defer` to all scripts except jQuery, eliminates render-blocking |
| ⚡ CSS Async | Loads non-critical stylesheets asynchronously |
| 🖼️ LCP Preload | Adds `fetchpriority="high"` + `<link rel="preload">` for the hero image |
| 🖼️ Lazy Load Fix | Removes lazy from first image, adds it to images below the fold |
| 🔍 Meta Description | Auto-generates meta descriptions from excerpts (if Yoast/Rank Math absent) |
| 🔍 Read More Fix | Enriches weak link texts like "READ MORE" for SEO bots |
| 🤖 SEO Optimizer | Scans all pages/posts, reports issues, auto-fixes them, allows manual editing |
| 💾 Browser Cache | Adds `Cache-Control: max-age=31536000` header for static assets |

### How the SEO Optimizer Works

1. Go to WordPress Admin → **Settings → DW Performance**
2. In the **SEO Optimizer** card, toggle **"Auto Fix"** on or off
3. Click **"Scan & Optimize SEO"**
4. All pages and posts are scanned; results appear in a table
5. You can **edit each page's meta description** inline and click "Save"
6. The **"Edit in WP →"** link takes you directly to the WP editor for that page

### Installation

1. Copy the `dijitalworlds-performance` folder to `/wp-content/plugins/`
2. Activate it from the WordPress Admin → **Plugins** page
3. Click the **"Settings"** link directly below the plugin name
4. Enter your LCP image URL and save your preferences

### Requirements

- WordPress 5.8+
- PHP 7.4+

---

## 📁 File Structure

```
dijitalworlds-performance/
├── dijitalworlds-performance.php   ← Plugin entry point
├── includes/
│   ├── class-css-js-optimizer.php  ← JS defer, CSS async, LCP preload, cache headers
│   ├── class-image-optimizer.php   ← Lazy load management, WebP serving
│   ├── class-seo-fixer.php         ← Runtime meta desc injection, link text fix
│   └── class-seo-optimizer.php     ← AJAX bulk SEO scan & auto-fix engine
└── admin/
    ├── settings-page.php           ← Admin UI & AJAX enqueue
    ├── admin.css                   ← Admin panel styles
    └── admin.js                    ← SEO Optimizer AJAX + interactive table
```

---

## 📝 License

GPL-2.0+ — Free to use and modify.

## 👤 Author

**Cemal Hekimoğlu** — [dijitalworlds.com](https://dijitalworlds.com)
