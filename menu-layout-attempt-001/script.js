const nav = document.querySelector('#siteNav');
const source = document.querySelector('#menuSource');
const leftMenu = document.querySelector('#menuLeft');
const rightMenu = document.querySelector('#menuRight');
const mobileMenuList = document.querySelector('#mobileMenuList');
const toggle = document.querySelector('#menuToggle');
const mobileMenu = document.querySelector('#mobileMenu');

function buildMenu() {
  const items = [...source.children];
  // The extra item goes to the left when the count is odd.
  const leftCount = Math.ceil(items.length / 2);

  leftMenu.replaceChildren(
    ...items.slice(0, leftCount).map((item) => item.cloneNode(true)),
  );
  rightMenu.replaceChildren(
    ...items.slice(leftCount).map((item) => item.cloneNode(true)),
  );
  mobileMenuList.replaceChildren(...items.map((item) => item.cloneNode(true)));
}

function menuOverflows() {
  return [leftMenu, rightMenu].some(
    (menu) => menu.scrollWidth > menu.clientWidth + 1,
  );
}

function updateMenuMode() {
  // Measure the expanded desktop layout. CSS grid keeps the logo centered.
  nav.classList.remove('is-collapsed');
  const collapsed = menuOverflows();
  nav.classList.toggle('is-collapsed', collapsed);

  if (!collapsed) {
    mobileMenu.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Ouvrir le menu');
  }
  nav.classList.add('is-ready');
}

toggle.addEventListener('click', () => {
  const open = mobileMenu.hidden;
  mobileMenu.hidden = !open;
  toggle.setAttribute('aria-expanded', String(open));
  toggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
});

buildMenu();
new ResizeObserver(updateMenuMode).observe(nav);
updateMenuMode();
