# 🏷️ Skill: Product Management (Gestão de Produtos)

**Descrição:** Sistema completo de gestão de produtos com controle de estoque, categorias hierárquicas, validações de negócio e integração com orçamentos, serviços e faturas.

**Categoria:** Gestão de Produtos e Estoque
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Gerenciar todo o ciclo de vida dos produtos, desde o cadastro até a integração com orçamentos e faturas, com controle de estoque avançado, validações de negócio rigorosas e relacionamento hierárquico com categorias.

## 📋 Requisitos Técnicos

### **✅ Tipos de Produtos: Físicos vs Serviços**

```php
class ProductManagementService extends AbstractBaseService
{
    public function createProduct(array $data, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($data, $tenantId) {
            // 1. Validar tipo de produto
            $productType = $data['type'] ?? 'physical';
            $validation = $this->validateProductType($data, $productType);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Validar campos específicos por tipo
            $specificValidation = $this->validateProductSpecificFields($data, $productType);
            if (!$specificValidation->isSuccess()) {
                return $specificValidation;
            }

            // 3. Validar código único
            $codeValidation = $this->validateProductCode($data['code'], $tenantId);
            if (!$codeValidation->isSuccess()) {
                return $codeValidation;
            }

            // 4. Criar produto
            $product = $this->repository->create(array_merge($data, [
                'tenant_id' => $tenantId,
                'type' => $productType,
            ]));

            // 5. Criar estoque inicial se for produto físico
            if ($productType === 'physical') {
                $this->createInitialInventory($product, $data['initial_quantity'] ?? 0);
            }

            return $this->success($product, 'Produto criado com sucesso');
        });
    }

    private function validateProductType(array $data, string $productType): ServiceResult
    {
        $validTypes = ['physical', 'service'];

        if (!in_array($productType, $validTypes)) {
            return $this->error('Tipo de produto inválido', OperationStatus::INVALID_DATA);
        }

        // Validar campos obrigatórios por tipo
        if ($productType === 'physical') {
            if (empty($data['code'])) {
                return $this->error('Código é obrigatório para produtos físicos', OperationStatus::INVALID_DATA);
            }
            if (!isset($data['initial_quantity'])) {
                return $this->error('Quantidade inicial é obrigatória para produtos físicos', OperationStatus::INVALID_DATA);
            }
        }

        if ($productType === 'service') {
            if (empty($data['service_code'])) {
                return $this->error('Código de serviço é obrigatório para serviços', OperationStatus::INVALID_DATA);
            }
        }

        return $this->success(null, 'Tipo de produto válido');
    }

    private function validateProductSpecificFields(array $data, string $productType): ServiceResult
    {
        $issues = [];

        if ($productType === 'physical') {
            // Validar campos específicos de produtos físicos
            if (isset($data['weight']) && $data['weight'] <= 0) {
                $issues[] = 'Peso deve ser maior que zero';
            }

            if (isset($data['dimensions']) && !$this->validateDimensions($data['dimensions'])) {
                $issues[] = 'Dimensões inválidas';
            }

            if (isset($data['min_quantity']) && $data['min_quantity'] < 0) {
                $issues[] = 'Quantidade mínima não pode ser negativa';
            }
        }

        if ($productType === 'service') {
            // Validar campos específicos de serviços
            if (isset($data['duration']) && $data['duration'] <= 0) {
                $issues[] = 'Duração deve ser maior que zero';
            }

            if (isset($data['service_type']) && !in_array($data['service_type'], ['hourly', 'fixed', 'package'])) {
                $issues[] = 'Tipo de serviço inválido';
            }
        }

        if (!empty($issues)) {
            return $this->error(implode(', ', $issues), OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Campos específicos válidos');
    }

    private function validateDimensions(string $dimensions): bool
    {
        // Formato esperado: "LxAxP" (ex: "10x20x30")
        return preg_match('/^\d+(\.\d+)?x\d+(\.\d+)?x\d+(\.\d+)?$/', $dimensions);
    }
}
```

### **✅ Controle de Estoque Avançado**

