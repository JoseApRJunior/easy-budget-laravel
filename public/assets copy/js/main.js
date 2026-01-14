// main.js - Funcionalidades globais do header/footer

/**
 * Inicializa o Tom Select em um elemento ou grupo de elementos
 * @param {string|HTMLElement} selector - Seletor CSS ou elemento HTML
 * @param {object} options - Opções adicionais para o Tom Select
 */
export function initTomSelect(selector, options = {}) {
   if (typeof TomSelect === 'undefined') {
      console.warn('TomSelect não está carregado.');
      return;
   }

   const elements = typeof selector === 'string' ? document.querySelectorAll(selector) : [selector];

   elements.forEach(el => {
      if (el.tomselect) return; // Evita inicializar múltiplas vezes

      const defaultOptions = {
         create: false,
         allowEmptyOption: true,
         maxOptions: null, // Mostra todas as opções (importante para categorias)
         plugins: ['dropdown_input'], // Adiciona campo de busca no dropdown
         dropdownParent: 'body', // Resolve problemas de overflow/z-index fixando no body
         render: {
             option: function(data, escape) {
                 // Estilização customizada para optgroups ou níveis de hierarquia
                 const isSub = data.text.includes('\u00A0\u00A0') || data.text.startsWith('  ');
                 return `<div>
                     <span class="${isSub ? 'ps-3 text-muted' : 'fw-medium'}">${escape(data.text)}</span>
                 </div>`;
             },
             item: function(data, escape) {
                 return `<div>${escape(data.text)}</div>`;
             }
         }
      };

      new TomSelect(el, { ...defaultOptions, ...options });
   });
}

// Tornar global para uso em scripts inline (como adição dinâmica de itens)
window.initTomSelect = initTomSelect;

document.addEventListener("DOMContentLoaded", function () {
   // Inicializar Tom Select em elementos com a classe .tom-select
   initTomSelect(".tom-select");

   // Submenu dropdown
   document.querySelectorAll('.dropdown-submenu .dropdown-toggle').forEach(function(element) {
      element.addEventListener('click', function(e) {
         e.preventDefault();
         e.stopPropagation();

         const submenu = this.nextElementSibling;
         if (submenu) {
            submenu.classList.toggle('show');
         }
      });
   });
});
