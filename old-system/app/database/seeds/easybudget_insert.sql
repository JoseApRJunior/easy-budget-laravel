-- Inserindo dados para a tabela easybudget.units: ~13 rows (aproximadamente)
INSERT INTO `units` (`id`, `slug`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'cm', 'Centímetro', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 'g', 'Gramas', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 'kg', 'Kilograma', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 'l', 'Litro', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(5, 'm', 'Metro', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(6, 'm2', 'Metro Quadrado', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(7, 'm3', 'Metro Cúbico', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(8, 'mm', 'Milímetro', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(9, 'ml', 'Mililitro', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(10, 'ft', 'Pé', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(11, 'in', 'Polegada', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(12, 't', 'Tonelada', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(13, 'un', 'Unidade', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Inserindo dados para a tabela easybudget.roles: ~4 rows (aproximadamente)
INSERT INTO `roles` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Administrador com acesso total', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 'manager', 'Gerente com acesso parcial', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 'provider', 'Prestador padrão', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 'user', 'Usuário padrão', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Inserindo dados para a tabela easybudget.permissions: ~5 rows (aproximadamente)
INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'create_user', 'Criar novos usuários', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 'edit_user', 'Editar usuários existentes', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 'delete_user', 'Excluir usuários', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 'view_reports', 'Visualizar relatórios', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(5, 'manage_budget', 'Gerenciar orçamentos', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Inserindo dados para a tabela easybudget.role_permissions: ~8 rows (aproximadamente)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
(2, 4), (2, 5),
(3, 4);

-- Inserindo dados para a tabela easybudget.tenants: ~3 rows (aproximadamente)
INSERT INTO `tenants` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'admin_1716968671_a1b2c3d4', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 'manager_1716968671_e5f6g7h8', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 'teste_1716968671_i9j0k1l2', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Inserindo dados para a tabela easybudget.users: ~3 rows (aproximadamente)
INSERT INTO `users` (`id`, `tenant_id`, `email`, `password`, `logo`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin@easybudget.net.br', '$2y$10$LhZe8oTmEXpuLfKwAcVGF.92ircrGI0B0jJAIG82Qg9KIwAqOeb2m', NULL, 1, '2025-05-29 00:00:00', '0000-00-00 00:00:00'),
(2, 2, 'manager@easybudget.net.br', '$2y$10$LhZe8oTmEXpuLfKwAcVGF.92ircrGI0B0jJAIG82Qg9KIwAqOeb2m', NULL, 1, '2025-05-29 00:00:00', '0000-00-00 00:00:00'),
(3, 3, 'teste@easybudget.net.br', '$2y$10$LhZe8oTmEXpuLfKwAcVGF.92ircrGI0B0jJAIG82Qg9KIwAqOeb2m', NULL, 1, '2025-05-29 00:00:00', '0000-00-00 00:00:00');

-- Inserindo dados para a tabela easybudget.user_roles: ~3 rows (aproximadamente)
INSERT INTO `user_roles` (`user_id`, `role_id`, `tenant_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 2, 2, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 3, '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Inserindo dados para a tabela easybudget.areas_of_activity: ~83 rows (aproximadamente)
INSERT INTO `areas_of_activity` (`id`, `slug`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'others', 'Outros', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 'aerospace', 'Aeroespacial', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 'agriculture', 'Agricultura', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 'food_and_beverage', 'Alimentos e Bebidas', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(5, 'animation', 'Animação', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(6, 'analytics', 'Análise de Dados', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(7, 'mobile_app', 'Aplicativo Móvel', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(8, 'architecture', 'Arquitetura', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(9, 'art', 'Arte', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(10, 'plan-subscription', 'Assinatura de Plano', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(11, 'automotive', 'Automotivo', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(12, 'biotechnology', 'Biotecnologia', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(13, 'blockchain', 'Blockchain', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(14, 'venture_capital', 'Capital de Risco', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(15, 'supply_chain', 'Cadeia de Suprimentos', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(16, 'film', 'Cinema', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(17, 'data_science', 'Ciência de Dados', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(18, 'retail', 'Comércio', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(19, 'e_commerce', 'Comércio Eletrônico', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(20, 'cloud_computing', 'Computação em Nuvem', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(21, 'construction', 'Construção', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(22, 'consulting', 'Consultoria', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(23, 'accounting', 'Contabilidade', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(24, 'parts-control', 'Controle de Peças', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(25, 'web_development', 'Desenvolvimento Web', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(26, 'design', 'Design', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(27, 'interior_design', 'Design de Interiores', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(28, 'education', 'Educação', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(29, 'energy', 'Energia', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(30, 'e_learning', 'Ensino a Distância', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(31, 'entertainment', 'Entretenimento', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(32, 'sports', 'Esportes', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(33, 'pharmaceuticals', 'Farmacêutica', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(34, 'billing', 'Faturamento', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(35, 'fintech', 'Fintech', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(36, 'finance', 'Financeiro', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(37, 'photography', 'Fotografia', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(38, 'franchise', 'Franquia', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(39, 'team-management', 'Gestão de Equipe', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(40, 'waste_management', 'Gestão de Resíduos', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(41, 'government', 'Governo', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(42, 'hardware', 'Hardware', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(43, 'hospitality', 'Hospitalidade', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(44, 'real_estate', 'Imobiliário', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(45, 'industrial', 'Indústria', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(46, 'whatsapp-integration', 'Integração WhatsApp', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(47, 'artificial_intelligence', 'Inteligência Artificial', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(48, 'gaming', 'Jogos', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(49, 'journalism', 'Jornalismo', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(50, 'logistics', 'Logística', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(51, 'manufacturing', 'Manufatura', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(52, 'marketing', 'Marketing', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(53, 'digital_marketing', 'Marketing Digital', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(54, 'environment', 'Meio Ambiente', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(55, 'media', 'Mídia', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(56, 'mining', 'Mineração', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(57, 'music', 'Música', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(58, 'non_profit', 'Organizações Sem Fins Lucrativos', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(59, 'budgets', 'Orçamentos', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(60, 'research', 'Pesquisa', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(61, 'biotechnology_research', 'Pesquisa em Biotecnologia', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(62, 'private_equity', 'Private Equity', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(63, 'publishing', 'Publicação', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(64, 'advertising', 'Publicidade', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(65, 'chemicals', 'Química', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(66, 'recycling', 'Reciclagem', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(67, 'public_relations', 'Relações Públicas', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(68, 'health', 'Saúde', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(69, 'security', 'Segurança', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(70, 'biotechnology_services', 'Serviços de Biotecnologia', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(71, 'consulting_services', 'Serviços de Consultoria', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(72, 'mining_services', 'Serviços de Mineração', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(73, 'healthcare_services', 'Serviços de Saúde', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(74, 'telecommunications_services', 'Serviços de Telecomunicações', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(75, 'tourism_services', 'Serviços de Turismo', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(76, 'travel_services', 'Serviços de Viagens', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(77, 'education_services', 'Serviços Educacionais', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(78, 'software', 'Software', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(79, 'telecommunications', 'Telecomunicações', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(80, 'outsourcing', 'Terceirização', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(81, 'technology', 'Tecnologia', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(82, 'vocational_training', 'Treinamento Profissional', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(83, 'travel', 'Turismo', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Inserindo dados para a tabela easybudget.professions: ~33 rows (aproximadamente)
INSERT INTO `professions` (`id`, `slug`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'others', 'Outros', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 'lawyer', 'Advogado', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 'architect', 'Arquiteto', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 'artist', 'Artista', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(5, 'biologist', 'Biólogo', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(6, 'chef', 'Chef de Cozinha', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(7, 'scientist', 'Cientista', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(8, 'political_scientist', 'Cientista Político', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(9, 'accountant', 'Contador', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(10, 'consultant', 'Consultor', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(11, 'dentist', 'Dentista', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(12, 'designer', 'Designer', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(13, 'economist', 'Economista', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(14, 'nurse', 'Enfermeiro', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(15, 'engineer', 'Engenheiro', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(16, 'writer', 'Escritor', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(17, 'it_specialist', 'Especialista em TI', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(18, 'pharmacist', 'Farmacêutico', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(19, 'physicist', 'Físico', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(20, 'historian', 'Historiador', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(21, 'journalist', 'Jornalista', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(22, 'linguist', 'Linguista', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(23, 'mathematician', 'Matemático', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(24, 'doctor', 'Médico', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(25, 'musician', 'Músico', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(26, 'pilot', 'Piloto', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(27, 'teacher', 'Professor', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(28, 'psychologist', 'Psicólogo', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(29, 'psychiatrist', 'Psiquiatra', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(30, 'geologist', 'Geólogo', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(31, 'sociologist', 'Sociólogo', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(32, 'technician', 'Técnico', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(33, 'veterinarian', 'Veterinário', 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Inserindo dados para a tabela easybudget.contacts: ~4 rows (aproximadamente)
INSERT INTO `contacts` (`id`, `tenant_id`, `email`, `email_business`, `phone`, `phone_business`, `website`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin@easybudget.net.br', 'admin@easybudget.net.br', '43999590945', '43999590945', 'https://easybudget.net.br', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 2, 'manager@easybudget.net.br', 'manager@easybudget.net.br', '43999590945', '43999590945', 'https://easybudget.net.br', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 'teste@easybudget.net.br', 'teste@easybudget.net.br', '43999590945', '43999590945', 'https://easybudget.net.br', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 3, 'user@easybudget.net.br', 'user@easybudget.net.br', '43999590945', '43999590945', 'https://easybudget.net.br', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Inserindo dados para a tabela easybudget.common_datas: ~4 rows (aproximadamente)
INSERT INTO `common_datas` (`id`, `tenant_id`, `first_name`, `last_name`, `birth_date`, `cnpj`, `cpf`, `company_name`, `description`, `created_at`, `updated_at`, `area_of_activity_id`, `profession_id`) VALUES
(1, 1, 'admin', 'admin', '1990-01-01 00:00:00', '12345678901234', '12345678901', 'EasyBudget', 'Administrador do sistema', '2025-05-29 09:44:31', '2025-05-29 09:44:31', 81, 17),
(2, 2, 'manager', 'manager', '1990-01-01 00:00:00', '12345678901234', '12345678901', 'EasyBudget', 'Gerente do sistema', '2025-05-29 09:44:31', '2025-05-29 09:44:31', 81, 17),
(3, 3, 'teste', 'teste', '1990-01-01 00:00:00', '12345678901234', '12345678901', 'EasyBudget', 'Prestador do sistema', '2025-05-29 09:44:31', '2025-05-29 09:44:31', 81, 17),
(4, 3, 'Cliente', 'Teste', '1990-01-01 00:00:00', '12345678901234', '12345678901', 'EasyBudget', 'Usuário do sistema', '2025-05-29 09:44:31', '2025-05-29 09:44:31', 81, 17);

-- Inserindo dados para a tabela easybudget.addresses: ~4 rows (aproximadamente)
INSERT INTO `addresses` (`id`, `tenant_id`, `address`, `address_number`, `neighborhood`, `city`, `state`, `cep`, `created_at`, `updated_at`) VALUES
(1, 1, 'rua dos administradores', '123', 'bairro dos administradores', 'cidade dos administradores', 'SP', '12345678', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 2, 'rua dos gerentes', '123', 'bairro dos gerentes', 'cidade dos gerentes', 'SP', '12345678', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 'rua dos prestadores', '123', 'bairro dos prestadores', 'cidade dos prestadores', 'SP', '12345678', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 3, 'rua dos usuários', '123', 'bairro dos usuários', 'cidade dos usuários', 'SP', '12345678', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Inserindo dados para a tabela easybudget.providers: ~3 rows (aproximadamente)
INSERT INTO `providers` (`id`, `tenant_id`, `user_id`, `common_data_id`, `contact_id`, `address_id`, `terms_accepted`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 2, 2, 2, 2, 2, 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 3, 3, 3, 3, 1, '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Inserindo dados para a tabela easybudget.customers: ~1 rows (aproximadamente)
INSERT INTO `customers` (`id`, `tenant_id`, `common_data_id`, `contact_id`, `address_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 4, 4, 4, 'active', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Inserindo dados para a tabela easybudget.plans: ~3 rows (aproximadamente)
INSERT INTO `plans` (`id`, `name`, `slug`, `description`, `price`, `status`, `max_budgets`, `max_clients`, `features`, `created_at`, `updated_at`) VALUES
(1, 'Plano Free', 'free', 'Comece com simplicidade e sem custos!', 0.00, 1, 3, 1, '["Acesso a recursos básicos","Até 3 orçamentos por mês","1 Cliente por mês"]', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 'Plano Básico', 'basic', 'Gerencie seus orçamentos com eficiência!', 15.00, 1, 15, 5, '["Acesso a recursos básicos","Até 15 orçamentos por mês","5 Clientes por mês","Relatórios básicos"]', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 'Plano Premium', 'premium', 'A solução completa para sua gestão!', 25.00, 1, -1, -1, '["Acesso a todos os recursos","Orçamentos ilimitados","Clientes ilimitados","Relatórios avançados","Integração com pagamentos","Gerencimento de projetos"]', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Inserindo dados para a tabela easybudget.plan_subscriptions: ~3 rows (aproximadamente)
INSERT INTO `plan_subscriptions` (`id`, `provider_id`, `plan_id`, `tenant_id`, `status`, `public_hash`, `transaction_amount`, `start_date`, `end_date`, `payment_method`, `payment_id`, `last_payment_date`, `next_payment_date`, `transaction_date`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 1, 'pending', NULL, 0.00, '2025-05-29 00:00:00', '2025-07-04 00:00:00', NULL, NULL, NULL, NULL, NULL, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 2, 2, 2, 'pending', NULL, 0.00, '2025-05-29 00:00:00', '2025-07-04 00:00:00', NULL, NULL, NULL, NULL, NULL, '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 3, 3, 3, 'active', NULL, 10.00, '2025-05-29 00:00:00', '2025-07-04 00:00:00', NULL, NULL, NULL, NULL, NULL, '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Inserindo dados para a tabela easybudget.budget_statuses: ~7 rows (aproximadamente)
INSERT INTO `budget_statuses` (`id`, `slug`, `name`, `description`, `color`, `icon`, `order_index`, `is_active`, `created_at`) VALUES
(1, 'DRAFT', 'Rascunho', 'Orçamento em elaboração, permite modificações', '#6c757d', 'bi-pencil-square', 1, 1, '2025-05-29 15:44:31'),
(2, 'PENDING', 'Pendente', 'Aguardando aprovação do cliente', '#ffc107', 'bi-clock', 2, 1, '2025-05-29 15:44:31'),
(3, 'APPROVED', 'Aprovado', 'Orçamento aprovado pelo cliente', '#28a745', 'bi-check-circle', 3, 1, '2025-05-29 15:44:31'),
(4, 'COMPLETED', 'Concluído', 'Todos os serviços foram realizados', '#28a745', 'bi-check2-all', 5, 1, '2025-05-29 15:44:31'),
(5, 'REJECTED', 'Rejeitado', 'Orçamento não aprovado pelo cliente', '#dc3545', 'bi-x-circle', 6, 1, '2025-05-29 15:44:31'),
(6, 'CANCELLED', 'Cancelado', 'Orçamento cancelado após aprovação', '#6c757d', 'bi-slash-circle', 7, 1, '2025-05-29 15:44:31'),
(7, 'EXPIRED', 'Expirado', 'Prazo de validade do orçamento expirado', '#dc3545', 'bi-calendar-x', 8, 1, '2025-05-29 15:44:31');

-- Inserindo dados para a tabela easybudget.resources: ~36 rows (aproximadamente)
INSERT INTO `resources` (`id`, `name`, `slug`, `in_dev`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Listagem de Planos', 'plan-listing', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(2, 'Detalhes do Plano', 'plan-details', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(3, 'Histórico de Planos', 'plan-history', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(4, 'Comparação de Planos', 'plan-comparison', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(5, 'Cadastro de Prestador', 'provider-registration', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(6, 'Atualização de Prestador', 'provider-update', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(7, 'Documentos do Prestador', 'provider-documents', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(8, 'Avaliações do Prestador', 'provider-ratings', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(9, 'Assinatura de Plano', 'plan-subscription', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(10, 'Renovação Automática', 'auto-renewal', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(11, 'Histórico de Pagamentos', 'payment-history', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(12, 'Cancelamento de Plano', 'plan-cancellation', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(13, 'Relatório de Prestadores', 'provider-reports', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(14, 'Análise de Planos', 'plan-analytics', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(15, 'Dashboard de Gestão', 'management-dashboard', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(16, 'Métricas de Desempenho', 'performance-metrics', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(17, 'Cadastro de Clientes', 'customer-management', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(18, 'Ordens de Serviço', 'service-orders', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(19, 'Cadastro de Serviços', 'service-registration', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(20, 'Status de Ordem', 'order-status', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(21, 'Agenda de Serviços', 'service-schedule', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(22, 'Gestão de Equipe', 'team-management', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(23, 'Controle de Peças', 'parts-control', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(24, 'Orçamentos', 'budgets', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(25, 'Faturamento', 'billing', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(26, 'Controle de Pagamentos', 'payment-control', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(27, 'Comissões', 'commissions', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(28, 'Fluxo de Caixa', 'cash-flow', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(29, 'Painel de Controle', 'dashboard', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(30, 'Relatórios Gerenciais', 'management-reports', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(31, 'Histórico de Clientes', 'customer-history', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(32, 'Avaliações de Serviço', 'service-ratings', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(33, 'Notificações Automáticas', 'auto-notifications', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(34, 'Lembretes de Manutenção', 'maintenance-reminders', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(35, 'Integração WhatsApp', 'whatsapp-integration', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31'),
(36, 'App Mobile', 'mobile-app', 1, 'inactive', '2025-05-29 15:44:31', '2025-05-29 15:44:31');