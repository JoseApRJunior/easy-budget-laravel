export const UIUtils = {
   showLoading(element) {
      element.classList.remove("d-none");
   },

   hideLoading(element) {
      element.classList.add("d-none");
   },

   showError(message, container, duration = 5000) {
      const alertDiv = document.createElement("div");
      alertDiv.className =
         "alert alert-danger alert-dismissible fade show mt-3";
      alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        `;
      container.insertAdjacentElement("afterend", alertDiv);
      setTimeout(() => alertDiv.remove(), duration);
   },
};
