document.addEventListener("DOMContentLoaded", () => {
    const toggle = document.querySelector(".nav-toggle");
    const links = document.querySelector(".nav-links");
    if (!toggle || !links) return;

    toggle.addEventListener("click", () => {
        const abierto = links.classList.toggle("abierto");
        toggle.setAttribute("aria-expanded", abierto ? "true" : "false");
    });
});

// Vista previa de imagen al subir foto (input[type=file][data-preview])
document.addEventListener("change", (ev) => {
    const input = ev.target;
    if (!input.matches('input[type="file"][data-preview]')) return;
    const img = document.getElementById(input.dataset.preview);
    if (!img || !input.files || !input.files[0]) return;
    img.src = URL.createObjectURL(input.files[0]);
    img.style.display = "block";
});
