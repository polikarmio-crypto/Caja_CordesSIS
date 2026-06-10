(function () {
    console.log('[login-bg] cargado');
    var canvas = document.getElementById('login-canvas');
    if (!canvas) return;
    var scene = canvas.parentElement;
    var ctx = canvas.getContext('2d');
    var W, H, particles;
    var mouse = { x: null, y: null };


    /* ─── BLOQUE DE CONFIGURACIÓN — editá estos valores ─── */
    var N = 90;               // más partículas = más denso
    var SPEED = 0.5;          // 0.1 lento, 1.5 rápido
    var CONNECT_DIST = 120;   // distancia de conexión entre puntos
    var MOUSE_DIST = 160;     // distancia de reacción al mouse (px)
    var PARTICLE_SIZE = 2;    // tamaño máximo de cada punto (px)
    var COLORS = [            // colores de los puntos
        '#007A5E',
        '#00ba8b',
        '#004c3a',
        '#1D9E75',
        '#5DCAA5'
    ];
    var BLOB_COLOR_1 = 'rgba(0,122,94,0.18)';   // blob superior derecho
    var BLOB_COLOR_2 = 'rgba(0,186,139,0.13)';  // blob inferior izquierdo
    /* ─────────────────────────────────────────── */

    function resize() {
        W = canvas.width = scene.offsetWidth;
        H = canvas.height = scene.offsetHeight;
    }

    function Particle() {
        this.x = Math.random() * W;
        this.y = Math.random() * H;
        this.r = Math.random() * PARTICLE_SIZE + 0.8;
        this.vx = (Math.random() - 0.5) * SPEED;
        this.vy = (Math.random() - 0.5) * SPEED;
        this.color = COLORS[Math.floor(Math.random() * COLORS.length)];
        this.alpha = Math.random() * 0.6 + 0.2;
    }

    function init() {
        resize();
        particles = [];
        for (var i = 0; i < N; i++) particles.push(new Particle());
    }

    function draw() {
        ctx.clearRect(0, 0, W, H);

        /* blobs suaves de fondo */
        var g1 = ctx.createRadialGradient(W * 0.75, H * 0.15, 0, W * 0.75, H * 0.15, W * 0.45);
        g1.addColorStop(0, BLOB_COLOR_1);
        g1.addColorStop(1, 'transparent');
        ctx.fillStyle = g1;
        ctx.fillRect(0, 0, W, H);

        var g2 = ctx.createRadialGradient(W * 0.1, H * 0.85, 0, W * 0.1, H * 0.85, W * 0.35);
        g2.addColorStop(0, BLOB_COLOR_2);
        g2.addColorStop(1, 'transparent');
        ctx.fillStyle = g2;
        ctx.fillRect(0, 0, W, H);

        /* líneas entre partículas cercanas */
        for (var i = 0; i < N; i++) {
            var a = particles[i];
            for (var j = i + 1; j < N; j++) {
                var b = particles[j];
                var dx = a.x - b.x, dy = a.y - b.y;
                var dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < CONNECT_DIST) {
                    ctx.beginPath();
                    ctx.strokeStyle = 'rgba(0,186,139,' + (0.18 * (1 - dist / CONNECT_DIST)) + ')';
                    ctx.lineWidth = 0.6;
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(b.x, b.y);
                    ctx.stroke();
                }
            }

            /* línea al cursor */
            if (mouse.x !== null) {
                var mx = a.x - mouse.x, my = a.y - mouse.y;
                var md = Math.sqrt(mx * mx + my * my);
                if (md < MOUSE_DIST) {
                    ctx.beginPath();
                    ctx.strokeStyle = 'rgba(0,186,139,' + (0.35 * (1 - md / MOUSE_DIST)) + ')';
                    ctx.lineWidth = 0.8;
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(mouse.x, mouse.y);
                    ctx.stroke();
                }
            }
        }

        /* dibujar partículas y moverlas */
        for (var i = 0; i < N; i++) {
            var p = particles[i];
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = p.color;
            ctx.globalAlpha = p.alpha;
            ctx.fill();
            ctx.globalAlpha = 1;

            p.x += p.vx;
            p.y += p.vy;
            if (p.x < 0 || p.x > W) p.vx *= -1;
            if (p.y < 0 || p.y > H) p.vy *= -1;
        }

        requestAnimationFrame(draw);
    }

    scene.addEventListener('mousemove', function (e) {
        var r = scene.getBoundingClientRect();
        mouse.x = e.clientX - r.left;
        mouse.y = e.clientY - r.top;
    });
    scene.addEventListener('mouseleave', function () {
        mouse.x = null;
        mouse.y = null;
    });
    window.addEventListener('resize', function () { resize(); });

    init();
    draw();
})();