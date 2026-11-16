/* Mensajes de error/éxito y estado de carga de los formularios.
   Los scripts PHP redirigen con ?error=... o ?registro=exitoso; aquí se traducen a texto claro. */
(function () {
  var MENSAJES = {
    vacio: ["error", "Completa todos los campos."],
    correo: ["error", "Ese correo ya está registrado. Prueba iniciando sesión."],
    correo_invalido: ["error", "El correo no tiene un formato válido."],
    password_corta: ["error", "La contraseña debe tener al menos 6 caracteres."],
    usuario: ["error", "No existe una cuenta con ese correo."],
    password: ["error", "La contraseña es incorrecta."],
    servidor: ["error", "Ocurrió un problema en el servidor. Inténtalo de nuevo."],
    registro: ["ok", "¡Cuenta creada! Ya puedes iniciar sesión."],
    enviado: ["ok", "¡Mensaje enviado! Te responderemos pronto."]
  };

  var params = new URLSearchParams(window.location.search);
  var caja = document.getElementById("mensaje-form");
  if (caja) {
    var clave = params.get("error") || (params.get("registro") === "exitoso" ? "registro" : null) ||
                (params.get("enviado") ? "enviado" : null);
    if (clave && MENSAJES[clave]) {
      caja.textContent = MENSAJES[clave][1];
      caja.className = "form-mensaje form-mensaje--" + MENSAJES[clave][0];
      caja.hidden = false;
    }
  }

  // Estado de carga: desactiva el botón mientras se envía el formulario
  document.querySelectorAll("form[data-carga]").forEach(function (form) {
    form.addEventListener("submit", function () {
      var boton = form.querySelector("button[type=submit]");
      if (boton) { boton.disabled = true; boton.textContent = "Enviando..."; }
    });
  });
})();