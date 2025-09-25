-- Copiando estrutura para tabela easybudget.plan_subscriptions
DROP TABLE IF EXISTS `plan_subscriptions`;

CREATE TABLE
  IF NOT EXISTS `plan_subscriptions` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `provider_id` int (11) NOT NULL,
    `plan_id` int (11) NOT NULL,
    `tenant_id` int (11) NOT NULL,
    `status` enum ('active', 'cancelled', 'pending', 'expired') NOT NULL,
    `public_hash` varchar(64) DEFAULT NULL,
    `transaction_amount` decimal(10, 2) NOT NULL,
    `start_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `end_date` datetime DEFAULT NULL,
    `payment_method` varchar(50) DEFAULT NULL,
    `payment_id` varchar(50) DEFAULT NULL,
    `last_payment_date` datetime DEFAULT NULL,
    `next_payment_date` datetime DEFAULT NULL,
    `transaction_date` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_provider` (`provider_id`),
    KEY `idx_plan` (`plan_id`),
    KEY `idx_tenant` (`tenant_id`),
    KEY `idx_status` (`status`),
    KEY `idx_dates` (`start_date`, `end_date`),
    CONSTRAINT `fk_subscriptions_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`),
    CONSTRAINT `fk_subscriptions_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`),
    CONSTRAINT `fk_subscriptions_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 32 DEFAULT CHARSET = utf8mb4;

-- Copiando estrutura para tabela easybudget.products
DROP TABLE IF EXISTS `products`;

CREATE TABLE
  IF NOT EXISTS `products` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) DEFAULT NULL,
    `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `price` decimal(10, 2) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `active` tinyint (1) DEFAULT '1',
    `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tenant_code` (`tenant_id`, `code`),
    KEY `idx_tenant_code` (`tenant_id`, `code`),
    KEY `idx_tenant_name` (`tenant_id`, `name`),
    KEY `idx_tenant_active` (`tenant_id`, `active`),
    KEY `idx_tenant_price` (`tenant_id`, `price`),
    CONSTRAINT `fk_products_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 42 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando estrutura para tabela easybudget.product_inventory
DROP TABLE IF EXISTS `product_inventory`;

CREATE TABLE
  IF NOT EXISTS `product_inventory` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) NOT NULL,
    `product_id` int (11) NOT NULL,
    `quantity` int (11) NOT NULL DEFAULT '0',
    `min_quantity` int (11) DEFAULT '0',
    `max_quantity` int (11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tenant` (`tenant_id`),
    KEY `idx_product` (`product_id`),
    CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
    CONSTRAINT `fk_inventory_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando estrutura para tabela easybudget.professions
DROP TABLE IF EXISTS `professions`;

CREATE TABLE
  IF NOT EXISTS `professions` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `is_active` tinyint (1) DEFAULT '1',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 34 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando estrutura para tabela easybudget.providers
DROP TABLE IF EXISTS `providers`;

CREATE TABLE
  IF NOT EXISTS `providers` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) NOT NULL,
    `user_id` int (11) NOT NULL,
    `common_data_id` int (11) NOT NULL,
    `contact_id` int (11) NOT NULL,
    `address_id` int (11) NOT NULL,
    `terms_accepted` tinyint (1) NOT NULL DEFAULT '0',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_tenant` (`user_id`, `tenant_id`),
    KEY `idx_tenant` (`tenant_id`),
    KEY `idx_user` (`user_id`),
    KEY `fk_providers_common_data` (`common_data_id`),
    KEY `fk_providers_contact` (`contact_id`),
    KEY `fk_providers_address` (`address_id`),
    CONSTRAINT `fk_providers_address` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
    CONSTRAINT `fk_providers_common_data` FOREIGN KEY (`common_data_id`) REFERENCES `common_datas` (`id`),
    CONSTRAINT `fk_providers_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`),
    CONSTRAINT `fk_providers_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_providers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 20 DEFAULT CHARSET = utf8mb4;

-- Copiando estrutura para tabela easybudget.provider_credentials
DROP TABLE IF EXISTS `provider_credentials`;

CREATE TABLE
  IF NOT EXISTS `provider_credentials` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `provider_id` int (11) NOT NULL,
    `tenant_id` int (11) NOT NULL,
    `payment_gateway` varchar(50) NOT NULL DEFAULT 'mercadopago',
    `user_id_gateway` varchar(50) NOT NULL,
    `access_token_encrypted` text NOT NULL,
    `refresh_token_encrypted` text NOT NULL,
    `public_key` varchar(50) NOT NULL,
    `expires_in` int (11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `provider_id` (`provider_id`),
    KEY `tenant_id` (`tenant_id`),
    CONSTRAINT `provider_credentials_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`),
    CONSTRAINT `provider_credentials_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 2 DEFAULT CHARSET = utf8mb4;

