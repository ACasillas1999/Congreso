document.addEventListener("DOMContentLoaded", function() {
    var body = document.querySelector("body");
    body.classList.add("fade-in"); // Aplicar animación de entrada al cargar la página

    var form = document.querySelector("form");
    if (form) {
        form.addEventListener("submit", function(e) {
            e.preventDefault();  // Prevenir el envío inmediato del formulario
            body.classList.add("fade-out"); // Aplicar animación de salida

            // Esperar a que la animación termine antes de enviar el formulario
            setTimeout(function() {
                form.submit();
            }, 500);  // 500ms debe coincidir con la duración de la animación CSS
        });
    }

    // Para manejar la animación de salida al navegar fuera de la página
    window.addEventListener("beforeunload", function(e) {
        body.classList.add("fade-out"); // Aplicar animación de salida
    });
});
