// ------------------------------------------------------------------
// Détection de débordement uniquement.
// Les items du menu sont codés en dur dans index.html (rangée visible
// ET panneau mobile) — ce script ne génère plus aucun contenu, il se
// contente de basculer entre l'affichage "menu" et l'affichage
// "hamburger" selon que ça rentre ou non.
// ------------------------------------------------------------------

const navEl = document.getElementById('nav');
const leftListEl = document.getElementById('navLeft');
const rightListEl = document.getElementById('navRight');
const hamburgerEl = document.getElementById('navHamburger');
const mobilePanelEl = document.getElementById('navMobilePanel');

/**
 * Une liste déborde si son contenu naturel (scrollWidth) est plus
 * large que la boîte réellement allouée par la grille (clientWidth).
 * Ça fonctionne même quand la liste est visuellement masquée
 * (visibility: hidden), car elle reste dans le flux et conserve sa
 * taille de boîte réelle — seul son contenu cesse d'être peint.
 */
function isOverflowing(listEl) {
  return listEl.scrollWidth > listEl.clientWidth + 1; // +1px de marge anti-flicker
}

function checkOverflow() {
  // On mesure toujours sur l'état "déployé" : si on est déjà en mode
  // collapsed, la grille garde ses colonnes 1fr (voir CSS), donc la
  // mesure reste valable pour décider si on peut redéployer le menu.
  const collapsed = isOverflowing(leftListEl) || isOverflowing(rightListEl);

  navEl.classList.toggle('nav--collapsed', collapsed);

  if (!collapsed) {
    mobilePanelEl.hidden = true;
    hamburgerEl.setAttribute('aria-expanded', 'false');
  }
}

hamburgerEl.addEventListener('click', () => {
  const isOpen = !mobilePanelEl.hidden;
  mobilePanelEl.hidden = isOpen;
  hamburgerEl.setAttribute('aria-expanded', String(!isOpen));
});

checkOverflow();

// Recalcule à chaque redimensionnement de la barre de nav elle-même
// (fenêtre, rotation mobile, zoom, etc.)
new ResizeObserver(checkOverflow).observe(navEl);
