/**
 * Idioma español centralizado para Select2.
 * Uso: language: (typeof window.SELECT2_LANG_ES !== 'undefined' ? window.SELECT2_LANG_ES : {})
 */
(function () {
    'use strict';
    window.SELECT2_LANG_ES = {
        noResults: function () { return 'Sin resultados'; },
        searching: function () { return 'Buscando...'; },
        inputTooShort: function () { return 'Escriba al menos 1 carácter'; },
        loadingMore: function () { return 'Cargando más...'; },
        errorLoading: function () { return 'No se pudieron cargar los resultados'; }
    };
})();
