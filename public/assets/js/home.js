// Home.js simplificado para página inicial atual
document.addEventListener("DOMContentLoaded", function () {
   // Inicializar elementos básicos
   initializeElements();
});

function initializeElements() {
   // Event listeners para planos
   const planButtons = document.querySelectorAll(".select-plan");
   const conhecaPlanos = document.getElementById("conhecaPlanos");

   if (conhecaPlanos) {
      conhecaPlanos.addEventListener("click", (e) => {
         e.preventDefault();
         scrollToElement("plans");
      });
   }

   planButtons.forEach((button) => {
      button.addEventListener("click", () => {
         const selectedPlan = button.getAttribute("data-plan");
         // Para a página atual, apenas redireciona para o registro
         window.location.href = `/register?plan=${selectedPlan}`;
      });
   });
}


function scrollToElement(elementId) {
   const element = document.getElementById(elementId);
   if (element) {
      const offset = 100;
      const elementPosition = element.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.scrollY - offset;
      window.scrollTo({ top: offsetPosition, behavior: "smooth" });
   }
}
