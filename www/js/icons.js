/**
 * js/icons.js
 * ============================================================
 * Genera el markup <svg> de los íconos en el lado del cliente,
 * leyendo los mismos paths que define includes/icons.php
 * (inyectados por header.php en window.MUNAY_ICONS).
 *
 * Antes el menú de navegación (renderNav, en js/common.js)
 * tenía su propia copia hardcodeada de cada path SVG. Ahora
 * usa esta función, así que el ícono se define en UN solo lugar
 * en todo el proyecto: includes/icons.php.
 *
 * Uso:
 *   MunayIcon('shifts')        -> <svg ...></svg> de 24px
 *   MunayIcon('shifts', 18)    -> el mismo ícono a 18px
 * ============================================================
 */
(function () {
    'use strict';

    function MunayIcon(name, size) {
        size = size || 24;
        var paths = window.MUNAY_ICONS || {};
        var d = paths[name];
        if (!d) return '';
        return '<svg width="' + size + '" height="' + size + '" viewBox="0 0 24 24" fill="currentColor"><path d="' + d + '"/></svg>';
    }

    window.MunayIcon = MunayIcon;
})();
