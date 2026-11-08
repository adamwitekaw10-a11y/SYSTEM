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

const offerTabsRoot = document.querySelector("[data-offer-tabs]");
if (offerTabsRoot) {
  const tabs = [...offerTabsRoot.querySelectorAll("[data-offer-tab]")];
  const panels = [...offerTabsRoot.querySelectorAll("[data-offer-panel]")];
  const validIds = new Set(tabs.map((tab) => tab.getAttribute("data-offer-tab")));

  const activateOfferTab = (id, { updateHash = true } = {}) => {
    if (!validIds.has(id)) id = "ksiegowosc";
    tabs.forEach((tab) => {
      const selected = tab.getAttribute("data-offer-tab") === id;
      tab.classList.toggle("is-active", selected);
      tab.setAttribute("aria-selected", selected ? "true" : "false");
      tab.tabIndex = selected ? 0 : -1;
    });
    panels.forEach((panel) => {
      const selected = panel.getAttribute("data-offer-panel") === id;
      panel.classList.toggle("is-active", selected);
      panel.hidden = !selected;
    });
    if (updateHash) {
      const next = `#${id}`;
      if (location.hash !== next) history.replaceState(null, "", next);
    }
  };

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      activateOfferTab(tab.getAttribute("data-offer-tab"));
    });
    tab.addEventListener("keydown", (event) => {
      const index = tabs.indexOf(tab);
      let nextIndex = -1;
      if (event.key === "ArrowRight" || event.key === "ArrowDown") nextIndex = (index + 1) % tabs.length;
      if (event.key === "ArrowLeft" || event.key === "ArrowUp") nextIndex = (index - 1 + tabs.length) % tabs.length;
      if (event.key === "Home") nextIndex = 0;
      if (event.key === "End") nextIndex = tabs.length - 1;
      if (nextIndex < 0) return;
      event.preventDefault();
      tabs[nextIndex].focus();
      activateOfferTab(tabs[nextIndex].getAttribute("data-offer-tab"));
    });
  });

  const fromHash = () => {
    const id = decodeURIComponent(location.hash.replace(/^#/, ""));
    activateOfferTab(validIds.has(id) ? id : "ksiegowosc", { updateHash: false });
  };
  fromHash();
  window.addEventListener("hashchange", fromHash);
}
