document.addEventListener("DOMContentLoaded", () => {
    const toggle = document.querySelector(".nav-toggle");
    const links = document.querySelector(".nav-links");
    if (!toggle || !links) return;

    toggle.addEventListener("click", () => {
        const abierto = links.classList.toggle("abierto");
        toggle.setAttribute("aria-expanded", abierto ? "true" : "false");
    });
});
