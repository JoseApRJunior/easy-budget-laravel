-- Criar banco
DROP DATABASE IF EXISTS easybudget;

CREATE DATABASE easybudget;

USE easybudget;

SET
    SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET
    time_zone = "-03:00";

-- ========================================
-- 1. TABELAS SEM DEPENDÊNCIAS (NÍVEL 0)
-- ========================================
SET
    FOREIGN_KEY_CHECKS = 0;

-- Resources
CREATE TABLE
    IF NOT EXISTS `resources` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `slug` varchar(100) NOT NULL,
        `in_dev` tinyint (1) NOT NULL DEFAULT 0,
        `status` enum ('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`),
        KEY `idx_status` (`status`),
        KEY `idx_slug` (`slug`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Areas of Activity
CREATE TABLE
    IF NOT EXISTS `areas_of_activity` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `slug` varchar(50) NOT NULL,
        `name` varchar(100) NOT NULL,
        `is_active` tinyint (1) DEFAULT 1,
        `created_at` datetime DEFAULT current_timestamp(),
        `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_slug` (`slug`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Professions
CREATE TABLE
    IF NOT EXISTS `professions` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `slug` varchar(50) NOT NULL,
        `name` varchar(100) NOT NULL,
        `is_active` tinyint (1) DEFAULT 1,
        `created_at` datetime DEFAULT current_timestamp(),
        `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_slug` (`slug`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Units
CREATE TABLE
    IF NOT EXISTS `units` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `slug` varchar(50) NOT NULL,
        `name` varchar(100) NOT NULL,
        `is_active` tinyint (1) DEFAULT 1,
        `created_at` datetime DEFAULT current_timestamp(),
        `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_slug` (`slug`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Roles
CREATE TABLE
    IF NOT EXISTS `roles` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` varchar(255) DEFAULT NULL,
        `created_at` datetime DEFAULT current_timestamp(),
        `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_role_name` (`name`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Permissions
CREATE TABLE
    IF NOT EXISTS `permissions` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` varchar(255) DEFAULT NULL,
        `created_at` datetime DEFAULT current_timestamp(),
        `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_permission_name` (`name`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Role Permissions
CREATE TABLE
    IF NOT EXISTS `role_permissions` (
        `role_id` int (11) NOT NULL,
        `permission_id` int (11) NOT NULL,
        KEY `idx_role` (`role_id`),
        KEY `idx_permission` (`permission_id`),
        CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Tenants
CREATE TABLE
    IF NOT EXISTS `tenants` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_tenants_name` (`name`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Budget Statuses
CREATE TABLE
    IF NOT EXISTS `budget_statuses` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `slug` varchar(20) NOT NULL,
        `name` varchar(50) NOT NULL,
        `description` text DEFAULT NULL,
        `color` varchar(7) DEFAULT NULL,
        `icon` varchar(30) DEFAULT NULL,
        `order_index` int (11) DEFAULT NULL,
        `is_active` tinyint (1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Service Statuses
CREATE TABLE
    IF NOT EXISTS `service_statuses` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `slug` varchar(20) NOT NULL,
        `name` varchar(50) NOT NULL,
        `description` text DEFAULT NULL,
        `color` varchar(7) DEFAULT NULL,
        `icon` varchar(30) DEFAULT NULL,
        `order_index` int (11) DEFAULT NULL,
        `is_active` tinyint (1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Categories
CREATE TABLE
    IF NOT EXISTS `categories` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `slug` varchar(255) DEFAULT NULL,
        `name` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Plans
CREATE TABLE
    IF NOT EXISTS `plans` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `name` varchar(50) NOT NULL,
        `slug` varchar(50) NOT NULL,
        `description` text DEFAULT NULL,
        `price` decimal(10, 2) NOT NULL,
        `status` tinyint (1) DEFAULT 1,
        `max_budgets` int (11) NOT NULL,
        `max_clients` int (11) NOT NULL,
        `features` text DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_plan_slug` (`slug`),
        KEY `idx_status` (`status`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Invoice Statuses
CREATE TABLE
    IF NOT EXISTS `invoice_statuses` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `slug` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        `color` varchar(7) NOT NULL DEFAULT '#6c757d',
        `icon` varchar(50) NOT NULL DEFAULT 'bi-circle-fill',
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- 2. TABELAS COM FOREIGN KEYS NÍVEL 1
-- ========================================
-- Users (depende de tenants)
CREATE TABLE
    IF NOT EXISTS `users` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `email` varchar(100) NOT NULL,
        `password` varchar(255) NOT NULL,
        `logo` varchar(255) DEFAULT NULL,
        `is_active` tinyint (1) DEFAULT 0,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_tenant` (`tenant_id`),
        CONSTRAINT `fk_users_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- User Roles (depende de users, roles, tenants)
CREATE TABLE
    IF NOT EXISTS `user_roles` (
        `user_id` int (11) NOT NULL,
        `role_id` int (11) NOT NULL,
        `tenant_id` int (11) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`user_id`, `role_id`, `tenant_id`),
        KEY `idx_role` (`role_id`),
        KEY `idx_tenant` (`tenant_id`),
        CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_user_roles_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
        CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Common Datas (depende de tenants, areas_of_activity, professions)
CREATE TABLE
    IF NOT EXISTS `common_datas` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `first_name` varchar(50) NOT NULL,
        `last_name` varchar(50) NOT NULL,
        `birth_date` datetime DEFAULT NULL,
        `cnpj` varchar(20) DEFAULT NULL,
        `cpf` varchar(20) DEFAULT NULL,
        `company_name` varchar(255) DEFAULT NULL,
        `description` varchar(250) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `area_of_activity_id` int (11) DEFAULT NULL,
        `profession_id` int (11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_tenant` (`tenant_id`),
        KEY `idx_area` (`area_of_activity_id`),
        KEY `idx_profession` (`profession_id`),
        CONSTRAINT `fk_common_datas_area` FOREIGN KEY (`area_of_activity_id`) REFERENCES `areas_of_activity` (`id`),
        CONSTRAINT `fk_common_datas_profession` FOREIGN KEY (`profession_id`) REFERENCES `professions` (`id`),
        CONSTRAINT `fk_common_datas_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Contacts (depende de tenants)
CREATE TABLE
    IF NOT EXISTS `contacts` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `email` varchar(100) NOT NULL,
        `email_business` varchar(100) DEFAULT NULL,
        `phone` varchar(20) NOT NULL,
        `phone_business` varchar(20) NOT NULL,
        `website` varchar(255) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_tenant` (`tenant_id`),
        CONSTRAINT `fk_contacts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Addresses (depende de tenants)
CREATE TABLE
    IF NOT EXISTS `addresses` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `address` varchar(255) DEFAULT NULL,
        `address_number` varchar(20) DEFAULT NULL,
        `neighborhood` varchar(100) DEFAULT NULL,
        `city` varchar(100) DEFAULT NULL,
        `state` varchar(2) DEFAULT NULL,
        `cep` varchar(10) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_tenant` (`tenant_id`),
        CONSTRAINT `fk_addresses_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Products (depende de tenants)
CREATE TABLE
    IF NOT EXISTS `products` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) DEFAULT NULL,
        `name` varchar(255) DEFAULT NULL,
        `description` varchar(500) DEFAULT NULL,
        `price` decimal(10, 2) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `code` varchar(50) DEFAULT NULL,
        `active` tinyint (1) DEFAULT 1,
        `image` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_tenant_code` (`tenant_id`, `code`),
        KEY `idx_tenant_code` (`tenant_id`, `code`),
        KEY `idx_tenant_name` (`tenant_id`, `name`),
        KEY `idx_tenant_active` (`tenant_id`, `active`),
        KEY `idx_tenant_price` (`tenant_id`, `price`),
        CONSTRAINT `fk_products_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- User Confirmation Tokens (depende de users, tenants)
CREATE TABLE
    IF NOT EXISTS `user_confirmation_tokens` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `user_id` int (11) NOT NULL,
        `tenant_id` int (11) NOT NULL,
        `token` varchar(64) NOT NULL,
        `expires_at` datetime NOT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_token` (`token`),
        KEY `idx_user` (`user_id`),
        KEY `idx_tenant` (`tenant_id`),
        KEY `idx_expires` (`expires_at`),
        CONSTRAINT `fk_tokens_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
        CONSTRAINT `fk_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- 3. TABELAS COM FOREIGN KEYS NÍVEL 2
-- ========================================
-- Providers (depende de users, common_datas, contacts, addresses)
CREATE TABLE
    IF NOT EXISTS `providers` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `user_id` int (11) NOT NULL,
        `common_data_id` int (11) NOT NULL,
        `contact_id` int (11) NOT NULL,
        `address_id` int (11) NOT NULL,
        `terms_accepted` tinyint (1) NOT NULL DEFAULT 0,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
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
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Customers (depende de tenants, common_datas, contacts, addresses)
CREATE TABLE
    IF NOT EXISTS `customers` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `common_data_id` int (11) NOT NULL,
        `contact_id` int (11) NOT NULL,
        `address_id` int (11) NOT NULL,
        `status` enum ('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active',
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_status` (`status`),
        KEY `fk_customers_tenant` (`tenant_id`),
        KEY `fk_customers_common_data` (`common_data_id`),
        KEY `fk_customers_contact` (`contact_id`),
        KEY `fk_customers_address` (`address_id`),
        CONSTRAINT `fk_customers_address` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
        CONSTRAINT `fk_customers_common_data` FOREIGN KEY (`common_data_id`) REFERENCES `common_datas` (`id`),
        CONSTRAINT `fk_customers_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`),
        CONSTRAINT `fk_customers_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Plan Subscriptions (depende de providers, plans, tenants)
CREATE TABLE
    IF NOT EXISTS `plan_subscriptions` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `provider_id` int (11) NOT NULL,
        `plan_id` int (11) NOT NULL,
        `tenant_id` int (11) NOT NULL,
        `status` enum ('active', 'cancelled', 'pending', 'expired') NOT NULL,
        `public_hash` varchar(64) DEFAULT NULL,
        `transaction_amount` decimal(10, 2) NOT NULL,
        `start_date` datetime NOT NULL DEFAULT current_timestamp(),
        `end_date` datetime DEFAULT NULL,
        `payment_method` varchar(50) DEFAULT NULL,
        `payment_id` varchar(50) DEFAULT NULL,
        `last_payment_date` datetime DEFAULT NULL,
        `next_payment_date` datetime DEFAULT NULL,
        `transaction_date` datetime DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_provider` (`provider_id`),
        KEY `idx_plan` (`plan_id`),
        KEY `idx_tenant` (`tenant_id`),
        KEY `idx_status` (`status`),
        KEY `idx_dates` (`start_date`, `end_date`),
        CONSTRAINT `fk_subscriptions_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`),
        CONSTRAINT `fk_subscriptions_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`),
        CONSTRAINT `fk_subscriptions_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Product Inventory (depende de tenants, products)
CREATE TABLE
    IF NOT EXISTS `product_inventory` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `product_id` int (11) NOT NULL,
        `quantity` int (11) NOT NULL DEFAULT 0,
        `min_quantity` int (11) DEFAULT 0,
        `max_quantity` int (11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_tenant` (`tenant_id`),
        KEY `idx_product` (`product_id`),
        CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
        CONSTRAINT `fk_inventory_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Inventory Movements (depende de tenants, products)
CREATE TABLE
    IF NOT EXISTS `inventory_movements` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `product_id` int (11) NOT NULL,
        `type` enum ('in', 'out') NOT NULL,
        `quantity` int (11) NOT NULL,
        `reason` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_tenant` (`tenant_id`),
        KEY `idx_product` (`product_id`),
        KEY `idx_type` (`type`),
        CONSTRAINT `fk_movements_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
        CONSTRAINT `fk_movements_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Reports (depende de tenants, users)
CREATE TABLE
    IF NOT EXISTS `reports` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `user_id` int (11) NOT NULL,
        `hash` varchar(64) DEFAULT NULL,
        `type` varchar(50) NOT NULL,
        `description` text DEFAULT NULL,
        `file_name` varchar(255) NOT NULL,
        `status` varchar(20) NOT NULL,
        `format` varchar(10) NOT NULL,
        `size` float NOT NULL DEFAULT 0,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `tenant_id` (`tenant_id`),
        KEY `idx_report_hash` (`hash`, `tenant_id`),
        CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
        CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Activities (depende de tenants, users)
CREATE TABLE
    IF NOT EXISTS `activities` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `user_id` int (11) NOT NULL,
        `action_type` varchar(50) NOT NULL,
        `entity_type` varchar(50) NOT NULL,
        `entity_id` int (11) NOT NULL,
        `description` varchar(100) DEFAULT NULL,
        `metadata` text DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `tenant_id` (`tenant_id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
        CONSTRAINT `activities_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Provider Credentials (depende de providers, tenants)
CREATE TABLE
    IF NOT EXISTS `provider_credentials` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `provider_id` INT NOT NULL,
        `tenant_id` INT NOT NULL,
        `payment_gateway` VARCHAR(50) NOT NULL DEFAULT 'mercadopago',
        `user_id_gateway` VARCHAR(50) NOT NULL,
        `access_token_encrypted` TEXT NOT NULL,
        `refresh_token_encrypted` TEXT NOT NULL,
        `public_key` VARCHAR(50) NOT NULL,
        `expires_in` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (provider_id) REFERENCES providers (id),
        FOREIGN KEY (tenant_id) REFERENCES tenants (id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Supports (depende de tenants)
CREATE TABLE
    IF NOT EXISTS `supports` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) DEFAULT NULL,
        `first_name` varchar(255) DEFAULT NULL,
        `last_name` varchar(255) DEFAULT NULL,
        `email` varchar(255) DEFAULT NULL,
        `subject` varchar(255) DEFAULT NULL,
        `message` text DEFAULT NULL,
        `status` enum (
            'ABERTO',
            'RESPONDIDO',
            'RESOLVIDO',
            'FECHADO',
            'EM_ANDAMENTO',
            'AGUARDANDO_RESPOSTA',
            'CANCELADO'
        ) NOT NULL DEFAULT 'ABERTO',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_tenant` (`tenant_id`),
        CONSTRAINT `fk_supports_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ========================================
-- 4. TABELAS COM FOREIGN KEYS NÍVEL 3
-- ========================================
-- Budgets (depende de tenants, customers, budget_statuses)
CREATE TABLE
    IF NOT EXISTS `budgets` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `customer_id` int (11) NOT NULL,
        `budget_statuses_id` int (11) NOT NULL,
        `code` varchar(50) NOT NULL,
        `due_date` datetime DEFAULT NULL,
        `discount` decimal(10, 2) DEFAULT 0.00,
        `total` decimal(10, 2) DEFAULT 0.00,
        `description` text DEFAULT NULL,
        `payment_terms` text DEFAULT NULL,
        `attachment` blob DEFAULT NULL,
        `history` text DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `fk_budgets_customer` (`customer_id`),
        KEY `fk_budgets_budget_statuses` (`budget_statuses_id`),
        KEY `idx_budget_filters` (`tenant_id`, `due_date`, `created_at`),
        CONSTRAINT `fk_budgets_budget_statuses` FOREIGN KEY (`budget_statuses_id`) REFERENCES `budget_statuses` (`id`),
        CONSTRAINT `fk_budgets_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
        CONSTRAINT `fk_budgets_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Merchant Orders Mercado Pago (depende de providers, tenants, plan_subscriptions)
CREATE TABLE
    IF NOT EXISTS `merchant_orders_mercado_pago` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `merchant_order_id` varchar(50) NOT NULL,
        `provider_id` int (11) NOT NULL,
        `tenant_id` int (11) NOT NULL,
        `plan_subscription_id` int (11) NOT NULL,
        `status` enum (
            'opened',
            'closed',
            'expired',
            'cancelled',
            'processing'
        ) NOT NULL,
        `order_status` enum (
            'payment_required',
            'payment_in_process',
            'reverted',
            'paid',
            'patially_reverted',
            'patially_paid',
            'partially_in_process',
            'undefined',
            'expired'
        ) NOT NULL,
        `total_amount` decimal(10, 2) NOT NULL,
        `created_at` datetime DEFAULT current_timestamp(),
        `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_provider` (`provider_id`),
        KEY `idx_tenant` (`tenant_id`),
        KEY `idx_subscription` (`plan_subscription_id`),
        CONSTRAINT `fk_merchant_orders_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`),
        CONSTRAINT `fk_merchant_orders_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
        CONSTRAINT `fk_merchant_orders_subscription` FOREIGN KEY (`plan_subscription_id`) REFERENCES `plan_subscriptions` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Payment Mercado Pago Plans (depende de providers, tenants, plan_subscriptions)
CREATE TABLE
    IF NOT EXISTS `payment_mercado_pago_plans` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `payment_id` varchar(50) NOT NULL,
        `provider_id` int (11) NOT NULL,
        `tenant_id` int (11) NOT NULL,
        `plan_subscription_id` int (11) NOT NULL,
        `status` enum (
            'approved',
            'pending',
            'authorized',
            'in_process',
            'in_mediation',
            'rejected',
            'cancelled',
            'refunded',
            'charged_back',
            'recovered'
        ) NULL DEFAULT NULL,
        `payment_method` varchar(50) NOT NULL,
        `transaction_amount` decimal(10, 2) NOT NULL,
        `transaction_date` datetime DEFAULT NULL,
        `created_at` datetime DEFAULT current_timestamp(),
        `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_provider` (`provider_id`),
        KEY `idx_tenant` (`tenant_id`),
        KEY `idx_subscription` (`plan_subscription_id`),
        CONSTRAINT `fk_payment_plans_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`),
        CONSTRAINT `fk_payment_plans_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
        CONSTRAINT `fk_payment_plans_subscription` FOREIGN KEY (`plan_subscription_id`) REFERENCES `plan_subscriptions` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- 5. TABELAS COM FOREIGN KEYS NÍVEL 4
-- ========================================
-- Services (depende de tenants, budgets, categories, service_statuses)
CREATE TABLE
    IF NOT EXISTS `services` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `budget_id` int (11) NOT NULL,
        `category_id` int (11) NOT NULL,
        `service_statuses_id` int (11) NOT NULL,
        `code` varchar(50) NOT NULL,
        `description` text DEFAULT NULL,
        `discount` decimal(10, 2) NOT NULL,
        `total` decimal(10, 2) NOT NULL,
        `due_date` datetime DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `tenant_id` (`tenant_id`),
        KEY `budget_id` (`budget_id`),
        KEY `category_id` (`category_id`),
        KEY `service_statuses_id` (`service_statuses_id`),
        CONSTRAINT `services_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
        CONSTRAINT `services_ibfk_2` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`),
        CONSTRAINT `services_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
        CONSTRAINT `services_ibfk_4` FOREIGN KEY (`service_statuses_id`) REFERENCES `service_statuses` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Invoices (depende de tenants, customers, services, invoice_statuses)
CREATE TABLE
    IF NOT EXISTS `invoices` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `service_id` int (11) NOT NULL,
        `customer_id` int (11) NOT NULL,
        `invoice_statuses_id` INT (11) NULL,
        `code` varchar(20) NOT NULL,
        `public_hash` varchar(64) UNIQUE NULL,
        `subtotal` decimal(10, 2) NOT NULL,
        `discount` decimal(10, 2) DEFAULT 0.00,
        `total` decimal(10, 2) NOT NULL,
        `due_date` date NOT NULL,
        `payment_method` varchar(50) DEFAULT NULL,
        `payment_id` varchar(50) DEFAULT NULL,
        `transaction_amount` decimal(10, 2) DEFAULT NULL,
        `transaction_date` datetime DEFAULT NULL,
        `notes` text DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `code` (`tenant_id`, `code`),
        KEY `tenant_id` (`tenant_id`),
        KEY `service_id` (`service_id`),
        KEY `customer_id` (`customer_id`),
        CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
        CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
        CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
        CONSTRAINT `invoices_fk_status` FOREIGN KEY (`invoice_statuses_id`) REFERENCES `invoice_statuses` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Schedules (depende de tenants, services, user_confirmation_tokens)
CREATE TABLE
    IF NOT EXISTS `schedules` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `tenant_id` INT NOT NULL,
        `service_id` INT NOT NULL,
        `user_confirmation_token_id` INT NOT NULL,
        `start_date_time` DATETIME NOT NULL,
        `end_date_time` DATETIME NULL,
        `location` varchar(255) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
        FOREIGN KEY (user_confirmation_token_id) REFERENCES user_confirmation_tokens (id) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- 6. TABELAS COM FOREIGN KEYS NÍVEL 5
-- ========================================
-- Service Items (depende de tenants, services, products)
CREATE TABLE
    IF NOT EXISTS `service_items` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `service_id` int (11) NOT NULL,
        `product_id` int (11) NOT NULL,
        `quantity` int (11) NOT NULL DEFAULT 1,
        `unit_value` decimal(10, 2) NOT NULL,
        `total` decimal(10, 2) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `product_id` (`product_id`),
        KEY `service_id` (`service_id`),
        KEY `tenant_id` (`tenant_id`),
        CONSTRAINT `service_items_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
        CONSTRAINT `service_items_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
        CONSTRAINT `service_items_ibfk_3` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Invoice Items (depende de tenants, invoices, products)
CREATE TABLE
    IF NOT EXISTS `invoice_items` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `invoice_id` int (11) NOT NULL,
        `product_id` int (11) NOT NULL,
        `description` text NOT NULL,
        `quantity` int (11) NOT NULL,
        `unit_price` decimal(10, 2) NOT NULL,
        `total` decimal(10, 2) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `tenant_id` (`tenant_id`),
        KEY `invoice_id` (`invoice_id`),
        KEY `product_id` (`product_id`),
        CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
        CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
        CONSTRAINT `invoice_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Payment Mercado Pago Invoices (depende de tenants, invoices)
CREATE TABLE
    IF NOT EXISTS `payment_mercado_pago_invoices` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `payment_id` varchar(50) NOT NULL,
        `tenant_id` int (11) NOT NULL,
        `invoice_id` int (11) NOT NULL,
        `status` enum (
            'approved',
            'pending',
            'authorized',
            'in_process',
            'in_mediation',
            'rejected',
            'cancelled',
            'refunded',
            'charged_back',
            'recovered',
            'failure',
            'partially_refunded'
        ) NULL DEFAULT 'pending',
        `payment_method` varchar(50) NOT NULL,
        `transaction_amount` decimal(10, 2) NOT NULL,
        `transaction_date` datetime DEFAULT NULL,
        `created_at` datetime DEFAULT current_timestamp(),
        `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_payment_invoice` (`payment_id`, `invoice_id`),
        KEY `idx_payment_id` (`payment_id`),
        KEY `idx_invoice` (`invoice_id`),
        KEY `idx_tenant` (`tenant_id`),
        KEY `idx_status` (`status`),
        KEY `idx_transaction_date` (`transaction_date`),
        CONSTRAINT `fk_payment_invoices_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_payment_invoices_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

COMMIT;
