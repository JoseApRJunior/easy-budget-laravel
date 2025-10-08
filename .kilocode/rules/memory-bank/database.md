# Database - Easy Budget Laravel

## 🗄️ Schema do Banco de Dados

### **📊 Visão Geral**

-  **Database:** easy_budget
-  **Engine:** InnoDB
-  **Charset:** utf8mb4
-  **Collation:** utf8mb4_unicode_ci
-  **Tabelas:** 40+ tabelas principais (contando tabelas de sistema)
-  **Multi-tenant:** Isolamento completo por empresa
-  **Arquitetura:** Sistema complexo com integração Mercado Pago
-  **Status:** Schema inicial criado via migration em Laravel 12 (migrado de sistema legado Twig + DoctrineDBAL)

### **🏗️ Estrutura das Tabelas Principais**

#### **🏢 Tabelas Base e Catálogos**

##### **tenants**

```sql
CREATE TABLE tenants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

##### **units**

```sql
CREATE TABLE units (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

##### **areas_of_activity**

```sql
CREATE TABLE areas_of_activity (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_areas_active (is_active)
);
```

##### **professions**

```sql
CREATE TABLE professions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

##### **categories**

```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### **🔐 Sistema de Permissões e RBAC**

##### **roles**

```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

##### **permissions**

```sql
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    description VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

##### **role_permissions**

```sql
CREATE TABLE role_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    role_id BIGINT UNSIGNED,
    permission_id BIGINT UNSIGNED,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_role_permissions (tenant_id, role_id, permission_id)
);
```

##### **user_roles**

```sql
CREATE TABLE user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    role_id BIGINT UNSIGNED,
    tenant_id BIGINT UNSIGNED,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_roles (user_id, role_id, tenant_id)
);
```

#### **👥 Usuários e Autenticação**

##### **users**

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    logo VARCHAR(255) NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

##### **user_confirmation_tokens**

```sql
CREATE TABLE user_confirmation_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    tenant_id BIGINT UNSIGNED,
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

#### **🏢 Dados Comuns e Relacionamentos**

##### **addresses**

```sql
CREATE TABLE addresses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    address VARCHAR(255) NOT NULL,
    address_number VARCHAR(20) NULL,
    neighborhood VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(2) NOT NULL,
    cep VARCHAR(9) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

##### **contacts**

```sql
CREATE TABLE contacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) NULL,
    email_business VARCHAR(255) UNIQUE NULL,
    phone_business VARCHAR(20) NULL,
    website VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

##### **common_datas**

```sql
CREATE TABLE common_datas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    birth_date DATE NULL,
    cnpj VARCHAR(14) UNIQUE NULL,
    cpf VARCHAR(11) UNIQUE NULL,
    company_name VARCHAR(255) NULL,
    description TEXT NULL,
    area_of_activity_id BIGINT UNSIGNED NULL,
    profession_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (area_of_activity_id) REFERENCES areas_of_activity(id) ON DELETE RESTRICT,
    FOREIGN KEY (profession_id) REFERENCES professions(id) ON DELETE RESTRICT
);
```

##### **customers**

```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    common_data_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    address_id BIGINT UNSIGNED NULL,
    status VARCHAR(20) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (common_data_id) REFERENCES common_datas(id) ON DELETE SET NULL,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (address_id) REFERENCES addresses(id) ON DELETE SET NULL
);
```

##### **providers**

```sql
CREATE TABLE providers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    common_data_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    address_id BIGINT UNSIGNED NULL,
    terms_accepted BOOLEAN NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (common_data_id) REFERENCES common_datas(id) ON DELETE SET NULL,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (address_id) REFERENCES addresses(id) ON DELETE SET NULL,
    UNIQUE KEY uq_providers_tenant_user (tenant_id, user_id)
);
```

#### **📦 Produtos e Estoque**

##### **products**

```sql
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(500) NULL,
    price DECIMAL(10,2) NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    code VARCHAR(50) NULL,
    image VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_products_tenant_code (tenant_id, code)
);
```

##### **product_inventory**

```sql
CREATE TABLE product_inventory (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    product_id BIGINT UNSIGNED,
    quantity INT DEFAULT 0,
    min_quantity INT DEFAULT 0,
    max_quantity INT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

##### **inventory_movements**

```sql
CREATE TABLE inventory_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    product_id BIGINT UNSIGNED,
    type VARCHAR(10) NOT NULL, -- 'in' | 'out'
    quantity INT NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

#### **💰 Sistema de Orçamentos e Serviços**

##### **budgets**

```sql
CREATE TABLE budgets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    customer_id BIGINT UNSIGNED,
    budget_statuses_id BIGINT UNSIGNED,
    user_confirmation_token_id BIGINT UNSIGNED NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    due_date DATE NULL,
    discount DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    payment_terms TEXT NULL,
    attachment VARCHAR(255) NULL,
    history LONGTEXT NULL,
    pdf_verification_hash VARCHAR(64) UNIQUE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (budget_statuses_id) REFERENCES budget_statuses(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_confirmation_token_id) REFERENCES user_confirmation_tokens(id) ON DELETE SET NULL
);
```

##### **services**

```sql
CREATE TABLE services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    budget_id BIGINT UNSIGNED,
    category_id BIGINT UNSIGNED,
    service_statuses_id BIGINT UNSIGNED,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT NULL,
    discount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) DEFAULT 0,
    due_date DATE NULL,
    pdf_verification_hash VARCHAR(64) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (service_statuses_id) REFERENCES service_statuses(id) ON DELETE RESTRICT
);
```

##### **service_items**

```sql
CREATE TABLE service_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    service_id BIGINT UNSIGNED,
    product_id BIGINT UNSIGNED,
    unit_value DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    total DECIMAL(10,2) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);
