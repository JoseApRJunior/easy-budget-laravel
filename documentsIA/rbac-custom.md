# Implementação Custom RBAC para Easy Budget

## Visão Geral
RBAC custom implementado com roles/perms globais e assignments scoped por tenant_id em user_roles/role_permissions. Relationships via Eloquent belongsToMany. Sem dependências Spatie para simplicidade e compatibilidade multi-tenant.

## Arquitetura
- **Models Globais**: Role e Permission estendem Illuminate\Database\Eloquent\Model, sem TenantScoped trait.
- **Relationships**:
  - Role: belongsToMany(Permission, 'role_permissions')
  - Role: belongsToMany(User, 'user_roles', 'role_id', 'user_id', withPivot('tenant_id'))
  - Permission: belongsToMany(Role, 'role_permissions')
  - Permission: belongsToMany(User, 'user_permissions', 'permission_id', 'user_id', withPivot('tenant_id')) // Assumindo tabela custom
- **Scoping**: Assignments scoped via wherePivot('tenant_id', current_tenant_id) em queries.
- **Seeder/Factories**: Usam apenas 'name' e 'guard_name'; sem 'slug' ou 'description'.
- **Testes**: Verificam globalidade de models; scoping em pivots via attach/detach.

## Benefícios
- Compatibilidade total com stancl/tenancy.
- Sem overhead de Spatie em multi-tenant.
- Fácil extensão para checks custom: $user->roles()->wherePivot('tenant_id', auth()->tenant()->id)->pluck('permissions.name')

## Próximos Passos
- Implementar trait para helper methods (hasPermission, assignRole).
- Criar migration para user_permissions se necessário.
- Atualizar controllers/services para usar custom checks em vez de Spatie API.
