-- --------------------------------------------------------
-- Servidor:                     127.0.0.1
-- Versão do servidor:           10.4.32-MariaDB - mariadb.org binary distribution
-- OS do Servidor:               Win64
-- HeidiSQL Versão:              12.11.0.7065
-- --------------------------------------------------------
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;

/*!40101 SET NAMES utf8 */;

/*!50503 SET NAMES utf8mb4 */;

/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;

/*!40103 SET TIME_ZONE='+00:00' */;

/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Copiando estrutura do banco de dados para easybudget
DROP DATABASE IF EXISTS `easybudget`;

CREATE DATABASE IF NOT EXISTS `easybudget` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `easybudget`;

-- Copiando estrutura para tabela easybudget.activities
DROP TABLE IF EXISTS `activities`;

CREATE TABLE
    IF NOT EXISTS `activities` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `user_id` int (11) NOT NULL,
        `action_type` varchar(50) NOT NULL,
        `entity_type` varchar(50) NOT NULL,
        `entity_id` int (11) NOT NULL,
        `description` varchar(200) DEFAULT NULL,
        `metadata` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `tenant_id` (`tenant_id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
        CONSTRAINT `activities_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ) ENGINE = InnoDB AUTO_INCREMENT = 475 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando estrutura para tabela easybudget.addresses
DROP TABLE IF EXISTS `addresses`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 29 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.addresses: ~6 rows (aproximadamente)
INSERT INTO
    `addresses` (
        `id`,
        `tenant_id`,
        `address`,
        `address_number`,
        `neighborhood`,
        `city`,
        `state`,
        `cep`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        1,
        'rua dos administradores',
        '123',
        'bairro dos administradores',
        'cidade dos administradores',
        'SP',
        '12345678',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        2,
        2,
        'rua dos gerentes',
        '123',
        'bairro dos gerentes',
        'cidade dos gerentes',
        'SP',
        '12345678',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        3,
        3,
        'rua dos prestadores',
        '123',
        'bairro dos prestadores',
        'cidade dos prestadores',
        'SP',
        '12.345-678',
        '2025-05-29 09:44:31',
        '2025-05-31 13:46:22'
    ),
    (
        4,
        3,
        'rua dos usuários',
        '123',
        'bairro dos usuários',
        'cidade dos usuários',
        'SP',
        '12345678',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        27,
        37,
        'Rua Mosqueteirinho Amarelo',
        '130',
        'Jardim Santa Efigênia',
        'Arapongas',
        'PR',
        '86.706-579',
        '2025-06-30 08:48:54',
        '2025-07-01 11:52:16'
    ),
    (
        28,
        37,
        'Rua da Obra',
        '100',
        'Bairro Central',
        'Cidade Exemplo',
        'SP',
        '12345-000',
        '2025-06-30 10:51:41',
        '2025-06-30 10:51:41'
    );

-- Copiando estrutura para tabela easybudget.areas_of_activity
DROP TABLE IF EXISTS `areas_of_activity`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 84 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.areas_of_activity: ~83 rows (aproximadamente)
INSERT INTO
    `areas_of_activity` (
        `id`,
        `slug`,
        `name`,
        `is_active`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        'others',
        'Outros',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        2,
        'aerospace',
        'Aeroespacial',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        3,
        'agriculture',
        'Agricultura',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        4,
        'food_and_beverage',
        'Alimentos e Bebidas',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        5,
        'animation',
        'Animação',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        6,
        'analytics',
        'Análise de Dados',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        7,
        'mobile_app',
        'Aplicativo Móvel',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        8,
        'architecture',
        'Arquitetura',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        9,
        'art',
        'Arte',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        10,
        'plan-subscription',
        'Assinatura de Plano',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        11,
        'automotive',
        'Automotivo',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        12,
        'biotechnology',
        'Biotecnologia',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        13,
        'blockchain',
        'Blockchain',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        14,
        'venture_capital',
        'Capital de Risco',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        15,
        'supply_chain',
        'Cadeia de Suprimentos',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        16,
        'film',
        'Cinema',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        17,
        'data_science',
        'Ciência de Dados',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        18,
        'retail',
        'Comércio',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        19,
        'e_commerce',
        'Comércio Eletrônico',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        20,
        'cloud_computing',
        'Computação em Nuvem',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        21,
        'construction',
        'Construção',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        22,
        'consulting',
        'Consultoria',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        23,
        'accounting',
        'Contabilidade',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        24,
        'parts-control',
        'Controle de Peças',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        25,
        'web_development',
        'Desenvolvimento Web',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        26,
        'design',
        'Design',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        27,
        'interior_design',
        'Design de Interiores',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        28,
        'education',
        'Educação',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        29,
        'energy',
        'Energia',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        30,
        'e_learning',
        'Ensino a Distância',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        31,
        'entertainment',
        'Entretenimento',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        32,
        'sports',
        'Esportes',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        33,
        'pharmaceuticals',
        'Farmacêutica',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        34,
        'billing',
        'Faturamento',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        35,
        'fintech',
        'Fintech',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        36,
        'finance',
        'Financeiro',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        37,
        'photography',
        'Fotografia',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        38,
        'franchise',
        'Franquia',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        39,
        'team-management',
        'Gestão de Equipe',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        40,
        'waste_management',
        'Gestão de Resíduos',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        41,
        'government',
        'Governo',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        42,
        'hardware',
        'Hardware',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        43,
        'hospitality',
        'Hospitalidade',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        44,
        'real_estate',
        'Imobiliário',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        45,
        'industrial',
        'Indústria',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        46,
        'whatsapp-integration',
        'Integração WhatsApp',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        47,
        'artificial_intelligence',
        'Inteligência Artificial',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        48,
        'gaming',
        'Jogos',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        49,
        'journalism',
        'Jornalismo',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        50,
        'logistics',
        'Logística',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        51,
        'manufacturing',
        'Manufatura',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        52,
        'marketing',
        'Marketing',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        53,
        'digital_marketing',
        'Marketing Digital',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        54,
        'environment',
        'Meio Ambiente',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        55,
        'media',
        'Mídia',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        56,
        'mining',
        'Mineração',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        57,
        'music',
        'Música',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        58,
        'non_profit',
        'Organizações Sem Fins Lucrativos',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        59,
        'budgets',
        'Orçamentos',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        60,
        'research',
        'Pesquisa',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        61,
        'biotechnology_research',
        'Pesquisa em Biotecnologia',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        62,
        'private_equity',
        'Private Equity',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        63,
        'publishing',
        'Publicação',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        64,
        'advertising',
        'Publicidade',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        65,
        'chemicals',
        'Química',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        66,
        'recycling',
        'Reciclagem',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        67,
        'public_relations',
        'Relações Públicas',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        68,
        'health',
        'Saúde',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        69,
        'security',
        'Segurança',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        70,
        'biotechnology_services',
        'Serviços de Biotecnologia',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        71,
        'consulting_services',
        'Serviços de Consultoria',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        72,
        'mining_services',
        'Serviços de Mineração',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        73,
        'healthcare_services',
        'Serviços de Saúde',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        74,
        'telecommunications_services',
        'Serviços de Telecomunicações',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        75,
        'tourism_services',
        'Serviços de Turismo',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        76,
        'travel_services',
        'Serviços de Viagens',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        77,
        'education_services',
        'Serviços Educacionais',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        78,
        'software',
        'Software',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        79,
        'telecommunications',
        'Telecomunicações',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        80,
        'outsourcing',
        'Terceirização',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        81,
        'technology',
        'Tecnologia',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        82,
        'vocational_training',
        'Treinamento Profissional',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        83,
        'travel',
        'Turismo',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    );

