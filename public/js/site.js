document.documentElement.classList.add("js");

const toggle = document.querySelector(".nav-toggle");
const nav = document.querySelector("#menu");
if (toggle && nav) {
  toggle.addEventListener("click", () => {
    const open = nav.classList.toggle("is-open");
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
  });
}

const slider = document.querySelector("[data-slider]");
if (slider) {
  const slides = [...slider.querySelectorAll(".slide")];
  const tabs = [...slider.querySelectorAll(".slider-tab")];
  let index = 0;
  let timer = 0;
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function show(next) {
    index = (next + slides.length) % slides.length;
    slides.forEach((slide, item) => {
      const active = item === index;
      slide.classList.toggle("is-active", active);
    });
    tabs.forEach((tab, item) => {
      tab.setAttribute("aria-selected", item === index ? "true" : "false");
    });
  }

  function stop() {
    window.clearInterval(timer);
  }

  function start() {
    if (reduce || slides.length < 2) return;
    stop();
    timer = window.setInterval(() => show(index + 1), 5500);
  }

  let paused = false;

  tabs.forEach((tab, item) => {
    tab.addEventListener("click", () => {
      paused = true;
      stop();
      show(item);
    });
  });
  slider.addEventListener("mouseenter", stop);
  slider.addEventListener("mouseleave", () => {
    if (!paused) start();
  });
  slider.addEventListener("focusin", stop);
  slider.addEventListener("focusout", (event) => {
    if (!paused && !slider.contains(event.relatedTarget)) start();
  });
  show(0);
  start();
}

const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
const benefitItems = document.querySelectorAll(".benefits li");
if (!benefitItems.length || reduceMotion || !("IntersectionObserver" in window)) {
  benefitItems.forEach((item) => item.classList.add("is-in"));
} else {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add("is-in");
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.35 });
  benefitItems.forEach((item, itemIndex) => {
    item.style.setProperty("--delay", `${itemIndex * 60}ms`);
    observer.observe(item);
  });
}

document.querySelectorAll("form[data-confirm]").forEach((form) => {
  form.addEventListener("submit", (event) => {
    const message = form.getAttribute("data-confirm");
    if (message && !window.confirm(message)) event.preventDefault();
  });
});
