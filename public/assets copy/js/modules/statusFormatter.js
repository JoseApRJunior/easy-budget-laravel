export const DateFormatter = {
   format(dateString, locale = "pt-BR") {
      if (!dateString) return "";
      return new Date(dateString).toLocaleDateString(locale);
   },

   parse(dateString) {
      return new Date(dateString);
   },
};
