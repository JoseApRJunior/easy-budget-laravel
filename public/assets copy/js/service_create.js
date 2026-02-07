const searchInput = document.getElementById("search-input");
const servicesTable = document.getElementById("budgets-table");
if (searchInput) {
   searchInput.addEventListener("input", () => {
      const searchTerm = searchInput.value.toLowerCase();
      const filteredBudgets = budgets.filter((budget) => {
         const code = budget.code.toLowerCase();
         const customerName =
            budget.first_name.toLowerCase() +
            " " +
            budget.last_name.toLowerCase();
         const description = budget.description.toLowerCase();
         return (
            code.includes(searchTerm) ||
            customerName.includes(searchTerm) ||
            description.includes(searchTerm)
         );
      });
      renderBudgets(filteredBudgets);
   });
}

function renderBudgets(budgets) {
   const tbody = servicesTable.querySelector("tbody");
   tbody.innerHTML = "";
   budgets.forEach((budget) => {
      const row = document.createElement("tr");
      row.innerHTML = `
				<td class="col-md-2">
				    ${budget.code}
				</td>
				<td class="col-md-3">
				    ${budget.first_name.toLowerCase() + " " + budget.last_name.toLowerCase()}
				</td>
				<td class="col-md-6">
				    ${budget.description}
				</td>
				    `;
      row.setAttribute("data-budget-code", budget.code); // Adicione o atributo data-budget-code ao elemento tr
      tbody.appendChild(row);

      // Adicione o evento de clique ao elemento tr
      row.addEventListener("click", (e) => {
         // Obtenha o ID do budget selecionado
         const budgetCode = e.target
            .closest("tr")
            .getAttribute("data-budget-code");

         // Preencha os campos de código de orçamento com o code do budget selecionado
         document.getElementById("create-service-form").elements["code"].value =
            budgetCode;
         document.getElementById("search-input").value = "";
         const tbody = servicesTable.querySelector("tbody");
         tbody.innerHTML = "";
      });
   });
}

const textarea = document.getElementById("description");
const charCount = document.getElementById("char-count-value");

textarea.addEventListener("input", () => {
   const charsLeft = textarea.maxLength - textarea.value.length;
   charCount.textContent = charsLeft;
});

const itemsTable = document.getElementById("items-table");
const itemsTbody = document.getElementById("items-tbody");
const addItemButton = document.getElementById("add-item-button");
const form = document.getElementById("create-service-form");
const totalServiceSpan = document.getElementById("total-service");

let items = [];
let itemCount = 1;

// Adiciona evento para limpar sugestões com a tecla ESC
document.addEventListener("keydown", function (event) {
   if (event.key === "Escape") {
      // Limpa todas as sugestões de produtos
      document
         .querySelectorAll('[id^="product-suggestions-"]')
         .forEach((div) => {
            div.innerHTML = "";
         });
   }
});

addItemButton.addEventListener("click", (event) => {
   event.preventDefault();
   event.stopPropagation();
   event.target.form.reset = false;
   // Código para adicionar o item aqui
   const item = {
      item: `${itemCount}`,
      id: "",
      code: "",
      name: "",
      unit_value: 0,
      quantity: 1,
      total: 0,
   };
   items.push(item);
   itemCount = items.length + 1; // Atualiza o itemCount para o próximo item
   renderItems(item, items.length - 1);
});