```

#### **🧾 Sistema de Faturamento**

##### **invoices**

```sql
CREATE TABLE invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    service_id BIGINT UNSIGNED,
    customer_id BIGINT UNSIGNED,
    invoice_statuses_id BIGINT UNSIGNED,
    code VARCHAR(50) UNIQUE NOT NULL,
    public_hash VARCHAR(64) NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    due_date DATE NULL,
    payment_method VARCHAR(50) NULL,
    payment_id VARCHAR(255) NULL,
    transaction_amount DECIMAL(10,2) NULL,
    transaction_date DATETIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (invoice_statuses_id) REFERENCES invoice_statuses(id) ON DELETE RESTRICT
);
```

##### **invoice_items**

```sql
CREATE TABLE invoice_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    invoice_id BIGINT UNSIGNED,
    product_id BIGINT UNSIGNED,
    description VARCHAR(255) NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);
```

#### **💳 Sistema de Assinaturas e Mercado Pago**

##### **plans**

```sql
CREATE TABLE plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    status BOOLEAN DEFAULT TRUE,
    max_budgets INT NOT NULL,
    max_clients INT NOT NULL,
    features JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

##### **plan_subscriptions**

```sql
CREATE TABLE plan_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    status ENUM('active', 'cancelled', 'pending', 'expired') NOT NULL,
    transaction_amount DECIMAL(10,2) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NULL,
    transaction_date DATETIME NULL,
    payment_method VARCHAR(50) NULL,
    payment_id VARCHAR(50) NULL,
    public_hash VARCHAR(255) NULL,
    last_payment_date DATETIME NULL,
    next_payment_date DATETIME NULL,
    tenant_id BIGINT UNSIGNED,
    provider_id BIGINT UNSIGNED,
    plan_id BIGINT UNSIGNED,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
);
```

##### **payment_mercado_pago_plans**

```sql
CREATE TABLE payment_mercado_pago_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id VARCHAR(255) NOT NULL,
    tenant_id BIGINT UNSIGNED,
    provider_id BIGINT UNSIGNED,
    plan_subscription_id BIGINT UNSIGNED,
    status VARCHAR(20) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_amount DECIMAL(10,2) NOT NULL,
    transaction_date DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_subscription_id) REFERENCES plan_subscriptions(id) ON DELETE CASCADE
);
```

