/* global dwPerf, jQuery */
(function ($) {
    'use strict';

    var t = dwPerf.strings; // localized strings shorthand

    /* =====================================================
       SEO OPTIMIZER
    ===================================================== */

    var $runBtn     = $('#dw-seo-run-btn');
    var $autoFix    = $('#dw-seo-auto-fix');
    var $reportWrap = $('#dw-seo-report');
    var $summary    = $('#dw-seo-summary');
    var $tableBody  = $('#dw-seo-table-body');

    $runBtn.on('click', function () {
        var autoFix = $autoFix.is(':checked') ? '1' : '0';

        $runBtn.prop('disabled', true).html('<span class="dw-spinner"></span> ' + t.scanning);
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
                    alert(t.err_save + ': ' + (response.data || t.err_generic));
                    return;
                }

                renderSummary(response.data.summary);
                renderReport(response.data.report);
                $reportWrap.fadeIn(300);
            },
            error: function () {
                alert(t.err_ajax);
            },
            complete: function () {
                $runBtn.prop('disabled', false).html(t.scan_btn);
            },
        });
    });

    /* ----- Summary render ----- */
    function renderSummary(s) {
        var fixText = s.auto_fix ? ' (' + s.fixed + ' ' + t.auto_fixed + ')' : '';
        $summary.html(
            '<div class="dw-summary-bar">' +
            '<span class="dw-sum-item dw-sum-ok">✅ ' + t.ok_label + ': <strong>' + s.ok + '</strong></span>' +
            '<span class="dw-sum-item dw-sum-warn">⚠️ ' + t.warn_label + ': <strong>' + s.warn + '</strong></span>' +
            (s.auto_fix ? '<span class="dw-sum-item dw-sum-fix">🔧 ' + t.fixed_label + ': <strong>' + s.fixed + '</strong></span>' : '') +
            '<span class="dw-sum-item">' + t.total_label + ': <strong>' + s.total + '</strong> ' + t.pages + fixText + '</span>' +
            '</div>'
        );
    }

    /* ----- Table render ----- */
    function renderReport(report) {
        report.forEach(function (row) {
            var statusBadge = '';
            if (row.status === 'ok') {
                statusBadge = '<span class="dw-badge dw-badge-ok">' + t.ok_badge + '</span>';
            } else if (row.status === 'fixed') {
                statusBadge = '<span class="dw-badge dw-badge-fix">' + t.fixed_badge + '</span>';
            } else {
                statusBadge = '<span class="dw-badge dw-badge-warn">' + t.warn_badge + '</span>';
            }

            var issuesList = '';
            if (row.issues.length > 0) {
                issuesList = '<ul class="dw-issues-list">' +
                    row.issues.map(function (i) { return '<li>• ' + escHtml(i) + '</li>'; }).join('') +
                    '</ul>';
            }

            var fixesList = '';
            if (row.fixes.length > 0) {
                fixesList = '<ul class="dw-fixes-list">' +
                    row.fixes.map(function (f) { return '<li>✔ ' + escHtml(f) + '</li>'; }).join('') +
                    '</ul>';
            }

            var metaLen  = row.meta_len || 0;
            var lenColor = metaLen >= 50 && metaLen <= 160 ? '#166534' : '#854d0e';
            var metaArea =
                '<div class="dw-meta-field">' +
                '<textarea class="dw-meta-input" data-post-id="' + row.id + '" rows="2" maxlength="160">' +
                escHtml(row.meta_desc || '') +
                '</textarea>' +
                '<div class="dw-meta-footer">' +
                '<span class="dw-char-count" style="color:' + lenColor + ';">' + metaLen + '/160</span>' +
                '<button class="dw-save-btn" data-post-id="' + row.id + '">' + t.save + '</button>' +
                '</div>' +
                '</div>';

            var $tr = $(
                '<tr data-post-id="' + row.id + '">' +
                '<td><a href="' + escHtml(row.url) + '" target="_blank">' + escHtml(row.title) + '</a>' +
                '<br><small style="color:#94a3b8;">' + escHtml(row.type) + ' · ' + row.words + ' ' + t.words + '</small></td>' +
                '<td>' + statusBadge + issuesList + fixesList + '</td>' +
                '<td>' + metaArea + '</td>' +
                '<td><a href="' + escHtml(row.edit_url) + '" target="_blank" class="dw-edit-link">' + t.edit_link + '</a></td>' +
                '</tr>'
            );

            $tableBody.append($tr);
        });

        // Live char counter
        $tableBody.on('input', '.dw-meta-input', function () {
            var len      = $(this).val().length;
            var $counter = $(this).closest('.dw-meta-field').find('.dw-char-count');
            $counter.text(len + '/160');
            $counter.css('color', len >= 50 && len <= 160 ? '#166534' : '#854d0e');
        });

        // Manual save button
        $tableBody.on('click', '.dw-save-btn', function () {
            var $btn     = $(this);
            var postId   = $btn.data('post-id');
            var $input   = $btn.closest('.dw-meta-field').find('.dw-meta-input');
            var metaDesc = $input.val();

            $btn.prop('disabled', true).text(t.saving);

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
                        $btn.text(t.saved);
                        setTimeout(function () {
                            $btn.prop('disabled', false).text(t.save);
                        }, 2000);

                        var $row = $tableBody.find('tr[data-post-id="' + postId + '"]');
                        $row.find('.dw-badge').replaceWith('<span class="dw-badge dw-badge-fix">' + t.fixed_badge + '</span>');
                    } else {
                        alert(t.err_save + ': ' + (res.data || t.err_generic));
                        $btn.prop('disabled', false).text(t.save);
                    }
                },
                error: function () {
                    alert(t.err_ajax);
                    $btn.prop('disabled', false).text(t.save);
                },
            });
        });
    }

    /* ----- Helper: HTML escape ----- */
    function escHtml(str) {
        if (!str) { return ''; }
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

}(jQuery));