```php
class InventoryManagementService extends AbstractBaseService
{
    public function registerMovement(int $productId, string $type, int $quantity, string $reason, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($productId, $type, $quantity, $reason, $tenantId) {
            // 1. Validar produto e estoque
            $product = $this->findProductByIdAndTenantId($productId, $tenantId);
            if (!$product) {
                return $this->error('Produto não encontrado', OperationStatus::NOT_FOUND);
            }

            if ($product->type !== 'physical') {
                return $this->error('Estoque só é permitido para produtos físicos', OperationStatus::INVALID_DATA);
            }

            // 2. Validar tipo de movimento
            $validTypes = ['in', 'out', 'adjustment'];
            if (!in_array($type, $validTypes)) {
                return $this->error('Tipo de movimento inválido', OperationStatus::INVALID_DATA);
            }

            // 3. Validar quantidade
            $validation = $this->validateMovementQuantity($type, $quantity, $product);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 4. Executar movimento
            $movement = $this->createMovement($product, $type, $quantity, $reason, $tenantId);

            // 5. Atualizar estoque
            $this->updateInventory($product, $type, $quantity);

            // 6. Verificar alertas de estoque
            $this->checkInventoryAlerts($product);

            return $this->success($movement, 'Movimentação registrada com sucesso');
        });
    }

    private function validateMovementQuantity(string $type, int $quantity, Product $product): ServiceResult
    {
        if ($quantity <= 0) {
            return $this->error('Quantidade deve ser maior que zero', OperationStatus::INVALID_DATA);
        }

        if ($type === 'out') {
            $currentStock = $this->getCurrentStock($product);
            if ($currentStock < $quantity) {
                return $this->error("Estoque insuficiente. Disponível: {$currentStock}", OperationStatus::INVALID_DATA);
            }
        }

        return $this->success(null, 'Quantidade válida');
    }

    private function createMovement(Product $product, string $type, int $quantity, string $reason, int $tenantId): InventoryMovement
    {
        return InventoryMovement::create([
            'tenant_id' => $tenantId,
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    private function updateInventory(Product $product, string $type, int $quantity): void
    {
        $inventory = $this->getOrCreateInventory($product);

        if ($type === 'in') {
            $inventory->quantity += $quantity;
        } elseif ($type === 'out') {
            $inventory->quantity -= $quantity;
        } elseif ($type === 'adjustment') {
            $inventory->quantity = $quantity;
        }

        $inventory->save();
    }

    private function checkInventoryAlerts(Product $product): void
    {
        $inventory = $this->getOrCreateInventory($product);
        $currentStock = $inventory->quantity;
        $minQuantity = $inventory->min_quantity;

        if ($currentStock < $minQuantity) {
            // Enviar alerta de estoque baixo
            $this->sendLowStockAlert($product, $currentStock, $minQuantity);
        }

        if ($currentStock === 0) {
            // Enviar alerta de estoque zerado
            $this->sendOutOfStockAlert($product);
        }
    }

    public function getInventoryHistory(int $productId, int $tenantId, array $filters = []): ServiceResult
    {
        return $this->safeExecute(function() use ($productId, $tenantId, $filters) {
            $product = $this->findProductByIdAndTenantId($productId, $tenantId);
            if (!$product) {
                return $this->error('Produto não encontrado', OperationStatus::NOT_FOUND);
            }

            $query = InventoryMovement::where('product_id', $productId)
                ->where('tenant_id', $tenantId);

            // Aplicar filtros
            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            $movements = $query->orderBy('created_at', 'desc')->get();

            return $this->success([
                'movements' => $movements,
                'current_stock' => $this->getCurrentStock($product),
                'min_quantity' => $this->getMinQuantity($product),
            ], 'Histórico de movimentação obtido');
        });
    }
}
```

### **✅ Categorias e Hierarquia**

