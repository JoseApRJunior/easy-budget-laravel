# Data Model: Easy Budget Platform

**Date**: 2025-10-23 | **Input**: Database schema from memory bank

## Overview

This document outlines the data model for the Easy Budget Platform, based on the comprehensive database schema. The model supports multi-tenant architecture with complete data isolation and includes all necessary entities for CRM, financial management, reporting, and system operations.

## Core Entities

### 1. Tenant

**Purpose**: Represents a separate business or organization in the multi-tenant system.

**Attributes**:

-  id (BIGINT, PRIMARY KEY)
-  name (VARCHAR(255), UNIQUE)
-  is_active (BOOLEAN, DEFAULT TRUE)
-  created_at, updated_at (TIMESTAMP)

**Relationships**:

-  One-to-many with Users, Customers, Products, Budgets, etc.
-  All business data belongs to a tenant

**Constraints**:

-  Complete data isolation via tenant_id foreign keys
-  CASCADE delete for dependent data

### 2. User

**Purpose**: Represents individuals accessing the platform with role-based access.

**Attributes**:

-  id (BIGINT, PRIMARY KEY)
-  tenant_id (BIGINT, FOREIGN KEY to tenants)
-  email (VARCHAR(100), UNIQUE)
-  password (VARCHAR(255))
-  is_active (BOOLEAN, DEFAULT TRUE)
-  logo (VARCHAR(255))
-  remember_token (VARCHAR(100))
-  created_at, updated_at (TIMESTAMP)

**Relationships**:

-  Belongs to Tenant
-  Has many Budgets, Invoices, Audit Logs
-  Many-to-many with Roles via user_roles

**Security**:

-  Passwords hashed
-  Google OAuth integration (google_id, avatar, google_data)

### 3. Customer

**Purpose**: Represents clients or customers of the business for CRM management.

**Attributes**:

-  id (BIGINT, PRIMARY KEY)
-  tenant_id (BIGINT, FOREIGN KEY)
-  common_data_id (BIGINT, FOREIGN KEY to common_datas)
-  contact_id (BIGINT, FOREIGN KEY to contacts)
-  address_id (BIGINT, FOREIGN KEY to addresses)
-  status (VARCHAR(20))
-  created_at, updated_at (TIMESTAMP)

**Relationships**:

-  Belongs to Tenant, Common Data, Contact, Address
-  Has many Budgets, Invoices

**CRM Features**:

-  Interaction history tracking
-  Segmentation and tagging
-  Multiple addresses and contacts

### 4. Budget

**Purpose**: Represents financial proposals or estimates for services.

**Attributes**:

-  id (BIGINT, PRIMARY KEY)
-  tenant_id (BIGINT, FOREIGN KEY)
-  customer_id (BIGINT, FOREIGN KEY to customers)
-  budget_statuses_id (BIGINT, FOREIGN KEY)
-  user_confirmation_token_id (BIGINT, FOREIGN KEY)
-  code (VARCHAR(50), UNIQUE)
-  due_date (DATE)
-  discount (DECIMAL(10,2))
-  total (DECIMAL(10,2))
-  description (TEXT)
-  payment_terms (TEXT)
-  attachment (VARCHAR(255))
-  history (LONGTEXT)
-  pdf_verification_hash (VARCHAR(64), UNIQUE)
-  created_at, updated_at (TIMESTAMP)

**Relationships**:

-  Belongs to Tenant, Customer, Budget Status
-  Has many Services, Invoices
-  Linked to User Confirmation Token for approval

**Features**:

-  Versioned with history tracking
-  PDF generation with verification hash
-  Approval workflow via tokens

### 5. Service

**Purpose**: Represents specific service offerings within a budget.

**Attributes**:

-  id (BIGINT, PRIMARY KEY)
-  tenant_id (BIGINT, FOREIGN KEY)
-  budget_id (BIGINT, FOREIGN KEY to budgets)
-  category_id (BIGINT, FOREIGN KEY to categories)
-  service_statuses_id (BIGINT, FOREIGN KEY)
-  code (VARCHAR(50), UNIQUE)
-  description (TEXT)
-  discount (DECIMAL(10,2), DEFAULT 0)
-  total (DECIMAL(10,2), DEFAULT 0)
-  due_date (DATE)
-  pdf_verification_hash (VARCHAR(64))
-  created_at, updated_at (TIMESTAMP)

**Relationships**:

-  Belongs to Tenant, Budget, Category, Service Status
-  Has many Service Items, Invoices

### 6. Invoice

**Purpose**: Represents billing documents for completed services.

**Attributes**:

-  id (BIGINT, PRIMARY KEY)
-  tenant_id (BIGINT, FOREIGN KEY)
-  service_id (BIGINT, FOREIGN KEY to services)
-  customer_id (BIGINT, FOREIGN KEY to customers)
-  invoice_statuses_id (BIGINT, FOREIGN KEY)
-  code (VARCHAR(50), UNIQUE)
-  public_hash (VARCHAR(64))
-  subtotal (DECIMAL(10,2))
-  discount (DECIMAL(10,2))
-  total (DECIMAL(10,2))
-  due_date (DATE)
-  payment_method (VARCHAR(50))
-  payment_id (VARCHAR(255))
-  transaction_amount (DECIMAL(10,2))
-  transaction_date (DATETIME)
-  notes (TEXT)
-  created_at, updated_at (TIMESTAMP)

