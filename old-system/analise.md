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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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
// verficar de cada model

```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

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

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
-- Copiando estrutura para tabela easybudget.role_permissions
DROP TABLE IF EXISTS `role_permissions`;

CREATE TABLE role_permissions (
    role_id int(11) NOT NULL,
    permission_id int(11) NOT NULL,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_role (role_id),
    KEY idx_permission (permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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

```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
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
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
-- Exportação de dados foi desmarcado.
-- Copiando estrutura para tabela easybudget.middleware_metrics_history
CREATE TABLE
IF NOT EXISTS `middleware_metrics_history` (
`id` bigint (20) NOT NULL AUTO_INCREMENT,
`tenant_id` int (11) NOT NULL,
`middleware_name` varchar(100) NOT NULL,
`endpoint` varchar(255) NOT NULL,
`method` varchar(10) NOT NULL,
`response_time` decimal(10, 3) NOT NULL COMMENT 'Tempo de resposta em milissegundos',
`memory_usage` bigint (20) NOT NULL COMMENT 'Uso de memória em bytes',
`cpu_usage` decimal(5, 2) DEFAULT NULL COMMENT 'Uso de CPU em porcentagem',
`status_code` int (3) NOT NULL,
`error_message` text DEFAULT NULL,
`user_id` int (11) DEFAULT NULL,
`ip_address` varchar(45) DEFAULT NULL,
`user_agent` text DEFAULT NULL,
`request_size` bigint (20) DEFAULT NULL COMMENT 'Tamanho da requisição em bytes',
`response_size` bigint (20) DEFAULT NULL COMMENT 'Tamanho da resposta em bytes',
`database_queries` int (11) DEFAULT NULL COMMENT 'Número de queries executadas',
`cache_hits` int (11) DEFAULT NULL COMMENT 'Número de cache hits',
`cache_misses` int (11) DEFAULT NULL COMMENT 'Número de cache misses',
`created_at` datetime NOT NULL DEFAULT current_timestamp(),
PRIMARY KEY (`id`),
KEY `idx_tenant_id` (`tenant_id`),
KEY `idx_middleware_name` (`middleware_name`),
KEY `idx_endpoint` (`endpoint`),
KEY `idx_method` (`method`),
KEY `idx_status_code` (`status_code`),
KEY `idx_response_time` (`response_time`),
KEY `idx_memory_usage` (`memory_usage`),
KEY `idx_user_id` (`user_id`),
KEY `idx_tenant_created` (`tenant_id`, `created_at`),
KEY `idx_middleware_metrics_tenant_period` (`tenant_id`, `created_at`),
KEY `idx_middleware_metrics_middleware_name` (`tenant_id`, `middleware_name`, `created_at`),
KEY `idx_middleware_metrics_endpoint` (`tenant_id`, `endpoint`, `created_at`),
KEY `idx_middleware_metrics_response_time` (`tenant_id`, `response_time`, `created_at`),
KEY `idx_middleware_metrics_memory_usage` (`tenant_id`, `memory_usage`, `created_at`),
KEY `idx_middleware_metrics_status_code` (`tenant_id`, `status_code`, `created_at`),
KEY `idx_middleware_metrics_aggregation` (
`tenant_id`,
`created_at`,
`response_time`,
`memory_usage`,
`status_code`
),
CONSTRAINT `fk_middleware_metrics_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_middleware_metrics_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 6 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Histórico de métricas de performance dos middlewares';
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

```sql
-- Exportação de dados foi desmarcado.
-- Copiando estrutura para tabela easybudget.monitoring_alerts_history
CREATE TABLE
IF NOT EXISTS `monitoring_alerts_history` (
`id` bigint (20) NOT NULL AUTO_INCREMENT,
`tenant_id` int (11) NOT NULL,
`alert_type` enum (
'performance',
'error',
'security',
'availability',
'resource'
) NOT NULL,
`severity` enum ('low', 'medium', 'high', 'critical') NOT NULL,
`middleware_name` varchar(100) NOT NULL,
`endpoint` varchar(255) DEFAULT NULL,
`metric_name` varchar(100) NOT NULL,
`metric_value` decimal(15, 3) NOT NULL,
`threshold_value` decimal(15, 3) NOT NULL,
`message` text NOT NULL,
`additional_data` longtext CHARACTER
SET
utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid (`additional_data`)),
`is_resolved` tinyint (1) NOT NULL DEFAULT 0,
`resolved_at` datetime DEFAULT NULL,
`resolved_by` int (11) DEFAULT NULL,
`resolution_notes` text DEFAULT NULL,
`notification_sent` tinyint (1) NOT NULL DEFAULT 0,
`notification_sent_at` datetime DEFAULT NULL,
`created_at` datetime NOT NULL DEFAULT current_timestamp(),
`updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
PRIMARY KEY (`id`),
KEY `idx_tenant_id` (`tenant_id`),
KEY `idx_alert_type` (`alert_type`),
KEY `idx_severity` (`severity`),
KEY `idx_middleware_name` (`middleware_name`),
KEY `idx_endpoint` (`endpoint`),
KEY `idx_metric_name` (`metric_name`),
KEY `idx_is_resolved` (`is_resolved`),
KEY `idx_created_at` (`created_at`),
KEY `idx_updated_at` (`updated_at`),
KEY `idx_notification_sent` (`notification_sent`),
KEY `idx_tenant_created` (`tenant_id`, `created_at`),
KEY `idx_severity_created` (`severity`, `created_at`),
KEY `idx_tenant_middleware` (`tenant_id`, `middleware_name`, `created_at`),
KEY `idx_tenant_endpoint` (`tenant_id`, `endpoint`, `created_at`),
KEY `fk_monitoring_alerts_resolved_by` (`resolved_by`),
CONSTRAINT `fk_monitoring_alerts_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_monitoring_alerts_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Histórico de alertas do sistema de monitoramento';
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/..

```sql
CREATE TABLE `sessions` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `user_id` INT NOT NULL,
  `session_token` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `last_activity` DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `expires_at` DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `session_data` JSON DEFAULT NULL,
  `pay_load` JSON DEFAULT NULL,
  INDEX `IDX_SESSIONS_USER_ID` (`user_id`),
  UNIQUE INDEX `UNIQ_USER_SESSIONS_TOKEN` (`session_token`),
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_SESSIONS_USER` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
```

**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [

    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Exemplo a seguir:

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...

## Verificar antes de plans

```php
   /**
     * Regras de validação para o modelo Permission.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
        ];
    }
```