```php
class ProductCategoryService extends AbstractBaseService
{
    public function assignCategories(int $productId, array $categoryIds, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($productId, $categoryIds, $tenantId) {
            $product = $this->findProductByIdAndTenantId($productId, $tenantId);
            if (!$product) {
                return $this->error('Produto não encontrado', OperationStatus::NOT_FOUND);
            }

            // Validar categorias
            $categories = Category::whereIn('id', $categoryIds)
                ->where('tenant_id', $tenantId)
                ->get();

            if ($categories->count() !== count($categoryIds)) {
                return $this->error('Algumas categorias não foram encontradas', OperationStatus::NOT_FOUND);
            }

            // Validar hierarquia
            foreach ($categories as $category) {
                if (!$this->validateCategoryHierarchy($category, $tenantId)) {
                    return $this->error("Categoria {$category->name} tem hierarquia inválida", OperationStatus::INVALID_DATA);
                }
            }

            // Associar categorias
            $product->categories()->sync($categoryIds);

            return $this->success($categories, 'Categorias associadas com sucesso');
        });
    }

    public function getProductsWithCategories(int $tenantId, array $filters = []): ServiceResult
    {
        return $this->safeExecute(function() use ($tenantId, $filters) {
            $query = Product::where('tenant_id', $tenantId)
                ->with(['categories' => function($query) {
                    $query->with('parent');
                }]);

            // Aplicar filtros
            if (isset($filters['category_id'])) {
                $query->whereHas('categories', function($q) use ($filters) {
                    $q->where('categories.id', $filters['category_id']);
                });
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['active'])) {
                $query->where('active', $filters['active']);
            }

            $products = $query->get();

            // Construir árvore de categorias
            $categoryTree = $this->buildCategoryTree($tenantId);

            return $this->success([
                'products' => $products,
                'category_tree' => $categoryTree,
            ], 'Produtos com categorias obtidos');
        });
    }

    private function buildCategoryTree(int $tenantId): array
    {
        $categories = Category::where('tenant_id', $tenantId)
            ->with('children')
            ->orderBy('name')
            ->get();

        return $this->buildTreeRecursive($categories, null);
    }

    private function buildTreeRecursive($categories, ?int $parentId): array
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $children = $this->buildTreeRecursive($categories, $category->id);

                $tree[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'parent_id' => $category->parent_id,
                    'children' => $children,
                    'product_count' => $category->products()->count(),
                ];
            }
        }

        return $tree;
    }
}
```

### **✅ Validações de Negócio**

```php
class ProductValidationService extends AbstractBaseService
{
    public function validateProductBusinessRules(array $data): ServiceResult
    {
        $issues = [];

        // 1. Validar preço
        if (isset($data['price'])) {
            $priceValidation = $this->validatePrice($data['price']);
            if (!$priceValidation->isSuccess()) {
                $issues[] = $priceValidation->getMessage();
            }
        }

        // 2. Validar código
        if (isset($data['code'])) {
            $codeValidation = $this->validateCode($data['code'], $data['tenant_id'] ?? null, $data['id'] ?? null);
            if (!$codeValidation->isSuccess()) {
                $issues[] = $codeValidation->getMessage();
            }
        }

        // 3. Validar estoque
        if (isset($data['type']) && $data['type'] === 'physical') {
            $stockValidation = $this->validateStockRules($data);
            if (!$stockValidation->isSuccess()) {
                $issues[] = $stockValidation->getMessage();
            }
        }

        // 4. Validar relacionamentos
        if (isset($data['category_ids'])) {
            $categoryValidation = $this->validateCategories($data['category_ids'], $data['tenant_id'] ?? null);
            if (!$categoryValidation->isSuccess()) {
                $issues[] = $categoryValidation->getMessage();
            }
        }

        if (!empty($issues)) {
            return $this->error(implode('; ', $issues), OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Validações de negócio aprovadas');
    }

    private function validatePrice(float $price): ServiceResult
    {
        if ($price < 0) {
            return $this->error('Preço não pode ser negativo', OperationStatus::INVALID_DATA);
        }

        if ($price > 999999.99) {
            return $this->error('Preço muito alto (máximo: R$ 999.999,99)', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Preço válido');
    }

    private function validateCode(string $code, ?int $tenantId, ?int $productId = null): ServiceResult
    {
        // Validar formato do código
        if (!preg_match('/^[A-Z0-9\-_]+$/', $code)) {
            return $this->error('Código deve conter apenas letras, números, hífens e underscores', OperationStatus::INVALID_DATA);
        }

        // Validar unicidade
        $query = Product::where('code', $code);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($productId) {
            $query->where('id', '!=', $productId);
        }

        if ($query->exists()) {
            return $this->error('Código já está em uso', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Código válido');
    }

    private function validateStockRules(array $data): ServiceResult
    {
        $issues = [];

        if (isset($data['min_quantity']) && isset($data['max_quantity'])) {
            if ($data['min_quantity'] > $data['max_quantity']) {
                $issues[] = 'Quantidade mínima não pode ser maior que a máxima';
            }
        }

        if (isset($data['initial_quantity']) && isset($data['min_quantity'])) {
            if ($data['initial_quantity'] < $data['min_quantity']) {
                $issues[] = 'Quantidade inicial não pode ser menor que a mínima';
            }
        }

        if (!empty($issues)) {
            return $this->error(implode('; ', $issues), OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Regras de estoque válidas');
    }

    private function validateCategories(array $categoryIds, ?int $tenantId): ServiceResult
    {
        if (empty($categoryIds)) {
            return $this->success(null, 'Nenhuma categoria selecionada');
        }

        $categories = Category::whereIn('id', $categoryIds);

        if ($tenantId) {
            $categories->where('tenant_id', $tenantId);
        }

        $foundCategories = $categories->count();

        if ($foundCategories !== count($categoryIds)) {
            return $this->error('Algumas categorias não foram encontradas ou não pertencem ao tenant', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Categorias válidas');
    }
}
```

