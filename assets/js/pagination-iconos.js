/**
 * Paginación tipo DataTables (Anterior, 1, 2, 3, ..., N, Siguiente) para modo iconos.
 * El contenedor debe tener data-table="#idTabla" para enlazar con la DataTable.
 * Uso: $(container).attr('data-table', '#tablaReportes'); $(container).html(buildPaginationIconos(info));
 */
(function () {
    'use strict';

    function pagesToShow(currentPage0Based, totalPages) {
        if (totalPages <= 0) return [];
        if (totalPages <= 7) {
            var p = [];
            for (var i = 1; i <= totalPages; i++) p.push(i);
            return p;
        }
        var current = currentPage0Based + 1;
        var set = {};
        set[1] = true;
        set[totalPages] = true;
        for (var i = Math.max(1, current - 2); i <= Math.min(totalPages, current + 2); i++) set[i] = true;
        var sorted = Object.keys(set).map(Number).sort(function (a, b) { return a - b; });
        var out = [];
        for (var j = 0; j < sorted.length; j++) {
            if (j > 0 && sorted[j] - sorted[j - 1] > 1) out.push('...');
            out.push(sorted[j]);
        }
        return out;
    }

    window.buildPaginationIconos = function (info) {
        var pages = pagesToShow(info.page, info.pages);
        var infoText = 'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + info.recordsDisplay + ' registros';
        var html = '<span class="dataTables_info">' + infoText + '</span>';
        html += '<span class="dataTables_paginate paginate_button_wrap">';
        html += '<span class="paginate_button previous' + (info.page === 0 ? ' disabled' : '') + '" data-page="prev" role="button">Anterior</span>';
        for (var i = 0; i < pages.length; i++) {
            if (pages[i] === '...') {
                html += '<span class="paginate_button ellipsis" role="button">...</span>';
            } else {
                var isCurrent = (pages[i] === info.page + 1);
                html += '<span class="paginate_button' + (isCurrent ? ' current' : '') + '" data-page="' + (pages[i] - 1) + '" role="button">' + pages[i] + '</span>';
            }
        }
        html += '<span class="paginate_button next' + (info.page >= info.pages - 1 ? ' disabled' : '') + '" data-page="next" role="button">Siguiente</span>';
        html += '</span>';
        return html;
    };

    $(document).on('click', '.paginate_button_wrap .paginate_button', function (e) {
        var $btn = $(this);
        if ($btn.hasClass('disabled') || $btn.hasClass('ellipsis')) return;
        var $container = $btn.closest('[data-table], [data-page-handler]');
        if (!$container.length) return;
        var tableSel = $container.attr('data-table');
        var page = $btn.attr('data-page');
        var pageNum = (page === 'prev') ? 'prev' : (page === 'next') ? 'next' : parseInt(page, 10);
        if (tableSel) {
            var dt = $(tableSel).DataTable();
            if (!dt) return;
            if (page === 'prev') dt.page('previous').draw(false);
            else if (page === 'next') dt.page('next').draw(false);
            else if (!isNaN(pageNum)) dt.page(pageNum).draw(false);
        } else {
            var handler = $container.attr('data-page-handler');
            if (handler && typeof window[handler] === 'function') window[handler](pageNum);
        }
    });
})();
