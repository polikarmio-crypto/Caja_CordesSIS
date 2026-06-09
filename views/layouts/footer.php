    </div>
    
    <!-- Controles de Accesibilidad Flotantes (RF-105) -->
    <div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; display: flex; gap: 8px;">
        <button onclick="toggleFuenteGrande()" style="padding: 10px 14px; border-radius: 8px; border: 1px solid #007a5e; background: #ffffff; color: #007a5e; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            🔍 Texto +
        </button>
    </div>

    <script>
        // Aplicar preferencias guardadas al cargar la página
        if (localStorage.getItem('fuente-grande') === 'true') {
            document.body.classList.add('fuente-grande');
        }

        function toggleFuenteGrande() {
            const active = document.body.classList.toggle('fuente-grande');
            localStorage.setItem('fuente-grande', active);
        }
    </script>
</body>
</html>
