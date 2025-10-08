-- ========================================
-- DADOS DE USUÁRIOS E EXEMPLOS
-- ========================================

-- Users
INSERT INTO `users` (`id`, `tenant_id`, `email`, `password`, `logo`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin@easybudget.net.br', '$2y$10$LhZe8oTmEXpuLfKwAcVGF.92ircrGI0B0jJAIG82Qg9KIwAqOeb2m', NULL, 1, '2025-05-29 00:00:00', '0000-00-00 00:00:00'),
(2, 2, 'manager@easybudget.net.br', '$2y$10$LhZe8oTmEXpuLfKwAcVGF.92ircrGI0B0jJAIG82Qg9KIwAqOeb2m', NULL, 1, '2025-05-29 00:00:00', '0000-00-00 00:00:00'),
(3, 3, 'teste@easybudget.net.br', '$2y$10$LhZe8oTmEXpuLfKwAcVGF.92ircrGI0B0jJAIG82Qg9KIwAqOeb2m', NULL, 1, '2025-05-29 00:00:00', '0000-00-00 00:00:00');

-- User Roles
INSERT INTO `user_roles` (`user_id`, `role_id`, `tenant_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 2, 2, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 3, '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Contacts
INSERT INTO `contacts` (`id`, `tenant_id`, `email`, `email_business`, `phone`, `phone_business`, `website`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin@easybudget.net.br', 'admin@easybudget.net.br', '43999590945', '43999590945', 'https://easybudget.net.br', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 2, 'manager@easybudget.net.br', 'manager@easybudget.net.br', '43999590945', '43999590945', 'https://easybudget.net.br', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 'teste@easybudget.net.br', 'teste@easybudget.net.br', '43999590945', '43999590945', 'https://easybudget.net.br', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 3, 'user@easybudget.net.br', 'user@easybudget.net.br', '43999590945', '43999590945', 'https://easybudget.net.br', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Common Datas
INSERT INTO `common_datas` (`id`, `tenant_id`, `first_name`, `last_name`, `birth_date`, `cnpj`, `cpf`, `company_name`, `area_of_activity_id`, `profession_id`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin', 'admin', '1990-01-01 00:00:00', '12345678901234', '12345678901', 'EasyBudget', 81, 17, 'Administrador do sistema', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 2, 'manager', 'manager', '1990-01-01 00:00:00', '12345678901234', '12345678901', 'EasyBudget', 81, 17, 'Gerente do sistema', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 'teste', 'teste', '1990-01-01 00:00:00', '12345678901234', '12345678901', 'EasyBudget', 81, 17, 'Prestador do sistema', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 3, 'Cliente', 'Teste', '1990-01-01 00:00:00', '12345678901234', '12345678901', 'EasyBudget', 81, 17, 'Usuário do sistema', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Addresses
INSERT INTO `addresses` (`id`, `tenant_id`, `address`, `address_number`, `neighborhood`, `city`, `state`, `cep`, `created_at`, `updated_at`) VALUES
(1, 1, 'rua dos administradores', '123', 'bairro dos administradores', 'cidade dos administradores', 'SP', '12345678', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 2, 'rua dos gerentes', '123', 'bairro dos gerentes', 'cidade dos gerentes', 'SP', '12345678', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 'rua dos prestadores', '123', 'bairro dos prestadores', 'cidade dos prestadores', 'SP', '12345678', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 3, 'rua dos usuários', '123', 'bairro dos usuários', 'cidade dos usuários', 'SP', '12345678', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Providers
INSERT INTO `providers` (`id`, `tenant_id`, `user_id`, `common_data_id`, `contact_id`, `address_id`, `terms_accepted`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 2, 2, 2, 2, 2, 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 3, 3, 3, 3, 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Customers
INSERT INTO `customers` (`id`, `tenant_id`, `common_data_id`, `contact_id`, `address_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 4, 4, 4, 'active', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Plan Subscriptions
INSERT INTO `plan_subscriptions` (`id`, `provider_id`, `plan_id`, `tenant_id`, `status`, `public_hash`, `transaction_amount`, `start_date`, `end_date`, `payment_method`, `payment_id`, `last_payment_date`, `next_payment_date`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 1, 'pending', NULL, 0.00, '2025-05-29 00:00:00', '2025-07-04 00:00:00', NULL, NULL, NULL, NULL, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 2, 2, 2, 'pending', NULL, 0.00, '2025-05-29 00:00:00', '2025-07-04 00:00:00', NULL, NULL, NULL, NULL, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 3, 3, 'active', NULL, 10.00, '2025-05-29 00:00:00', '2025-07-04 00:00:00', NULL, NULL, NULL, NULL, '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Budgets
INSERT INTO `budgets` (`id`, `tenant_id`, `customer_id`, `budget_statuses_id`, `code`, `due_date`, `total`, `description`, `payment_terms`, `attachment`, `history`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 1, '202505290001', '2023-12-31 00:00:00', 1000.00, 'Orçamento para reforma', 'Pagamento em 2x', NULL, NULL, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 3, 1, 2, '202505290002', '2023-12-31 00:00:00', 2000.00, 'Orçamento para pintura', 'Pagamento à vista', NULL, NULL, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 1, 3, '202505290003', '2023-12-31 00:00:00', 1500.00, 'Orçamento para elétrica', 'Pagamento em 3x', NULL, NULL, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 3, 1, 4, '202505290004', '2023-12-31 00:00:00', 2500.00, 'Orçamento para hidráulica', 'Pagamento em 4x', NULL, NULL, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(5, 3, 1, 1, '202505290005', '2023-12-31 00:00:00', 3000.00, 'Orçamento para construção', 'Pagamento em 5x', NULL, NULL, '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Services
INSERT INTO `services` (`id`, `tenant_id`, `budget_id`, `category_id`, `service_statuses_id`, `code`, `description`, `discount`, `total`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 1, 1, '202505290001-S001', 'Serviço de reforma', 0.00, 500.00, '2023-12-15 00:00:00', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 3, 2, 2, 2, '202505290002-S002', 'Serviço de pintura', 0.00, 1000.00, '2023-12-20 00:00:00', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 3, 3, 3, '202505290003-S003', 'Serviço de elétrica', 0.00, 750.00, '2023-12-25 00:00:00', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 3, 4, 4, 4, '202505290004-S004', 'Serviço de hidráulica', 0.00, 1250.00, '2023-12-30 00:00:00', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(5, 3, 5, 5, 1, '202505290005-S005', 'Serviço de construção', 0.00, 1500.00, '2023-12-31 00:00:00', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Products
INSERT INTO `products` (`id`, `tenant_id`, `code`, `name`, `description`, `price`, `active`, `image`, `created_at`, `updated_at`) VALUES
(1, 3, 'TINT001', 'Lata de Tinta Acrílica 18L', 'Tinta acrílica de alta qualidade, ideal para pintura de paredes internas e externas.', 220.50, 1, 'https://example.com/tinta-acrilica.jpg', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(2, 3, 'TINT002', 'Lata de Tinta Látex 15L', 'Tinta látex premium com excelente cobertura e durabilidade para ambientes residenciais.', 199.99, 1, 'https://example.com/tinta-latex.jpg', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(3, 3, 'TINT003', 'Lata de Tinta Epóxi 5L', 'Tinta epóxi resistente, indicada para pisos industriais e áreas de alto tráfego.', 320.00, 1, 'https://example.com/tinta-epoxi.jpg', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(4, 3, 'EQUIP001', 'Lixadeira Orbital', 'Equipamento elétrico para lixamento de superfícies, preparando-as para a pintura.', 350.00, 1, 'https://example.com/lixadeira-orbital.jpg', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(5, 3, 'ACESS001', 'Conjunto de Pincéis', 'Conjunto com pincéis de diferentes tamanhos, ideal para detalhes e acabamento na pintura.', 45.00, 1, 'https://example.com/conjunto-de-pinceis.jpg', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(6, 3, 'PROT001', 'Lona de Proteção 3x3m', 'Lona descartável para proteger móveis e pisos durante a aplicação da tinta.', 18.00, 1, 'https://example.com/lona-protecao.jpg', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(7, 3, 'EPI001', 'Kit de Proteção Individual', 'Kit completo com máscara, luvas e óculos de proteção para pintura.', 75.00, 1, 'https://example.com/kit-protecao-individual.jpg', '2025-05-29 12:44:31', '2025-05-29 12:44:31');

-- Service Items
INSERT INTO `service_items` (`id`, `tenant_id`, `service_id`, `product_id`, `quantity`, `unit_value`, `total`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 1, 2, 50.00, 100.00, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 3, 2, 2, 3, 100.00, 300.00, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 3, 3, 1, 75.00, 75.00, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 3, 4, 4, 4, 125.00, 500.00, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(5, 3, 1, 5, 5, 150.00, 750.00, '2025-05-29 09:44:31', '2025-05-29 09:44:31');