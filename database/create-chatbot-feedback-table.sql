CREATE TABLE IF NOT EXISTS `chatbot_feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT NULL,
    `query` TEXT NOT NULL,
    `response` TEXT NOT NULL,
    `rating` TINYINT NOT NULL, -- 1 for like, -1 for dislike
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `client` (`id_client`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
