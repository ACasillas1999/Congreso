document.addEventListener("DOMContentLoaded", function() {
    var body = document.querySelector("body");
    body.classList.add("fade-in"); // Aplicar animación de entrada al cargar la página

    // Función para manejar la animación de salida al hacer clic en botones y enlaces
    function applyFadeOut(event) {
        event.preventDefault();  // Prevenir la acción inmediata
        body.classList.add("fade-out"); // Aplicar animación de salida

        // Esperar a que la animación termine antes de redirigir
        setTimeout(function() {
            if (event.target.tagName === "FORM") {
                event.target.submit();
            } else {
                window.location.href = event.target.href;
            }
        }, 500);  // 500ms debe coincidir con la duración de la animación CSS
    }

    // Aplicar la animación de salida a todos los enlaces
    document.querySelectorAll("a").forEach(function(link) {
        link.addEventListener("click", applyFadeOut);
    });

    // Aplicar la animación de salida a todos los formularios
    document.querySelectorAll("form").forEach(function(form) {
        form.addEventListener("submit", applyFadeOut);
    });

    // Para manejar la animación de salida al navegar fuera de la página
    window.addEventListener("beforeunload", function(e) {
        body.classList.add("fade-out"); // Aplicar animación de salida
    });
});