-- Copiando estrutura para tabela easybudget.budgets
DROP TABLE IF EXISTS `budgets`;

CREATE TABLE
    IF NOT EXISTS `budgets` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `customer_id` int (11) NOT NULL,
        `budget_statuses_id` int (11) NOT NULL,
        `user_confirmation_token_id` int (11) DEFAULT NULL,
        `code` varchar(50) NOT NULL,
        `due_date` datetime DEFAULT NULL,
        `total` decimal(10, 2) DEFAULT 0.00,
        `description` text DEFAULT NULL,
        `payment_terms` text DEFAULT NULL,
        `attachment` blob DEFAULT NULL,
        `history` text DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `pdf_verification_hash` varchar(64) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `fk_budgets_customer` (`customer_id`),
        KEY `fk_budgets_budget_statuses` (`budget_statuses_id`),
        KEY `idx_budget_filters` (`tenant_id`, `due_date`, `created_at`),
        KEY `budgets_user_confirmation_token_id_foreign` (`user_confirmation_token_id`),
        CONSTRAINT `budgets_user_confirmation_token_id_foreign` FOREIGN KEY (`user_confirmation_token_id`) REFERENCES `user_confirmation_tokens` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
        CONSTRAINT `fk_budgets_budget_statuses` FOREIGN KEY (`budget_statuses_id`) REFERENCES `budget_statuses` (`id`),
        CONSTRAINT `fk_budgets_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
        CONSTRAINT `fk_budgets_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB AUTO_INCREMENT = 30 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.budgets: ~8 rows (aproximadamente)
INSERT INTO
    `budgets` (
        `id`,
        `tenant_id`,
        `customer_id`,
        `budget_statuses_id`,
        `user_confirmation_token_id`,
        `code`,
        `due_date`,
        `total`,
        `description`,
        `payment_terms`,
        `attachment`,
        `history`,
        `created_at`,
        `updated_at`,
        `pdf_verification_hash`
    )
VALUES
    (
        1,
        3,
        1,
        1,
        NULL,
        '202505290001',
        '2025-12-31 00:00:00',
        850.00,
        'Orçamento para reforma',
        'Pagamento em 2x',
        NULL,
        NULL,
        '2025-05-29 09:44:31',
        '2025-06-03 14:05:48',
        NULL
    ),
    (
        2,
        3,
        1,
        2,
        NULL,
        '202505290002',
        '2025-12-31 00:00:00',
        300.00,
        'Orçamento para pintura',
        'Pagamento à vista',
        NULL,
        NULL,
        '2025-05-29 09:44:31',
        '2025-06-27 10:54:44',
        NULL
    ),
    (
        3,
        3,
        1,
        3,
        NULL,
        '202505290003',
        '2023-12-31 00:00:00',
        75.00,
        'Orçamento para elétrica',
        'Pagamento em 3x',
        NULL,
        NULL,
        '2025-05-29 09:44:31',
        '2025-05-30 08:38:18',
        NULL
    ),
    (
        4,
        3,
        1,
        3,
        NULL,
        '202505290004',
        '2023-12-31 00:00:00',
        625.00,
        'Orçamento para hidráulica',
        'Pagamento em 4x',
        NULL,
        NULL,
        '2025-05-29 09:44:31',
        '2025-06-02 09:23:11',
        NULL
    ),
    (
        5,
        3,
        1,
        1,
        NULL,
        '202505290005',
        '2023-12-31 00:00:00',
        0.00,
        'Orçamento para construção',
        'Pagamento em 5x',
        NULL,
        NULL,
        '2025-05-29 09:44:31',
        '2025-05-30 08:34:54',
        NULL
    ),
    (
        27,
        3,
        1,
        1,
        NULL,
        '202506020006',
        '2025-06-02 00:00:00',
        0.00,
        'Teste Orçamento',
        '2 x cartao',
        _binary '',
        '',
        '2025-06-02 09:39:50',
        '2025-06-02 09:39:50',
        NULL
    ),
    (
        28,
        3,
        1,
        3,
        NULL,
        '202506020007',
        '2025-06-04 00:00:00',
        1581.47,
        'Pintura completa de Casa',
        '',
        _binary '',
        '',
        '2025-06-02 09:44:54',
        '2025-06-27 11:07:25',
        NULL
    ),
    (
        29,
        37,
        6,
        3,
        165,
        '202506300001',
        '2025-09-30 00:00:00',
        46648.00,
        'Obra casa da Carla, Tijolo 11.5/19/24::1.000\n11.5/14/24::2.500\nTabua 30::25 peça\n..............15::15pec\nSarrafo eucalipto::20 peça\nCimento. :: 50 saco\nAreia:6 MT\nPedrisco ::6 Mt',
        'Prazo da obra: 3 meses',
        NULL,
        '\n',
        '2025-06-30 10:51:41',
        '2025-07-11 11:20:54',
        'b9b60f7edbf1932f6913d2b1dab93810f93a5404'
    );

-- Copiando estrutura para tabela easybudget.budget_statuses
DROP TABLE IF EXISTS `budget_statuses`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 9 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.budget_statuses: ~7 rows (aproximadamente)
INSERT INTO
    `budget_statuses` (
        `id`,
        `slug`,
        `name`,
        `description`,
        `color`,
        `icon`,
        `order_index`,
        `is_active`,
        `created_at`
    )
VALUES
    (
        1,
        'DRAFT',
        'Rascunho',
        'Orçamento em elaboração, permite modificações',
        '#6c757d',
        'bi-pencil-square',
        1,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        2,
        'PENDING',
        'Pendente',
        'Aguardando aprovação do cliente',
        '#ffc107',
        'bi-clock',
        2,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        3,
        'APPROVED',
        'Aprovado',
        'Orçamento aprovado pelo cliente',
        '#28a745',
        'bi-check-circle',
        3,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        4,
        'COMPLETED',
        'Concluído',
        'Todos os serviços foram realizados',
        '#28a745',
        'bi-check2-all',
        5,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        5,
        'REJECTED',
        'Rejeitado',
        'Orçamento não aprovado pelo cliente',
        '#dc3545',
        'bi-x-circle',
        6,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        6,
        'CANCELLED',
        'Cancelado',
        'Orçamento cancelado após aprovação',
        '#dc3545',
        'bi-slash-circle',
        7,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        7,
        'EXPIRED',
        'Expirado',
        'Prazo de validade do orçamento expirado',
        '#dc3545',
        'bi-calendar-x',
        8,
        1,
        '2025-05-29 15:44:31'
    );

-- Copiando estrutura para tabela easybudget.categories
DROP TABLE IF EXISTS `categories`;

CREATE TABLE
    IF NOT EXISTS `categories` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `slug` varchar(255) DEFAULT NULL,
        `name` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB AUTO_INCREMENT = 28 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.categories: ~26 rows (aproximadamente)
INSERT INTO
    `categories` (`id`, `slug`, `name`, `created_at`, `updated_at`)