function renderItems(item, index) {
   if (item) {
      const newRow = document.createElement("tr");
      newRow.innerHTML = `
    <td class="col-md-1 text-center">
      ${item.item}
    </td>
    <td class="col-md-1 text-center">
      <span id="code-${index}">${item.code || ""}</span>
      <input type="hidden" id="code-hidden-${index}" value="${item.id || ""}">
    </td>
    <td class="col-md-5">
      <input type="text" class="form-control" id="name-${index}" value="${
         item.name || ""
      }">
      <div id="product-suggestions-${index}"></div>
    </td>
    <td class="col-md-2 text-center">
      <span id="unit-value-display-${index}">R$ ${item.unit_value
         .toFixed(2)
         .replace(".", ",")}</span>
      <input type="hidden" id="unit-value-${index}" value="${item.unit_value}">
    </td>
    <td class="col-md-1">
      <input type="number" class="form-control text-center" id="quantity-${index}" min="1" value="${
         item.quantity || 1
      }">
    </td>
    <td class="col-md-2 text-center">
      <span id="total-${index}">R$ ${item.total
         .toFixed(2)
         .replace(".", ",")}</span>
    </td>
    <td class="col-md-1">
      <button class="btn btn-danger" onclick="removeItem(${index}); event.preventDefault();">
        Remover
      </button>
    </td>
  `;

      itemsTbody.appendChild(newRow);

      // Adiciona evento de mudança no campo de nome
      const nameInput = document.getElementById(`name-${index}`);
      nameInput.addEventListener("input", () => {
         const searchTerm = nameInput.value.toLowerCase();
         const filteredProducts = products.filter((product) => {
            const productName = product.name.toLowerCase();
            return productName.includes(searchTerm);
         });
         renderProductSuggestions(filteredProducts, index);
      });

      // Adiciona evento para limpar sugestões com ESC no campo de nome
      nameInput.addEventListener("keydown", (event) => {
         if (event.key === "Escape") {
            const suggestionsDiv = document.getElementById(
               `product-suggestions-${index}`
            );
            if (suggestionsDiv) {
               suggestionsDiv.innerHTML = "";
            }
            event.preventDefault(); // Evita que o ESC limpe o campo
         }
      });

      // Adiciona evento para fechar sugestões ao clicar fora
      document.addEventListener("click", (event) => {
         if (!nameInput.contains(event.target)) {
            const suggestionsDiv = document.getElementById(
               `product-suggestions-${index}`
            );
            if (suggestionsDiv) {
               suggestionsDiv.innerHTML = "";
            }
         }
      });
   }
}

function renderProductSuggestions(products, index) {
   const productSuggestionsDiv = document.getElementById(
      `product-suggestions-${index}`
   );

   if (!productSuggestionsDiv) return;

   productSuggestionsDiv.innerHTML = "";

   // Se o termo de busca estiver vazio, não mostra sugestões
   const nameInput = document.getElementById(`name-${index}`);
   if (!nameInput || nameInput.value.trim() === "") {
      return;
   }

   // Filtra produtos que já estão na lista de itens
   const filteredProducts = products.filter((product) => {
      // Verifica se este produto já existe em algum item da lista
      const productExists = items.some(
         (item) => item.id === product.id && item !== items[index] // Ignora o item atual que está sendo editado
      );

      // Retorna true para produtos que não existem na lista (para incluí-los nas sugestões)
      return !productExists;
   });

   // Aplica estilos ao container de sugestões
   productSuggestionsDiv.style.position = "absolute";
   productSuggestionsDiv.style.zIndex = "1000";
   productSuggestionsDiv.style.borderRadius = "4px";
   productSuggestionsDiv.style.boxShadow = "0 2px 5px rgba(0,0,0,0.2)";
   productSuggestionsDiv.style.maxHeight = "200px";
   productSuggestionsDiv.style.overflowY = "auto";

   if (filteredProducts.length === 0) {
      const noResults = document.createElement("div");
      noResults.className = "no-results";
      noResults.style.padding = "8px 12px";
      noResults.style.color = "#6c757d";
      noResults.style.fontStyle = "italic";
      noResults.textContent = "Nenhum produto encontrado ou já adicionado.";
      productSuggestionsDiv.appendChild(noResults);
      return;
   }

   filteredProducts.forEach((product) => {
      const productSuggestion = document.createElement("div");
      productSuggestion.className = "product-suggestion";
      productSuggestion.style.padding = "8px 12px";
      productSuggestion.style.cursor = "pointer";
      productSuggestion.style.borderBottom = "1px solid #eee";

      // Texto da sugestão
      productSuggestion.textContent =
         product.name +
         " - " +
         `R$ ${product.price.toFixed(2).replace(".", ",")} - Código: ${
            product.code || ""
         }`;

      productSuggestion.addEventListener("mouseout", () => {
         productSuggestion.style.backgroundColor = "";
      });

      productSuggestion.addEventListener("click", () => {
         // Atualiza o código
         const codeSpan = document.getElementById(`code-${index}`);
         if (codeSpan) {
            codeSpan.textContent = product.code || "";
         }

         // Atualiza o ID oculto
         const codeHiddenInput = document.getElementById(
            `code-hidden-${index}`
         );
         if (codeHiddenInput) {
            codeHiddenInput.value = product.id;
         }

         // Atualiza o nome
         const nameInput = document.getElementById(`name-${index}`);
         nameInput.value = product.name;

         // Atualiza o valor unitário exibido
         const unitValueDisplay = document.getElementById(
            `unit-value-display-${index}`
         );
         if (unitValueDisplay) {
            unitValueDisplay.textContent = `R$ ${product.price
               .toFixed(2)
               .replace(".", ",")}`;
         }

         // Atualiza o valor unitário oculto
         const unitValueInput = document.getElementById(`unit-value-${index}`);
         if (unitValueInput) {
            unitValueInput.value = product.price;
         }

         // Atualiza o item no array
         const item = {
            item: `${index + 1}`,
            id: product.id,
            code: product.code || "",
            name: product.name,
            unit_value: product.price,
            quantity:
               parseInt(document.getElementById(`quantity-${index}`).value) ||
               1,
            total:
               product.price *
               (parseInt(document.getElementById(`quantity-${index}`).value) ||
                  1),
         };

         items.splice(index, 1, item);
         itemCount = items.length; // Atualiza o itemCount para o próximo item
         productSuggestionsDiv.innerHTML = "";
         updateTotal(index);
      });

      productSuggestionsDiv.appendChild(productSuggestion);
   });

   // Remove a borda do último item
   const lastSuggestion = productSuggestionsDiv.lastChild;
   if (lastSuggestion) {
      lastSuggestion.style.borderBottom = "none";
   }
}

