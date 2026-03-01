/* global dwPerf, jQuery */
(function ($) {
    'use strict';

    /* =====================================================
       SEO OPTIMIZER
    ===================================================== */

    var $runBtn      = $('#dw-seo-run-btn');
    var $autoFix     = $('#dw-seo-auto-fix');
    var $reportWrap  = $('#dw-seo-report');
    var $summary     = $('#dw-seo-summary');
    var $tableBody   = $('#dw-seo-table-body');

    $runBtn.on('click', function () {
        var autoFix = $autoFix.is(':checked') ? '1' : '0';

        $runBtn.prop('disabled', true).html('<span class="dw-spinner"></span> Taranıyor...');
        $reportWrap.hide();
        $tableBody.empty();
        $summary.empty();

        $.ajax({
            url: dwPerf.ajax_url,
            method: 'POST',
            data: {
                action: 'dw_seo_scan',
                nonce: dwPerf.nonce,
                auto_fix: autoFix,
            },
            success: function (response) {
                if (!response.success) {
                    alert('Hata: ' + (response.data || 'Bilinmeyen hata.'));
                    return;
                }

                renderSummary(response.data.summary);
                renderReport(response.data.report);
                $reportWrap.fadeIn(300);
            },
            error: function () {
                alert('AJAX isteği başarısız. Lütfen sayfayı yenileyip tekrar deneyin.');
            },
            complete: function () {
                $runBtn.prop('disabled', false).html('🔍 SEO Tara & Optimize Et');
            },
        });
    });

    /* ----- Özet render ----- */
    function renderSummary(s) {
        var fixText = s.auto_fix ? ' (' + s.fixed + ' otomatik düzeltildi)' : '';
        $summary.html(
            '<div class="dw-summary-bar">' +
            '<span class="dw-sum-item dw-sum-ok">✅ Sorunsuz: <strong>' + s.ok + '</strong></span>' +
            '<span class="dw-sum-item dw-sum-warn">⚠️ Uyarı: <strong>' + s.warn + '</strong></span>' +
            (s.auto_fix ? '<span class="dw-sum-item dw-sum-fix">🔧 Düzeltildi: <strong>' + s.fixed + '</strong></span>' : '') +
            '<span class="dw-sum-item">Toplam: <strong>' + s.total + '</strong> sayfa' + fixText + '</span>' +
            '</div>'
        );
    }

    /* ----- Tablo render ----- */
    function renderReport(report) {
        report.forEach(function (row) {
            var statusBadge = '';
            if (row.status === 'ok') {
                statusBadge = '<span class="dw-badge dw-badge-ok">✅ İyi</span>';
            } else if (row.status === 'fixed') {
                statusBadge = '<span class="dw-badge dw-badge-fix">🔧 Düzeltildi</span>';
            } else {
                statusBadge = '<span class="dw-badge dw-badge-warn">⚠️ Uyarı</span>';
            }

            // Sorunlar listesi
            var issuesList = '';
            if (row.issues.length > 0) {
                issuesList = '<ul class="dw-issues-list">' +
                    row.issues.map(function (i) { return '<li>• ' + escHtml(i) + '</li>'; }).join('') +
                    '</ul>';
            }

            // Düzeltmeler listesi
            var fixesList = '';
            if (row.fixes.length > 0) {
                fixesList = '<ul class="dw-fixes-list">' +
                    row.fixes.map(function (f) { return '<li>✔ ' + escHtml(f) + '</li>'; }).join('') +
                    '</ul>';
            }

            // Meta description alanı (düzenlenebilir)
            var metaLen  = row.meta_len || 0;
            var lenColor = metaLen >= 50 && metaLen <= 160 ? '#166534' : '#854d0e';
            var metaArea =
                '<div class="dw-meta-field">' +
                '<textarea class="dw-meta-input" data-post-id="' + row.id + '" rows="2" maxlength="160">' +
                escHtml(row.meta_desc || '') +
                '</textarea>' +
                '<div class="dw-meta-footer">' +
                '<span class="dw-char-count" style="color:' + lenColor + ';">' + metaLen + '/160</span>' +
                '<button class="dw-save-btn" data-post-id="' + row.id + '">Kaydet</button>' +
                '</div>' +
                '</div>';

            var $tr = $(
                '<tr data-post-id="' + row.id + '">' +
                '<td><a href="' + escHtml(row.url) + '" target="_blank">' + escHtml(row.title) + '</a>' +
                '<br><small style="color:#94a3b8;">' + escHtml(row.type) + ' · ' + row.words + ' kelime</small></td>' +
                '<td>' + statusBadge + issuesList + fixesList + '</td>' +
                '<td>' + metaArea + '</td>' +
                '<td><a href="' + escHtml(row.edit_url) + '" target="_blank" class="dw-edit-link">WP Düzenle →</a></td>' +
                '</tr>'
            );

            $tableBody.append($tr);
        });

        // Karakter sayacı canlı güncelle
        $tableBody.on('input', '.dw-meta-input', function () {
            var len     = $(this).val().length;
            var $counter = $(this).closest('.dw-meta-field').find('.dw-char-count');
            $counter.text(len + '/160');
            $counter.css('color', len >= 50 && len <= 160 ? '#166534' : '#854d0e');
        });

        // Manuel kaydet butonu
        $tableBody.on('click', '.dw-save-btn', function () {
            var $btn    = $(this);
            var postId  = $btn.data('post-id');
            var $input  = $btn.closest('.dw-meta-field').find('.dw-meta-input');
            var metaDesc = $input.val();

            $btn.prop('disabled', true).text('Kaydediliyor...');

            $.ajax({
                url: dwPerf.ajax_url,
                method: 'POST',
                data: {
                    action: 'dw_seo_save_single',
                    nonce: dwPerf.nonce,
                    post_id: postId,
                    meta_desc: metaDesc,
                },
                success: function (res) {
                    if (res.success) {
                        $btn.text('✅ Kaydedildi');
                        setTimeout(function () {
                            $btn.prop('disabled', false).text('Kaydet');
                        }, 2000);

                        // Satırın durumunu güncelle
                        var $row = $tableBody.find('tr[data-post-id="' + postId + '"]');
                        $row.find('.dw-badge').replaceWith('<span class="dw-badge dw-badge-fix">🔧 Düzeltildi</span>');
                    } else {
                        alert('Kayıt hatası: ' + (res.data || 'Bilinmeyen'));
                        $btn.prop('disabled', false).text('Kaydet');
                    }
                },
                error: function () {
                    alert('AJAX hatası.');
                    $btn.prop('disabled', false).text('Kaydet');
                },
            });
        });
    }

    /* ----- Yardımcı: HTML escape ----- */
    function escHtml(str) {
        if (!str) { return ''; }
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

}(jQuery));