VALUES
    (
        1,
        'carpentry',
        'Carpintaria',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        2,
        'construction_civil',
        'Construção Civil',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        3,
        'construction_furniture',
        'Construção de Móveis',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        4,
        'construction_doors',
        'Construção de Portas',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        5,
        'construction_electric',
        'Elétrica',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        6,
        'construction_hydraulic',
        'Hidráulica',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        7,
        'installation_pumps',
        'Instalação de Bombas',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        8,
        'installation_pipes',
        'Instalação de Tubulações',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        9,
        'installation_glass',
        'Instalação de Vidros',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        10,
        'electrical_installation',
        'Instalação Elétrica',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        11,
        'maintenance_pumps',
        'Manutenção de Bombas',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        12,
        'maintenance_vehicles',
        'Manutenção de Veículos',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        13,
        'maintenance_electric',
        'Manutenção Elétrica',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        14,
        'mechanical',
        'Mecânica',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        15,
        'masonry',
        'Obra de Alvenaria',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        16,
        'painting',
        'Pintura',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        17,
        'painting_wall',
        'Pintura de Parede',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        18,
        'painting_ceiling',
        'Pintura de Teto',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        19,
        'reforms',
        'Reformas',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        20,
        'engine_repair',
        'Reparo de Motores',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        21,
        'repair_furniture',
        'Reparo de Móveis',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        22,
        'repair_doors',
        'Reparo de Portas',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        23,
        'glass_repair',
        'Reparo de Vidros',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        24,
        'metal_working',
        'Serralheria',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        25,
        'welding',
        'Vidraceiro',
        '2025-05-29 15:44:31',
        '2025-07-01 14:43:34'
    ),
    (
        27,
        'outers',
        'Outros',
        '2025-06-04 15:34:06',
        '2025-07-01 14:44:19'
    );

-- Copiando estrutura para tabela easybudget.common_datas
DROP TABLE IF EXISTS `common_datas`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 30 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.common_datas: ~6 rows (aproximadamente)
INSERT INTO
    `common_datas` (
        `id`,
        `tenant_id`,
        `first_name`,
        `last_name`,
        `birth_date`,
        `cnpj`,
        `cpf`,
        `company_name`,
        `description`,
        `created_at`,
        `updated_at`,
        `area_of_activity_id`,
        `profession_id`
    )
VALUES
    (
        1,
        1,
        'admin',
        'admin',
        '1990-01-01 00:00:00',
        '12345678901234',
        '12345678901',
        'EasyBudget',
        'Administrador do sistema',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31',
        81,
        17
    ),
    (
        2,
        2,
        'manager',
        'manager',
        '1990-01-01 00:00:00',
        '12345678901234',
        '12345678901',
        'EasyBudget',
        'Gerente do sistema',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31',
        81,
        17
    ),
    (
        3,
        3,
        'teste',
        'teste',
        '1990-01-01 00:00:00',
        '12.345.678/9012-34',
        '123.456.789-01',
        'EasyBudget',
        'Prestador do sistema',
        '2025-05-29 09:44:31',
        '2025-05-31 13:47:06',
        59,
        17
    ),
    (
        4,
        3,
        'Ediana',
        'Aparecida Liara da Paz',
        '1990-01-01 00:00:00',
        '12345678901234',
        '12345678901',
        'Empresa Teste',
        'Usuário do sistema',
        '2025-05-29 09:44:31',
        '2025-06-23 11:21:52',
        81,
        17
    ),
    (
        28,
        37,
        'Ivan',
        'Henrique Ramos',
        '2025-07-01 00:00:00',
        '56.392.530/0001-87',
        '111.111.111-11',
        'Equipe IR Costruções',
        '',
        '2025-06-30 08:48:54',
        '2025-07-01 11:52:16',
        21,
        1
    ),
    (
        29,
        37,
        'Carla',
        'Teste',
        NULL,
        NULL,
        NULL,
        NULL,
        NULL,
        '2025-06-30 10:51:41',
        '2025-06-30 10:51:41',
        NULL,
        NULL
    );

-- Copiando estrutura para tabela easybudget.contacts
DROP TABLE IF EXISTS `contacts`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 29 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.contacts: ~6 rows (aproximadamente)
INSERT INTO
    `contacts` (
        `id`,
        `tenant_id`,
        `email`,
        `email_business`,
        `phone`,
        `phone_business`,
        `website`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        1,
        'admin@easybudget.net.br',
        'admin@easybudget.net.br',
        '43999590945',
        '43999590945',
        'https://easybudget.net.br',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        2,
        2,
        'manager@easybudget.net.br',
        'manager@easybudget.net.br',
        '43999590945',
        '43999590945',
        'https://easybudget.net.br',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        3,
        3,
        'teste@easybudget.net.br',
        'teste@easybudget.net.br',
        '(43) 99959-0945',
        '(43) 99959-0945',
        'https://easybudget.net.br',
        '2025-05-29 09:44:31',
        '2025-06-02 09:38:24'
    ),
    (
        4,
        3,
        'juniorklan.ju@gmail.comm',
        'juniorklan.ju@gmail.com',
        '43999590945',
        '43999590945',
        'https://easybudget.net.br',
        '2025-05-29 09:44:31',
        '2025-07-03 10:42:35'
    ),
    (
        27,
        37,
        'ivanhenriqueramosh@gmail.com',
        'ivanhenriqueramosh@gmail.com',
        '(43) 99863-4201',
        '(43) 99863-4201',
        '',
        '2025-06-30 08:48:54',
        '2025-07-01 11:52:16'
    ),
    (
        28,
        37,
        'juniorklan.ju@gmail.com',
        NULL,
        '11987654321',
        '',
        NULL,
        '2025-06-30 10:51:41',
        '2025-07-03 10:42:32'
    );

-- Copiando estrutura para tabela easybudget.customers
DROP TABLE IF EXISTS `customers`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 7 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.customers: ~2 rows (aproximadamente)
INSERT INTO
    `customers` (
        `id`,
        `tenant_id`,
        `common_data_id`,
        `contact_id`,
        `address_id`,
        `status`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        3,
        4,
        4,
        4,
        'active',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        6,
        37,
        29,
        28,
        28,
        'active',
        '2025-06-30 10:51:41',
        '2025-06-30 10:51:41'
    );

-- Copiando estrutura para tabela easybudget.inventory_movements
DROP TABLE IF EXISTS `inventory_movements`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 3 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.inventory_movements: ~0 rows (aproximadamente)
INSERT INTO
    `inventory_movements` (
        `id`,
        `tenant_id`,
        `product_id`,
        `type`,
        `quantity`,
        `reason`,
        `created_at`
    )
VALUES
    (2, 3, 1, 'in', 5, NULL, '2025-05-29 13:39:11');

-- Copiando estrutura para tabela easybudget.merchant_orders_mercado_pago
DROP TABLE IF EXISTS `merchant_orders_mercado_pago`;

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
        CONSTRAINT `fk_merchant_orders_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`),
        CONSTRAINT `fk_merchant_orders_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.merchant_orders_mercado_pago: ~0 rows (aproximadamente)
-- Copiando estrutura para tabela easybudget.payments_mercado_pago
DROP TABLE IF EXISTS `payments_mercado_pago`;

CREATE TABLE
    IF NOT EXISTS `payments_mercado_pago` (
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
            'recovered',
            'null'
        ) NOT NULL,
        `payment_method` varchar(50) NOT NULL,
        `transaction_amount` decimal(10, 2) NOT NULL,
        `created_at` datetime DEFAULT current_timestamp(),
        `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_provider` (`provider_id`),
        KEY `idx_tenant` (`tenant_id`),
        CONSTRAINT `fk_payments_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`),
        CONSTRAINT `fk_payments_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.payments_mercado_pago: ~0 rows (aproximadamente)