##### **merchant_orders_mercado_pago**

```sql
CREATE TABLE merchant_orders_mercado_pago (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    provider_id BIGINT UNSIGNED,
    merchant_order_id VARCHAR(255) NOT NULL,
    plan_subscription_id BIGINT UNSIGNED,
    status VARCHAR(20) NOT NULL,
    order_status VARCHAR(50) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_subscription_id) REFERENCES plan_subscriptions(id) ON DELETE CASCADE
);
```

##### **payment_mercado_pago_invoices**

```sql
CREATE TABLE payment_mercado_pago_invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id VARCHAR(255) NOT NULL,
    tenant_id BIGINT UNSIGNED,
    invoice_id BIGINT UNSIGNED,
    status VARCHAR(20) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_amount DECIMAL(10,2) NOT NULL,
    transaction_date DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);
```

##### **provider_credentials**

```sql
CREATE TABLE provider_credentials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_gateway VARCHAR(50) NOT NULL,
    access_token_encrypted TEXT NOT NULL,
    refresh_token_encrypted TEXT NOT NULL,
    public_key VARCHAR(50) NOT NULL,
    user_id_gateway VARCHAR(50) NOT NULL,
    expires_in INT NULL,
    provider_id BIGINT UNSIGNED,
    tenant_id BIGINT UNSIGNED,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

#### **📋 Sistema de Agendamentos**

##### **schedules**

```sql
CREATE TABLE schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    service_id BIGINT UNSIGNED,
    user_confirmation_token_id BIGINT UNSIGNED,
    start_date_time DATETIME NOT NULL,
    end_date_time DATETIME NOT NULL,
    location VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (user_confirmation_token_id) REFERENCES user_confirmation_tokens(id) ON DELETE CASCADE
);
```

#### **📊 Sistema de Relatórios e Notificações**

##### **reports**

```sql
CREATE TABLE reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    hash VARCHAR(64) NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT NULL,
    file_name VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL, -- pending, processing, completed, failed
    format VARCHAR(10) NOT NULL, -- pdf, xlsx, csv
    size FLOAT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

##### **notifications**

```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    type VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

#### **🔍 Sistema de Auditoria e Monitoramento**

##### **audit_logs**

```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    model_type VARCHAR(255) NULL,
    model_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    metadata JSON NULL,
    description TEXT NULL,
    severity ENUM('low', 'info', 'warning', 'high', 'critical') DEFAULT 'info',
    category VARCHAR(50) NULL,
    is_system_action BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_audit_tenant_created (tenant_id, created_at),
    INDEX idx_audit_user_created (user_id, created_at),
    INDEX idx_audit_tenant_severity (tenant_id, severity),
    INDEX idx_audit_tenant_category (tenant_id, category),
    INDEX idx_audit_tenant_action (tenant_id, action),
    INDEX idx_audit_model (model_type, model_id),
    INDEX idx_audit_tenant_user_created (tenant_id, user_id, created_at)
);
```

##### **activities**

```sql
CREATE TABLE activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    action_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    metadata TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

##### **middleware_metrics_history**

```sql
CREATE TABLE middleware_metrics_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    middleware_name VARCHAR(100) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    response_time FLOAT NOT NULL,
    memory_usage BIGINT UNSIGNED NULL,
    cpu_usage FLOAT NULL,
    status_code INT NOT NULL,
    error_message TEXT NULL,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    request_size BIGINT UNSIGNED NULL,
    response_size BIGINT UNSIGNED NULL,
    database_queries INT NULL,
    cache_hits INT NULL,
    cache_misses INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

##### **monitoring_alerts_history**

```sql
CREATE TABLE monitoring_alerts_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    alert_type VARCHAR(20) NOT NULL, -- performance,error,security,availability,resource
    severity VARCHAR(10) NOT NULL,   -- low,medium,high,critical
    middleware_name VARCHAR(100) NOT NULL,
    endpoint VARCHAR(255) NULL,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,3) NOT NULL,
    threshold_value DECIMAL(10,3) NOT NULL,
    message TEXT NOT NULL,
    additional_data JSON NULL,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_at DATETIME NULL,
    resolved_by BIGINT UNSIGNED NULL,
    resolution_notes TEXT NULL,
    notification_sent BOOLEAN DEFAULT FALSE,
    notification_sent_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### **⚙️ Tabelas de Sistema**

