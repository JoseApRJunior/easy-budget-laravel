export const initializeCharacterCounter = () => {
   const textarea = document.getElementById("description");
   const charCount = document.getElementById("char-count-value");

   if (textarea && charCount) {
      // Atualiza o contador inicial
      const updateCounter = () => {
         const charsLeft = textarea.maxLength - textarea.value.length;
         charCount.textContent = charsLeft;
      };

      // Adiciona o listener para input
      textarea.addEventListener("input", updateCounter);

      // Executa uma vez para inicializar o contador
      updateCounter();
   }
};