### **✅ Integrações com Orçamentos, Serviços e Faturas**

```php
class ProductIntegrationService extends AbstractBaseService
{
    public function addProductToBudget(int $budgetId, int $productId, int $quantity, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($budgetId, $productId, $quantity, $tenantId) {
            // 1. Validar orçamento
            $budget = Budget::where('id', $budgetId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$budget) {
                return $this->error('Orçamento não encontrado', OperationStatus::NOT_FOUND);
            }

            // 2. Validar produto
            $product = $this->findProductByIdAndTenantId($productId, $tenantId);
            if (!$product) {
                return $this->error('Produto não encontrado', OperationStatus::NOT_FOUND);
            }

            // 3. Validar estoque (se for produto físico)
            if ($product->type === 'physical') {
                $stockValidation = $this->validateStockForBudget($product, $quantity);
                if (!$stockValidation->isSuccess()) {
                    return $stockValidation;
                }
            }

            // 4. Adicionar ao orçamento
            $budgetItem = BudgetItem::create([
                'tenant_id' => $tenantId,
                'budget_id' => $budgetId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'total' => $quantity * $product->price,
            ]);

            // 5. Atualizar total do orçamento
            $this->updateBudgetTotal($budget);

            return $this->success($budgetItem, 'Produto adicionado ao orçamento');
        });
    }

    public function addProductToService(int $serviceId, int $productId, int $quantity, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($serviceId, $productId, $quantity, $tenantId) {
            // 1. Validar serviço
            $service = Service::where('id', $serviceId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$service) {
                return $this->error('Serviço não encontrado', OperationStatus::NOT_FOUND);
            }

            // 2. Validar produto
            $product = $this->findProductByIdAndTenantId($productId, $tenantId);
            if (!$product) {
                return $this->error('Produto não encontrado', OperationStatus::NOT_FOUND);
            }

            // 3. Validar estoque
            if ($product->type === 'physical') {
                $stockValidation = $this->validateStockForService($product, $quantity);
                if (!$stockValidation->isSuccess()) {
                    return $stockValidation;
                }
            }

            // 4. Adicionar ao serviço
            $serviceItem = ServiceItem::create([
                'tenant_id' => $tenantId,
                'service_id' => $serviceId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'total' => $quantity * $product->price,
            ]);

            // 5. Atualizar total do serviço
            $this->updateServiceTotal($service);

            return $this->success($serviceItem, 'Produto adicionado ao serviço');
        });
    }

    public function createInvoiceFromProducts(array $productsData, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($productsData, $tenantId) {
            $invoiceItems = [];
            $total = 0;

            foreach ($productsData as $productData) {
                $product = $this->findProductByIdAndTenantId($productData['product_id'], $tenantId);
                if (!$product) {
                    return $this->error("Produto {$productData['product_id']} não encontrado", OperationStatus::NOT_FOUND);
                }

                // Validar estoque
                if ($product->type === 'physical') {
                    $stockValidation = $this->validateStockForInvoice($product, $productData['quantity']);
                    if (!$stockValidation->isSuccess()) {
                        return $stockValidation;
                    }
                }

                $itemTotal = $productData['quantity'] * $product->price;
                $total += $itemTotal;

                $invoiceItems[] = [
                    'tenant_id' => $tenantId,
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $productData['quantity'],
                    'unit_price' => $product->price,
                    'total' => $itemTotal,
                ];
            }

            // Criar fatura
            $invoice = Invoice::create([
                'tenant_id' => $tenantId,
                'total' => $total,
                'status' => 'pending',
            ]);

            // Criar itens da fatura
            foreach ($invoiceItems as $item) {
                InvoiceItem::create(array_merge($item, ['invoice_id' => $invoice->id]));
            }

            return $this->success($invoice, 'Fatura criada com produtos');
        });
    }

    private function validateStockForBudget(Product $product, int $quantity): ServiceResult
    {
        $currentStock = $this->getCurrentStock($product);

        if ($currentStock < $quantity) {
            return $this->error("Estoque insuficiente para orçamento. Disponível: {$currentStock}, Solicitado: {$quantity}", OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Estoque suficiente para orçamento');
    }

    private function validateStockForService(Product $product, int $quantity): ServiceResult
    {
        $currentStock = $this->getCurrentStock($product);

        if ($currentStock < $quantity) {
            return $this->error("Estoque insuficiente para serviço. Disponível: {$currentStock}, Solicitado: {$quantity}", OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Estoque suficiente para serviço');
    }

    private function validateStockForInvoice(Product $product, int $quantity): ServiceResult
    {
        $currentStock = $this->getCurrentStock($product);

        if ($currentStock < $quantity) {
            return $this->error("Estoque insuficiente para fatura. Disponível: {$currentStock}, Solicitado: {$quantity}", OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Estoque suficiente para fatura');
    }
}
```

