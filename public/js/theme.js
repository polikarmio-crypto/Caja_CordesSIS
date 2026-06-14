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

// Validación global de formularios
document.addEventListener('DOMContentLoaded', function() {
    var forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            var inputs = form.querySelectorAll('input, textarea, select');
            var isValid = true;
            var errorMsg = '';

            for (var i = 0; i < inputs.length; i++) {
                var input = inputs[i];
                
                if (input.type === 'hidden' || input.disabled || input.readOnly) {
                    continue;
                }

                var tagName = input.tagName.toLowerCase();
                if (tagName === 'input' || tagName === 'textarea') {
                    if (input.type === 'text' || input.type === 'password' || input.type === 'email' || tagName === 'textarea') {
                        var val = input.value;
                        var trimmed = val.trim();

                        // Validar campos obligatorios vacíos o con puros espacios
                        if (input.hasAttribute('required') && trimmed === '') {
                            isValid = false;
                            errorMsg = 'Por favor complete todos los campos requeridos y no use solo espacios.';
                            input.focus();
                            break;
                        }

                        // Validar que no se llenen con una sola letra/dígito
                        if (trimmed !== '') {
                            if (trimmed.length < 2 && input.type !== 'file' && input.name !== 'puntuacion' && input.name !== 'cantidad[]') {
                                isValid = false;
                                errorMsg = 'Los campos de texto deben contener al menos 2 caracteres.';
                                input.focus();
                                break;
                            }

                            // Validar caracteres en campos de nombres y apellidos
                            var nameAttr = (input.name || '').toLowerCase();
                            var idAttr = (input.id || '').toLowerCase();
                            if (nameAttr.includes('nombre') || nameAttr.includes('apellido') || idAttr.includes('nombre') || idAttr.includes('apellido')) {
                                var nameRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-\.]+$/;
                                if (!nameRegex.test(trimmed)) {
                                    isValid = false;
                                    errorMsg = 'Los nombres y apellidos solo pueden contener letras y espacios.';
                                    input.focus();
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if (!isValid) {
                event.preventDefault();
                alert(errorMsg);
            }
        });
    });
});