##### **alert_settings**

```sql
CREATE TABLE alert_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    settings JSON NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

##### **resources**

```sql
CREATE TABLE resources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    in_dev BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) NOT NULL, -- active, inactive, deleted
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

##### **supports**

```sql
CREATE TABLE supports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(255) NULL,
    last_name VARCHAR(255) NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(30) NOT NULL,
    tenant_id BIGINT UNSIGNED,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

#### **📊 Tabelas de Status**

##### **budget_statuses**

```sql
CREATE TABLE budget_statuses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) UNIQUE NOT NULL,
    description VARCHAR(500) NULL,
    color VARCHAR(7) NULL,
    icon VARCHAR(50) NULL,
    order_index INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

##### **service_statuses**

```sql
CREATE TABLE service_statuses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(500) NULL,
    color VARCHAR(7) NULL,
    icon VARCHAR(30) NULL,
    order_index INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

##### **invoice_statuses**

```sql
CREATE TABLE invoice_statuses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(500) NULL,
    color VARCHAR(7) NULL,
    icon VARCHAR(50) NULL,
    order_index INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### **⚙️ Tabelas de Configurações**

##### **user_settings**

```sql
CREATE TABLE user_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    avatar VARCHAR(255) NULL,
    full_name VARCHAR(255) NULL,
    bio TEXT NULL,
    phone VARCHAR(20) NULL,
    birth_date DATE NULL,
    social_facebook VARCHAR(255) NULL,
    social_twitter VARCHAR(255) NULL,
    social_linkedin VARCHAR(255) NULL,
    social_instagram VARCHAR(255) NULL,
    theme VARCHAR(20) DEFAULT 'auto',
    primary_color VARCHAR(7) DEFAULT '#3B82F6',
    layout_density VARCHAR(20) DEFAULT 'normal',
    sidebar_position VARCHAR(10) DEFAULT 'left',
    animations_enabled BOOLEAN DEFAULT TRUE,
    sound_enabled BOOLEAN DEFAULT TRUE,
    email_notifications BOOLEAN DEFAULT TRUE,
    transaction_notifications BOOLEAN DEFAULT TRUE,
    weekly_reports BOOLEAN DEFAULT FALSE,
    security_alerts BOOLEAN DEFAULT TRUE,
    newsletter_subscription BOOLEAN DEFAULT FALSE,
    push_notifications BOOLEAN DEFAULT FALSE,
    custom_preferences JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_settings_tenant_user (tenant_id, user_id)
);
```

##### **system_settings**

```sql
CREATE TABLE system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED,
    company_name VARCHAR(255) NULL,
    contact_email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    website VARCHAR(255) NULL,
    logo VARCHAR(255) NULL,
    currency VARCHAR(3) DEFAULT 'BRL',
    timezone VARCHAR(50) DEFAULT 'America/Sao_Paulo',
    language VARCHAR(10) DEFAULT 'pt-BR',
    address_street VARCHAR(255) NULL,
    address_number VARCHAR(20) NULL,
    address_complement VARCHAR(100) NULL,
    address_neighborhood VARCHAR(100) NULL,
    address_city VARCHAR(100) NULL,
    address_state VARCHAR(50) NULL,
    address_zip_code VARCHAR(10) NULL,
    address_country VARCHAR(50) NULL,
    maintenance_mode BOOLEAN DEFAULT FALSE,
    maintenance_message TEXT NULL,
    registration_enabled BOOLEAN DEFAULT TRUE,
    email_verification_required BOOLEAN DEFAULT TRUE,
    session_lifetime INT DEFAULT 120,
    max_login_attempts INT DEFAULT 5,
    lockout_duration INT DEFAULT 15,
    allowed_file_types JSON NULL,
    max_file_size INT DEFAULT 2048,
    system_preferences JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_system_settings_tenant (tenant_id)
);
```