### **✅ Gestão de Imagens de Produtos**

```php
class ProductImageService extends AbstractBaseService
{
    public function uploadProductImage(int $productId, UploadedFile $image, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($productId, $image, $tenantId) {
            // 1. Validar produto
            $product = $this->findProductByIdAndTenantId($productId, $tenantId);
            if (!$product) {
                return $this->error('Produto não encontrado', OperationStatus::NOT_FOUND);
            }

            // 2. Validar imagem
            $validation = $this->validateImage($image);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 3. Processar imagem
            $processedImage = $this->processImage($image, $product);

            // 4. Salvar imagem
            $imagePath = $this->saveImage($processedImage, $product);

            // 5. Atualizar produto com caminho da imagem
            $product->update(['image' => $imagePath]);

            return $this->success([
                'image_path' => $imagePath,
                'image_url' => $this->getImageUrl($imagePath),
            ], 'Imagem enviada com sucesso');
        });
    }

    private function validateImage(UploadedFile $image): ServiceResult
    {
        // Validar tipo de arquivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($image->getMimeType(), $allowedTypes)) {
            return $this->error('Tipo de arquivo não permitido. Use JPEG, PNG ou WebP', OperationStatus::INVALID_DATA);
        }

        // Validar tamanho (máximo 5MB)
        if ($image->getSize() > 5 * 1024 * 1024) {
            return $this->error('Imagem muito grande. Máximo 5MB', OperationStatus::INVALID_DATA);
        }

        // Validar dimensões
        list($width, $height) = getimagesize($image->getPathname());

        if ($width < 100 || $height < 100) {
            return $this->error('Imagem muito pequena. Mínimo 100x100px', OperationStatus::INVALID_DATA);
        }

        if ($width > 4000 || $height > 4000) {
            return $this->error('Imagem muito grande. Máximo 4000x4000px', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Imagem válida');
    }

    private function processImage(UploadedFile $image, Product $product): string
    {
        $imagePath = $image->getPathname();
        $imageInfo = getimagesize($imagePath);
        $imageType = $imageInfo[2];

        // Criar imagem GD
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($imagePath);
                break;
            default:
                throw new Exception('Tipo de imagem não suportado');
        }

        // Redimensionar para tamanho padrão (800x600)
        $targetWidth = 800;
        $targetHeight = 600;

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        // Calcular proporções
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($sourceRatio > $targetRatio) {
            // Imagem larga - ajustar pela largura
            $newHeight = $targetWidth / $sourceRatio;
            $newWidth = $targetWidth;
        } else {
            // Imagem alta - ajustar pela altura
            $newWidth = $targetHeight * $sourceRatio;
            $newHeight = $targetHeight;
        }

        // Criar imagem redimensionada
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

        // Salvar imagem processada
        $processedImagePath = storage_path('app/public/products/' . $product->id . '_processed.' . $this->getImageExtension($imageType));

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                imagejpeg($resizedImage, $processedImagePath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($resizedImage, $processedImagePath, 6);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($resizedImage, $processedImagePath, 80);
                break;
        }

        // Limpar memória
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        return $processedImagePath;
    }

    private function saveImage(string $imagePath, Product $product): string
    {
        $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
        $fileName = "product_{$product->id}_" . time() . ".{$extension}";
        $storagePath = "products/{$fileName}";

        // Mover para storage público
        Storage::putFileAs('public/products', new File($imagePath), $fileName);

        // Remover arquivo temporário
        unlink($imagePath);

        return $storagePath;
    }

    private function getImageUrl(string $imagePath): string
    {
        return Storage::url($imagePath);
    }

    private function getImageExtension(int $imageType): string
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => 'jpg',
        };
    }

    public function deleteProductImage(int $productId, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($productId, $tenantId) {
            $product = $this->findProductByIdAndTenantId($productId, $tenantId);
            if (!$product) {
                return $this->error('Produto não encontrado', OperationStatus::NOT_FOUND);
            }

            if (!$product->image) {
                return $this->error('Produto não possui imagem', OperationStatus::INVALID_DATA);
            }

            // Remover arquivo do storage
            Storage::delete($product->image);

            // Atualizar produto
            $product->update(['image' => null]);

            return $this->success(null, 'Imagem removida com sucesso');
        });
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Gestão de Produtos**

```php
public function testProductCreationWithValidation()
{
    $tenant = Tenant::factory()->create();

    // Testar criação de produto físico
    $productData = [
        'tenant_id' => $tenant->id,
        'name' => 'Notebook Gamer',
        'description' => 'Notebook para jogos',
        'price' => 5000.00,
        'code' => 'NB-GAMER-001',
        'type' => 'physical',
        'initial_quantity' => 10,
        'min_quantity' => 2,
        'max_quantity' => 100,
        'active' => true,
    ];

    $result = $this->productService->createProduct($productData, $tenant->id);
    $this->assertTrue($result->isSuccess());

    $product = $result->getData();
    $this->assertEquals('Notebook Gamer', $product->name);
    $this->assertEquals('physical', $product->type);
    $this->assertEquals(10, $product->inventory->quantity);
}

