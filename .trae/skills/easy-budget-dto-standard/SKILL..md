---
name: easy-budget-dto-standard
description: Garante que novos DTOs sigam o padrão AbstractDTO com Reflection.
---

# Padrão de DTO Easy Budget

Sempre que o usuário pedir para criar um novo DTO (Data Transfer Object):
1. Estenda a classe `AbstractDTO`.
2. Utilize Reflection para o método `fromArray` para garantir que chaves extras sejam ignoradas.
3. Garanta que dados aninhados (como endereços ou itens) sejam incluídos no `toArray` para evitar perda de dados.
