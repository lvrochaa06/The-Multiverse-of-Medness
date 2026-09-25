
const navbar = document.getElementById('navbar');

function actualizarNavbar() {
  if (!navbar) return; // si la página no tiene navbar, no hace nada
  if (window.scrollY > 40) {
    navbar.classList.add('is-scrolled');
  } else {
    navbar.classList.remove('is-scrolled');
  }
}

window.addEventListener('scroll', actualizarNavbar);
actualizarNavbar(); // se ejecuta una vez al cargar, por si la página ya abre con scroll



const navToggle = document.getElementById('navToggle');
const navMenu = document.querySelector('.navbar__nav');

if (navToggle && navMenu) {
  navToggle.addEventListener('click', () => {
    const abierto = navMenu.classList.toggle('is-open');
    navToggle.classList.toggle('is-active', abierto);
    navToggle.setAttribute('aria-expanded', abierto);
  });

  // Cierra el menú automáticamente al elegir un link (comodidad en celular)
  navMenu.querySelectorAll('.navbar__link').forEach((link) => {
    link.addEventListener('click', () => {
      navMenu.classList.remove('is-open');
      navToggle.classList.remove('is-active');
      navToggle.setAttribute('aria-expanded', 'false');
    });
  });
}


const registerForm = document.querySelector('.register-form');

if (registerForm) {
  registerForm.addEventListener('submit', (evento) => {
    evento.preventDefault(); // evita que la página se recargue (quítalo al usar PHP)

    const nombre = document.getElementById('nombre').value.trim();
    const correo = document.getElementById('correo').value.trim();
    const password = document.getElementById('password').value.trim();

    if (!nombre || !correo || !password) {
      alert('Por favor completa todos los campos.');
      return;
    }

    // Aquí normalmente enviarías los datos a un archivo PHP,
    // con fetch() o dejando que el <form action="..."> lo haga solo.
    alert(`¡Bienvenido/a, ${nombre}! Tu cuenta fue creada (simulado).`);
    registerForm.reset();
  });
}



const contactForm = document.querySelector('.contact-form');

if (contactForm) {
  contactForm.addEventListener('submit', (evento) => {
    evento.preventDefault(); // quítalo cuando conectes el envío real (PHP / email)

    const nombre = document.getElementById('contacto-nombre').value.trim();
    const correo = document.getElementById('contacto-correo').value.trim();
    const mensaje = document.getElementById('contacto-mensaje').value.trim();
    const mensajeEstado = contactForm.querySelector('.form-message');

    if (!nombre || !correo || !mensaje) {
      mensajeEstado.textContent = 'Por favor completa todos los campos.';
      mensajeEstado.className = 'form-message is-error';
      return;
    }

    mensajeEstado.textContent = '¡Gracias! Tu mensaje fue enviado (simulado).';
    mensajeEstado.className = 'form-message is-success';
    contactForm.reset();
  });
}



const botonesPlan = document.querySelectorAll('.plan-card__btn');

botonesPlan.forEach((boton) => {
  boton.addEventListener('click', (evento) => {
    evento.preventDefault(); // por ahora no navega a ninguna parte

    const tarjeta = boton.closest('.plan-card');
    const nombrePlan = tarjeta.querySelector('.plan-card__name').textContent;

    // Quita la selección de todas las tarjetas de planes
    document.querySelectorAll('.plan-card').forEach((card) => {
      card.classList.remove('is-selected');
    });

    // Marca la tarjeta elegida
    tarjeta.classList.add('is-selected');

    alert(`Elegiste el plan ${nombrePlan}. Más adelante puedes conectar este botón con tu registro.`);
  });
});