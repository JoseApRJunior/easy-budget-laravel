**Correção do model de acordo com o antigo SQL:**

```php
protected $casts = [
    'birth_date' => 'date',                 // ✅ Pode ser alterado formato YYYY-MM-DD
    'due_date' => 'date',                  // ✅ Pode ser alterado formato YYYY-MM-DD
    'created_at' => 'immutable_datetime',  // ✅ Nunca muda formato YYYY-MM-DD HH:MM:SS
    'updated_at' => 'datetime',            // ✅ Pode ser alterado formato YYYY-MM-DD HH:MM:SS
];
```

Verificar se o relacionamento entre os modelos de dados requer a utilização de BelongsTo, HasMany ou ambos, conforme a necessidade do contexto. Caso seja utilizado algum desses relacionamentos, validar se a classe especificada possui o relacionamento reverso correspondente implementado, verificar melhor formato para campos date/datetime/immutable_datetime/...