-- Copiando estrutura para tabela easybudget.permissions
DROP TABLE IF EXISTS `permissions`;

CREATE TABLE
    IF NOT EXISTS `permissions` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` varchar(255) DEFAULT NULL,
        `created_at` datetime DEFAULT current_timestamp(),
        `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_permission_name` (`name`)
    ) ENGINE = InnoDB AUTO_INCREMENT = 6 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.permissions: ~5 rows (aproximadamente)
INSERT INTO
    `permissions` (
        `id`,
        `name`,
        `description`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        'create_user',
        'Criar novos usuários',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        2,
        'edit_user',
        'Editar usuários existentes',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        3,
        'delete_user',
        'Excluir usuários',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        4,
        'view_reports',
        'Visualizar relatórios',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        5,
        'manage_budget',
        'Gerenciar orçamentos',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    );

-- Copiando estrutura para tabela easybudget.plans
DROP TABLE IF EXISTS `plans`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.plans: ~3 rows (aproximadamente)
INSERT INTO
    `plans` (
        `id`,
        `name`,
        `slug`,
        `description`,
        `price`,
        `status`,
        `max_budgets`,
        `max_clients`,
        `features`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        'Plano Free',
        'free',
        'Comece com simplicidade e sem custos!',
        0.00,
        1,
        3,
        1,
        '["Acesso a recursos básicos","Até 3 orçamentos por mês","1 Cliente por mês"]',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        2,
        'Plano Básico',
        'basic',
        'Gerencie seus orçamentos com eficiência!',
        15.00,
        1,
        15,
        5,
        '["Acesso a recursos básicos","Até 15 orçamentos por mês","5 Clientes por mês","Relatórios básicos"]',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        3,
        'Plano Premium',
        'premium',
        'A solução completa para sua gestão!',
        25.00,
        1,
        -1,
        -1,
        '["Acesso a todos os recursos","Orçamentos ilimitados","Clientes ilimitados","Relatórios avançados","Integração com pagamentos","Gerencimento de projetos"]',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    );

-- Copiando estrutura para tabela easybudget.plan_subscriptions
DROP TABLE IF EXISTS `plan_subscriptions`;

CREATE TABLE
    IF NOT EXISTS `plan_subscriptions` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `provider_id` int (11) NOT NULL,
        `plan_id` int (11) NOT NULL,
        `tenant_id` int (11) NOT NULL,
        `status` enum ('active', 'cancelled', 'pending', 'expired') NOT NULL,
        `transaction_amount` decimal(10, 2) NOT NULL,
        `start_date` datetime NOT NULL DEFAULT current_timestamp(),
        `end_date` datetime DEFAULT NULL,
        `payment_method` varchar(50) DEFAULT NULL,
        `payment_id` varchar(50) DEFAULT NULL,
        `last_payment_date` datetime DEFAULT NULL,
        `next_payment_date` datetime DEFAULT NULL,
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
    ) ENGINE = InnoDB AUTO_INCREMENT = 10 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.plan_subscriptions: ~4 rows (aproximadamente)
INSERT INTO
    `plan_subscriptions` (
        `id`,
        `provider_id`,
        `plan_id`,
        `tenant_id`,
        `status`,
        `transaction_amount`,
        `start_date`,
        `end_date`,
        `payment_method`,
        `payment_id`,
        `last_payment_date`,
        `next_payment_date`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        1,
        3,
        1,
        'pending',
        0.00,
        '2025-05-29 00:00:00',
        '2025-07-04 00:00:00',
        NULL,
        NULL,
        NULL,
        NULL,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        2,
        2,
        2,
        2,
        'pending',
        0.00,
        '2025-05-29 00:00:00',
        '2025-07-04 00:00:00',
        NULL,
        NULL,
        NULL,
        NULL,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        3,
        3,
        3,
        3,
        'active',
        10.00,
        '2025-05-29 00:00:00',
        '2025-07-04 00:00:00',
        NULL,
        NULL,
        NULL,
        NULL,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        9,
        19,
        1,
        37,
        'active',
        0.00,
        '2025-06-30 00:00:00',
        NULL,
        'free',
        NULL,
        NULL,
        NULL,
        '2025-06-30 08:48:54',
        '2025-06-30 08:48:54'
    );

-- Copiando estrutura para tabela easybudget.products
DROP TABLE IF EXISTS `products`;

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
        KEY `idx_tenant_price` (`tenant_id`, `price`)
    ) ENGINE = InnoDB AUTO_INCREMENT = 57 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.products: ~22 rows (aproximadamente)
INSERT INTO
    `products` (
        `id`,
        `tenant_id`,
        `name`,
        `description`,
        `price`,
        `created_at`,
        `updated_at`,
        `code`,
        `active`,
        `image`
    )
VALUES
    (
        1,
        3,
        'Lata de Tinta Acrílica 18L',
        'Tinta acrílica de alta qualidade, ideal para pintura de paredes internas e externas.',
        220.50,
        '2025-05-29 15:44:31',
        '2025-05-29 13:18:41',
        'TINT001',
        1,
        ''
    ),
    (
        2,
        3,
        'Lata de Tinta Látex 15L',
        'Tinta látex premium com excelente cobertura e durabilidade para ambientes residenciais.',
        199.99,
        '2025-05-29 15:44:31',
        '2025-05-29 13:18:42',
        'TINT002',
        1,
        ''
    ),
    (
        3,
        3,
        'Lata de Tinta Epóxi 5L',
        'Tinta epóxi resistente, indicada para pisos industriais e áreas de alto tráfego.',
        320.00,
        '2025-05-29 15:44:31',
        '2025-05-29 13:18:42',
        'TINT003',
        1,
        ''
    ),
    (
        4,
        3,
        'Lixadeira Orbital',
        'Equipamento elétrico para lixamento de superfícies, preparando-as para a pintura.',
        350.00,
        '2025-05-29 15:44:31',
        '2025-05-29 13:18:43',
        'EQUIP001',
        1,
        ''
    ),
    (
        5,
        3,
        'Conjunto de Pincéis',
        'Conjunto com pincéis de diferentes tamanhos, ideal para detalhes e acabamento na pintura.',
        45.00,
        '2025-05-29 15:44:31',
        '2025-05-29 13:18:44',
        'ACESS001',
        1,
        ''
    ),
    (
        6,
        3,
        'Lona de Proteção 3x3m',
        'Lona descartável para proteger móveis e pisos durante a aplicação da tinta.',
        18.00,
        '2025-05-29 15:44:31',
        '2025-05-29 13:18:46',
        'PROT001',
        1,
        ''
    ),
    (
        7,
        3,
        'Kit de Proteção Individual',
        'Kit completo com máscara, luvas e óculos de proteção para pintura.',
        75.00,
        '2025-05-29 15:44:31',
        '2025-05-29 13:18:47',
        'EPI001',
        1,
        ''
    ),
    (
        27,
        37,
        'Tijolo 11.5/19/24',
        'Tijolo cerâmico para alvenaria estrutural.',
        1.50,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MAT-TIJ-01',
        1,
        NULL
    ),
    (
        28,
        37,
        'Tijolo 11.5/14/24',
        'Tijolo cerâmico para vedação.',
        1.20,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MAT-TIJ-02',
        1,
        NULL
    ),
    (
        29,
        37,
        'Tábua de 30cm',
        'Tábua de pinus para formas e andaimes.',
        45.00,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MAT-MAD-01',
        1,
        NULL
    ),
    (
        30,
        37,
        'Tábua de 15cm',
        'Tábua de pinus para acabamentos.',
        25.00,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MAT-MAD-02',
        1,
        NULL
    ),
    (
        31,
        37,
        'Sarrafo de Eucalipto',
        'Sarrafo para estruturas de telhado e formas.',
        15.00,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MAT-MAD-03',
        1,
        NULL
    ),
    (
        32,
        37,
        'Cimento 50kg',
        'Saco de cimento Portland CP II.',
        35.00,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MAT-CIM-01',
        1,
        NULL
    ),
    (
        33,
        37,
        'Areia Média (Metro)',
        'Areia lavada para concreto e argamassa.',
        120.00,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MAT-AGR-01',
        1,
        NULL
    ),
    (
        34,
        37,
        'Pedrisco (Metro)',
        'Pedra britada pequena para concreto.',
        130.00,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MAT-AGR-02',
        1,
        NULL
    ),
    (
        35,
        37,
        'Mão de Obra - Encanamento',
        'Serviços de instalação de esgoto.',
        1400.00,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MDO-ENC-01',
        1,
        NULL
    ),
    (
        36,
        37,
        'Mão de Obra - Contra Piso',
        'Execução de contra piso (233m²).',
        6990.00,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MDO-PIS-01',
        1,
        NULL
    ),
    (
        37,
        37,
        'Mão de Obra - Parede Garagem',
        'Construção de parede de divisa (24m).',
        5373.00,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MDO-ALV-01',
        1,
        NULL
    ),
    (
        38,
        37,
        'Mão de Obra - Escada',
        'Construção de escada de concreto.',
        4000.00,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MDO-ESC-01',
        1,
        NULL
    ),
    (
        39,
        37,
        'Mão de Obra - Baldrame',
        'Execução de viga baldrame (139m).',
        7645.00,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MDO-FUN-01',
        1,
        NULL
    ),
    (
        40,
        37,
        'Mão de Obra - Laje',
        'Execução de laje para garagem e quarto (40m²).',
        5600.00,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MDO-LAJ-01',
        1,
        NULL
    ),
    (
        41,
        37,
        'Mão de Obra - Muro de Arrimo',
        'Construção de muro de arrimo (68m).',
        15640.00,
        '2025-06-30 13:51:41',
        '2025-06-30 13:51:41',
        'MDO-MUR-01',
        1,
        NULL
    );

-- Copiando estrutura para tabela easybudget.product_inventory
DROP TABLE IF EXISTS `product_inventory`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.product_inventory: ~0 rows (aproximadamente)
INSERT INTO
    `product_inventory` (
        `id`,
        `tenant_id`,
        `product_id`,
        `quantity`,
        `min_quantity`,
        `max_quantity`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        3,
        3,
        1,
        5,
        1,
        10,
        '2025-05-29 13:38:21',
        '2025-05-29 13:38:36'
    );

-- Copiando estrutura para tabela easybudget.professions
DROP TABLE IF EXISTS `professions`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 34 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.professions: ~33 rows (aproximadamente)
INSERT INTO
    `professions` (
        `id`,
        `slug`,
        `name`,
        `is_active`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        'others',
        'Outros',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        2,
        'lawyer',
        'Advogado',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        3,
        'architect',
        'Arquiteto',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        4,
        'artist',
        'Artista',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        5,
        'biologist',
        'Biólogo',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        6,
        'chef',
        'Chef de Cozinha',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        7,
        'scientist',
        'Cientista',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        8,
        'political_scientist',
        'Cientista Político',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        9,
        'accountant',
        'Contador',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        10,
        'consultant',
        'Consultor',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        11,
        'dentist',
        'Dentista',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        12,
        'designer',
        'Designer',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        13,
        'economist',
        'Economista',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        14,
        'nurse',
        'Enfermeiro',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        15,
        'engineer',
        'Engenheiro',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        16,
        'writer',
        'Escritor',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        17,
        'it_specialist',
        'Especialista em TI',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        18,
        'pharmacist',
        'Farmacêutico',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        19,
        'physicist',
        'Físico',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        20,
        'historian',
        'Historiador',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        21,
        'journalist',
        'Jornalista',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        22,
        'linguist',
        'Linguista',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        23,
        'mathematician',
        'Matemático',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        24,
        'doctor',
        'Médico',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        25,
        'musician',
        'Músico',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        26,
        'pilot',
        'Piloto',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        27,
        'teacher',
        'Professor',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        28,
        'psychologist',
        'Psicólogo',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        29,
        'psychiatrist',
        'Psiquiatra',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        30,
        'geologist',
        'Geólogo',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        31,
        'sociologist',
        'Sociólogo',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        32,
        'technician',
        'Técnico',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        33,
        'veterinarian',
        'Veterinário',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    );

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 20 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.providers: ~4 rows (aproximadamente)
INSERT INTO
    `providers` (
        `id`,
        `tenant_id`,
        `user_id`,
        `common_data_id`,
        `contact_id`,
        `address_id`,
        `terms_accepted`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        1,
        1,
        1,
        1,
        1,
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        2,
        2,
        2,
        2,
        2,
        2,
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        3,
        3,
        3,
        3,
        3,
        3,
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        19,
        37,
        19,
        28,
        27,
        27,
        1,
        '2025-06-30 08:48:54',
        '2025-06-30 08:48:54'
    );

-- Copiando estrutura para tabela easybudget.reports
DROP TABLE IF EXISTS `reports`;

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
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `pdf_verification_hash` varchar(64) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `tenant_id` (`tenant_id`),
        KEY `idx_report_hash` (`hash`, `tenant_id`),
        CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
        CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
    ) ENGINE = InnoDB AUTO_INCREMENT = 118 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.reports: ~2 rows (aproximadamente)
INSERT INTO
    `reports` (
        `id`,
        `tenant_id`,
        `user_id`,
        `hash`,
        `type`,
        `description`,
        `file_name`,
        `status`,
        `format`,
        `size`,
        `created_at`,
        `pdf_verification_hash`
    )
VALUES
    (
        116,
        3,
        3,
        '618e2c484d521e782b71a004cebafafa1285d3b1cb388b7be80ca93fb68d2909',
        'budget',
        'csrf_token: 0b4e1fa9650b9cc24f4992270d749eb886bc9506b4cd43d31bc658a0d46357a6 | code: 0007 | start_date:  | end_date:  | customer_name:  | total:  | status: ',
        'relatorio_orcamentos_20250603_13_04_55_001_registros.pdf',
        'generated',
        'pdf',
        0.04,
        '2025-06-03 16:05:04',
        NULL
    ),
    (
        117,
        3,
        3,
        '965537096463fdc70af161cfba859f5bf85c5d0a3fd83d0788955d30099e9fe9',
        'budget',
        'csrf_token: 1e28e114d555fcb23872cc30ef5b45b6999795024417b86f640dd4d954f1bfb1 | code:  | start_date:  | end_date:  | customer_name:  | total:  | status: ',
        'relatorio_orcamentos_20250622_15_44_01_007_registros.pdf',
        'generated',
        'pdf',
        0.04,
        '2025-06-22 18:44:09',
        NULL
    );

-- Copiando estrutura para tabela easybudget.resources
DROP TABLE IF EXISTS `resources`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 37 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.resources: ~36 rows (aproximadamente)
INSERT INTO
    `resources` (
        `id`,
        `name`,
        `slug`,
        `in_dev`,
        `status`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        'Listagem de Planos',
        'plan-listing',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        2,
        'Detalhes do Plano',
        'plan-details',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        3,
        'Histórico de Planos',
        'plan-history',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        4,
        'Comparação de Planos',
        'plan-comparison',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        5,
        'Cadastro de Prestador',
        'provider-registration',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        6,
        'Atualização de Prestador',
        'provider-update',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        7,
        'Documentos do Prestador',
        'provider-documents',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        8,
        'Avaliações do Prestador',
        'provider-ratings',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        9,
        'Assinatura de Plano',
        'plan-subscription',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        10,
        'Renovação Automática',
        'auto-renewal',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        11,
        'Histórico de Pagamentos',
        'payment-history',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        12,
        'Cancelamento de Plano',
        'plan-cancellation',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        13,
        'Relatório de Prestadores',
        'provider-reports',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        14,
        'Análise de Planos',
        'plan-analytics',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        15,
        'Dashboard de Gestão',
        'management-dashboard',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        16,
        'Métricas de Desempenho',
        'performance-metrics',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        17,
        'Cadastro de Clientes',
        'customer-management',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        18,
        'Ordens de Serviço',
        'service-orders',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        19,
        'Cadastro de Serviços',
        'service-registration',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        20,
        'Status de Ordem',
        'order-status',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        21,
        'Agenda de Serviços',
        'service-schedule',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        22,
        'Gestão de Equipe',
        'team-management',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        23,
        'Controle de Peças',
        'parts-control',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        24,
        'Orçamentos',
        'budgets',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        25,
        'Faturamento',
        'billing',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        26,
        'Controle de Pagamentos',
        'payment-control',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        27,
        'Comissões',
        'commissions',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        28,
        'Fluxo de Caixa',
        'cash-flow',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        29,
        'Painel de Controle',
        'dashboard',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        30,
        'Relatórios Gerenciais',
        'management-reports',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        31,
        'Histórico de Clientes',
        'customer-history',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        32,
        'Avaliações de Serviço',
        'service-ratings',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        33,
        'Notificações Automáticas',
        'auto-notifications',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        34,
        'Lembretes de Manutenção',
        'maintenance-reminders',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        35,
        'Integração WhatsApp',
        'whatsapp-integration',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    ),
    (
        36,
        'App Mobile',
        'mobile-app',
        1,
        'inactive',
        '2025-05-29 15:44:31',
        '2025-05-29 15:44:31'
    );

-- Copiando estrutura para tabela easybudget.roles
DROP TABLE IF EXISTS `roles`;

CREATE TABLE
    IF NOT EXISTS `roles` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` varchar(255) DEFAULT NULL,
        `created_at` datetime DEFAULT current_timestamp(),
        `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_role_name` (`name`)
    ) ENGINE = InnoDB AUTO_INCREMENT = 5 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.roles: ~4 rows (aproximadamente)
