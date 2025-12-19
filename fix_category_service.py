#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os

def fix_category_service():
    file_path = 'app/Services/Domain/CategoryService.php'

    if not os.path.exists(file_path):
        print(f"Erro: Arquivo {file_path} não encontrado!")
        return False

    # Ler o arquivo
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Definir o código antigo e novo
    old_code = '''            // Usar o método específico do CategoryRepository que inclui funcionalidades avançadas
            $paginator = $this->categoryRepository->getPaginated(
                $normalized,
                $perPage,
                [], // with - pode ser expandido se necessário
                [ 'name' => 'asc' ], // orderBy padrão
                $onlyTrashed,
            );'''

    new_code = '''            // Usar o método específico do CategoryRepository que inclui funcionalidades avançadas
            // O filtro "deleted=only" é aplicado automaticamente pelo método getPaginated()
            $paginator = $this->categoryRepository->getPaginated(
                $normalized,
                $perPage,
                [], // with - pode ser expandido se necessário
                [ 'name' => 'asc' ] // orderBy padrão
            );'''

    # Substituir
    if old_code in content:
        content = content.replace(old_code, new_code)

        # Salvar o arquivo
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)

        print("✅ CategoryService.php corrigido com sucesso!")
        return True
    else:
        print("❌ Código antigo não encontrado no arquivo!")
        return False

if __name__ == "__main__":
    fix_category_service()
