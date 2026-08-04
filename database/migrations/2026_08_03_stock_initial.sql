CREATE TABLE IF NOT EXISTS `stock_initial` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `produit_id` int UNSIGNED NOT NULL,
  `emplacement_id` int UNSIGNED NOT NULL,
  `mode_stock` enum('produit','emballage') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'produit',
  `caisses_initiales` int NOT NULL DEFAULT '0',
  `quantite_initiale` int NOT NULL DEFAULT '0',
  `motif` text COLLATE utf8mb4_unicode_ci,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stock_initial` (`produit_id`,`emplacement_id`,`mode_stock`),
  KEY `idx_stock_initial_emplacement` (`emplacement_id`),
  KEY `idx_stock_initial_mode` (`mode_stock`),
  KEY `idx_stock_initial_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