#### **💾 Tabelas de Cache e Sessões**

##### **cache**

```sql
CREATE TABLE cache (
    `key` VARCHAR(255) PRIMARY KEY,
    `value` MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL
);
```

##### **cache_locks**

```sql
CREATE TABLE cache_locks (
    `key` VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL
);
```

##### **sessions**

```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sessions_user_id (user_id),
    INDEX idx_sessions_last_activity (last_activity)
);
```

### **⚡ Índices de Performance**

#### **Índices Compostos**

```sql
-- Performance para budgets
CREATE INDEX idx_budgets_tenant_status_date ON budgets (tenant_id, status, created_at);
CREATE INDEX idx_budgets_customer_tenant ON budgets (customer_id, tenant_id);

-- Performance para customers
CREATE INDEX idx_customers_tenant_type_name ON customers (tenant_id, type, name);

-- Performance para audit_logs
CREATE INDEX idx_audit_logs_tenant_action_date ON audit_logs (tenant_id, action, created_at);
CREATE INDEX idx_audit_logs_user_tenant ON audit_logs (user_id, tenant_id);
CREATE INDEX idx_audit_logs_severity_category ON audit_logs (severity, category);
```

#### **Índices Parciais**

```sql
-- Índices condicionais para otimização
CREATE INDEX idx_budgets_tenant_active ON budgets (tenant_id, status) WHERE status = 'active';
```

### **🔗 Relacionamentos Chave**

#### **Multi-tenant Isolation**

-  Todas as tabelas principais têm `tenant_id`
-  Foreign keys com CASCADE delete para limpeza automática
-  Global scopes aplicados automaticamente

#### **Business Logic Relationships**

```
tenants (1) ──── (N) users
tenants (1) ──── (N) customers
tenants (1) ──── (N) products
customers (1) ──── (N) budgets
budgets (1) ──── (N) budget_items
products (1) ──── (N) budget_items
budgets (1) ──── (0,1) invoices
invoices (1) ──── (N) payments
```

### **📈 Estratégias de Otimização**

#### **Partitioning Strategy**

-  **Temporal partitioning** para tabelas de auditoria (por mês/ano)
-  **Tenant-based partitioning** para grandes volumes de dados
-  **Archive strategy** para dados antigos

#### **Query Optimization**

-  **Eager loading** para relacionamentos N+1
-  **Select specific columns** para reduzir transferência de dados
-  **Subqueries otimizadas** com índices adequados
-  **Batch operations** para múltiplas atualizações

### **🔒 Segurança e Integridade**

#### **Referential Integrity**

-  Foreign key constraints em todos os relacionamentos
-  CASCADE delete para dependências obrigatórias
-  SET NULL para relacionamentos opcionais

#### **Data Validation**

-  CHECK constraints para enums e validações
-  NOT NULL constraints para campos obrigatórios
-  UNIQUE constraints para campos únicos
-  JSON validation para campos estruturados

### **📊 Monitoramento e Manutenção**

#### **Performance Monitoring**

-  Query execution time tracking
-  Index usage statistics
-  Table size monitoring
-  Slow query logging

#### **Maintenance Tasks**

-  Regular index optimization (`OPTIMIZE TABLE`)
-  Deadlock monitoring e resolução
-  Connection pool monitoring
-  Backup verification

Este documento descreve o schema completo e otimizado do banco de dados Easy Budget Laravel, incluindo todas as tabelas, relacionamentos, índices e estratégias de performance implementadas.

**Última atualização:** 08/10/2025 - Revisão completa baseada na migration inicial, adicionadas tabelas faltantes (user_settings, system_settings, cache, cache_locks, sessions) e atualizado contador para 40+ tabelas.
