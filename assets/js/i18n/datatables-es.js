/**
 * Idioma español centralizado para DataTables.
 * Uso: language: (typeof window.DATATABLES_LANG_ES !== 'undefined' ? window.DATATABLES_LANG_ES : {})
 */
(function () {
    'use strict';
    window.DATATABLES_LANG_ES = {
        processing: 'Procesando...',
        lengthMenu: 'Mostrar _MENU_ registros',
        zeroRecords: 'No se encontraron resultados',
        emptyTable: 'Ningun dato disponible en esta tabla',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
        infoFiltered: '(filtrado de un total de _MAX_ registros)',
        loadingRecords: 'Cargando...',
        search: 'Buscar:',
        paginate: {
            first: 'Primero',
            last: 'Ultimo',
            next: 'Siguiente',
            previous: 'Anterior'
        },
        aria: {
            sortAscending: ': Activar para ordenar la columna de manera ascendente',
            sortDescending: ': Activar para ordenar la columna de manera descendente'
        }
    };
})();
