document.addEventListener("DOMContentLoaded", function () {
   const initializeTabs = () => {
      const triggerTabList = [].slice.call(
         document.querySelectorAll(".list-group-item")
      );
      triggerTabList.forEach(function (triggerEl) {
         const tabTrigger = new bootstrap.Tab(triggerEl);

      triggerEl.addEventListener("click", function (event) {
         event.preventDefault();
         tabTrigger.show();
         // Persistir aba ativa na URL via hash
         const tabId = triggerEl.getAttribute('href').substring(1);
         const url = new URL(window.location);
         url.hash = `#${tabId}`;
         // Remover query 'tab' antiga para evitar conflito
         url.searchParams.delete('tab');
         window.history.replaceState({}, '', url);
      });
      });
   };

   // Função para alternar visibilidade da senha (olho)
   const initializePasswordToggle = () => {
      const toggleButtons = document.querySelectorAll('.password-toggle');
      
      if (toggleButtons.length === 0) {
         return;
      }
      
      toggleButtons.forEach(button => {
         button.addEventListener('click', function() {
            const inputId = this.getAttribute('data-input');
            const input = document.getElementById(inputId);
            const eyeIcon = this.querySelector('i');
            
            if (!input || !eyeIcon) {
               return;
            }
            
            if (input.type === 'password') {
               input.type = 'text';
               eyeIcon.classList.remove('bi-eye');
               eyeIcon.classList.add('bi-eye-slash');
            } else {
               input.type = 'password';
               eyeIcon.classList.remove('bi-eye-slash');
               eyeIcon.classList.add('bi-eye');
            }
         });
      });
   };

   // Ativar aba baseada na URL
   const activateTabFromUrl = () => {
      // Priorizar hash (#integracao), depois query ?tab=integracao
      let tabId = window.location.hash ? window.location.hash.substring(1) : '';
      if (!tabId) {
         const urlParams = new URLSearchParams(window.location.search);
         tabId = urlParams.get('tab') || '';
      }
      if (tabId) {
         // Limpar ativações padrão
         document.querySelectorAll('.list-group-item').forEach(el => el.classList.remove('active'));
         document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('show', 'active'));
         const tabElement = document.querySelector(`[href="#${tabId}"]`);
         if (tabElement) {
            const tabTrigger = new bootstrap.Tab(tabElement);
            tabTrigger.show();
         }
      }
   };

   // Manipular envio de formulários com feedback visual
   const initializeFormHandlers = () => {
      const forms = document.querySelectorAll('form[action*="settings"]');
      
      forms.forEach(form => {
         form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
               submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Salvando...';
               submitBtn.disabled = true;
            }
         });
      });
   };

   // Inicializar tooltips e popovers
   const initializeTooltips = () => {
      const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.map(function (tooltipTriggerEl) {
         return new bootstrap.Tooltip(tooltipTriggerEl);
      });
   };

   // Validar senhas em tempo real com força e confirmação
   const initializePasswordValidation = () => {
      const newPassword = document.getElementById('new_password');
      const confirmPassword = document.getElementById('new_password_confirmation');
      const passwordRulesContainer = document.querySelector('.password-rules');
      const matchFeedback = document.querySelector('.password-match-feedback');
      const mismatchFeedback = document.querySelector('.password-mismatch-feedback');
      
      if (!newPassword || !confirmPassword || !passwordRulesContainer) {
         return;
      }
      
      const passwordRules = [
         { regex: /.{6,}/, text: "Pelo menos 6 caracteres", element: document.querySelector('[data-rule="length"]') },
         { regex: /[a-z]/, text: "Letras minúsculas (a-z)", element: document.querySelector('[data-rule="lowercase"]') },
         { regex: /[A-Z]/, text: "Letras maiúsculas (A-Z)", element: document.querySelector('[data-rule="uppercase"]') },
         { regex: /[0-9]/, text: "Números (0-9)", element: document.querySelector('[data-rule="numbers"]') },
         { regex: /[@#$!%*?&]/, text: "Caracteres especiais (@#$!%*?&)", element: document.querySelector('[data-rule="special"]') },
      ];
      
      if (newPassword) {
         newPassword.addEventListener('input', function() {
            if (this.value.length > 0) {
               passwordRulesContainer.style.display = 'block';
               
               let allRulesValid = true;
               passwordRules.forEach(rule => {
                  if (rule.regex.test(this.value)) {
                     rule.element.querySelector('i').classList.remove('bi-circle', 'text-secondary');
                     rule.element.querySelector('i').classList.add('bi-check-circle', 'text-success');
                  } else {
                     rule.element.querySelector('i').classList.remove('bi-check-circle', 'text-success');
                     rule.element.querySelector('i').classList.add('bi-circle', 'text-secondary');
                     allRulesValid = false;
                  }
               });
               
               if (allRulesValid) {
                  this.classList.add('is-valid');
                  this.classList.remove('is-invalid');
               } else {
                  this.classList.add('is-invalid');
                  this.classList.remove('is-valid');
               }
            } else {
               passwordRulesContainer.style.display = 'none';
               this.classList.remove('is-valid', 'is-invalid');
            }
            
            if (confirmPassword.value) {
               validatePasswordConfirmation();
            }
         });
      }
      
      if (confirmPassword) {
         const validatePasswordConfirmation = () => {
            if (confirmPassword.value.length > 0) {
               if (newPassword.value === confirmPassword.value && newPassword.value.length > 0) {
                  confirmPassword.classList.add('is-valid');
                  confirmPassword.classList.remove('is-invalid');
                  if (matchFeedback) matchFeedback.style.display = 'block';
                  if (mismatchFeedback) mismatchFeedback.style.display = 'none';
               } else {
                  confirmPassword.classList.add('is-invalid');
                  confirmPassword.classList.remove('is-valid');
                  if (matchFeedback) matchFeedback.style.display = 'none';
                  if (mismatchFeedback) mismatchFeedback.style.display = 'block';
               }
            } else {
               confirmPassword.classList.remove('is-valid', 'is-invalid');
               if (matchFeedback) matchFeedback.style.display = 'none';
               if (mismatchFeedback) mismatchFeedback.style.display = 'none';
            }
         };
         
         confirmPassword.addEventListener('input', validatePasswordConfirmation);
      }
   };

   const initializeColorPreview = () => {
      const colorInput = document.getElementById('primary_color');
      if (colorInput) {
         colorInput.addEventListener('input', function() {
            document.documentElement.style.setProperty('--bs-primary', this.value);
         });
      }
   };

   // Executar inicializações
   console.log('Inicializando configurações...');
   initializeTabs();
   activateTabFromUrl();
   initializeFormHandlers();
   initializeTooltips();
   initializePasswordValidation();
   initializePasswordToggle();
   initializeColorPreview();
});

// Funções Globais para Configurações
function confirmDisconnectSettings() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Desconectar Mercado Pago?',
            text: "Você precisará refazer a conexão para processar pagamentos automaticamente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--text-error)',
            cancelButtonColor: 'var(--secondary-color)',
            confirmButtonText: 'Sim, desconectar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('form-disconnect-mp-settings');
                if (form) form.submit();
            }
        });
    } else {
        if (confirm('Tem certeza que deseja desconectar esta integração?')) {
            const form = document.getElementById('form-disconnect-mp-settings');
            if (form) form.submit();
        }
    }
}
