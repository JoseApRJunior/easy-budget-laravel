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
});
