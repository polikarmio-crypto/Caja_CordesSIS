/**
 * theme.js — Sistema de temas Caja Cordes
 * Guarda el tema POR USUARIO usando su email/id de sesión.
 * Incluir al final del <body> en header.php:
 *   <script src="<?= BASE_URL ?>/js/theme.js"></script>
 *   <script>ThemeManager.init('<?= $_SESSION["user_id"] ?? "guest" ?>');</script>
 */

var ThemeManager = (function () {

    var userId = 'guest';

    function storageKey() {
        return 'cc_theme_' + userId;
    }

    /**
     * Inicializa el sistema de temas para el usuario actual.
     * @param {string} uid  — ID o email del usuario (desde PHP session)
     */
    function init(uid) {
        userId = uid || 'guest';

        var saved = localStorage.getItem(storageKey());

        /* Si no tiene preferencia guardada, usar dark por defecto */
        var theme = saved || 'dark';

        apply(theme, false);

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                updateButton(theme);
            });
        } else {
            updateButton(theme);
        }
    }

    /**
     * Aplica el tema al <html> y actualiza el botón toggle.
     * @param {string}  theme    — 'dark' | 'light'
     * @param {boolean} animate  — si animar la transición (true al hacer click)
     */
    function apply(theme, animate) {
        var html = document.documentElement;

        if (animate) {
            html.style.transition = 'none';
            /* forzar repaint antes de re-activar transitions */
            setTimeout(function () {
                html.style.transition = '';
            }, 50);
        }

        html.setAttribute('data-theme', theme);

        updateButton(theme);
    }

    /**
     * Actualiza el icono y texto del botón toggle.
     */
    function updateButton(theme) {
        var btn   = document.getElementById('themeToggleBtn');
        var label = document.getElementById('themeToggleLabel');
        var moon  = document.getElementById('themeIconMoon');
        var sun   = document.getElementById('themeIconSun');

        if (!btn) return;

        if (theme === 'dark') {
            if (label) label.textContent = 'Modo claro';
            if (moon)  moon.style.display = 'block';
            if (sun)   sun.style.display  = 'none';
        } else {
            if (label) label.textContent = 'Modo oscuro';
            if (moon)  moon.style.display = 'none';
            if (sun)   sun.style.display  = 'block';
        }
    }

    /**
     * Alterna entre dark y light y guarda la preferencia del usuario.
     */
    function toggle() {
        var current = document.documentElement.getAttribute('data-theme') || 'dark';
        var next    = current === 'dark' ? 'light' : 'dark';

        localStorage.setItem(storageKey(), next);
        apply(next, true);
    }

    /**
     * Devuelve el tema actual.
     */
    function current() {
        return document.documentElement.getAttribute('data-theme') || 'dark';
    }

    return { init: init, toggle: toggle, current: current, updateButton: updateButton };

})();