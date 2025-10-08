-- ========================================
-- INSERÇÃO DE DADOS INICIAIS
-- ========================================

-- Resources
INSERT INTO `resources` (`id`, `name`, `slug`, `in_dev`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Listagem de Planos', 'plan-listing', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(2, 'Detalhes do Plano', 'plan-details', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(3, 'Histórico de Planos', 'plan-history', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(4, 'Comparação de Planos', 'plan-comparison', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(5, 'Cadastro de Prestador', 'provider-registration', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(6, 'Atualização de Prestador', 'provider-update', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(7, 'Documentos do Prestador', 'provider-documents', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(8, 'Avaliações do Prestador', 'provider-ratings', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(9, 'Assinatura de Plano', 'plan-subscription', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(10, 'Renovação Automática', 'auto-renewal', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(11, 'Histórico de Pagamentos', 'payment-history', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(12, 'Cancelamento de Plano', 'plan-cancellation', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(13, 'Relatório de Prestadores', 'provider-reports', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(14, 'Análise de Planos', 'plan-analytics', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(15, 'Dashboard de Gestão', 'management-dashboard', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(16, 'Métricas de Desempenho', 'performance-metrics', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(17, 'Cadastro de Clientes', 'customer-management', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(18, 'Ordens de Serviço', 'service-orders', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(19, 'Cadastro de Serviços', 'service-registration', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(20, 'Status de Ordem', 'order-status', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(21, 'Agenda de Serviços', 'service-schedule', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(22, 'Gestão de Equipe', 'team-management', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(23, 'Controle de Peças', 'parts-control', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(24, 'Orçamentos', 'budgets', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(25, 'Faturamento', 'billing', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(26, 'Controle de Pagamentos', 'payment-control', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(27, 'Comissões', 'commissions', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(28, 'Fluxo de Caixa', 'cash-flow', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(29, 'Painel de Controle', 'dashboard', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(30, 'Relatórios Gerenciais', 'management-reports', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(31, 'Histórico de Clientes', 'customer-history', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(32, 'Avaliações de Serviço', 'service-ratings', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(33, 'Notificações Automáticas', 'auto-notifications', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(34, 'Lembretes de Manutenção', 'maintenance-reminders', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(35, 'Integração WhatsApp', 'whatsapp-integration', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(36, 'App Mobile', 'mobile-app', 1, 'inactive', '2025-05-29 12:44:31', '2025-05-29 12:44:31');

-- Areas of Activity
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

-- Professions
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

-- Units
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

-- Roles
INSERT INTO `roles` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Administrador com acesso total', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 'manager', 'Gerente com acesso parcial', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 'provider', 'Prestador padrão', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 'user', 'Usuário padrão', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Permissions
INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'create_user', 'Criar novos usuários', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 'edit_user', 'Editar usuários existentes', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 'delete_user', 'Excluir usuários', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(4, 'view_reports', 'Visualizar relatórios', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(5, 'manage_budget', 'Gerenciar orçamentos', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Role Permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
(2, 4), (2, 5),
(3, 4);

-- Tenants
INSERT INTO `tenants` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'admin_1716968671_a1b2c3d4', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 'manager_1716968671_e5f6g7h8', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 'teste_1716968671_i9j0k1l2', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Budget Statuses
INSERT INTO `budget_statuses` (`id`, `slug`, `name`, `description`, `color`, `icon`, `order_index`, `is_active`, `created_at`) VALUES
(1, 'DRAFT', 'Rascunho', 'Orçamento em elaboração, permite modificações', '#6c757d', 'bi-pencil-square', 1, 1, '2025-05-29 12:44:31'),
(2, 'PENDING', 'Pendente', 'Aguardando aprovação do cliente', '#ffc107', 'bi-clock', 2, 1, '2025-05-29 12:44:31'),
(3, 'APPROVED', 'Aprovado', 'Orçamento aprovado pelo cliente', '#28a745', 'bi-check-circle', 3, 1, '2025-05-29 12:44:31'),
(4, 'COMPLETED', 'Concluído', 'Todos os serviços foram realizados', '#28a745', 'bi-check2-all', 5, 1, '2025-05-29 12:44:31'),
(5, 'REJECTED', 'Rejeitado', 'Orçamento não aprovado pelo cliente', '#dc3545', 'bi-x-circle', 6, 1, '2025-05-29 12:44:31'),
(6, 'CANCELLED', 'Cancelado', 'Orçamento cancelado após aprovação', '#6c757d', 'bi-slash-circle', 7, 1, '2025-05-29 12:44:31'),
(7, 'EXPIRED', 'Expirado', 'Prazo de validade do orçamento expirado', '#dc3545', 'bi-calendar-x', 8, 1, '2025-05-29 12:44:31');

-- Service Statuses
INSERT INTO `service_statuses` (`id`, `slug`, `name`, `description`, `color`, `icon`, `order_index`, `is_active`, `created_at`) VALUES
(1, 'DRAFT', 'Rascunho', 'Serviço em elaboração, permite modificações', '#adb5bd', 'bi-pencil-square', 0, 1, '2025-06-04 10:30:13'),
(2, 'PENDING', 'Pendente', 'Serviço registrado aguardando agendamento', '#ffc107', 'bi-clock', 1, 1, '2025-05-29 12:44:31'),
(3, 'SCHEDULING', 'Agendamento', 'Data e hora a serem definidas para execução do serviço', '#007bff', 'bi-calendar-check', 2, 1, '2025-05-29 12:44:31'),
(4, 'PREPARING', 'Em Preparação', 'Equipe está preparando recursos e materiais', '#ffc107', 'bi-tools', 3, 1, '2025-05-29 12:44:31'),
(5, 'IN_PROGRESS', 'Em Andamento', 'Serviço está sendo executado no momento', '#007bff', 'bi-gear', 4, 1, '2025-05-29 12:44:31'),
(6, 'ON_HOLD', 'Em Espera', 'Serviço temporariamente pausado', '#6c757d', 'bi-pause-circle', 5, 1, '2025-05-29 12:44:31'),
(7, 'SCHEDULED', 'Agendado', 'Serviço com data marcada', '#007bff', 'bi-calendar-plus', 6, 1, '2025-05-29 12:44:31'),
(8, 'COMPLETED', 'Concluído', 'Serviço finalizado com sucesso', '#28a745', 'bi-check-circle', 7, 1, '2025-05-29 12:44:31'),
(9, 'PARTIAL', 'Concluído Parcial', 'Serviço finalizado parcialmente', '#28a745', 'bi-check-circle-fill', 8, 1, '2025-05-29 12:44:31'),
(10, 'CANCELLED', 'Cancelado', 'Serviço cancelado antes da execução', '#dc3545', 'bi-x-circle', 9, 1, '2025-05-29 12:44:31'),
(11, 'NOT_PERFORMED', 'Não Realizado', 'Não foi possível realizar o serviço', '#dc3545', 'bi-slash-circle', 10, 1, '2025-05-29 12:44:31'),
(12, 'EXPIRED', 'Expirado', 'Prazo de validade do orçamento expirado', '#dc3545', 'bi-calendar-x', 11, 1, '2025-05-29 12:44:31');

-- Categories
INSERT INTO `categories` (`id`, `slug`, `name`, `created_at`, `updated_at`) VALUES
(1, 'carpentry', 'Carpintaria', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(2, 'construction_civil', 'Construção Civil', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(3, 'construction_furniture', 'Construção de Móveis', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(4, 'construction_doors', 'Construção de Portas', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(5, 'construction_electric', 'Elétrica', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(6, 'construction_hydraulic', 'Hidráulica', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(7, 'installation_pumps', 'Instalação de Bombas', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(8, 'installation_pipes', 'Instalação de Tubulações', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(9, 'installation_glass', 'Instalação de Vidros', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(10, 'electrical_installation', 'Instalação Elétrica', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(11, 'maintenance_pumps', 'Manutenção de Bombas', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(12, 'maintenance_vehicles', 'Manutenção de Veículos', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(13, 'maintenance_electric', 'Manutenção Elétrica', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(14, 'mechanical', 'Mecânica', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(15, 'masonry', 'Obra de Alvenaria', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(16, 'painting', 'Pintura', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(17, 'painting_wall', 'Pintura de Parede', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(18, 'painting_ceiling', 'Pintura de Teto', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(19, 'reforms', 'Reformas', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(20, 'engine_repair', 'Reparo de Motores', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(21, 'repair_furniture', 'Reparo de Móveis', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(22, 'repair_doors', 'Reparo de Portas', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(23, 'glass_repair', 'Reparo de Vidros', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(24, 'metal_working', 'Serralheria', '2025-05-29 12:44:31', '2025-05-29 12:44:31'),
(25, 'welding', 'Vidraceiro', '2025-05-29 12:44:31', '2025-05-29 12:44:31');

-- Plans
INSERT INTO `plans` (`id`, `name`, `slug`, `description`, `price`, `status`, `max_budgets`, `max_clients`, `features`, `created_at`, `updated_at`) VALUES
(1, 'Plano Free', 'free', 'Comece com simplicidade e sem custos!', 0.00, 1, 3, 1, '["Acesso a recursos básicos","Até 3 orçamentos por mês","1 Cliente por mês"]', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(2, 'Plano Básico', 'basic', 'Gerencie seus orçamentos com eficiência!', 15.00, 1, 15, 5, '["Acesso a recursos básicos","Até 15 orçamentos por mês","5 Clientes por mês","Relatórios básicos"]', '2025-05-29 09:44:31', '2025-05-29 09:44:31'),
(3, 'Plano Premium', 'premium', 'A solução completa para sua gestão!', 25.00, 1, -1, -1, '["Acesso a todos os recursos","Orçamentos ilimitados","Clientes ilimitados","Relatórios avançados","Integração com pagamentos","Gerencimento de projetos"]', '2025-05-29 09:44:31', '2025-05-29 09:44:31');

-- Invoice Statuses
INSERT INTO `invoice_statuses` (`id`, `name`, `slug`, `description`, `color`, `icon`) VALUES
(1, 'Pendente', 'pending', 'A fatura foi gerada e aguarda pagamento.', '#ffc107', 'bi-hourglass-split'),
(2, 'Paga', 'paid', 'O pagamento da fatura foi confirmado.', '#198754', 'bi-check-circle-fill'),
(3, 'Cancelada', 'cancelled', 'A fatura foi cancelada e não é mais válida.', '#dc3545', 'bi-x-circle-fill'),
(4, 'Vencida', 'overdue', 'A data de vencimento da fatura passou sem pagamento.', '#6f42c1', 'bi-calendar-x-fill');