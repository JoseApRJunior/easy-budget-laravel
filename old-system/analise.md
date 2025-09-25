old-system\create_db_producao_antigo.sql:23-40

```sql
-- Copiando estrutura para tabela easybudget.activities
DROP TABLE IF EXISTS `activities`;
CREATE TABLE IF NOT EXISTS `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `metadata` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
  CONSTRAINT `activities_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado.

```sql
-- Copiando estrutura para tabela easybudget.addresses
DROP TABLE IF EXISTS `addresses`;
CREATE TABLE IF NOT EXISTS `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `neighborhood` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  CONSTRAINT `fk_addresses_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado.

```sql
-- Copiando estrutura para tabela easybudget.areas_of_activity
DROP TABLE IF EXISTS `areas_of_activity`;
CREATE TABLE IF NOT EXISTS `areas_of_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado.

```sql
-- Copiando estrutura para tabela easybudget.budgets
DROP TABLE IF EXISTS `budgets`;
CREATE TABLE IF NOT EXISTS `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `budget_statuses_id` int(11) NOT NULL,
  `user_confirmation_token_id` int(11) DEFAULT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `due_date` datetime DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT '0.00',
  `total` decimal(10,2) DEFAULT '0.00',
  `description` text COLLATE utf8mb4_unicode_ci,
  `payment_terms` text COLLATE utf8mb4_unicode_ci,
  `attachment` blob,
  `history` text COLLATE utf8mb4_unicode_ci,
  `pdf_verification_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_budgets_customer` (`customer_id`),
  KEY `fk_budgets_budget_statuses` (`budget_statuses_id`),
  KEY `fk_budgets_user_confirmation_token` (`user_confirmation_token_id`),
  KEY `idx_budget_filters` (`tenant_id`,`due_date`,`created_at`),
  CONSTRAINT `fk_budgets_budget_statuses` FOREIGN KEY (`budget_statuses_id`) REFERENCES `budget_statuses` (`id`),
  CONSTRAINT `fk_budgets_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_budgets_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
  CONSTRAINT `fk_budgets_user_confirmation_token` FOREIGN KEY (`user_confirmation_token_id`) REFERENCES `user_confirmation_tokens` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado.

```sql
-- Copiando estrutura para tabela easybudget.budget_statuses
DROP TABLE IF EXISTS `budget_statuses`;
CREATE TABLE IF NOT EXISTS `budget_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `color` varchar(7) DEFAULT NULL,
  `icon` varchar(30) DEFAULT NULL,
  `order_index` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado.

```sql
-- Copiando estrutura para tabela easybudget.categories
DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado.

```sql
-- Copiando estrutura para tabela easybudget.common_datas
DROP TABLE IF EXISTS `common_datas`;

CREATE TABLE
  IF NOT EXISTS `common_datas` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) NOT NULL,
    `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    `birth_date` datetime DEFAULT NULL,
    `cnpj` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `cpf` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `description` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

```sql
-- Copiando estrutura para tabela easybudget.contacts
DROP TABLE IF EXISTS `contacts`;

CREATE TABLE
  IF NOT EXISTS `contacts` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) NOT NULL,
    `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `email_business` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
    `phone_business` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
    `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tenant` (`tenant_id`),
    CONSTRAINT `fk_contacts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 29 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

```sql
-- Copiando estrutura para tabela easybudget.customers
DROP TABLE IF EXISTS `customers`;

