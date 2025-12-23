export default function () {
  const el = document.querySelector(".js-home-init");
  if (el) el.textContent = "home ready";
  console.info("Página 'home' carregada via Vite");
}
