document.documentElement.classList.add("js");

const toggle = document.querySelector(".nav-toggle");
const nav = document.querySelector("#menu");
if (toggle && nav) {
  toggle.addEventListener("click", () => {
    const open = nav.classList.toggle("is-open");
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
  });
}

document.querySelectorAll(".nav-item.has-submenu").forEach((item) => {
  const parent = item.querySelector(".nav-parent");
  if (!parent) return;
  const syncExpanded = () => {
    const open = item.matches(":hover, :focus-within, .is-open");
    parent.setAttribute("aria-expanded", open ? "true" : "false");
  };
  item.addEventListener("mouseenter", syncExpanded);
  item.addEventListener("mouseleave", syncExpanded);
  item.addEventListener("focusin", syncExpanded);
  item.addEventListener("focusout", () => {
    requestAnimationFrame(syncExpanded);
  });
});

const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
const revealItems = document.querySelectorAll(".offer-card, .highlights .highlight");
if (!revealItems.length || reduceMotion || !("IntersectionObserver" in window)) {
  revealItems.forEach((item) => item.classList.add("is-in"));
} else {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add("is-in");
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.15, rootMargin: "0px 0px 20% 0px" });
  revealItems.forEach((item, itemIndex) => {
    item.style.setProperty("--delay", `${(itemIndex % 4) * 70}ms`);
    observer.observe(item);
  });
  // Ensure below-the-fold items become visible even without scroll (print, full captures).
  requestAnimationFrame(() => {
    revealItems.forEach((item) => {
      const rect = item.getBoundingClientRect();
      if (rect.top < window.innerHeight * 1.15) item.classList.add("is-in");
    });
  });
}

document.querySelectorAll("form[data-confirm]").forEach((form) => {
  form.addEventListener("submit", (event) => {
    const message = form.getAttribute("data-confirm");
    if (message && !window.confirm(message)) event.preventDefault();
  });
});
