// ------------------------------------------------------------------
// Données du menu — remplacez ce tableau pour tester différents cas
// (2 items, 5 items, 10 items, etc.)
// ------------------------------------------------------------------
const MENU_ITEMS = [
  { label: "Qui sommes-nous", href: "#" },
  { label: "Nos activités", href: "#" },
  { label: "Nos cultures", href: "#" },
  { label: "Nos projets et initiatives", href: "#" },
  { label: "Infos pratiques", href: "#" },
];

const navEl = document.querySelector(".nav");
const leftListEl = document.getElementById("navLeft");
const rightListEl = document.getElementById("navRight");
const measureEl = document.getElementById("navMeasure");
const measureLeftEl = document.getElementById("navMeasureLeft");
const measureRightEl = document.getElementById("navMeasureRight");
const mobileListEl = document.getElementById("navMobileList");
const hamburgerEl = document.getElementById("navHamburger");
const mobilePanelEl = document.getElementById("navMobilePanel");

/**
 * Coupe le tableau d'items en deux : gauche / droite.
 * Le côté gauche reçoit l'item supplémentaire en cas de nombre impair.
 */
function splitItems(items) {
  const half = Math.ceil(items.length / 2);
  return {
    left: items.slice(0, half),
    right: items.slice(half),
  };
}

function makeLink(item) {
  const li = document.createElement("li");
  const a = document.createElement("a");
  a.href = item.href;
  a.textContent = item.label;
  li.appendChild(a);
  return li;
}

function renderList(el, items) {
  el.innerHTML = "";
  items.forEach((item) => el.appendChild(makeLink(item)));
}

function renderMenu() {
  const { left, right } = splitItems(MENU_ITEMS);

  // Rangée visible
  renderList(leftListEl, left);
  renderList(rightListEl, right);

  // Clone identique dans le mesureur invisible (même items, même ordre)
  renderList(measureLeftEl, left);
  renderList(measureRightEl, right);

  // Panneau mobile : tous les items à plat, dans l'ordre d'origine
  mobileListEl.innerHTML = "";
  MENU_ITEMS.forEach((item) => mobileListEl.appendChild(makeLink(item)));
}

/**
 * Compare la largeur "naturelle" du menu complet (mesurée hors écran,
 * sans wrap) à la largeur réellement disponible dans la barre de nav.
 * Si ça ne rentre pas -> mode hamburger, que ce soit à cause d'un
 * écran étroit (mobile) ou d'un simple excès d'items (desktop).
 */
function checkOverflow() {
  const available = navEl.clientWidth;
  const needed = measureEl.scrollWidth;

  // petite marge de sécurité pour éviter un flicker au pixel près
  const collapsed = needed > available - 4;

  navEl.classList.toggle("nav--collapsed", collapsed);
  hamburgerEl.setAttribute("aria-expanded", "false");
  if (!collapsed) {
    mobilePanelEl.hidden = true;
  }
}

hamburgerEl.addEventListener("click", () => {
  const isOpen = !mobilePanelEl.hidden;
  mobilePanelEl.hidden = isOpen;
  hamburgerEl.setAttribute("aria-expanded", String(!isOpen));
});

renderMenu();
checkOverflow();

// Recalcule à chaque redimensionnement de la barre de nav elle-même
// (couvre resize fenêtre, rotation mobile, changement de zoom, etc.)
new ResizeObserver(checkOverflow).observe(navEl);
