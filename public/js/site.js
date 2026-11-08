document.documentElement.classList.add("js");

const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
const finePointer = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
const isMobile = () => window.matchMedia("(max-width: 900px)").matches;

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

/* Smart header */
const header = document.querySelector(".site-header");
if (header) {
  const syncHeader = () => {
    header.classList.toggle("is-scrolled", window.scrollY > 70);
  };
  syncHeader();
  window.addEventListener("scroll", syncHeader, { passive: true });
}

/* Auto-mark reveal targets (motion only — no content changes) */
const markReveal = (selector, className) => {
  document.querySelectorAll(selector).forEach((el) => {
    if (!el.classList.contains(className)) el.classList.add(className);
  });
};

markReveal(".offer-intro h2", "reveal-title");
markReveal(".offer-intro > p:not(.eyebrow)", "reveal-copy");
markReveal(".benefits-head h2", "reveal-title");
markReveal(".benefits-media", "reveal-media");
markReveal(".benefit-card", "reveal-card");
markReveal(".footer-brand, .footer-contact, .footer-edit", "reveal-card");
markReveal(".page-hero h1", "reveal-title");
markReveal(".page-side-media, .page-banner, .offer-block-media", "reveal-media");
markReveal(".contact-grid .page-copy", "reveal-copy");
markReveal(".contact-grid iframe", "reveal-media");
markReveal(".subpage-panel > h2", "reveal-title");
markReveal(".subpage-panel > .lead", "reveal-copy");

const revealSelector = [
  ".reveal-title",
  ".reveal-copy",
  ".reveal-media",
  ".reveal-card",
  ".offer-card",
  ".highlights .highlight",
].join(", ");

const revealItems = [...document.querySelectorAll(revealSelector)];

const setCascadeDelay = (items, stepMs) => {
  items.forEach((item, index) => {
    item.style.setProperty("--delay", `${index * stepMs}ms`);
  });
};

setCascadeDelay([...document.querySelectorAll(".offer-cards .offer-card")], 80);
setCascadeDelay([...document.querySelectorAll(".highlights .highlight")], 80);
setCascadeDelay([...document.querySelectorAll(".benefit-grid .benefit-card")], 70);
setCascadeDelay(
  [...document.querySelectorAll(".footer-inner > .footer-brand, .footer-inner > .footer-contact, .footer-inner > .footer-edit")],
  80,
);

if (!revealItems.length || reduceMotion || !("IntersectionObserver" in window)) {
  revealItems.forEach((item) => item.classList.add("is-in"));
} else {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-in");
        observer.unobserve(entry.target);
      });
    },
    { threshold: 0.12, rootMargin: "0px 0px -12% 0px" },
  );

  revealItems.forEach((item) => observer.observe(item));

  requestAnimationFrame(() => {
    revealItems.forEach((item) => {
      const rect = item.getBoundingClientRect();
      if (rect.top < window.innerHeight * 0.92) item.classList.add("is-in");
    });
  });
}

/* Benefits photo parallax — desktop only, ≤20px */
const parallaxImg = document.querySelector(".benefits-media img");
if (parallaxImg && !reduceMotion) {
  let ticking = false;
  const updateParallax = () => {
    ticking = false;
    if (isMobile()) {
      parallaxImg.style.transform = "";
      return;
    }
    const media = parallaxImg.closest(".benefits-media");
    if (!media) return;
    const rect = media.getBoundingClientRect();
    const viewH = window.innerHeight || 1;
    if (rect.bottom < 0 || rect.top > viewH) return;
    const progress = (viewH / 2 - (rect.top + rect.height / 2)) / viewH;
    const offset = Math.max(-20, Math.min(20, progress * 28));
    parallaxImg.style.transform = `scale(1.08) translate3d(0, ${offset.toFixed(2)}px, 0)`;
  };
  const onScroll = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(updateParallax);
  };
  window.addEventListener("scroll", onScroll, { passive: true });
  window.addEventListener("resize", onScroll, { passive: true });
  onScroll();
}

/* Magnetic CTA — desktop fine pointer only */
if (!reduceMotion && finePointer && !isMobile()) {
  const magneticButtons = document.querySelectorAll(".button-hero, .hero-actions .button");
  magneticButtons.forEach((btn) => {
    let raf = 0;
    const reset = () => {
      btn.style.transform = "";
    };
    btn.addEventListener("pointerleave", reset);
    btn.addEventListener("blur", reset);
    btn.addEventListener("pointermove", (event) => {
      if (isMobile()) {
        reset();
        return;
      }
      if (raf) cancelAnimationFrame(raf);
      raf = requestAnimationFrame(() => {
        const rect = btn.getBoundingClientRect();
        const x = event.clientX - (rect.left + rect.width / 2);
        const y = event.clientY - (rect.top + rect.height / 2);
        const max = 3;
        const tx = Math.max(-max, Math.min(max, x * 0.12));
        const ty = Math.max(-max, Math.min(max, y * 0.12 - 2));
        btn.style.transform = `translate3d(${tx.toFixed(2)}px, ${ty.toFixed(2)}px, 0)`;
      });
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
