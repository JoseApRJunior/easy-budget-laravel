/**
 * Módulo para gerenciar a visualização prévia de imagens
 */

/**
 * Inicializa a funcionalidade de preview de imagem para um input específico
 * @param {Object} options - Opções de configuração
 * @param {string} options.inputId - ID do elemento input de arquivo
 * @param {string} options.previewId - ID do elemento img para preview
 * @param {string} options.buttonId - ID do botão de upload (opcional)
 * @param {number} options.maxSize - Tamanho máximo em bytes (padrão: 2MB)
 * @param {Array} options.allowedTypes - Tipos MIME permitidos (padrão: ['image/jpeg', 'image/png'])
 * @param {Function} options.onSuccess - Callback quando a imagem é carregada com sucesso
 * @param {Function} options.onError - Callback quando ocorre um erro
 */
export const setupImagePreview = (options) => {
   const {
      inputId,
      previewId,
      buttonId = null,
      maxSize = 2 * 1024 * 1024, // 2MB padrão
      allowedTypes = ['image/jpeg', 'image/png'],
      onSuccess = null,
      onError = null
   } = options;

   const imageInput = document.getElementById(inputId);
   const imagePreview = document.getElementById(previewId);
   const uploadButton = buttonId ? document.getElementById(buttonId) : null;

   if (!imageInput || !imagePreview) return;

   // Se houver um botão de upload, configura o evento de clique
   if (uploadButton) {
      uploadButton.addEventListener('click', () => {
         imageInput.click();
      });
   }

   // Configura o evento de mudança do input de arquivo
   imageInput.addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
         const file = e.target.files[0];

         // Verifica o tamanho do arquivo
         if (file.size > maxSize) {
            const errorMsg = `O arquivo é muito grande. O tamanho máximo permitido é ${Math.round(maxSize / (1024 * 1024))}MB.`;
            if (onError) {
               onError(errorMsg);
            } else {
               alert(errorMsg);
            }
            imageInput.value = '';
            return;
         }

         // Verifica o tipo do arquivo
         if (!allowedTypes.includes(file.type)) {
            const errorMsg = `Tipo de arquivo não permitido. Formatos aceitos: ${allowedTypes.map(type => type.split('/')[1].toUpperCase()).join(', ')}`;
            if (onError) {
               onError(errorMsg);
            } else {
               alert(errorMsg);
            }
            imageInput.value = '';
            return;
         }

         // Exibe a preview da imagem
         const reader = new FileReader();
         reader.onload = function(e) {
            imagePreview.src = e.target.result;
            
            // Atualiza o botão de upload se existir
            if (uploadButton) {
               uploadButton.innerHTML = '<i class="bi bi-check me-1"></i>Selecionado';
               uploadButton.classList.remove('btn-primary');
               uploadButton.classList.add('btn-success');
            }
            
            // Executa o callback de sucesso se fornecido
            if (onSuccess) {
               onSuccess(file, e.target.result);
            }
         };
         reader.readAsDataURL(file);
      }
   });
};

/**
 * Função legada para compatibilidade com código existente
 */
export const initializeImagePreview = () => {
   const previewImage = (input) => {
      if (input.files && input.files[0]) {
         const reader = new FileReader();

         reader.onload = (e) => {
            const preview = document.getElementById("preview");
            if (preview) {
               preview.src = e.target.result;
            }
         };

         reader.readAsDataURL(input.files[0]);
      }
   };

   // Adiciona o listener ao input de arquivo
   const logoInput = document.getElementById("logo");
   if (logoInput) {
      logoInput.addEventListener("change", function () {
         previewImage(this);
      });
   }

   // Adiciona o listener ao input de arquivo
   const imageInput = document.getElementById("image");
   if (imageInput) {
      imageInput.addEventListener("change", function () {
         previewImage(this);
      });
   }
};