**Relationships**:

-  Belongs to Tenant, Service, Customer, Invoice Status
-  Has many Invoice Items, Payment Records

**Integration**:

-  Mercado Pago payment tracking
-  Public hash for customer access

### 7. Product

**Purpose**: Represents items in the catalog for services and inventory.

**Attributes**:

-  id (BIGINT, PRIMARY KEY)
-  tenant_id (BIGINT, FOREIGN KEY)
-  name (VARCHAR(255))
-  description (VARCHAR(500))
-  price (DECIMAL(10,2))
-  active (BOOLEAN, DEFAULT TRUE)
-  code (VARCHAR(50))
-  image (VARCHAR(255))
-  created_at, updated_at (TIMESTAMP)

**Relationships**:

-  Belongs to Tenant
-  Has many Product Inventory, Service Items, Invoice Items

**Inventory**:

-  Linked to product_inventory for stock management
-  Inventory movements tracked separately

### 8. Audit Log

**Purpose**: Records all system activities for compliance and monitoring.

**Attributes**:

-  id (BIGINT, PRIMARY KEY)
-  tenant_id (BIGINT, FOREIGN KEY)
-  user_id (BIGINT, FOREIGN KEY to users)
-  action (VARCHAR(100))
-  model_type (VARCHAR(255))
-  model_id (BIGINT)
-  old_values (JSON)
-  new_values (JSON)
-  ip_address (VARCHAR(45))
-  user_agent (TEXT)
-  metadata (JSON)
-  description (TEXT)
-  severity (ENUM: low, info, warning, high, critical)
-  category (VARCHAR(50))
-  is_system_action (BOOLEAN, DEFAULT FALSE)
-  created_at, updated_at (TIMESTAMP)

**Relationships**:

-  Belongs to Tenant, User
-  Tracks changes to any model

**Features**:

-  Comprehensive tracking with context
-  Severity and category classification
-  IP and user agent logging

## Supporting Entities

### Common Data

**Purpose**: Shared data structure for people and companies.

**Attributes**:

-  id (BIGINT, PRIMARY KEY)
-  tenant_id (BIGINT, FOREIGN KEY)
-  first_name, last_name (VARCHAR(100))
-  birth_date (DATE)
-  cnpj, cpf (VARCHAR, UNIQUE)
-  company_name (VARCHAR(255))
-  description (TEXT)
-  area_of_activity_id, profession_id (BIGINT, FOREIGN KEY)

**Usage**: Used by Customers and Providers

### Plan Subscription

**Purpose**: Manages subscription plans and Mercado Pago integration.

**Attributes**:

-  id (BIGINT, PRIMARY KEY)
-  status (ENUM: active, cancelled, pending, expired)
-  transaction_amount (DECIMAL(10,2))
-  start_date, end_date (DATETIME)
-  transaction_date (DATETIME)
-  payment_method, payment_id (VARCHAR)
-  public_hash (VARCHAR(255))
-  last_payment_date, next_payment_date (DATETIME)
-  tenant_id, provider_id, plan_id (BIGINT, FOREIGN KEY)

**Integration**: Mercado Pago payment processing

### Email Metrics (Planned)

**Purpose**: Track email performance for system evolution.

**Attributes**:

-  id (BIGINT, PRIMARY KEY)
-  tenant_id (BIGINT, FOREIGN KEY)
-  email_type (VARCHAR(50))
-  recipient_email (VARCHAR(255))
-  status (VARCHAR(20))
-  delivery_time (DATETIME)
-  opened_at, clicked_at (DATETIME)
-  metadata (JSON)

## Relationships Summary

```
tenants (1) ─── (N) users
tenants (1) ─── (N) customers
tenants (1) ─── (N) products
tenants (1) ─── (N) budgets
tenants (1) ─── (N) services
tenants (1) ─── (N) invoices
tenants (1) ─── (N) audit_logs
customers (1) ─── (N) budgets
customers (1) ─── (N) invoices
budgets (1) ─── (N) services
services (1) ─── (N) invoices
products (1) ─── (N) service_items
products (1) ─── (N) invoice_items
```

## Performance Considerations

### Indexes

-  Composite indexes for tenant-based queries (e.g., tenant_id, status, created_at)
-  Unique constraints for codes and emails
-  Partial indexes for active records

### Optimizations

-  Eager loading for relationships
-  Caching with Redis for reports and metrics
-  Query optimization for large datasets
-  Pagination for list views

## Security and Compliance

### Multi-tenant Isolation

-  All tables have tenant_id
-  Foreign keys with CASCADE delete
-  Global scopes applied automatically

### Audit Trail

-  All changes logged with full context
-  Severity and category classification
-  IP and user agent tracking

### Data Protection

-  Sensitive data encrypted
-  LGPD/GDPR compliance
-  Access controls via RBAC

This data model provides a solid foundation for the Easy Budget Platform, ensuring scalability, security, and comprehensive business management capabilities.
