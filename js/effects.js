/* =================================================================
   VST — VISUAL EFFECTS PACK (companion to css/effects.css)
   - Hero: mouse spotlight + floating glass "languages" visual
   - Cards: 3D tilt with light glare on hover (mouse devices only)
   Safe to remove: the site works the same without this file.
   ================================================================= */
(function () {
  "use strict";

  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const finePointer = window.matchMedia("(hover: hover) and (pointer: fine)").matches;

  /* ---------------- Hero visual (globe + language chips) ---------------- */
  function initHeroVisual() {
    const inner = document.querySelector(".hero .hero-inner");
    if (!inner || inner.querySelector(".fx-hero-visual")) return;
    const visual = document.createElement("div");
    visual.className = "fx-hero-visual";
    visual.setAttribute("aria-hidden", "true");
    visual.innerHTML =
      '<div class="fx-globe"></div>' +
      '<div class="fx-chip"><b>EN</b>English</div>' +
      '<div class="fx-chip"><b>PT</b>Português</div>' +
      '<div class="fx-chip"><b>ES</b>Español</div>';
    inner.appendChild(visual);
  }

  /* ---------------- Hero mouse spotlight ---------------- */
  function initSpotlight() {
    const hero = document.querySelector(".hero");
    if (!hero || !finePointer || reduceMotion) return;
    let raf = 0;
    hero.addEventListener("mousemove", (e) => {
      if (raf) return;
      raf = requestAnimationFrame(() => {
        const r = hero.getBoundingClientRect();
        hero.style.setProperty("--mx", ((e.clientX - r.left) / r.width) * 100 + "%");
        hero.style.setProperty("--my", ((e.clientY - r.top) / r.height) * 100 + "%");
        raf = 0;
      });
    });
  }

  /* ---------------- 3D tilt + glare on cards ---------------- */
  const TILT_SELECTOR = ".service-card, .destination-card, .team-card, .ceo-card";
  const MAX_TILT = 7; // degrees

  function bindTilt(card) {
    if (card.dataset.fxTilt) return;
    card.dataset.fxTilt = "1";
    card.classList.add("fx-tilt");
    if (!finePointer || reduceMotion) return; // glare/tilt only with a mouse

    card.addEventListener("mousemove", (e) => {
      const r = card.getBoundingClientRect();
      const px = (e.clientX - r.left) / r.width;
      const py = (e.clientY - r.top) / r.height;
      card.classList.add("fx-moving");
      card.style.transform =
        "perspective(900px) rotateX(" + ((0.5 - py) * MAX_TILT).toFixed(2) + "deg) rotateY(" +
        ((px - 0.5) * MAX_TILT).toFixed(2) + "deg) translateY(-6px)";
      card.style.setProperty("--gx", px * 100 + "%");
      card.style.setProperty("--gy", py * 100 + "%");
    });
    card.addEventListener("mouseleave", () => {
      card.classList.remove("fx-moving");
      card.style.transform = "";
    });
  }

  function initTilt() {
    document.querySelectorAll(TILT_SELECTOR).forEach(bindTilt);
    // Team cards are rendered later by script.js (and re-rendered by filters)
    const team = document.getElementById("teamGrid");
    if (team && "MutationObserver" in window) {
      new MutationObserver(() => team.querySelectorAll(".team-card").forEach(bindTilt))
        .observe(team, { childList: true });
    }
  }

  function init() {
    initHeroVisual();
    initSpotlight();
    initTilt();
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();
