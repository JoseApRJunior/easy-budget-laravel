// main.js - Funcionalidades globais do header/footer
import { initTheme, toggleTheme } from "./modules/utils.js";

document.addEventListener("DOMContentLoaded", function () {
   // Inicializar tema
   initTheme();

   // Event listener para tema no header
   const themeButton = document.querySelector('[onclick="toggleTheme()"]');
   if (themeButton) {
      themeButton.removeAttribute("onclick");
      themeButton.addEventListener("click", toggleTheme);
   }

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