INSERT INTO
    `roles` (
        `id`,
        `name`,
        `description`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        'admin',
        'Administrador com acesso total',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        2,
        'manager',
        'Gerente com acesso parcial',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        3,
        'provider',
        'Prestador padrão',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        4,
        'user',
        'Usuário padrão',
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    );

-- Copiando estrutura para tabela easybudget.role_permissions
DROP TABLE IF EXISTS `role_permissions`;

CREATE TABLE
    IF NOT EXISTS `role_permissions` (
        `role_id` int (11) NOT NULL,
        `permission_id` int (11) NOT NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.role_permissions: ~8 rows (aproximadamente)
INSERT INTO
    `role_permissions` (`role_id`, `permission_id`)
VALUES
    (1, 1),
    (1, 2),
    (1, 3),
    (1, 4),
    (1, 5),
    (2, 4),
    (2, 5),
    (3, 4);

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
        `location` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `tenant_id` (`tenant_id`),
        KEY `service_id` (`service_id`),
        KEY `user_confirmation_token_id` (`user_confirmation_token_id`),
        CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
        CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
        CONSTRAINT `schedules_ibfk_3` FOREIGN KEY (`user_confirmation_token_id`) REFERENCES `user_confirmation_tokens` (`id`) ON DELETE CASCADE
    ) ENGINE = InnoDB AUTO_INCREMENT = 17 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.schedules: ~0 rows (aproximadamente)
