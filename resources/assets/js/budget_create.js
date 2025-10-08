// Funções auxiliares
const getDocumento = (customer) => {
   if (customer.cpf?.trim()) return customer.cpf;
   if (customer.cnpj?.trim()) return customer.cnpj;
   return "";
};

const apenasNumeros = (texto) => texto.replace(/[^0-9]/g, "");

// Elementos DOM
const elements = {
   search: document.getElementById("search-input"),
   customer: document.getElementById("customer"),
   customerId: document.getElementById("customer_id"),
   customerName: document.getElementById("customer_name"),
   phone: document.getElementById("phone"),
   email: document.getElementById("email"),
   suggestions: document.getElementById("suggestions-list"),
   description: document.getElementById("description"),
   charCount: document.getElementById("char-count"),
};

// Busca de clientes
elements.search.addEventListener("input", () => {
   const searchValue = elements.search.value.toLowerCase();
   const suggestions = customers.filter((customer) => {
      const documento = getDocumento(customer).toLowerCase();
      const fullName =
         `${customer.first_name} ${customer.last_name}`.toLowerCase();
      return fullName.includes(searchValue) || documento.includes(searchValue);
   });

   elements.suggestions.innerHTML = "";
   suggestions.forEach((suggestion) => {
      const documento = getDocumento(suggestion);
      const listItem = document.createElement("li");
      listItem.className =
         "list-group-item d-flex justify-content-between align-items-center";

      const customerInfo = document.createElement("span");
      customerInfo.textContent = `${suggestion.first_name} ${
         suggestion.last_name
      }${documento ? ` - ${documento}` : ""}`;

      const selectButton = document.createElement("button");
      selectButton.innerHTML = '<i class="bi bi-check-lg"></i> Selecionar';
      selectButton.className = "btn btn-sm btn-primary";

      listItem.appendChild(customerInfo);
      listItem.appendChild(selectButton);

      selectButton.addEventListener("click", () => {
         elements.customer.value = customerInfo.textContent;
         elements.customerId.value = suggestion.id;
         elements.customerName.value = `${suggestion.first_name} ${suggestion.last_name}`;
         elements.phone.value = suggestion.phone;
         elements.email.value = suggestion.email;
         elements.search.value = "";
         elements.suggestions.innerHTML = "";
      });

      elements.suggestions.appendChild(listItem);
   });
});

// Contador de caracteres
elements.description.addEventListener("input", () => {
   const remaining = 255 - elements.description.value.length;
   elements.charCount.querySelector("span").textContent = remaining;
});
