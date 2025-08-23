-- phpMyAdmin SQL Dump
-- version 5.2.1
-- Servidor: 127.0.0.1:3307
-- Tiempo de generación: 21-08-2025 a las 14:05:25
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
 /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
 /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
 /*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `senti_cr`
--

-- --------------------------------------------------------
-- Tabla: diario
-- --------------------------------------------------------
DROP TABLE IF EXISTS `diario`;
CREATE TABLE `diario` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('feliz','triste','ansioso','estresado','neutral') NOT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla: test_emocional
-- --------------------------------------------------------
DROP TABLE IF EXISTS `test_emocional`;
CREATE TABLE `test_emocional` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` date NOT NULL DEFAULT (current_date),   -- <== FIX: evita error cerca de curdate()
  `puntaje` int(11) DEFAULT NULL,
  `resultado` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla: usuarios
-- --------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `edad` int(11) DEFAULT NULL,
  `genero` enum('masculino','femenino','otro') DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Datos iniciales (ajustado genero=NULL en vez de '')
--
INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `clave`, `edad`, `genero`, `fecha_registro`) VALUES
(1, '', '', '$2y$10$h7ojzqVG7gv/KuJ5Z7xxK.Y3n0GLoWG9LOj.yIjH.ToBo1mrfBrGu', 0, NULL, '2025-08-21 11:17:07'),
(2, 'Daniel Bravo', 'danielbravo@gmail.com', '$2y$10$OtCZHtxpxV71k2BQdBliXun2HUZXgcSPQ7k14ZSbRm2C0wQrN2wIS', 21, NULL, '2025-08-21 11:21:21'),
(4, 'Alonso lopez', 'alonsolopez@gmail.com', '$2y$10$YXdOEtGrBRYvTc0jhqepkeVYaZu1SbyFDxT2EoRNqaESrcEIaNL52', 22, NULL, '2025-08-21 11:27:25'),
(5, 'Audrey Gonzales', 'Augonza@gmail.com', '$2y$10$PCiStduv5xWEkd.ZphuCGONXduPAfaOOuNDUs/rbBwMq4E8c/0t96', 22, NULL, '2025-08-21 11:28:21'),
(6, 'chino', 'chino@gmail.com', '$2y$10$Th/gtDmBQ2wUoXCuP.prd.w.506paAtSxXdaMRc6RES.sDKCOIVBq', 23, NULL, '2025-08-21 11:30:52'),
(7, 'Joaquin Chaves', 'joaquin@gmail.com', '$2y$10$myUO4Dk4uCl2uKuDVDjYz.3KKnNMPu.IzAGyMwMnHEqNpuqdWQT6u', 44, NULL, '2025-08-21 12:02:13');

-- --------------------------------------------------------
-- Índices
-- --------------------------------------------------------
ALTER TABLE `diario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

ALTER TABLE `test_emocional`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

-- --------------------------------------------------------
-- AUTO_INCREMENT
-- --------------------------------------------------------
ALTER TABLE `diario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `test_emocional`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

-- --------------------------------------------------------
-- Foreign Keys
-- --------------------------------------------------------
ALTER TABLE `diario`
  ADD CONSTRAINT `diario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `test_emocional`
  ADD CONSTRAINT `test_emocional_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
 /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
 /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ========================================================
-- FORO / POSTS (independiente del dump de phpMyAdmin)
-- ========================================================

CREATE TABLE IF NOT EXISTS `posts` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NULL,
  `user_name`  VARCHAR(100) NOT NULL,
  `anonymous`  TINYINT(1) NOT NULL DEFAULT 1,
  `category`   VARCHAR(50) NOT NULL,
  `title`      VARCHAR(200) NOT NULL,
  `content`    TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX `idx_posts_user` (`user_id`),
  INDEX `idx_posts_created` (`created_at`),
  INDEX `idx_posts_category` (`category`),
  FULLTEXT KEY `ft_posts_title_content` (`title`, `content`),

  CONSTRAINT `fk_posts_user`
    FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `replies` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `post_id`    INT NOT NULL,
  `user_id`    INT NULL,
  `user_name`  VARCHAR(100) NOT NULL,
  `content`    TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX `idx_replies_post` (`post_id`),
  INDEX `idx_replies_user` (`user_id`),

  CONSTRAINT `fk_replies_post`
    FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`)
    ON DELETE CASCADE,

  CONSTRAINT `fk_replies_user`
    FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_likes` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `post_id`    INT NOT NULL,
  `user_id`    INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY `uk_post_like_user` (`post_id`, `user_id`),
  INDEX `idx_post_likes_user` (`user_id`),

  CONSTRAINT `fk_likes_post`
    FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`)
    ON DELETE CASCADE,

  CONSTRAINT `fk_likes_user`
    FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  specialist_name VARCHAR(100) NOT NULL DEFAULT 'Especialista',
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  INDEX idx_cs_user (user_id),
  INDEX idx_cs_status (status),
  INDEX idx_cs_started (started_at),
  CONSTRAINT fk_cs_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  sender ENUM('system','user','support') NOT NULL,
  content TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cm_session (session_id),
  CONSTRAINT fk_cm_session FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_agents (
  user_id INT PRIMARY KEY,
  added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_agents_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE chat_sessions
  ADD COLUMN agent_id INT NULL AFTER user_id,
  ADD INDEX idx_cs_agent_status (agent_id, status, started_at),
  ADD INDEX idx_cs_user_status (user_id, status, started_at);

ALTER TABLE chat_sessions
  ADD CONSTRAINT fk_cs_agent
    FOREIGN KEY (agent_id) REFERENCES usuarios(id) ON DELETE SET NULL;

CREATE INDEX idx_cm_session_id ON chat_messages (session_id, id);