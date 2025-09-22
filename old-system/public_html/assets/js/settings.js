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
         });
      });
   };

   initializeTabs();
});
