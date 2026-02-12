<?php
/**
 * Idioma español para DataTables (objeto listo para imprimir en JS).
 * Incluir en cualquier dashboard que use DataTables para que la tabla salga en español
 * sin depender de la URL (iframe, base, etc.).
 *
 * Uso:
 *   1. En el PHP: include_once __DIR__ . '/../../includes/datatables_lang_es.php';
 *      (ajustar la ruta según la profundidad del módulo: ../../ para modules/xxx, ../../../ para modules/xxx/yyy)
 *   2. En el <head>, después de DataTables:
 *      <script>window.DATATABLES_LANG_ES = <?php echo $datatablesLangEs; ?>;</script>
 *   3. En la config de DataTable: language: window.DATATABLES_LANG_ES || {}
 */
if (!isset($datatablesLangEs)) {
    $__dt_lang_path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'i18n' . DIRECTORY_SEPARATOR . 'es-ES.json';
    $datatablesLangEs = '{}';
    if (is_file($__dt_lang_path)) {
        $raw = @file_get_contents($__dt_lang_path);
        if ($raw !== false && $raw !== '') {
            $dec = @json_decode($raw);
            if ($dec !== null) {
                $datatablesLangEs = json_encode($dec, JSON_UNESCAPED_UNICODE);
            }
        }
    }
    unset($__dt_lang_path);
}
