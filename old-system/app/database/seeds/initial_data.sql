-- Tabela de Usuários
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstName VARCHAR(50) NOT NULL,
    lastName VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de Roles (Funções)
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de Permissões
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de relação entre Usuários e Roles
CREATE TABLE user_roles (
    user_id INT,
    role_id INT,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE
);

-- Tabela de relação entre Roles e Permissões
CREATE TABLE role_permissions (
    role_id INT,
    permission_id INT,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
);

-- Inserir Roles
INSERT INTO
    roles (name, description)
VALUES (
        'admin',
        'Administrador com acesso total'
    ),
    (
        'manager',
        'Gerente com acesso parcial'
    ),
    ('user', 'Usuário padrão');

-- Inserir Permissões
INSERT INTO
    permissions (name, description)
VALUES (
        'create_user',
        'Criar novos usuários'
    ),
    (
        'edit_user',
        'Editar usuários existentes'
    ),
    (
        'delete_user',
        'Excluir usuários'
    ),
    (
        'view_reports',
        'Visualizar relatórios'
    ),
    (
        'manage_budget',
        'Gerenciar orçamentos'
    );

-- Inserir Usuários (senhas: 'password123')
INSERT INTO
    users (
        firstName,
        lastName,
        email,
        password
    )
VALUES (
        'Admin',
        'User',
        'admin@example.com',
        '$2a$12$kGRSCb2g2wrW4.Yll0mZxerDJM6fDcYze9CLewmeQ66FDRsCFnrHG'
    ),
    (
        'Manager',
        'User',
        'manager@example.com',
        '$2a$12$kGRSCb2g2wrW4.Yll0mZxerDJM6fDcYze9CLewmeQ66FDRsCFnrHG'
    ),
    (
        'Normal',
        'User',
        'user@example.com',
        '$2a$12$kGRSCb2g2wrW4.Yll0mZxerDJM6fDcYze9CLewmeQ66FDRsCFnrHG'
    );

-- Atribuir Roles aos Usuários
INSERT INTO
    user_roles (user_id, role_id)
VALUES (
        1,
        (
            SELECT id
            FROM roles
            WHERE
                name = 'admin'
        )
    ),
    (
        2,
        (
            SELECT id
            FROM roles
            WHERE
                name = 'manager'
        )
    ),
    (
        3,
        (
            SELECT id
            FROM roles
            WHERE
                name = 'user'
        )
    );

-- Atribuir Permissões às Roles
INSERT INTO
    role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE (r.name = 'admin')
    OR (
        r.name = 'manager'
        AND p.name IN (
            'view_reports',
            'manage_budget'
        )
    )
    OR (
        r.name = 'user'
        AND p.name = 'view_reports'
    );