-- Copiando estrutura para tabela easybudget.services
DROP TABLE IF EXISTS `services`;

CREATE TABLE
    IF NOT EXISTS `services` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `tenant_id` int (11) NOT NULL,
        `budget_id` int (11) NOT NULL,
        `category_id` int (11) NOT NULL,
        `service_statuses_id` int (11) NOT NULL,
        `code` varchar(50) NOT NULL,
        `description` text DEFAULT NULL,
        `total` decimal(10, 2) NOT NULL,
        `due_date` datetime DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `pdf_verification_hash` varchar(64) DEFAULT NULL,
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

-- Copiando dados para a tabela easybudget.services: ~17 rows (aproximadamente)
INSERT INTO
    `services` (
        `id`,
        `tenant_id`,
        `budget_id`,
        `category_id`,
        `service_statuses_id`,
        `code`,
        `description`,
        `total`,
        `due_date`,
        `created_at`,
        `updated_at`,
        `pdf_verification_hash`
    )
VALUES
    (
        1,
        3,
        1,
        1,
        1,
        '202505290001-S001',
        'Serviço de reforma',
        850.00,
        '2025-12-15 00:00:00',
        '2025-05-29 09:44:31',
        '2025-06-04 10:51:34',
        NULL
    ),
    (
        2,
        3,
        2,
        2,
        2,
        '202505290002-S002',
        'Serviço de pintura',
        300.00,
        '2025-12-19 00:00:00',
        '2025-05-29 09:44:31',
        '2025-06-27 10:54:44',
        NULL
    ),
    (
        3,
        3,
        3,
        3,
        1,
        '202505290003-S003',
        'Serviço de elétrica',
        75.00,
        '2025-12-25 00:00:00',
        '2025-05-29 09:44:31',
        '2025-06-04 10:37:08',
        NULL
    ),
    (
        4,
        3,
        4,
        4,
        1,
        '202505290004-S004',
        'Serviço de hidráulica',
        625.00,
        '2025-12-30 00:00:00',
        '2025-05-29 09:44:31',
        '2025-06-04 10:37:09',
        NULL
    ),
    (
        5,
        3,
        5,
        5,
        1,
        '202505290005-S005',
        'Serviço de construção',
        0.00,
        '2025-12-31 00:00:00',
        '2025-05-29 09:44:31',
        '2025-06-04 10:37:11',
        NULL
    ),
    (
        25,
        3,
        28,
        27,
        3,
        '202506020007-S001',
        'Pintura interna da casa',
        220.50,
        '2025-07-04 00:00:00',
        '2025-06-04 12:41:54',
        '2025-06-27 13:24:36',
        NULL
    ),
    (
        26,
        3,
        28,
        27,
        3,
        '202506020007-S002',
        'Pintura externa da casa',
        220.50,
        '2025-07-04 00:00:00',
        '2025-06-04 12:42:34',
        '2025-06-24 13:04:09',
        NULL
    ),
    (
        27,
        3,
        28,
        27,
        3,
        '202506020007-S003',
        'Pintura do portão',
        220.50,
        '2025-07-04 00:00:00',
        '2025-06-04 12:43:22',
        '2025-06-24 13:04:09',
        NULL
    ),
    (
        28,
        3,
        28,
        27,
        3,
        '202506020007-S004',
        'Pintura de muro',
        199.99,
        '2025-07-04 00:00:00',
        '2025-06-04 12:45:13',
        '2025-06-27 11:07:25',
        NULL
    ),
    (
        29,
        3,
        28,
        27,
        3,
        '202506020007-S005',
        'Pintura de calçada',
        719.98,
        '2025-07-04 00:00:00',
        '2025-06-04 12:48:53',
        '2025-06-24 13:04:09',
        NULL
    ),
    (
        30,
        37,
        29,
        6,
        3,
        '202506300001-S001',
        'Serviço de Encanamento e Esgoto',
        1400.00,
        '2025-09-30 00:00:00',
        '2025-06-30 10:51:41',
        '2025-07-11 10:05:51',
        '480625b8ff2a00634dac8deeb987cdc4c23d38e0'
    ),
    (
        31,
        37,
        29,
        15,
        3,
        '202506300001-S002',
        'Serviço de Contra Piso (233m²)',
        6990.00,
        '2025-09-30 00:00:00',
        '2025-06-30 10:51:41',
        '2025-07-11 08:53:47',
        ''
    ),
    (
        32,
        37,
        29,
        15,
        3,
        '202506300001-S003',
        'Serviço de Parede da Garagem (24m)',
        5373.00,
        '2025-09-30 00:00:00',
        '2025-06-30 10:51:41',
        '2025-07-11 08:53:48',
        ''
    ),
    (
        33,
        37,
        29,
        2,
        3,
        '202506300001-S004',
        'Serviço de Construção da Escada',
        4000.00,
        '2025-09-30 00:00:00',
        '2025-06-30 10:51:41',
        '2025-07-11 08:53:49',
        ''
    ),
    (
        34,
        37,
        29,
        2,
        3,
        '202506300001-S005',
        'Serviço de Viga Baldrame (139m)',
        7645.00,
        '2025-09-30 00:00:00',
        '2025-06-30 10:51:41',
        '2025-07-11 08:53:50',
        ''
    ),
    (
        35,
        37,
        29,
        2,
        3,
        '202506300001-S006',
        'Serviço de Laje (40m²)',
        5600.00,
        '2025-09-30 00:00:00',
        '2025-06-30 10:51:41',
        '2025-07-11 08:53:50',
        ''
    ),
    (
        36,
        37,
        29,
        2,
        3,
        '202506300001-S007',
        'Serviço de Muro de Arrimo (68m)',
        15640.00,
        '2025-09-30 00:00:00',
        '2025-06-30 10:51:41',
        '2025-07-11 08:53:51',
        ''
    );

