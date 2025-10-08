/**
 * Home.js - VERSÃO MÍNIMA - Apenas botões essenciais
 */

// Log inicial para verificar se o arquivo está sendo carregado
console.log("=== HOME-MINIMAL.JS CARREGADO ===");

// Função básica para scroll suave
function scrollToElement(elementId) {
   const element = document.getElementById(elementId);
   if (element) {
      const offset = 100;
      const elementPosition = element.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.scrollY - offset;
      window.scrollTo({ top: offsetPosition, behavior: "smooth" });
   }
}

// Função básica para atualizar seleção de plano
function updatePlanSelection(selectedPlan, buttons, select) {
   // Atualizar select
   for (let i = 0; i < select.options.length; i++) {
      if (select.options[i].text.includes(selectedPlan)) {
         select.selectedIndex = i;
         break;
      }
   }

   // Atualizar botões
   buttons.forEach((btn) => {
      if (btn.getAttribute("data-plan") === selectedPlan) {
         btn.classList.add("active");
      } else {
         btn.classList.remove("active");
      }
   });
}

// Inicialização quando DOM estiver carregado
document.addEventListener("DOMContentLoaded", function () {
    console.log("=== DOM CARREGADO - INICIANDO CONFIGURAÇÃO ===");
    console.log("Home.js carregado - Iniciando configuração...");

   // Configurar botão "Conheça nossos planos"
   const conhecaPlanos = document.getElementById("conhecaPlanos");
   if (conhecaPlanos) {
      console.log("✓ Botão conhecaPlanos encontrado");
      conhecaPlanos.addEventListener("click", (e) => {
         e.preventDefault();
         console.log("✓ Clicou em conhecaPlanos - fazendo scroll para planos");
         scrollToElement("plans");
      });
   } else {
      console.log("✗ Botão conhecaPlanos NÃO encontrado");
   }

   // Configurar botões de seleção de planos
   const planButtons = document.querySelectorAll(".select-plan");
   const planSelect = document.getElementById("planSelect");

   console.log("✓ Encontrados " + planButtons.length + " botões select-plan");
   console.log("✓ planSelect encontrado:", !!planSelect);

   planButtons.forEach((button, index) => {
      console.log(
         "✓ Configurando botão " + (index + 1) + " com plano:",
         button.getAttribute("data-plan")
      );
      button.addEventListener("click", () => {
         const selectedPlan = button.getAttribute("data-plan");
         console.log("✓ Clicou no plano:", selectedPlan);
         updatePlanSelection(selectedPlan, planButtons, planSelect);
         scrollToElement("preCadastroForm");
      });
   });

   console.log("✓ Configuração completa!");
});