-- Copiando estrutura para tabela easybudget.reports
DROP TABLE IF EXISTS `reports`;

CREATE TABLE
  IF NOT EXISTS `reports` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) NOT NULL,
    `user_id` int (11) NOT NULL,
    `hash` varchar(64) DEFAULT NULL,
    `type` varchar(50) NOT NULL,
    `description` text,
    `file_name` varchar(255) NOT NULL,
    `status` varchar(20) NOT NULL,
    `format` varchar(10) NOT NULL,
    `size` float NOT NULL DEFAULT '0',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `tenant_id` (`tenant_id`),
    KEY `idx_report_hash` (`hash`, `tenant_id`),
    CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Copiando estrutura para tabela easybudget.resources
DROP TABLE IF EXISTS `resources`;

CREATE TABLE
  IF NOT EXISTS `resources` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `in_dev` tinyint (1) NOT NULL DEFAULT '0',
    `status` enum ('active', 'inactive', 'deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_status` (`status`),
    KEY `idx_slug` (`slug`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 37 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando estrutura para tabela easybudget.roles
DROP TABLE IF EXISTS `roles`;

CREATE TABLE
  IF NOT EXISTS `roles` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` varchar(255) DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_role_name` (`name`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 5 DEFAULT CHARSET = utf8mb4;

-- Copiando estrutura para tabela easybudget.role_permissions
DROP TABLE IF EXISTS `role_permissions`;

CREATE TABLE
  IF NOT EXISTS `role_permissions` (
    `role_id` int (11) NOT NULL,
    `permission_id` int (11) NOT NULL,
    KEY `idx_role` (`role_id`),
    KEY `idx_permission` (`permission_id`),
    CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Copiando estrutura para tabela easybudget.schedules
DROP TABLE IF EXISTS `schedules`;

CREATE TABLE
  IF NOT EXISTS `schedules` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) NOT NULL,
    `service_id` int (11) NOT NULL,
    `user_confirmation_token_id` int (11) NOT NULL,
    `start_date_time` datetime NOT NULL,
    `end_date_time` datetime DEFAULT NULL,
    `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `tenant_id` (`tenant_id`),
    KEY `service_id` (`service_id`),
    KEY `user_confirmation_token_id` (`user_confirmation_token_id`),
    CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
    CONSTRAINT `schedules_ibfk_3` FOREIGN KEY (`user_confirmation_token_id`) REFERENCES `user_confirmation_tokens` (`id`) ON DELETE CASCADE
  ) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando estrutura para tabela easybudget.services
DROP TABLE IF EXISTS `services`;

CREATE TABLE
  IF NOT EXISTS `services` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) NOT NULL,
    `budget_id` int (11) NOT NULL,
    `category_id` int (11) NOT NULL,
    `service_statuses_id` int (11) NOT NULL,
    `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    `description` text COLLATE utf8mb4_unicode_ci,
    `discount` decimal(10, 2) NOT NULL,
    `total` decimal(10, 2) NOT NULL,
    `due_date` datetime DEFAULT NULL,
    `pdf_verification_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `tenant_id` (`tenant_id`),
    KEY `budget_id` (`budget_id`),
    KEY `category_id` (`category_id`),
    KEY `service_statuses_id` (`service_statuses_id`),
    CONSTRAINT `services_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
    CONSTRAINT `services_ibfk_2` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`),
    CONSTRAINT `services_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
    CONSTRAINT `services_ibfk_4` FOREIGN KEY (`service_statuses_id`) REFERENCES `service_statuses` (`id`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 37 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando estrutura para tabela easybudget.service_items
DROP TABLE IF EXISTS `service_items`;

CREATE TABLE
  IF NOT EXISTS `service_items` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) NOT NULL,
    `service_id` int (11) NOT NULL,
    `product_id` int (11) NOT NULL,
    `quantity` int (11) NOT NULL DEFAULT '1',
    `unit_value` decimal(10, 2) NOT NULL,
    `total` decimal(10, 2) NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    KEY `service_id` (`service_id`),
    KEY `tenant_id` (`tenant_id`),
    CONSTRAINT `service_items_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
    CONSTRAINT `service_items_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
    CONSTRAINT `service_items_ibfk_3` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 66 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando estrutura para tabela easybudget.service_statuses
DROP TABLE IF EXISTS `service_statuses`;

CREATE TABLE
  IF NOT EXISTS `service_statuses` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `slug` varchar(20) NOT NULL,
    `name` varchar(50) NOT NULL,
    `description` text,
    `color` varchar(7) DEFAULT NULL,
    `icon` varchar(30) DEFAULT NULL,
    `order_index` int (11) DEFAULT NULL,
    `is_active` tinyint (1) DEFAULT '1',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 13 DEFAULT CHARSET = utf8mb4;

-- Copiando estrutura para tabela easybudget.supports
DROP TABLE IF EXISTS `supports`;

CREATE TABLE
  IF NOT EXISTS `supports` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) DEFAULT NULL,
    `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `message` text COLLATE utf8mb4_unicode_ci,
    `status` enum (
      'ABERTO',
      'RESPONDIDO',
      'RESOLVIDO',
      'FECHADO',
      'EM_ANDAMENTO',
      'AGUARDANDO_RESPOSTA',
      'CANCELADO'
    ) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ABERTO',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_supports_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando estrutura para tabela easybudget.tenants
DROP TABLE IF EXISTS `tenants`;

CREATE TABLE
  IF NOT EXISTS `tenants` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tenants_name` (`name`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 38 DEFAULT CHARSET = utf8mb4;

-- Copiando estrutura para tabela easybudget.units
DROP TABLE IF EXISTS `units`;

CREATE TABLE
  IF NOT EXISTS `units` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `is_active` tinyint (1) DEFAULT '1',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 14 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando estrutura para tabela easybudget.users
DROP TABLE IF EXISTS `users`;

CREATE TABLE
  IF NOT EXISTS `users` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `logo` varchar(255) DEFAULT NULL,
    `is_active` tinyint (1) DEFAULT '0',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_users_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 20 DEFAULT CHARSET = utf8mb4;

-- Copiando estrutura para tabela easybudget.user_confirmation_tokens
DROP TABLE IF EXISTS `user_confirmation_tokens`;

CREATE TABLE
  IF NOT EXISTS `user_confirmation_tokens` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `user_id` int (11) NOT NULL,
    `tenant_id` int (11) NOT NULL,
    `token` varchar(64) NOT NULL,
    `expires_at` datetime NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_token` (`token`),
    KEY `idx_user` (`user_id`),
    KEY `idx_tenant` (`tenant_id`),
    KEY `idx_expires` (`expires_at`),
    CONSTRAINT `fk_tokens_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
  ) ENGINE = InnoDB AUTO_INCREMENT = 170 DEFAULT CHARSET = utf8mb4;

-- Copiando estrutura para tabela easybudget.user_roles
DROP TABLE IF EXISTS `user_roles`;

CREATE TABLE
  IF NOT EXISTS `user_roles` (
    `user_id` int (11) NOT NULL,
    `role_id` int (11) NOT NULL,
    `tenant_id` int (11) NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `role_id`, `tenant_id`),
    KEY `idx_role` (`role_id`),
    KEY `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_roles_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;

/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
