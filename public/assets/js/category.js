/**
 * JavaScript para Módulo Categories
 *
 * Funcionalidades:
 * - Validações client-side
 * - Busca e filtros
 * - Interface responsiva
 * - Loading states
 * - Formatação de dados
 */

document.addEventListener("DOMContentLoaded", function () {
   // Elementos do DOM
   const searchInput = document.getElementById("search");
   const clearMainSearch = document.getElementById("clearMainSearch");
   const mainSearchBtn = document.getElementById("mainSearch");
   const initialMessage = document.getElementById("initial-message");
   const loadingSpinner = document.getElementById("loading-spinner");
   const resultsContainer = document.getElementById("results-container");
   const resultsCount = document.getElementById("results-count");
   const statusSelect = document.getElementById("status");
   const btnFilterCategories = document.getElementById("btnFilterCategories");

   // Verificação de elementos essenciais (página de listagem)
   if (searchInput) {
      // Inicializa o paginador de tabela
      const categoryPaginator = new TablePaginator({
         tableId: "results-table",
         paginationId: "pagination",
         infoId: "pagination-info",
         itemsPerPage: 10,
         colSpan: 6,
         formatRow: formatCategoryRow,
      });

      async function performSearch() {
         const searchTerm = searchInput?.value?.trim() || "";
         const status = statusSelect?.value?.trim() || "";

         if (!initialMessage || !loadingSpinner || !resultsContainer) {
            console.error("Elementos da interface não encontrados");
            return;
         }

         // Controle de visibilidade dos elementos
         initialMessage.classList.add("d-none");
         loadingSpinner.classList.remove("d-none");
         resultsContainer.classList.add("d-none");

         try {
            // Obtém o token CSRF
            const csrfToken = document
               .querySelector('meta[name="csrf-token"]')
               ?.getAttribute("content");
            if (!csrfToken) {
               throw new Error("Token CSRF não encontrado");
            }

            console.log("Iniciando busca com termo:", searchTerm);

            // Configuração da requisição AJAX (GET com query string)
            const params = new URLSearchParams();
            if (searchTerm) params.set("search", searchTerm);
            if (status) params.set("status", status);
            const qs = params.toString();
            const url = qs
               ? `/provider/categories/search?${qs}`
               : "/provider/categories/search";
            console.log("URL da requisição:", url);

            const response = await fetch(url, {
               method: "GET",
               headers: {
                  "X-Requested-With": "XMLHttpRequest",
                  Accept: "application/json",
                  "Content-Type": "application/json",
               },
               credentials: "include", // Envia cookies de sessão
            });
            console.log(
               "Resposta recebida:",
               response.status,
               response.statusText
            );

            if (!response.ok) {
               throw new Error(`HTTP error! status: ${response.status}`);
            }

            const responseData = await response.json();
            console.log("Dados recebidos:", responseData);

            // Atualiza a tabela com os resultados
            const data = responseData.data || [];
            console.log("Dados para a tabela:", data);

            try {
               categoryPaginator.updateTable(data);
               console.log("TablePaginator.updateTable executado com sucesso");
            } catch (error) {
               console.error("Erro ao atualizar tabela:", error);
            }

            // Atualiza o contador de resultados
            if (resultsCount) {
               resultsCount.textContent = `${data.length} resultados encontrados`;
               console.log("Contador atualizado:", resultsCount.textContent);
            } else {
               console.warn("Elemento resultsCount não encontrado");
            }

            // Mostra os resultados
            resultsContainer.classList.remove("d-none");
         } catch (error) {
            console.error("Erro na busca:", error);
            showError("Ocorreu um erro ao realizar a busca. Tente novamente.");
            initialMessage.classList.remove("d-none");
         } finally {
            loadingSpinner.classList.add("d-none");
         }
      }

      // Função para limpar campos e resetar estado
      function clearFields() {
         if (searchInput) {
            searchInput.value = "";
            searchInput.focus();
         }

         if (initialMessage) initialMessage.classList.remove("d-none");
         if (resultsContainer) resultsContainer.classList.add("d-none");
         if (loadingSpinner) loadingSpinner.classList.add("d-none");
         if (resultsCount) resultsCount.textContent = "";

         categoryPaginator.updateTable([]);
      }

      // Função para mostrar mensagens de erro
      function showError(message) {
         const existingAlerts = document.querySelectorAll(".alert");
         existingAlerts.forEach((alert) => alert.remove());

         const alertContainer = document.createElement("div");
         alertContainer.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

         const container = document.querySelector(".container-fluid");
         if (container) {
            container.insertBefore(
               alertContainer.firstElementChild,
               container.firstElementChild
            );
         }

         setTimeout(() => {
            const alert = document.querySelector(".alert-danger");
            if (alert) {
               alert.classList.remove("show");
               setTimeout(() => alert.remove(), 150);
            }
         }, 5000);
      }

      // Event Listeners para listagem
      if (mainSearchBtn) {
         mainSearchBtn.addEventListener("click", (e) => {
            e.preventDefault();
            handleFilterSubmit();
         });
      }

      if (searchInput) {
         searchInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
               e.preventDefault();
               handleFilterSubmit();
            }
         });
      }

      if (clearMainSearch) {
         clearMainSearch.addEventListener("click", clearFields);
      }

      if (btnFilterCategories) {
         btnFilterCategories.addEventListener("click", (e) => {
            e.preventDefault();
            handleFilterSubmit();
         });
      }

      function handleFilterSubmit() {
         const hasFilters = !!(
            (searchInput?.value || "").trim() ||
            (statusSelect?.value || "").trim()
         );

         if (!hasFilters) {
            const modalEl = document.getElementById(
               "confirmAllCategoriesModal"
            );
            if (!modalEl) {
               performSearch();
               return;
            }
            const confirmBtn = modalEl.querySelector(
               ".btn-confirm-all-categories"
            );
            const modal = new bootstrap.Modal(modalEl);
            const handler = function () {
               confirmBtn.removeEventListener("click", handler);
               modal.hide();
               performSearch();
            };
            confirmBtn.addEventListener("click", handler);
            modal.show();
         } else {
            performSearch();
         }
      }
   }

   // === VALIDAÇÕES CLIENT-SIDE PARA FORMULÁRIOS ===

   // Elementos do formulário de criação/edição
   const nameInput = document.getElementById("name");
   const formEl = document.querySelector("form");
   const submitBtn = document.querySelector('form button[type="submit"]');

   // Se estamos na página de formulário (tem campo name)
   if (nameInput && formEl) {
      // Validação em tempo real do campo nome
      nameInput.addEventListener("input", function () {
         validateName(this);
      });

      // Validação no submit do formulário
      if (formEl && submitBtn) {
         formEl.addEventListener("submit", function (e) {
            if (!validateForm(e)) {
               e.preventDefault();
               return false;
            }

            // Loading state
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML =
               '<i class="bi bi-hourglass-split me-2"></i>Salvando...';

            // Restaura o botão após 3 segundos como fallback
            setTimeout(() => {
               submitBtn.disabled = false;
               submitBtn.innerHTML = originalText;
            }, 3000);
         });
      }

      // Validação do nome da categoria
      function validateName(input) {
         const name = input.value.trim();
         const feedbackEl = input.nextElementSibling?.nextElementSibling;

         // Remove classes anteriores
         input.classList.remove("is-invalid", "is-valid");

         // Validações básicas
         if (name.length < 2) {
            setInvalid(
               input,
               "O nome deve ter pelo menos 2 caracteres",
               feedbackEl
            );
            return false;
         }

         if (name.length > 100) {
            setInvalid(
               input,
               "O nome não pode ter mais de 100 caracteres",
               feedbackEl
            );
            return false;
         }

         // Regex para caracteres válidos (letras, números, espaços, hífens)
         const validChars = /^[a-zA-Z0-9\s\-_çÇãÃõÕáÁéÉíÍóÓúÚüÜ&()]+$/;
         if (!validChars.test(name)) {
            setInvalid(input, "O nome contém caracteres inválidos", feedbackEl);
            return false;
         }

         setValid(input, "Nome válido", feedbackEl);
         return true;
      }

      // Função para marcar campo como inválido
      function setInvalid(input, message, feedbackEl) {
         input.classList.add("is-invalid");
         if (feedbackEl) {
            feedbackEl.textContent = message;
            feedbackEl.classList.remove("d-none");
         }
      }

      // Função para marcar campo como válido
      function setValid(input, message, feedbackEl) {
         input.classList.remove("is-invalid");
         input.classList.add("is-valid");
         if (feedbackEl) {
            feedbackEl.textContent = message;
            feedbackEl.classList.remove("d-none");
            feedbackEl.classList.remove("text-danger");
            feedbackEl.classList.add("text-success");
         }
      }

      // Validação completa do formulário
      function validateForm(e) {
         let isValid = true;

         // Valida campos obrigatórios
         const requiredFields = formEl.querySelectorAll("[required]");
         requiredFields.forEach((field) => {
            if (!validateRequired(field)) {
               isValid = false;
            }
         });

         // Validação específica do nome
         if (nameInput && !validateName(nameInput)) {
            isValid = false;
         }

         if (!isValid) {
            showFormError(
               "Por favor, corrija os erros highlighted antes de prosseguir."
            );
         }

         return isValid;
      }

      // Validação de campo obrigatório
      function validateRequired(field) {
         const value = field.value.trim();
         if (!value) {
            field.classList.add("is-invalid");
            return false;
         }
         field.classList.remove("is-invalid");
         return true;
      }

      // Função para mostrar erro do formulário
      function showFormError(message) {
         // Remove alertas anteriores
         const existingAlerts = formEl.parentElement.querySelectorAll(".alert");
         existingAlerts.forEach((alert) => alert.remove());

         const alertEl = document.createElement("div");
         alertEl.className = "alert alert-danger alert-dismissible fade show";
         alertEl.innerHTML = `
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;

         formEl.parentElement.insertBefore(alertEl, formEl);

         // Scroll para o erro
         alertEl.scrollIntoView({ behavior: "smooth", block: "center" });
      }

      // Remove feedback visual ao interagir com campos
      formEl.addEventListener("input", function (e) {
         const target = e.target;
         if (target.classList.contains("is-invalid")) {
            target.classList.remove("is-invalid");
         }
      });
   }
});

// Função para formatar cada linha da tabela de categorias
function formatCategoryRow(category) {
   const status = category.is_active || false ? "active" : "inactive";
   const statusLabel = status === "active" ? "Ativo" : "Inativo";
   const badgeClass = status === "active" ? "badge-success" : "badge-secondary";
   const toggleLabel = status === "active" ? "Desativar" : "Ativar";

   // Determina se é categoria pai ou filha
   const isChild = category.parent_id !== null;
   const hierarchyBadge = isChild
      ? '<span class="badge bg-info badge-sm">Subcategoria</span>'
      : '<span class="badge bg-primary badge-sm">Categoria Principal</span>';

   return `
    <tr class="table-row-hover">
        <td class="px-4 py-3 align-middle">
            <div>
                <div class="fw-semibold mb-1">${
                   category.name || "Nome não informado"
                }</div>
                <small class="text-muted">${hierarchyBadge}</small>
            </div>
        </td>
        <td class="px-3 py-3 align-middle">
            <div class="slug-info">
                <div class="text-muted small">/${
                   category.slug || "slug-nao-definido"
                }</div>
                <div class="text-muted small">ID: ${category.id}</div>
            </div>
        </td>
        <td class="px-3 py-3 align-middle">
            <div class="date-info">
                <div class="text-dark">${formatDate(category.created_at)}</div>
                <small class="text-muted">${formatTimeAgo(
                   category.created_at
                )}</small>
            </div>
        </td>
        <td class="px-3 py-3 align-middle">
            <span class="badge ${badgeClass}">${statusLabel}</span>
        </td>
        <td class="px-3 py-3 align-middle">
            <div class="metadata-info">
                <div class="text-muted small">
                    <i class="bi bi-building me-1"></i>Tenant: ${
                       category.tenant_id
                    }
                </div>
                ${
                   category.services_count
                      ? `<div class="text-muted small"><i class="bi bi-link me-1"></i>${category.services_count} serviços</div>`
                      : ""
                }
            </div>
        </td>
        <td class="text-center align-middle">
            <div class="btn-group" role="group">
                <a href="/provider/categories/${category.slug}"
                   class="btn btn-sm btn-outline-primary"
                   data-bs-toggle="tooltip"
                   title="Visualizar">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="/provider/categories/${category.slug}/edit"
                   class="btn btn-sm btn-outline-success"
                   data-bs-toggle="tooltip"
                   title="Editar">
                    <i class="bi bi-pencil"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-warning toggle-status-btn" data-id="${
                   category.id
                }" data-current="${status}">
                    <i class="bi bi-toggle2-on"></i> ${toggleLabel}
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete('${
                   category.id
                }', '${category.slug}')">
                    <i class="bi bi-trash"></i> Excluir
                </button>
            </div>
        </td>
    </tr>`;
}

// Função para confirmar exclusão
function confirmDelete(categoryId, categoryName) {
   const modal = new bootstrap.Modal(document.getElementById("deleteModal"));
   const confirmBtn = document.getElementById("confirmDeleteBtn");
   const csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      .getAttribute("content");

   // Atualiza o texto do modal
   const modalTitle = document.querySelector("#deleteModal .modal-title");
   const modalBody = document.querySelector("#deleteModal .modal-body");

   if (modalTitle) modalTitle.textContent = "Confirmar Exclusão";
   if (modalBody) {
      modalBody.innerHTML = `
            <div class="text-center">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-1 mb-3"></i>
                <p class="mb-2">Tem certeza que deseja excluir a categoria:</p>
                <strong>"${categoryName}"</strong>
                <p class="text-muted small mt-2">Esta ação não pode ser desfeita.</p>
            </div>
        `;
   }

   // Criar formulário DELETE dinamicamente
   const form = document.createElement("form");
   form.method = "POST";
   form.action = `/provider/categories/${categoryId}`;
   form.style.display = "none";

   // Token CSRF
   const csrfInput = document.createElement("input");
   csrfInput.type = "hidden";
   csrfInput.name = "_token";
   csrfInput.value = csrfToken;
   form.appendChild(csrfInput);

   // Método DELETE
   const methodInput = document.createElement("input");
   methodInput.type = "hidden";
   methodInput.name = "_method";
   methodInput.value = "DELETE";
   form.appendChild(methodInput);

   document.body.appendChild(form);

   // Configurar botão de confirmação
   confirmBtn.onclick = function () {
      form.submit();
      modal.hide();
   };

   // Cleanup ao fechar modal
   document
      .getElementById("deleteModal")
      .addEventListener("hidden.bs.modal", function () {
         document.body.removeChild(form);
         confirmBtn.onclick = null;
      });

   modal.show();
}

// Função para alternar status via AJAX
document.addEventListener("click", async function (e) {
   const target = e.target.closest(".toggle-status-btn");
   if (!target) return;
   e.preventDefault();

   try {
      const id = target.getAttribute("data-id");
      const csrfToken = document
         .querySelector('meta[name="csrf-token"]')
         .getAttribute("content");

      // Loading state
      target.disabled = true;
      const originalHTML = target.innerHTML;
      target.innerHTML = '<i class="bi bi-hourglass-split"></i>';

      const response = await fetch(`/provider/categories/${id}/toggle-status`, {
         method: "POST",
         headers: {
            "X-CSRF-TOKEN": csrfToken,
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
         },
      });

      const json = await response.json().catch(() => null);

      if (response.ok && json && json.success) {
         // Atualiza a interface
         const row = target.closest("tr");
         const badge = row && row.querySelector("td:nth-child(4) .badge");

         // Determinar novo status alternando
         const current = (
            target.getAttribute("data-current") || ""
         ).toLowerCase();
         const next = current === "active" ? "inactive" : "active";
         target.setAttribute("data-current", next);
         target.innerHTML = `<i class="bi bi-toggle2-on"></i> ${
            next === "active" ? "Desativar" : "Ativar"
         }`;

         if (badge) {
            badge.textContent = next === "active" ? "Ativo" : "Inativo";
            badge.classList.remove("badge-success", "badge-secondary");
            badge.classList.add(
               next === "active" ? "badge-success" : "badge-secondary"
            );
         }

         // Mostra mensagem de sucesso
         if (window.easyAlert) {
            window.easyAlert.success(
               json.message || "Status atualizado com sucesso!"
            );
         }
      } else {
         const msg =
            json && json.message ? json.message : "Erro ao atualizar status";
         if (window.easyAlert) {
            window.easyAlert.error(msg);
         } else {
            alert(msg);
         }
      }
   } catch (err) {
      console.error(err);
      if (window.easyAlert) {
         window.easyAlert.error("Erro na requisição de status");
      } else {
         alert("Erro na requisição de status");
      }
   } finally {
      target.disabled = false;
      if (target.innerHTML.includes("hourglass-split")) {
         target.innerHTML = '<i class="bi bi-toggle2-on"></i> Desativar';
      }
   }
});

// === UTILITÁRIOS ===

// Formata data para o padrão brasileiro
function formatDate(dateString) {
   if (!dateString) return "";
   const date = new Date(dateString);
   return date.toLocaleDateString("pt-BR");
}

// Formata tempo relativo
function formatTimeAgo(dateString) {
   if (!dateString) return "";
   const date = new Date(dateString);
   const now = new Date();
   const diffTime = Math.abs(now - date);
   const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

   if (diffDays === 1) return "Ontem";
   if (diffDays < 7) return `${diffDays} dias atrás`;
   if (diffDays < 30) return `${Math.floor(diffDays / 7)} semanas atrás`;
   if (diffDays < 365) return `${Math.floor(diffDays / 30)} meses atrás`;
   return `${Math.floor(diffDays / 365)} anos atrás`;
}