-- Copiando estrutura para tabela easybudget.service_items
DROP TABLE IF EXISTS `service_items`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 66 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.service_items: ~18 rows (aproximadamente)
INSERT INTO
    `service_items` (
        `id`,
        `tenant_id`,
        `service_id`,
        `product_id`,
        `quantity`,
        `unit_value`,
        `total`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        3,
        1,
        1,
        2,
        50.00,
        100.00,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        2,
        3,
        2,
        2,
        3,
        100.00,
        300.00,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        3,
        3,
        3,
        3,
        1,
        75.00,
        75.00,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        4,
        3,
        4,
        4,
        5,
        125.00,
        500.00,
        '2025-05-29 09:44:31',
        '2025-05-30 08:55:52'
    ),
    (
        5,
        3,
        5,
        5,
        5,
        150.00,
        750.00,
        '2025-05-29 09:44:31',
        '2025-06-23 11:43:35'
    ),
    (
        53,
        3,
        25,
        1,
        1,
        220.50,
        0.00,
        '2025-06-04 12:41:59',
        '2025-06-04 12:41:59'
    ),
    (
        54,
        3,
        26,
        1,
        1,
        220.50,
        0.00,
        '2025-06-04 12:42:34',
        '2025-06-04 12:42:34'
    ),
    (
        55,
        3,
        27,
        1,
        1,
        220.50,
        0.00,
        '2025-06-04 12:43:22',
        '2025-06-04 12:43:22'
    ),
    (
        56,
        3,
        28,
        2,
        1,
        199.99,
        0.00,
        '2025-06-04 12:45:13',
        '2025-06-04 12:45:13'
    ),
    (
        57,
        3,
        29,
        3,
        1,
        320.00,
        0.00,
        '2025-06-04 12:48:53',
        '2025-06-04 12:48:53'
    ),
    (
        58,
        3,
        29,
        2,
        2,
        199.99,
        0.00,
        '2025-06-23 11:51:30',
        '2025-06-23 11:51:30'
    ),
    (
        59,
        37,
        30,
        35,
        1,
        1400.00,
        1400.00,
        '2025-06-30 10:51:41',
        '2025-06-30 10:51:41'
    ),
    (
        60,
        37,
        31,
        36,
        1,
        6990.00,
        6990.00,
        '2025-06-30 10:51:41',
        '2025-06-30 10:51:41'
    ),
    (
        61,
        37,
        32,
        37,
        1,
        5373.00,
        5373.00,
        '2025-06-30 10:51:41',
        '2025-06-30 10:51:41'
    ),
    (
        62,
        37,
        33,
        38,
        1,
        4000.00,
        4000.00,
        '2025-06-30 10:51:41',
        '2025-06-30 10:51:41'
    ),
    (
        63,
        37,
        34,
        39,
        1,
        7645.00,
        7645.00,
        '2025-06-30 10:51:41',
        '2025-06-30 10:51:41'
    ),
    (
        64,
        37,
        35,
        40,
        1,
        5600.00,
        5600.00,
        '2025-06-30 10:51:41',
        '2025-06-30 10:51:41'
    ),
    (
        65,
        37,
        36,
        41,
        1,
        15640.00,
        15640.00,
        '2025-06-30 10:51:41',
        '2025-06-30 10:51:41'
    );

-- Copiando estrutura para tabela easybudget.service_statuses
DROP TABLE IF EXISTS `service_statuses`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 79 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.service_statuses: ~12 rows (aproximadamente)
INSERT INTO
    `service_statuses` (
        `id`,
        `slug`,
        `name`,
        `description`,
        `color`,
        `icon`,
        `order_index`,
        `is_active`,
        `created_at`
    )