CREATE TABLE
  IF NOT EXISTS `customers` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) NOT NULL,
    `common_data_id` int (11) NOT NULL,
    `contact_id` int (11) NOT NULL,
    `address_id` int (11) NOT NULL,
    `status` enum ('active', 'inactive', 'deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

```sql
-- Copiando estrutura para tabela easybudget.inventory_movements
DROP TABLE IF EXISTS `inventory_movements`;

CREATE TABLE
  IF NOT EXISTS `inventory_movements` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) NOT NULL,
    `product_id` int (11) NOT NULL,
    `type` enum ('in', 'out') COLLATE utf8mb4_unicode_ci NOT NULL,
    `quantity` int (11) NOT NULL,
    `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tenant` (`tenant_id`),
    KEY `idx_product` (`product_id`),
    KEY `idx_type` (`type`),
    CONSTRAINT `fk_movements_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
    CONSTRAINT `fk_movements_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 3 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

```sql
-- Copiando estrutura para tabela easybudget.invoices
DROP TABLE IF EXISTS `invoices`;

CREATE TABLE
  IF NOT EXISTS `invoices` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `tenant_id` int (11) NOT NULL,
    `service_id` int (11) NOT NULL,
    `customer_id` int (11) NOT NULL,
    `invoice_statuses_id` int (11) DEFAULT NULL,
    `code` varchar(20) NOT NULL,
    `public_hash` varchar(64) DEFAULT NULL,
    `subtotal` decimal(10, 2) NOT NULL,
    `discount` decimal(10, 2) DEFAULT '0.00',
    `total` decimal(10, 2) NOT NULL,
    `due_date` date NOT NULL,
    `payment_method` varchar(50) DEFAULT NULL,
    `payment_id` varchar(50) DEFAULT NULL,
    `transaction_amount` decimal(10, 2) DEFAULT NULL,
    `transaction_date` datetime DEFAULT NULL,
    `notes` text,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `code` (`tenant_id`, `code`),
    UNIQUE KEY `public_hash` (`public_hash`),
    KEY `tenant_id` (`tenant_id`),
    KEY `service_id` (`service_id`),
    KEY `customer_id` (`customer_id`),
    KEY `invoices_fk_status` (`invoice_statuses_id`),
    CONSTRAINT `invoices_fk_status` FOREIGN KEY (`invoice_statuses_id`) REFERENCES `invoice_statuses` (`id`),
    CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
    CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
    CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 7 DEFAULT CHARSET = utf8mb4;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

```sql
-- Copiando estrutura para tabela easybudget.invoice_items
DROP TABLE IF EXISTS `invoice_items`;

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
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `tenant_id` (`tenant_id`),
    KEY `invoice_id` (`invoice_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
    CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `invoice_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

```sql
-- Copiando estrutura para tabela easybudget.invoice_statuses
DROP TABLE IF EXISTS `invoice_statuses`;

CREATE TABLE IF NOT EXISTS `invoice_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `color` varchar(7) NOT NULL DEFAULT '#6c757d',
  `icon` varchar(50) NOT NULL DEFAULT 'bi-circle-fill',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

```sql
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
`created_at` datetime DEFAULT CURRENT_TIMESTAMP,
`updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
KEY `idx_provider` (`provider_id`),
KEY `idx_tenant` (`tenant_id`),
KEY `idx_subscription` (`plan_subscription_id`),
CONSTRAINT `fk_merchant_orders_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`),
CONSTRAINT `fk_merchant_orders_subscription` FOREIGN KEY (`plan_subscription_id`) REFERENCES `plan_subscriptions` (`id`),
CONSTRAINT `fk_merchant_orders_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

```sql
-- Copiando estrutura para tabela easybudget.payment_mercado_pago_invoices
DROP TABLE IF EXISTS `payment_mercado_pago_invoices`;

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
    ) DEFAULT 'pending',
    `payment_method` varchar(50) NOT NULL,
    `transaction_amount` decimal(10, 2) NOT NULL,
    `transaction_date` datetime DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_payment_invoice` (`payment_id`, `invoice_id`),
    KEY `idx_payment_id` (`payment_id`),
    KEY `idx_invoice` (`invoice_id`),
    KEY `idx_tenant` (`tenant_id`),
    KEY `idx_status` (`status`),
    KEY `idx_transaction_date` (`transaction_date`),
    CONSTRAINT `fk_payment_invoices_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_payment_invoices_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
  ) ENGINE = InnoDB AUTO_INCREMENT = 17 DEFAULT CHARSET = utf8mb4;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

```sql
-- Copiando estrutura para tabela easybudget.payment_mercado_pago_plans
DROP TABLE IF EXISTS `payment_mercado_pago_plans`;

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
    ) DEFAULT NULL,
    `payment_method` varchar(50) NOT NULL,
    `transaction_amount` decimal(10, 2) NOT NULL,
    `transaction_date` datetime DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_provider` (`provider_id`),
    KEY `idx_tenant` (`tenant_id`),
    KEY `idx_subscription` (`plan_subscription_id`),
    CONSTRAINT `fk_payment_plans_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`),
    CONSTRAINT `fk_payment_plans_subscription` FOREIGN KEY (`plan_subscription_id`) REFERENCES `plan_subscriptions` (`id`),
    CONSTRAINT `fk_payment_plans_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 2 DEFAULT CHARSET = utf8mb4;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

```sql
-- Copiando estrutura para tabela easybudget.permissions
DROP TABLE IF EXISTS `permissions`;

CREATE TABLE
  IF NOT EXISTS `permissions` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` varchar(255) DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_permission_name` (`name`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 6 DEFAULT CHARSET = utf8mb4;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

```sql
-- Copiando estrutura para tabela easybudget.plans
DROP TABLE IF EXISTS `plans`;

CREATE TABLE
  IF NOT EXISTS `plans` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `slug` varchar(50) NOT NULL,
    `description` text,
    `price` decimal(10, 2) NOT NULL,
    `status` tinyint (1) DEFAULT '1',
    `max_budgets` int (11) NOT NULL,
    `max_clients` int (11) NOT NULL,
    `features` text,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_plan_slug` (`slug`),
    KEY `idx_status` (`status`)
  ) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb4;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```
