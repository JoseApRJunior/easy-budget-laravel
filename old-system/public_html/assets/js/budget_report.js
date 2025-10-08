document
   .getElementById("export-pdf")
   .addEventListener("click", async function () {
      try {
         // Pegar os filtros atuais
         const formData = new FormData(document.getElementById("filter-form"));
         const params = new URLSearchParams(formData);

         // Criar URL com os parâmetros
         const url = `/provider/reports/budgets/pdf?${params.toString()}`;

         // Abrir em nova aba
         window.open(url, "_blank");
      } catch (error) {
         console.error("Erro ao gerar PDF:", error);
         alert("Erro ao gerar o PDF. Por favor, tente novamente.");
      }
   });

// Adicionar HTML do modal
const modalHtml = `
<div class="modal fade" id="excelPreviewModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Preview do Excel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="excel-preview" style="max-height: 70vh; overflow: auto;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-primary" id="downloadExcel">Download</button>
      </div>
    </div>
  </div>
</div>`;

// Adicionar modal ao body
document.body.insertAdjacentHTML("beforeend", modalHtml);

let excelBlob = null;
let fileName = "";

$("#export-excel").on("click", function () {
   // Pegar os filtros atuais
   const formData = $("#filter-form").serialize();

   // Mostrar loading
   const loadingToast = showToast("Gerando preview...", "info");

   $.ajax({
      url: `/provider/reports/budgets/excel?${formData}`,
      method: "GET",
      xhrFields: {
         responseType: "blob",
      },
      headers: {
         Accept:
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      },
      success: function (blob) {
         excelBlob = blob;

         // Converter para preview
         const reader = new FileReader();

         reader.onload = function (e) {
            try {
               const data = new Uint8Array(e.target.result);

               const workbook = XLSX.read(data, { type: "array" });

               const firstSheet = workbook.Sheets[workbook.SheetNames[0]];

               // Formatar valores antes de converter para HTML
               for (let cell in firstSheet) {
                  if (cell[0] === "!") continue; // Pular células especiais

                  const cellRef = XLSX.utils.decode_cell(cell);
                  const cellValue = firstSheet[cell].v;

                  // Formatar valores monetários (coluna F - índice 5)
                  if (
                     cellRef.c === 5 &&
                     cellRef.r > 0 &&
                     typeof cellValue === "number"
                  ) {
                     firstSheet[cell].z = "#,##0.00";
                     firstSheet[cell].w = new Intl.NumberFormat("pt-BR", {
                        style: "currency",
                        currency: "BRL",
                     }).format(cellValue);
                  }

                  // Formatar datas (colunas C e D - índices 2 e 3)
                  if ((cellRef.c === 2 || cellRef.c === 3) && cellRef.r > 0) {
                     if (cellValue && !isNaN(new Date(cellValue))) {
                        const date = new Date(cellValue);
                        firstSheet[cell].z = "dd/mm/yyyy";
                        firstSheet[cell].w = date.toLocaleDateString("pt-BR");
                     }
                  }

                  if (cellRef.c === 0) {
                     fileName = cellValue;
                  }
               }

               const html = XLSX.utils.sheet_to_html(firstSheet, {
                  editable: false,
                  table_class: "table table-striped table-bordered table-hover",
               });

               $("#excel-preview").html(html);

               // Adicionar estilos específicos para a tabela
               if (!$("#excel-preview-styles").length) {
                  $("head").append(`
              <style id="excel-preview-styles">
                #excel-preview table {
                  width: 100%;
                  margin-bottom: 1rem;
                  border-collapse: collapse;
                }
                #excel-preview th {
                  background-color: #f1f1f1;
                  font-weight: bold;
                  text-align: center;
                }
                #excel-preview td, #excel-preview th {
                  padding: 0.75rem;
                  vertical-align: middle;
                  border: 1px solid #dee2e6;
                }
                #excel-preview tr:nth-child(even) {
                  background-color:
                                    text-align: right;
                                }
                            </style>
                        `);
               }

               // Esconder loading
               loadingToast.hide();

               // Abrir modal
               $("#excelPreviewModal").modal("show");
            } catch (error) {
               console.error("Erro ao processar preview:", error);
               showToast(
                  "Erro ao gerar preview. Tentando download direto...",
                  "warning"
               );
               downloadExcel();
            }
         };

         reader.onerror = function () {
            console.error("Erro ao ler arquivo:", reader.error);
            showToast(
               "Erro ao ler arquivo. Tentando download direto...",
               "warning"
            );
            downloadExcel();
         };

         reader.readAsArrayBuffer(blob);
      },
      error: function (xhr, status, error) {
         console.error("Erro ao gerar a planilha Excel:", error);
         showToast(
            "Erro ao gerar a planilha Excel. Por favor, tente novamente.",
            "error"
         );
         loadingToast.hide();
      },
   });
});

// Função para download
function downloadExcel() {
   if (excelBlob) {
      const date = new Date().toISOString().split("T")[0];
      const time = new Date()
         .toISOString()
         .split("T")[1]
         .split(".")[0]
         .replace(/:/g, "-");
      const count = ""; // Aqui você pode definir o número de registros, se necessário

      const url = window.URL.createObjectURL(excelBlob);
      const a = $("<a>", {
         href: url,
         download: fileName,
      }).appendTo("body");

      a[0].click();
      a.remove();
      window.URL.revokeObjectURL(url);
   }
}

// Listener para o botão de download
$("#downloadExcel").on("click", downloadExcel);

// Função auxiliar para mostrar toasts
function showToast(message, type = "success") {
   const $toast = $(`
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);

   const $container = $(".toast-container");
   if (!$container.length) {
      $("body").append(
         '<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>'
      );
   }

   $(".toast-container").append($toast);

   const toast = new bootstrap.Toast($toast[0]);
   toast.show();

   return toast;
}
