class EasyAlert {
  constructor(options = {}) {
    this.defaults = {
      position: "top-right",
      duration: 5000,
      closeButton: true,
      animation: true,
      maxAlerts: 5,
      container: document.body,
      zIndex: 9999,
      theme: "light",
      icons: {
        success: '<i class="bi bi-check-circle-fill"></i>',
        error: '<i class="bi bi-x-circle-fill"></i>',
        warning: '<i class="bi bi-exclamation-triangle-fill"></i>',
        info: '<i class="bi bi-info-circle-fill"></i>',
        question: '<i class="bi bi-question-circle-fill"></i>',
      },
    };
    this.options = { ...this.defaults, ...options };
    this.initContainer();
    this.counter = 0;
    this.activeAlerts = [];
  }

  initContainer() {
    let container = document.querySelector(".easy-alert-container");
    if (!container) {
      container = document.createElement("div");
      container.className = `easy-alert-container easy-alert-${this.options.position}`;
      container.style.zIndex = this.options.zIndex;
      this.options.container.appendChild(container);
    }
    this.container = container;
  }

  show(type, message, options = {}) {
    const alertOptions = { ...this.options, ...options };
    const id = `easy-alert-${Date.now()}-${this.counter++}`;
    const alert = document.createElement("div");
    alert.id = id;
    alert.className = `easy-alert easy-alert-${type} ${alertOptions.animation ? "easy-alert-animated" : ""}`;
    alert.setAttribute("role", "alert");
    const icon = alertOptions.icons[type] || "";
    alert.innerHTML = `
      <div class="easy-alert-content">
        ${icon ? `<div class="easy-alert-icon">${icon}</div>` : ""}
        <div class="easy-alert-message">${message}</div>
        ${alertOptions.closeButton ? '<button type="button" class="easy-alert-close" aria-label="Fechar">&times;</button>' : ""}
      </div>
      ${alertOptions.duration > 0 ? '<div class="easy-alert-progress"></div>' : ""}
    `;
    if (alertOptions.closeButton) {
      const closeButton = alert.querySelector(".easy-alert-close");
      closeButton.addEventListener("click", () => this.close(id));
    }
    this.container.appendChild(alert);
    this.manageAlerts();
    this.activeAlerts.push({
      id,
      element: alert,
      timeout: alertOptions.duration > 0 ? setTimeout(() => this.close(id), alertOptions.duration) : null,
    });
    if (alertOptions.duration > 0) {
      const progressBar = alert.querySelector(".easy-alert-progress");
      progressBar.style.animationDuration = `${alertOptions.duration}ms`;
      progressBar.classList.add("easy-alert-progress-active");
    }
    return id;
  }

  close(id) {
    const alertIndex = this.activeAlerts.findIndex((alert) => alert.id === id);
    if (alertIndex !== -1) {
      const alert = this.activeAlerts[alertIndex];
      if (alert.timeout) clearTimeout(alert.timeout);
      alert.element.classList.add("easy-alert-exit");
      setTimeout(() => {
        if (alert.element.parentNode) alert.element.parentNode.removeChild(alert.element);
        this.activeAlerts.splice(alertIndex, 1);
      }, 300);
    }
  }

  closeAll() {
    this.activeAlerts.forEach((alert) => this.close(alert.id));
  }

  manageAlerts() {
    if (this.activeAlerts.length >= this.options.maxAlerts) this.close(this.activeAlerts[0].id);
  }

  success(message, options = {}) { return this.show("success", message, options); }
  error(message, options = {}) { return this.show("error", message, options); }
  warning(message, options = {}) { return this.show("warning", message, options); }
  info(message, options = {}) { return this.show("info", message, options); }
  question(message, options = {}) { return this.show("question", message, options); }

  validateField(element, message, options = {}) {
    element.classList.add("is-invalid");
    let feedback = element.nextElementSibling;
    if (!feedback || !feedback.classList.contains("invalid-alert-feedback")) {
      feedback = document.createElement("div");
      feedback.className = "invalid-alert-feedback";
      element.parentNode.insertBefore(feedback, element.nextSibling);
    }
    feedback.textContent = message;
    if (options.showAlert !== false) {
      this.error(message, { ...options, duration: options.duration || 3000 });
    }
    const clearError = () => {
      element.classList.remove("is-invalid");
      if (feedback) feedback.textContent = "";
      element.removeEventListener("input", clearError);
      element.removeEventListener("change", clearError);
    };
    element.addEventListener("input", clearError);
    element.addEventListener("change", clearError);
    return false;
  }

  validateForm(form, rules) {
    let isValid = true;
    for (const field in rules) {
      const element = form.elements[field];
      if (!element) continue;
      const rule = rules[field];
      const value = element.value.trim();
      if (rule.required && value === "") {
        this.validateField(element, rule.message || "Este campo é obrigatório");
        isValid = false; continue;
      }
      if (rule.minLength && value.length < rule.minLength) {
        this.validateField(element, rule.message || `Este campo deve ter pelo menos ${rule.minLength} caracteres`);
        isValid = false; continue;
      }
      if (rule.maxLength && value.length > rule.maxLength) {
        this.validateField(element, rule.message || `Este campo deve ter no máximo ${rule.maxLength} caracteres`);
        isValid = false; continue;
      }
      if (rule.pattern && !new RegExp(rule.pattern).test(value)) {
        this.validateField(element, rule.message || "Este campo não está no formato correto");
        isValid = false; continue;
      }
      if (rule.validate && typeof rule.validate === "function") {
        const result = rule.validate(value, form);
        if (result !== true) {
          this.validateField(element, result || rule.message || "Este campo é inválido");
          isValid = false; continue;
        }
      }
    }
    return isValid;
  }
}

const instance = new EasyAlert();
window.easyAlert = instance;
export default EasyAlert;
