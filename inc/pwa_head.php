<?php
// Etiquetas PWA comunes (RNF-06): manifiesto, color del tema y registro del Service Worker.
// Las rutas son relativas a la carpeta de las páginas (la raíz del proyecto).
?>
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#2e7d32">
<link rel="apple-touch-icon" href="icons/icon-192.png">
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('sw.js').catch(function () {});
    });
}
</script>
