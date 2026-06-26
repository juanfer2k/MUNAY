<?php
/**
 * includes/assets.php
 * ============================================================
 * Cloudflare (y los navegadores) cachean agresivamente los .css/.js
 * estáticos por URL exacta. Si purgas el caché manualmente cada vez
 * que subes un cambio se te puede olvidar (o, como ahora, el panel
 * de Cloudflare en cPanel puede estar caído).
 *
 * asset_url() le agrega a la URL un parámetro ?v=<fecha de
 * modificación del archivo>. Para Cloudflare/el navegador eso es
 * una URL DISTINTA cada vez que el archivo cambia en el servidor,
 * así que siempre van a pedir la versión fresca al origen, sin
 * necesidad de purgar nada a mano.
 *
 * Uso: <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
 * ============================================================
 */

function asset_url(string $relativePath): string
{
    // __DIR__ es .../www/includes, así que subimos un nivel a .../www
    $fullPath = dirname(__DIR__) . '/' . $relativePath;
    $version = @filemtime($fullPath);
    return $relativePath . ($version ? ('?v=' . $version) : '');
}
