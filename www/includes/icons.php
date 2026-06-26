<?php
/**
 * includes/icons.php
 * ============================================================
 * FUENTE ÚNICA de los paths SVG usados en toda la app.
 *
 * Antes este mismo dibujo (ej. el ícono de "Turnos") estaba
 * copiado y pegado en 3 sitios distintos (tarjeta de stats,
 * tab, y menú de navegación), cada uno con su propio <svg>.
 * Ahora se define UNA sola vez aquí y se reusa:
 *
 *   - En PHP:  echo munay_icon('shifts', 28);
 *   - En JS:   MunayIcon('shifts', 20)   (ver js/icons.js)
 *
 * header.php expone este mismo array como `window.MUNAY_ICONS`
 * para que el JS pueda generarlos sin duplicar los paths.
 * ============================================================
 */

const MUNAY_ICON_PATHS = [
    'dashboard' => 'M4 13h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1zm0 8h6c.55 0 1-.45 1-1v-4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1zm10 0h6c.55 0 1-.45 1-1v-8c0-.55-.45-1-1-1h-6c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1zM13 4v4c0 .55.45 1 1 1h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1h-6c-.55 0-1 .45-1 1z',
    'users'     => 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z',
    'shifts'    => 'M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z',
    'expenses'  => 'M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z',
    'changes'   => 'M12 6v2h-4V6h4zm0 4v2H8v-2h4zm0 4v2H8v-2h4zm-4 4h8v-2H8v2zm10-12h-3V4h-2v2H8V4H6v2H5c-.55 0-1 .45-1 1v14c0 .55.45 1 1 1h14c.55 0 1-.45 1-1V7c0-.55-.45-1-1-1z',
    'alerts'    => 'M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z',
    'police'    => 'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z',
    'logout'    => 'M16 17v-3H9v-4h7V7l5 5-5 5zM14 2a2 2 0 0 1 2 2v2h-2V4H5v16h9v-2h2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9z',
    'settings'  => 'M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61l-2-3.46c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41h-4c-0.24,0-0.43,0.17-0.47,0.41L9.04,5.35c-0.59,0.24-1.13,0.57-1.62,0.94L5.03,5.33c-0.22-0.08-0.47,0-0.59,0.22l-2,3.46c-0.13,0.22-0.07,0.47,0.12,0.61l2.03,1.58C4.56,11.36,4.5,11.69,4.5,12s0.06,0.64,0.07,0.94l-2.03,1.58c-0.18,0.14-0.23,0.41-0.12,0.61l2,3.46c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54c0.05,0.24,0.24,0.41,0.48,0.41h4c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96c0.22,0.08,0.47,0,0.59-0.22l2-3.46c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z',
];

/**
 * Devuelve el markup <svg> listo para imprimir en PHP.
 *
 * @param string $name   clave en MUNAY_ICON_PATHS
 * @param int    $size   ancho/alto en px (default 24)
 * @param string $extra  atributos extra para el <svg>, ej. 'fill="#3f8dee"'
 */
function munay_icon(string $name, int $size = 24, string $extra = ''): string
{
    $path = MUNAY_ICON_PATHS[$name] ?? null;
    if (!$path) return '';
    $fillAttr = $extra !== '' ? $extra : 'fill="currentColor"';
    return sprintf(
        '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" %2$s><path d="%3$s"/></svg>',
        $size,
        $fillAttr,
        $path
    );
}