function removeItem(index) {
   if (index < 0 || index >= items.length) {
      return;
   }

   // Remove o item do array global "items"
   items.splice(index, 1);

   // Atualiza os IDs dos itens no array (por exemplo, reordenando-os)
   items.forEach((item, index) => {
      item.item = index + 1;
   });

   // Atualiza o contador para o próximo item
   itemCount = items.length + 1;

   // Recalcula o total do serviço com base nos itens restantes
   calculateTotalService();

   // Renderiza os itens da tabela novamente
   renderAllItems();
}

function renderAllItems() {
   itemsTbody.innerHTML = "";

   items.forEach((item, index) => {
      renderItems(item, index);
   });
}

function updateItemIds() {
   items.forEach((item, index) => {
      item.item = `${index + 1}`;
   });
}

itemsTbody.addEventListener("input", (event) => {
   if (
      event.target.type === "number" &&
      event.target.id.startsWith("quantity-")
   ) {
      const index = event.target.id.split("-")[1];
      const quantity = parseInt(event.target.value) || 1;

      // Usa o valor unitário do campo oculto
      const unit_value = parseFloat(
         document.getElementById(`unit-value-${index}`).value
      );

      const total = unit_value * quantity;

      document.getElementById(`total-${index}`).textContent = `R$ ${total
         .toFixed(2)
         .replace(".", ",")}`;

      items[index].quantity = quantity;
      items[index].total = total;

      calculateTotalService();
   }
});

function updateTotal(index) {
   // Usa o valor unitário do campo oculto
   const unit_value = parseFloat(
      document.getElementById(`unit-value-${index}`).value
   );

   const quantity =
      parseInt(document.getElementById(`quantity-${index}`).value) || 1;

   const total = unit_value * quantity;

   document.getElementById(`total-${index}`).textContent = `R$ ${total
      .toFixed(2)
      .replace(".", ",")}`;

   items[index].quantity = quantity;
   items[index].total = total;

   calculateTotalService();
}

function calculateTotalService() {
   let total = 0;
   items.forEach((item) => {
      total += item.total;
   });
   totalServiceSpan.textContent = `R$ ${total.toFixed(2).replace(".", ",")}`;
}

document.querySelectorAll(".btn-danger").forEach((button) => {
   button.addEventListener("click", (event) => {
      event.preventDefault();
   });
});

document
   .getElementById("create-service-form")
   .addEventListener("submit", (e) => {
      const form = document.getElementById("create-service-form");
      const itemsInput = document.createElement("input");
      itemsInput.type = "hidden";
      itemsInput.name = "items";
      itemsInput.value = JSON.stringify(items);
      form.appendChild(itemsInput);
   });
