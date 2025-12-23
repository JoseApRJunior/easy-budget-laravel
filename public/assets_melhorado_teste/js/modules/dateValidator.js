export class DateValidator {
   constructor(startDateInput, endDateInput) {
      this.startDate = startDateInput;
      this.endDate = endDateInput;
      this.timeoutId = null;
      this.setupListeners();
   }

   validate() {
      const startFilled = this.startDate.value !== "";
      const endFilled = this.endDate.value !== "";

      this.clearValidation();

      if (startFilled !== endFilled) {
         if (startFilled) {
            this.showError(this.endDate, "* Data final obrigatória");
         } else {
            this.showError(this.startDate, "* Data inicial obrigatória");
         }
         return false;
      }

      if (
         startFilled &&
         endFilled &&
         this.startDate.value > this.endDate.value
      ) {
         this.showError(
            this.startDate,
            "* Data inicial deve ser menor que a final"
         );
         return false;
      }

      if (startFilled && endFilled) {
         this.showSuccess(this.startDate);
         this.showSuccess(this.endDate);
      }

      return true;
   }

   clearValidation() {
      [this.startDate, this.endDate].forEach((input) => {
         input.setCustomValidity("");
         input.classList.remove("is-invalid", "is-valid");
         const errorSpan = input.parentNode.querySelector(".required-asterisk");
         if (errorSpan) errorSpan.remove();
      });
   }

   showError(input, message) {
      input.setCustomValidity(message);
      input.classList.add("is-invalid");

      const asterisk = document.createElement("span");
      asterisk.className = "required-asterisk";
      asterisk.textContent = message;
      input.parentNode.appendChild(asterisk);
   }

   showSuccess(input) {
      input.classList.add("is-valid");
   }

   setupListeners() {
      const debouncedValidate = () => {
         clearTimeout(this.timeoutId);
         this.timeoutId = setTimeout(() => this.validate(), 100);
      };

      ["change", "input"].forEach((eventType) => {
         this.startDate.addEventListener(eventType, debouncedValidate);
         this.endDate.addEventListener(eventType, debouncedValidate);
      });
   }
}
