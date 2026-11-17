
// =========================================
// YALU - CHATBOT INICIAL
// Respuestas programadas en JavaScript.
// =========================================

const yaluLauncher = document.getElementById("yaluLauncher");
const yaluChat = document.getElementById("yaluChat");
const yaluClose = document.getElementById("yaluClose");
const yaluForm = document.getElementById("yaluForm");
const yaluInput = document.getElementById("yaluInput");
const yaluMessages = document.getElementById("yaluMessages");

const yaluSuggestions = document.querySelectorAll(
    ".yalu-suggestions button"
);


// =========================================
// ABRIR EL CHAT
// =========================================

yaluLauncher.addEventListener("click", () => {

    yaluChat.hidden = false;
    yaluLauncher.hidden = true;

    yaluInput.focus();

});


// =========================================
// CERRAR EL CHAT
// =========================================

yaluClose.addEventListener("click", () => {

    yaluChat.hidden = true;
    yaluLauncher.hidden = false;

    yaluLauncher.focus();

});


// =========================================
// MOSTRAR MENSAJES
// =========================================

function mostrarMensaje(texto, tipo) {

    const mensaje = document.createElement("div");

    mensaje.classList.add(
        "yalu-message",
        `yalu-message--${tipo}`
    );

    mensaje.textContent = texto;

    yaluMessages.appendChild(mensaje);

    yaluMessages.scrollTop = yaluMessages.scrollHeight;

}


// =========================================
// RESPUESTAS DE YALU
// =========================================

function responderYalu(pregunta) {

    const texto = pregunta
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");


    // SALUDOS

    if (
        ["hola", "buenas", "saludos", "hey"]
        .some(p => texto.includes(p))
    ) {

        return "¡Hola! Soy Yalu, tu guía del multiverso. ¿Qué te gustaría explorar hoy?";

    }


    // COMUNIDADES

    if (
        [
            "comunidad", "recomiend", "universo",
            "anime", "videojuego", "cosplay",
            "libro", "musica", "deporte",
            "tecnologia", "pelicula"
        ].some(p => texto.includes(p))
    ) {

        return `¡Hay muchos universos para explorar!

🎮 Videojuegos
🌸 Anime
🎬 Películas
📚 Libros
🎧 Música
🏆 Deportes
🎭 Cosplay
💻 Tecnología

Puedes entrar desde las tarjetas de la página o desde la sección Comunidades.`;

    }


    // EVENTOS

    if (
        ["evento", "torneo", "actividad", "concierto"]
        .some(p => texto.includes(p))
    ) {

        return "En la sección Eventos puedes consultar actividades destacadas. También puedes entrar en «Ver todos los eventos» para explorar más.";

    }


    // REGISTRO

    if (
        [
            "registro", "registrarme",
            "cuenta", "unirme", "crear usuario"
        ].some(p => texto.includes(p))
    ) {

        return "Para crear tu cuenta, abre el menú del icono de usuario en la parte superior y selecciona «Unirme».";

    }


    // INICIAR SESIÓN

    if (
        [
            "iniciar sesion", "entrar",
            "login", "acceder"
        ].some(p => texto.includes(p))
    ) {

        return "Para ingresar a tu cuenta, abre el menú del icono de usuario y selecciona «Entrar».";

    }


    // FOROS

    if (
        [
            "foro", "publicar", "preguntar",
            "comentario", "conversar"
        ].some(p => texto.includes(p))
    ) {

        return "Los foros son espacios para compartir ideas, hacer preguntas y conversar sobre los temas de cada comunidad. Puedes entrar desde el menú superior.";

    }


    // INFORMACIÓN DE LA PLATAFORMA

    if (
        [
            "que es", "plataforma",
            "multiverse", "multiverso"
        ].some(p => texto.includes(p))
    ) {

        return "The Multiverse of Madness es un espacio para descubrir comunidades, conectar con personas que comparten tus intereses y conocer eventos y conversaciones de distintos universos.";

    }


    // AGRADECIMIENTOS

    if (
        ["gracias", "muchas gracias"]
        .some(p => texto.includes(p))
    ) {

        return "¡Con mucho gusto! Aquí estaré para ayudarte a explorar el multiverso. ✨";

    }


    // AYUDA

    if (
        [
            "ayuda", "que puedes hacer",
            "como funcionas"
        ].some(p => texto.includes(p))
    ) {

        return "Puedo orientarte sobre las comunidades, los eventos, el registro, el inicio de sesión y los foros. Pregúntame lo que necesites.";

    }


    // RESPUESTA GENERAL

    return "Todavía estoy aprendiendo esa respuesta. Por ahora puedo orientarte sobre comunidades, eventos, registro, inicio de sesión y foros. ✨";

}


// =========================================
// ENVIAR PREGUNTAS
// =========================================

function enviarPregunta(pregunta) {

    const texto = pregunta.trim();

    if (!texto) {
        return;
    }

    mostrarMensaje(texto, "user");

    yaluInput.value = "";

    window.setTimeout(() => {

        mostrarMensaje(
            responderYalu(texto),
            "bot"
        );

    }, 350);

}


// =========================================
// FORMULARIO DEL CHAT
// =========================================

yaluForm.addEventListener("submit", (evento) => {

    evento.preventDefault();

    enviarPregunta(yaluInput.value);

});


// =========================================
// PREGUNTAS SUGERIDAS
// =========================================

yaluSuggestions.forEach((boton) => {

    boton.addEventListener("click", () => {

        enviarPregunta(
            boton.dataset.question || ""
        );

    });

});