VALUES
    (
        1,
        'DRAFT',
        'Rascunho',
        'Serviço em elaboração, permite modificações',
        '#6c757d',
        'bi-pencil-square',
        0,
        1,
        '2025-06-04 13:30:13'
    ),
    (
        2,
        'PENDING',
        'Pendente',
        'Serviço registrado aguardando aprovação',
        '#ffc107',
        'bi-clock',
        1,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        3,
        'SCHEDULING',
        'Agendamento',
        'Data e hora a serem definidas para execução do serviço',
        '#007bff',
        'bi-calendar-check',
        2,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        4,
        'PREPARING',
        'Em Preparação',
        'Equipe está preparando recursos e materiais',
        '#ffc107',
        'bi-tools',
        3,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        5,
        'IN_PROGRESS',
        'Em Andamento',
        'Serviço está sendo executado no momento',
        '#007bff',
        'bi-gear',
        4,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        6,
        'ON_HOLD',
        'Em Espera',
        'Serviço temporariamente pausado',
        '#6c757d',
        'bi-pause-circle',
        5,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        7,
        'SCHEDULED',
        'Agendado',
        'Serviço com data marcada',
        '#007bff',
        'bi-calendar-plus',
        6,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        8,
        'COMPLETED',
        'Concluído',
        'Serviço finalizado com sucesso',
        '#28a745',
        'bi-check-circle',
        7,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        9,
        'PARTIAL',
        'Concluído Parcial',
        'Serviço finalizado parcialmente',
        '#28a745',
        'bi-check-circle-fill',
        8,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        10,
        'CANCELLED',
        'Cancelado',
        'Serviço cancelado antes da execução',
        '#dc3545',
        'bi-x-circle',
        9,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        11,
        'NOT_PERFORMED',
        'Não Realizado',
        'Não foi possível realizar o serviço',
        '#dc3545',
        'bi-slash-circle',
        10,
        1,
        '2025-05-29 15:44:31'
    ),
    (
        12,
        'EXPIRED',
        'Expirado',
        'Prazo de validade do orçamento expirado',
        '#dc3545',
        'bi-calendar-x',
        11,
        1,
        '2025-05-29 15:44:31'
    );

-- Copiando estrutura para tabela easybudget.supports
DROP TABLE IF EXISTS `supports`;

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
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB AUTO_INCREMENT = 3 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.supports: ~0 rows (aproximadamente)
-- Copiando estrutura para tabela easybudget.tenants
DROP TABLE IF EXISTS `tenants`;

CREATE TABLE
    IF NOT EXISTS `tenants` (
        `id` int (11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_tenants_name` (`name`)
    ) ENGINE = InnoDB AUTO_INCREMENT = 38 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.tenants: ~4 rows (aproximadamente)
INSERT INTO
    `tenants` (`id`, `name`, `created_at`, `updated_at`)
VALUES
    (
        1,
        'admin_1716968671_a1b2c3d4',
        '2025-05-29 09:44:31',
        '2025-05-31 13:56:22'
    ),
    (
        2,
        'manager_1716968671_e5f6g7h8',
        '2025-05-29 09:44:31',
        '2025-05-31 13:56:31'
    ),
    (
        3,
        'teste_1716968671_i9j0k1l2',
        '2025-05-29 09:44:31',
        '2025-05-31 13:56:41'
    ),
    (
        37,
        'Ivan_1751284133_65f88b0d',
        '2025-06-30 08:48:53',
        '2025-06-30 08:48:53'
    );

-- Copiando estrutura para tabela easybudget.units
DROP TABLE IF EXISTS `units`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 28 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Copiando dados para a tabela easybudget.units: ~13 rows (aproximadamente)
INSERT INTO
    `units` (
        `id`,
        `slug`,
        `name`,
        `is_active`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        'cm',
        'Centímetro',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        2,
        'g',
        'Gramas',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        3,
        'kg',
        'Kilograma',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        4,
        'l',
        'Litro',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        5,
        'm',
        'Metro',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        6,
        'm2',
        'Metro Quadrado',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        7,
        'm3',
        'Metro Cúbico',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        8,
        'mm',
        'Milímetro',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        9,
        'ml',
        'Mililitro',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        10,
        'ft',
        'Pé',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        11,
        'in',
        'Polegada',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        12,
        't',
        'Tonelada',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        13,
        'un',
        'Unidade',
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    );

-- Copiando estrutura para tabela easybudget.users
DROP TABLE IF EXISTS `users`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 20 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.users: ~4 rows (aproximadamente)
INSERT INTO
    `users` (
        `id`,
        `tenant_id`,
        `email`,
        `password`,
        `logo`,
        `is_active`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        1,
        'admin@easybudget.net.br',
        '$2y$10$LhZe8oTmEXpuLfKwAcVGF.92ircrGI0B0jJAIG82Qg9KIwAqOeb2m',
        NULL,
        1,
        '2025-05-29 00:00:00',
        '0000-00-00 00:00:00'
    ),
    (
        2,
        2,
        'manager@easybudget.net.br',
        '$2y$10$LhZe8oTmEXpuLfKwAcVGF.92ircrGI0B0jJAIG82Qg9KIwAqOeb2m',
        NULL,
        1,
        '2025-05-29 00:00:00',
        '0000-00-00 00:00:00'
    ),
    (
        3,
        3,
        'teste@easybudget.net.br',
        '$2y$10$LhZe8oTmEXpuLfKwAcVGF.92ircrGI0B0jJAIG82Qg9KIwAqOeb2m',
        NULL,
        1,
        '2025-05-29 00:00:00',
        '2025-06-02 09:37:07'
    ),
    (
        19,
        37,
        'ivanhenriqueramosh@gmail.com',
        '$2a$10$zad3H.zYgK38R3vzb6QbHOb3tzyMO5ezcZEYbkkelwcOuzQzGmMwe',
        NULL,
        1,
        '2025-06-30 08:48:54',
        '2025-07-01 11:52:16'
    );

-- Copiando estrutura para tabela easybudget.user_confirmation_tokens
DROP TABLE IF EXISTS `user_confirmation_tokens`;

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
    ) ENGINE = InnoDB AUTO_INCREMENT = 167 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Copiando dados para a tabela easybudget.user_confirmation_tokens: ~0 rows (aproximadamente)
INSERT INTO
    `user_confirmation_tokens` (
        `id`,
        `user_id`,
        `tenant_id`,
        `token`,
        `expires_at`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        165,
        19,
        37,
        'd2a9cb3681ab057ec8a2d0c1abf555b31bf479bf74712d3e877193bfa1e8a30d',
        '2025-07-18 10:44:33',
        '2025-07-11 10:44:33',
        '2025-07-11 10:44:33'
    ),
    (
        166,
        19,
        37,
        '1484400513e0731bbfd6fb1eaf9630148bd8b87af172eb71454a4883e90424bf',
        '2025-07-18 10:44:35',
        '2025-07-11 10:44:35',
        '2025-07-11 10:44:35'
    );

-- Copiando estrutura para tabela easybudget.user_roles
DROP TABLE IF EXISTS `user_roles`;

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

-- Copiando dados para a tabela easybudget.user_roles: ~4 rows (aproximadamente)
INSERT INTO
    `user_roles` (
        `user_id`,
        `role_id`,
        `tenant_id`,
        `created_at`,
        `updated_at`
    )
VALUES
    (
        1,
        1,
        1,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        2,
        2,
        2,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        3,
        3,
        3,
        '2025-05-29 09:44:31',
        '2025-05-29 09:44:31'
    ),
    (
        19,
        3,
        37,
        '2025-06-30 08:48:54',
        '2025-06-30 08:48:54'
    );

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;

/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
