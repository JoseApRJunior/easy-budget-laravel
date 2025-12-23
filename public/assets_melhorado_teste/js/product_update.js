/**
 * product_create.js
 * Script para gerenciar a criação de produtos
 */

import { MoneyFormatter } from './modules/moneyFormatter.js';
import { setupImagePreview } from './modules/image-preview.js';

document.addEventListener("DOMContentLoaded", function () {
   // Inicialização dos componentes
   initializeComponents();
   
   /**
    * Inicializa todos os componentes do formulário
    */
   function initializeComponents() {
      // Inicializa o contador de caracteres
      initializeCharCounter();
      
      // Inicializa o preview de imagem
      setupImagePreview({
         inputId: 'image',
         previewId: 'imagePreview',
         buttonId: 'uploadButton',
         maxSize: 2 * 1024 * 1024, // 2MB
         allowedTypes: ['image/jpeg', 'image/png'],
         onError: (errorMsg) => {
            showAlert(errorMsg, 'danger');
         }
      });
      
      // Inicializa o campo de preço
      initializePriceField();
      
      // Inicializa a validação do formulário
      initializeFormValidation();
   }

   /**
    * Inicializa o contador de caracteres para o campo de descrição
    */
   function initializeCharCounter() {
      const textarea = document.getElementById('description');
      const charCount = document.getElementById('char-count-value');
      
      if (!textarea || !charCount) return;
      
      // Define a função de atualização do contador
      const updateCounter = () => {
         const maxLength = textarea.maxLength;
         const currentLength = textarea.value.length;
         const charsLeft = maxLength - currentLength;
         
         charCount.textContent = charsLeft;
         
         // Adiciona classe de alerta quando estiver próximo do limite
         if (charsLeft < 100) {
            charCount.classList.add('text-warning');
         } else {
            charCount.classList.remove('text-warning');
         }
      };
      
      // Adiciona o evento de input para atualizar o contador
      textarea.addEventListener('input', updateCounter);
      
      // Executa uma vez para inicializar o contador
      updateCounter();
   }

   /**
    * Inicializa o campo de preço com formatação de moeda
    */
   function initializePriceField() {
      const priceInput = document.getElementById('price');
      if (!priceInput) return;
      
      // Usa o MoneyFormatter para formatar o campo de preço
      MoneyFormatter.setupInput(priceInput);
      
      // Ajusta o valor inicial se necessário
      if (!priceInput.value) {
         priceInput.value = "0,00";
      }
   }

   /**
    * Inicializa a validação do formulário
    */
   function initializeFormValidation() {
      const form = document.getElementById('createForm');
      if (!form) return;
      
      form.addEventListener('submit', function(e) {
         let isValid = true;
         
         // Valida campos obrigatórios
         const requiredFields = form.querySelectorAll('[required]');
         requiredFields.forEach(field => {
            if (!field.value.trim()) {
               field.classList.add('is-invalid');
               isValid = false;
            } else {
               field.classList.remove('is-invalid');
               field.classList.add('is-valid');
            }
         });
         
         // Prepara o valor do preço para envio (remove formatação)
         const priceInput = document.getElementById('price');
         if (priceInput && priceInput.value) {
            // Mantém apenas os dígitos para o envio
            const rawValue = priceInput.value.replace(/\D/g, "");
            
            // Cria um campo oculto para enviar o valor sem formatação
            const hiddenInput = document.createElement("input");
            hiddenInput.type = "hidden";
            hiddenInput.name = "price_raw";
            hiddenInput.value = rawValue;
            form.appendChild(hiddenInput);
         }
         
         // Impede o envio do formulário se inválido
         if (!isValid) {
            e.preventDefault();
            showAlert('Por favor, corrija os erros no formulário antes de continuar.', 'danger');
         }
      });
      
      // Remove a classe de inválido quando o usuário começa a digitar
      form.querySelectorAll('input, select, textarea').forEach(field => {
         field.addEventListener('input', function() {
            this.classList.remove('is-invalid');
         });
      });
   }
   
   /**
    * Exibe um alerta na página
    * @param {string} message - Mensagem a ser exibida
    * @param {string} type - Tipo do alerta (success, danger, warning, info)
    */
   function showAlert(message, type = 'info') {
      const form = document.getElementById('createForm');
      if (!form) return;
      
      // Remove alertas existentes do mesmo tipo
      const existingAlerts = form.querySelectorAll(`.alert-${type}`);
      existingAlerts.forEach(alert => alert.remove());
      
      // Cria o novo alerta
      const alertElement = document.createElement('div');
      alertElement.className = `alert alert-${type} mt-3`;
      alertElement.textContent = message;
      
      // Adiciona o alerta ao início do formulário
      form.prepend(alertElement);
      
      // Remove o alerta após 5 segundos
      setTimeout(() => {
         alertElement.remove();
      }, 5000);
   }
});