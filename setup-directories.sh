#!/bin/bash

# Script para criar estrutura completa de diretórios para recursos/views/

echo "🚀 Criando estrutura completa de diretórios para Blade..."

# Criar diretórios principais
mkdir -p resources/views/{layouts,components,pages,emails,errors}

# Criar subdiretórios de componentes
mkdir -p resources/views/components/{ui,form,navigation}
mkdir -p resources/views/components/budget
mkdir -p resources/views/components/customer
mkdir -p resources/views/components/invoice
mkdir -p resources/views/components/service
mkdir -p resources/views/components/reports
mkdir -p resources/views/components/settings
mkdir -p resources/views/components/admin

# Criar subdiretórios de páginas
mkdir -p resources/views/pages/{auth,dashboard,budgets,customers,products,services,invoices,reports,settings,admin,home,legal}

# Criar subdiretórios específicos
mkdir -p resources/views/pages/reports/pdf
mkdir -p resources/views/pages/invoices/payment
mkdir -p resources/views/pages/settings/{profile,general,notifications,security,integration}
mkdir -p resources/views/pages/admin/{dashboard,metrics,monitoring,logs,alerts,analysis,tenants,users,plans}
mkdir -p resources/views/pages/budgets/{create,show,edit}
mkdir -p resources/views/pages/customers/{create,show,edit}
mkdir -p resources/views/pages/services/{create,show,edit}
mkdir -p resources/views/pages/products/{create,show,edit}
mkdir -p resources/views/pages/invoices/{create,show}

# Criar subdiretórios de emails
mkdir -p resources/views/emails/{layouts,auth,notifications,invoices,plans}

# Estrutura para componentes específicos por domínio
mkdir -p resources/views/components/domain/{budget,customer,invoice,service,product,report}

echo "✅ Estrutura de diretórios criada com sucesso!"
echo ""
echo "📁 Estrutura criada:"
find resources/views -type d | sort