public function testServiceCreationWithValidation()
{
    $tenant = Tenant::factory()->create();

    // Testar criação de serviço
    $serviceData = [
        'tenant_id' => $tenant->id,
        'name' => 'Instalação de Software',
        'description' => 'Instalação e configuração de softwares',
        'price' => 150.00,
        'service_code' => 'SERV-INST-001',
        'type' => 'service',
        'duration' => 60,
        'service_type' => 'hourly',
        'active' => true,
    ];

    $result = $this->productService->createProduct($serviceData, $tenant->id);
    $this->assertTrue($result->isSuccess());

    $product = $result->getData();
    $this->assertEquals('Instalação de Software', $product->name);
    $this->assertEquals('service', $product->type);
    $this->assertNull($product->inventory);
}

public function testInventoryManagement()
{
    $tenant = Tenant::factory()->create();
    $product = Product::factory()->physical()->create(['tenant_id' => $tenant->id]);

    // Testar entrada de estoque
    $result = $this->inventoryService->registerMovement(
        $product->id, 'in', 5, 'Compra', $tenant->id
    );
    $this->assertTrue($result->isSuccess());

    $this->assertEquals(5, $this->inventoryService->getCurrentStock($product));

    // Testar saída de estoque
    $result = $this->inventoryService->registerMovement(
        $product->id, 'out', 2, 'Venda', $tenant->id
    );
    $this->assertTrue($result->isSuccess());

    $this->assertEquals(3, $this->inventoryService->getCurrentStock($product));
}

