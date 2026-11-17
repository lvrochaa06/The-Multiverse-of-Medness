-- =====================================================================
--  THE MULTIVERSE OF MADNESS · Base de datos completa (v2)
--  Se puede importar sobre la base actual SIN perder tus datos:
--  crea lo que falta, agrega las columnas nuevas y las cuentas admin.
--  Probado para MariaDB 10.4 (XAMPP) · phpMyAdmin > Importar
-- =====================================================================
CREATE DATABASE IF NOT EXISTS `multiverse` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `multiverse`;
SET NAMES utf8mb4;
SET time_zone = '-05:00';

-- ---------------------------------------------------------------- COMUNIDADES
CREATE TABLE IF NOT EXISTS `comunidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `titulo` varchar(120) NOT NULL,
  `simbolo` varchar(10) NOT NULL DEFAULT '✦',
  `texto_union` varchar(255) NOT NULL,
  `imagen` varchar(80) NOT NULL,
  `descripcion` text NOT NULL,
  `descripcion2` text NOT NULL,
  `miembros` int(11) NOT NULL DEFAULT 0,
  `fecha_creacion` date NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `sobre` text NOT NULL,
  `fans` varchar(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `comunidad_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comunidad_id` int(11) NOT NULL,
  `texto` varchar(255) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `comunidad_id` (`comunidad_id`),
  CONSTRAINT `comunidad_items_ibfk_1` FOREIGN KEY (`comunidad_id`) REFERENCES `comunidades` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `miembros_destacados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comunidad_id` int(11) NOT NULL,
  `emoji` varchar(10) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `rol` varchar(80) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `comunidad_id` (`comunidad_id`),
  CONSTRAINT `miembros_destacados_ibfk_1` FOREIGN KEY (`comunidad_id`) REFERENCES `comunidades` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------- USUARIOS Y ROLES
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `correo` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('usuario','moderador','admin') NOT NULL DEFAULT 'usuario',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Roles: usuario (por defecto), moderador (modera foros) y admin (panel completo)
ALTER TABLE `usuarios` MODIFY `rol` enum('usuario','moderador','admin') NOT NULL DEFAULT 'usuario';

-- Cuentas de administrador del equipo (contraseña inicial: 123456789 — cámbiala cuando puedas)
INSERT INTO `usuarios` (`nombre`, `correo`, `password_hash`, `rol`) VALUES
('Yampol', 'yampol@gmail.com', '$2y$12$VYfLkiE6WG82alWR5E/O6u1nsuhv/4f9qJUInU/6PiSkfB8RdgeTm', 'admin'),
('Laura',  'laura@gmail.com',  '$2y$12$i4Dyl3vqKLz.28v3pLEGdOt4g8fU4YJqnledS7XLtTfTBFHu4tIra', 'admin')
ON DUPLICATE KEY UPDATE `rol` = 'admin', `password_hash` = VALUES(`password_hash`), `nombre` = VALUES(`nombre`);

-- ---------------------------------------------------------------- CONTACTO + MURO PÚBLICO
CREATE TABLE IF NOT EXISTS `mensajes_contacto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `correo` varchar(120) NOT NULL,
  `asunto` varchar(120) NOT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `mensajes_contacto`
  ADD COLUMN IF NOT EXISTS `tipo` enum('duda','idea','comunidad','problema','otro') NOT NULL DEFAULT 'duda',
  ADD COLUMN IF NOT EXISTS `respuesta` text DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `fecha_respuesta` timestamp NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `respondido_por` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `publico` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `acepta_publicar` tinyint(1) NOT NULL DEFAULT 0;