public function testProductIntegrationWithBudget()
{
    $tenant = Tenant::factory()->create();
    $product = Product::factory()->create(['tenant_id' => $tenant->id, 'price' => 100.00]);
    $budget = Budget::factory()->create(['tenant_id' => $tenant->id]);

    $result = $this->integrationService->addProductToBudget(
        $budget->id, $product->id, 2, $tenant->id
    );
    $this->assertTrue($result->isSuccess());

    $budgetItem = $result->getData();
    $this->assertEquals(2, $budgetItem->quantity);
    $this->assertEquals(200.00, $budgetItem->total);
}

public function testProductImageUpload()
{
    Storage::fake('public');

    $tenant = Tenant::factory()->create();
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    $image = UploadedFile::fake()->image('product.jpg', 800, 600);

    $result = $this->imageService->uploadProductImage($product->id, $image, $tenant->id);
    $this->assertTrue($result->isSuccess());

    $this->assertNotNull($product->fresh()->image);
    Storage::assertExists($product->fresh()->image);
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar ProductManagementService básico
- [ ] Sistema de validação de tipos de produtos
- [ ] Controle de estoque básico
- [ ] Validações de código e preço

### **Fase 2: Core Features**
- [ ] Sistema de categorias hierárquicas
- [ ] Controle avançado de estoque
- [ ] Histórico de movimentações
- [ ] Integração com orçamentos

### **Fase 3: Advanced Features**
- [ ] Integração com serviços e faturas
- [ ] Sistema de imagens de produtos
- [ ] Alertas de estoque
- [ ] Relatórios de movimentação

### **Fase 4: Integration**
- [ ] Dashboard de gestão de produtos
- [ ] Exportação de produtos
- [ ] Importação em lote
- [ ] API RESTful completa

## 📚 Documentação Relacionada

- [ProductManagementService](../../app/Services/Domain/ProductManagementService.php)
- [InventoryManagementService](../../app/Services/Domain/InventoryManagementService.php)
- [ProductCategoryService](../../app/Services/Domain/ProductCategoryService.php)
- [ProductValidationService](../../app/Services/Domain/ProductValidationService.php)
- [ProductIntegrationService](../../app/Services/Domain/ProductIntegrationService.php)
- [ProductImageService](../../app/Services/Infrastructure/ProductImageService.php)
- [Product Model](../../app/Models/Product.php)
- [Inventory Model](../../app/Models/ProductInventory.php)
- [InventoryMovement Model](../../app/Models/InventoryMovement.php)

## 🎯 Benefícios

### **✅ Gestão Completa**
- Controle total de produtos e serviços
- Estoque em tempo real
- Histórico de movimentações
- Integração completa com orçamentos

### **✅ Validade de Dados**
- Validações rigorosas de negócio
- Controle de duplicidades
- Formatos de dados padronizados
- Integridade referencial garantida

### **✅ Eficiência Operacional**
- Processos automatizados
- Redução de erros manuais
- Integração seamless com outros módulos
- Dashboard informativo

### **✅ Experiência do Usuário**
- Interface intuitiva
- Upload de imagens simplificado
- Busca e filtragem avançada
- Relatórios completos

---

**Última atualização:** 11/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