-- ---------------------------------------------------------------- EVENTOS (Colombia)
CREATE TABLE IF NOT EXISTS `eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `ciudad` varchar(60) NOT NULL,
  `lugar` varchar(120) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `fecha_texto` varchar(60) NOT NULL,
  `imagen` varchar(80) NOT NULL DEFAULT 'imagen_inicio.jpg',
  `enlace` varchar(255) NOT NULL DEFAULT '',
  `descripcion` text NOT NULL,
  `confirmado` tinyint(1) NOT NULL DEFAULT 0,
  `destacado` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fecha_inicio` (`fecha_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------- FOROS
CREATE TABLE IF NOT EXISTS `foro_hilos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comunidad_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `contenido` text NOT NULL,
  `fijado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultima_actividad` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `comunidad_id` (`comunidad_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `foro_hilos_ibfk_1` FOREIGN KEY (`comunidad_id`) REFERENCES `comunidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `foro_hilos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `foro_respuestas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hilo_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `contenido` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `hilo_id` (`hilo_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `foro_respuestas_ibfk_1` FOREIGN KEY (`hilo_id`) REFERENCES `foro_hilos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `foro_respuestas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------- ANUNCIOS DE YALU
CREATE TABLE IF NOT EXISTS `anuncios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `icono` varchar(10) NOT NULL DEFAULT '✨',
  `etiqueta` varchar(30) NOT NULL DEFAULT 'Próximamente',
  `titulo` varchar(80) NOT NULL,
  `texto` varchar(160) NOT NULL DEFAULT '',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
--  DATOS INICIALES (INSERT IGNORE: no pisa lo que ya exista)
-- =====================================================================
INSERT IGNORE INTO `comunidades` (`id`, `slug`, `nombre`, `titulo`, `simbolo`, `texto_union`, `imagen`, `descripcion`, `descripcion2`, `miembros`, `fecha_creacion`, `categoria`, `sobre`, `fans`) VALUES
(1, 'anime', 'Templo Sagrado', 'Anime y manga', '⛩️', '¿Te apasiona anime, manga y cultura otaku? Esta comunidad es para ti.', 'anime.jpg', 'Un espacio para hablar de anime, manga y cultura otaku.', 'Comparte, aprende y conoce gente con tus mismos gustos.', 980, '2026-11-01', 'Anime', 'Templo Sagrado reúne a personas que disfrutan de anime, manga y cultura otaku.', 'otakus de todo el universo'),
(2, 'cosplay', 'Cosplayers', 'Cosplay', '🎭', '¿Te apasiona cosplay, maquillaje y confección de trajes? Esta comunidad es para ti.', 'cosplayers.jpg', 'Un espacio para hablar de cosplay, maquillaje y confección de trajes.', 'Comparte, aprende y conoce gente con tus mismos gustos.', 890, '2026-11-01', 'Cosplay', 'Cosplayers reúne a personas que disfrutan de cosplay, maquillaje y confección de trajes.', 'cosplayers y creadores'),
(3, 'video_juegos', 'Gamers', 'Videojuegos', '🎮', '¿Te apasiona videojuegos en consola, PC y móvil? Esta comunidad es para ti.', 'gameconsole.jpg', 'Un espacio para hablar de videojuegos en consola, PC y móvil.', 'Comparte, aprende y conoce gente con tus mismos gustos.', 1240, '2026-11-01', 'Videojuegos', 'Gamers reúne a personas que disfrutan de videojuegos en consola, PC y móvil.', 'gamers de todas las plataformas'),
(4, 'tecnologia', 'Mundo Informático', 'Tecnología', '💻', '¿Te apasiona tecnología, programación y gadgets? Esta comunidad es para ti.', 'tecnologia.jpg', 'Un espacio para hablar de tecnología, programación y gadgets.', 'Comparte, aprende y conoce gente con tus mismos gustos.', 930, '2026-11-01', 'Tecnología', 'Mundo Informático reúne a personas que disfrutan de tecnología, programación y gadgets.', 'amantes de la tecnología'),
(5, 'libros', 'Biblioteca', 'Libros', '📚', '¿Te apasiona lectura, novelas y recomendaciones? Esta comunidad es para ti.', 'libros.jpg', 'Un espacio para hablar de lectura, novelas y recomendaciones.', 'Comparte, aprende y conoce gente con tus mismos gustos.', 540, '2026-11-01', 'Libros', 'Biblioteca reúne a personas que disfrutan de lectura, novelas y recomendaciones.', 'lectores apasionados'),
(6, 'peliculas', 'Cine Mundial', 'Cine', '🎬', '¿Te apasiona películas, series y cine de autor? Esta comunidad es para ti.', 'cine.jpg', 'Un espacio para hablar de películas, series y cine de autor.', 'Comparte, aprende y conoce gente con tus mismos gustos.', 760, '2026-11-01', 'Cine', 'Cine Mundial reúne a personas que disfrutan de películas, series y cine de autor.', 'cinéfilos'),
(7, 'musica', 'Frecuencia', 'Música', '🎵', '¿Te apasiona música, conciertos y festivales? Esta comunidad es para ti.', 'musica.jpg', 'Un espacio para hablar de música, conciertos y festivales.', 'Comparte, aprende y conoce gente con tus mismos gustos.', 1120, '2026-11-01', 'Música', 'Frecuencia reúne a personas que disfrutan de música, conciertos y festivales.', 'melómanos'),
(8, 'deportes', 'Zona Atlética', 'Deportes', '🏅', '¿Te apasiona deporte, entrenamiento y competencia? Esta comunidad es para ti.', 'deportes.jpg', 'Un espacio para hablar de deporte, entrenamiento y competencia.', 'Comparte, aprende y conoce gente con tus mismos gustos.', 680, '2026-11-01', 'Deportes', 'Zona Atlética reúne a personas que disfrutan de deporte, entrenamiento y competencia.', 'deportistas y aficionados');

INSERT IGNORE INTO `comunidad_items` (`id`, `comunidad_id`, `texto`, `orden`) VALUES
(1, 1, 'Conversaciones y recomendaciones', 1),
(2, 1, 'Eventos y novedades del tema', 2),
(3, 1, 'Gente con tus mismos intereses', 3),
(4, 2, 'Conversaciones y recomendaciones', 1),
(5, 2, 'Eventos y novedades del tema', 2),
(6, 2, 'Gente con tus mismos intereses', 3),
(7, 3, 'Conversaciones y recomendaciones', 1),
(8, 3, 'Eventos y novedades del tema', 2),
(9, 3, 'Gente con tus mismos intereses', 3),
(10, 4, 'Conversaciones y recomendaciones', 1),
(11, 4, 'Eventos y novedades del tema', 2),
(12, 4, 'Gente con tus mismos intereses', 3),
(13, 5, 'Conversaciones y recomendaciones', 1),
(14, 5, 'Eventos y novedades del tema', 2),
(15, 5, 'Gente con tus mismos intereses', 3),
(16, 6, 'Conversaciones y recomendaciones', 1),
(17, 6, 'Eventos y novedades del tema', 2),
(18, 6, 'Gente con tus mismos intereses', 3),
(19, 7, 'Conversaciones y recomendaciones', 1),
(20, 7, 'Eventos y novedades del tema', 2),
(21, 7, 'Gente con tus mismos intereses', 3),
(22, 8, 'Conversaciones y recomendaciones', 1),
(23, 8, 'Eventos y novedades del tema', 2),
(24, 8, 'Gente con tus mismos intereses', 3);

INSERT IGNORE INTO `miembros_destacados` (`id`, `comunidad_id`, `emoji`, `nombre`, `rol`, `orden`) VALUES
(1, 1, '⛩️', 'Miembro fundador', 'Moderador/a', 1),
(2, 1, '⭐', 'Fan destacado', 'Colaborador/a', 2),
(3, 2, '🎭', 'Miembro fundador', 'Moderador/a', 1),
(4, 2, '⭐', 'Fan destacado', 'Colaborador/a', 2),
(5, 3, '🎮', 'Miembro fundador', 'Moderador/a', 1),
(6, 3, '⭐', 'Fan destacado', 'Colaborador/a', 2),
(7, 4, '💻', 'Miembro fundador', 'Moderador/a', 1),
(8, 4, '⭐', 'Fan destacado', 'Colaborador/a', 2),
(9, 5, '📚', 'Miembro fundador', 'Moderador/a', 1),
(10, 5, '⭐', 'Fan destacado', 'Colaborador/a', 2),
(11, 6, '🎬', 'Miembro fundador', 'Moderador/a', 1),
(12, 6, '⭐', 'Fan destacado', 'Colaborador/a', 2),
(13, 7, '🎵', 'Miembro fundador', 'Moderador/a', 1),
(14, 7, '⭐', 'Fan destacado', 'Colaborador/a', 2),
(15, 8, '🏅', 'Miembro fundador', 'Moderador/a', 1),
(16, 8, '⭐', 'Fan destacado', 'Colaborador/a', 2);

INSERT IGNORE INTO `eventos` (`id`,`titulo`,`categoria`,`ciudad`,`lugar`,`fecha_inicio`,`fecha_fin`,`fecha_texto`,`imagen`,`enlace`,`descripcion`,`confirmado`,`destacado`,`activo`) VALUES
(1,'Comic Con Medellín','Anime','Medellín','Plaza Mayor','2026-11-27','2026-11-29','27–29 nov 2026','comic_con.jpg','https://plazamayor.com.co/event/comic-con-2026/','Cómics, anime, manga, videojuegos y cosplay en tres días de cultura pop en Plaza Mayor.',1,1,1),
(2,'Gamers MED — Gran final','Videojuegos','Medellín','Medellín, Antioquia','2026-11-20','2026-12-20','Nov–Dic 2026','Gamer_med_.jpg','https://www.gamersmed.com','Torneo de videojuegos con gran final presencial. Revisa el sitio oficial para fechas exactas.',0,1,1),
(3,'Feria de Cali','Música','Cali','Cali, Valle del Cauca','2026-12-25','2026-12-30','Finales de dic 2026','musica.jpg','','Salsa, conciertos y cabalgata en la capital de la salsa. Fechas por confirmar.',0,0,1),
(4,'Carnaval de Barranquilla','Música','Barranquilla','Vía 40, Barranquilla','2027-02-06','2027-02-09','Feb 2027','musica.jpg','https://carnavaldebarranquilla.org','Patrimonio de la humanidad: desfiles, música y disfraces. Fechas estimadas según el calendario del carnaval.',0,0,1),
(5,'Festival Estéreo Picnic 2027','Música','Bogotá','Parque Simón Bolívar','2027-03-19','2027-03-21','19–21 mar 2027','estereo_picnic.jpg','https://www.festivalestereopicnic.com/','Edición 16 del festival: rock, electrónica y urbano durante tres jornadas.',1,1,1),
(6,'FICCI 66 — Festival de Cine de Cartagena','Cine','Cartagena','Cartagena de Indias','2027-04-06','2027-04-11','6–11 abr 2027','cine.jpg','https://ficcifestival.com','El festival de cine más antiguo de América Latina, con entrada gratuita a las proyecciones.',1,0,1),
(7,'FILBo 2027 — Feria del Libro de Bogotá','Libros','Bogotá','Corferias','2027-04-20','2027-05-03','20 abr–3 may 2027','libros.jpg','https://www.feriadellibro.com','Edición 39 con Sharjah como invitado de honor: autores, editoriales y talleres.',1,0,1),
(8,'Comic Con Colombia (Bogotá)','Cosplay','Bogotá','Corferias','2027-06-25','2027-06-28','Jun 2027','cosplayers.jpg','','La edición de Bogotá se celebró en junio de 2026; se espera una nueva edición similar en 2027. Fecha por confirmar.',0,0,1),
(9,'Media Maratón de Bogotá','Deportes','Bogotá','Bogotá','2027-07-25','2027-08-01','Jul–Ago 2027','deportes.jpg','','Una de las carreras populares más grandes del país. Fecha por confirmar.',0,0,1),
(10,'Colombia Tech Week','Tecnología','Bogotá','Bogotá y otras ciudades','2027-08-09','2027-08-15','Ago 2027','tecnologia.jpg','https://colombiatechweek.co','Semana de tecnología y emprendimiento con actividades gratuitas en varias ciudades. Fechas por confirmar.',0,0,1),
(11,'Feria de las Flores','Música','Medellín','Medellín, Antioquia','2027-08-01','2027-08-10','Ago 2027','musica.jpg','','Desfile de silleteros, conciertos y cultura paisa. Fechas por confirmar.',0,0,1);

INSERT IGNORE INTO `anuncios` (`id`,`icono`,`etiqueta`,`titulo`,`texto`,`activo`,`orden`) VALUES
(1,'🪐','Próximamente','Cuatro universos nuevos','Arte y Dibujo, Ciencia y Espacio, Manualidades y Culturas del Mundo.',1,1),
(2,'✨','Próximamente','Multiverse Plus','Personaliza tu perfil y lleva tus comunidades más lejos.',1,2),
(3,'💬','En camino','Chat entre miembros','Conversaciones en tiempo real dentro de cada comunidad.',1,3);

INSERT IGNORE INTO `foro_hilos` (`id`,`comunidad_id`,`usuario_id`,`titulo`,`contenido`,`fijado`,`fecha`,`ultima_actividad`) VALUES
(1,NULL,(SELECT id FROM usuarios WHERE correo='laura@gmail.com'),'¡Bienvenidos al Multiverso! Presenten su universo','Este es el hilo de bienvenida. Cuéntanos quién eres y qué te apasiona: ¿anime, juegos, música, libros?',1,NOW(),NOW()),
(2,1,(SELECT id FROM usuarios WHERE correo='yampol@gmail.com'),'¿Qué anime están viendo esta temporada?','Yo ando entre clásicos y estrenos. Recomienden algo que no deba perderme.',0,NOW(),NOW()),
(3,3,(SELECT id FROM usuarios WHERE correo='laura@gmail.com'),'Squads para jugar los fines de semana','Armemos grupos por plataforma: PC, PlayStation, Xbox y móvil. Dejen su usuario y horario.',0,NOW(),NOW()),
(4,4,(SELECT id FROM usuarios WHERE correo='yampol@gmail.com'),'Primeros pasos para aprender a programar','¿Por dónde empezar? Python, JavaScript o algo más. Comparta cada uno su ruta.',0,NOW(),NOW()),
(5,5,(SELECT id FROM usuarios WHERE correo='laura@gmail.com'),'La última novela que los dejó pensando','Comparte título y por qué te marcó, sin spoilers.',0,NOW(),NOW()),
(6,7,(SELECT id FROM usuarios WHERE correo='yampol@gmail.com'),'Playlist colaborativa del Multiverso','Cada quien aporta una canción que lo represente. ¡Armemos la mejor mezcla!',0,NOW(),NOW());

INSERT IGNORE INTO `foro_respuestas` (`id`,`hilo_id`,`usuario_id`,`contenido`,`fecha`) VALUES
(1,1,(SELECT id FROM usuarios WHERE correo='yampol@gmail.com'),'¡Bienvenidos! Yo soy Yampol, del equipo. Aquí estamos para que cada pasión tenga su lugar.',NOW()),
(2,2,(SELECT id FROM usuarios WHERE correo='laura@gmail.com'),'Les recomiendo darle una oportunidad a las series cortas, se terminan en una tarde.',NOW()),
(3,3,(SELECT id FROM usuarios WHERE correo='yampol@gmail.com'),'Yo juego en PC los sábados por la noche. ¿Quién se apunta?',NOW());

-- Mensajes de ejemplo para el muro de Contacto (bórralos desde el panel cuando tengas reales)
INSERT IGNORE INTO `mensajes_contacto` (`id`,`nombre`,`correo`,`asunto`,`mensaje`,`leido`,`tipo`,`respuesta`,`publico`,`acepta_publicar`,`fecha_respuesta`,`respondido_por`) VALUES
(1,'Camila R.','camila@ejemplo.com','¿Cómo funcionan los foros?','¿Puedo abrir un tema aunque no sea de ninguna comunidad en particular?',1,'idea','Sí. En los foros hay una categoría General para temas libres. Entra, crea tu cuenta y abre el hilo con el botón "Nuevo tema".',1,1,NOW(),(SELECT id FROM usuarios WHERE correo='laura@gmail.com')),
(2,'Julián M.','julian@ejemplo.com','Propuesta: comunidad de manhwa','Me encantaría una comunidad solo para manhwa y webtoons. ¿Se puede?',1,'comunidad','¡Buena idea! Por ahora entra en Templo Sagrado, que cubre manga y cultura otaku. Estamos sumando universos nuevos y tu propuesta queda anotada.',1,1,NOW(),(SELECT id FROM usuarios WHERE correo='laura@gmail.com')),
(3,'Valentina S.','valentina@ejemplo.com','Eventos de cosplay en Colombia','¿Dónde veo cuándo es el próximo evento de cosplay?',1,'duda','En la página de Eventos puedes filtrar por categoría y ciudad. El Comic Con Medellín es el próximo: 27 al 29 de noviembre en Plaza Mayor.',1,1,NOW(),(SELECT id FROM usuarios WHERE correo='laura@gmail.com')),
(4,'Andrés P.','andres@ejemplo.com','No me llegaba el registro','Me registré y no sabía si había quedado la cuenta.',1,'problema','Gracias por avisar. Tras registrarte te llevamos al inicio de sesión con un aviso verde; entra con el mismo correo y contraseña.',1,1,NOW(),(SELECT id FROM usuarios WHERE correo='laura@gmail.com'));
