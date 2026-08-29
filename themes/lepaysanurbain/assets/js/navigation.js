/**
 * Check if the space available is too tight for the desktop menu and switch it
 * to the compact (mobile) mode
 *
 * WordPress Core still owns the overlay, submenu controls, focus handling,
 * and Escape. This file only switches to the compact presentation when a
 * top-level menu item overlaps the centred logo.
 */

// this undefined at top-level in modules
if (this !== undefined) {
  throw new Error(
    "Must be run as an ESM module (see wp_enqueue_script_module())",
  );
}
// parent <nav>
const navigationSelector =
  ".lpu-header nav.wp-block-navigation.lpu-header__navigation";
// <ul> containining the menu items
const listSelector =
  ".wp-block-navigation__responsive-container-content > .wp-block-navigation__container";

function getList(navigation) {
  return navigation.querySelector(listSelector);
}

function getLogoItem(list) {
  return Array.from(list.children).find(function (item) {
    return item.querySelector(".lpu-header__logo");
  });
}

function rectanglesOverlap(first, second) {
  // overlap if touch both horizontally and vertically
  return (
    first.left < second.right && // first not fully after right
    first.right > second.left && // first not fully before right
    first.top < second.bottom && // first not fully under right
    first.bottom > second.top // first not fully above right
  );
}

function isLogoCollidingWithMainMenuItems(list) {
  const listRect = list.getBoundingClientRect();
  const logoItem = getLogoItem(list);

  /* Core hides the closed responsive container below its own breakpoint. */
  if (!listRect.width || !logoItem) {
    return Boolean(!listRect.width);
  }

  const logo = logoItem.querySelector(".lpu-header__logo");
  const logoRect = logo.getBoundingClientRect();

  return Array.from(list.children).some(function (item) {
    return (
      item !== logoItem &&
      rectanglesOverlap(logoRect, item.getBoundingClientRect())
    );
  });
}

/**
 * The menu that opens in mobile mode when pressing the hamburger menu icon.
 * Not to be confused with the mega-menu.
 */
function ismobileOverlayOpen(list) {
  const container = list.closest(".wp-block-navigation__responsive-container");

  return Boolean(container && container.classList.contains("is-menu-open"));
}

function updateNavigation(navigation) {
  const list = getList(navigation);

  if (!list || !navigation.clientWidth || ismobileOverlayOpen(list)) {
    return;
  }
  /*
   * Temporarily restore the  desktop layout (if we where currently
   * in compact mode). This is done to check for collision when the menu is
   * in desktop mode instead of the compact mode.
   */
  navigation.classList.remove("lpu-navigation--compact");

  const enableCompact = isLogoCollidingWithMainMenuItems(list);
  navigation.classList.toggle("lpu-navigation--compact", enableCompact);
}

function scheduleUpdate(navigation) {
  // debouncing
  if (navigation.lpuLayoutFrame) {
    window.cancelAnimationFrame(navigation.lpuLayoutFrame);
  }

  navigation.lpuLayoutFrame = window.requestAnimationFrame(function () {
    updateNavigation(navigation);
    navigation.lpuLayoutFrame = null;
  });
}

function observeNavigation(navigation) {
  // won't trigger if the viewport width changes but the navigation doesn't
  // happens when navigation has enough space to fit completely
  // nicely avoids needless calls.
  const resizeObserver = new ResizeObserver(function () {
    scheduleUpdate(navigation);
  });
  const list = getList(navigation);

  resizeObserver.observe(navigation);

  if (list) {
    // navigation menu content changes
    new MutationObserver(function () {
      scheduleUpdate(navigation);
    }).observe(list, {
      childList: true,
      characterData: true,
      subtree: true,
    });

    // listens to the mobile menu being opened or closed
    // the wordpress code sets the classes has-modal-open and is-menu-open
    // when the mobile menu is opened (after clicking on the hamburger
    // icon)
    // probably not needed but better safe than sorry
    const responsiveContainer = list.closest(
      ".wp-block-navigation__responsive-container",
    );
    if (responsiveContainer) {
      new MutationObserver(function () {
        scheduleUpdate(navigation);
      }).observe(responsiveContainer, {
        attributes: true,
        attributeFilter: ["class"],
      });
    }
  }

  // probably not needed (covered by the resize observer) but better safe
  // than sorry
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(function () {
      scheduleUpdate(navigation);
    });
  }

  scheduleUpdate(navigation);
}

function init() {
  document.querySelectorAll(navigationSelector).forEach(observeNavigation);
}

if ("ResizeObserver" in window && "MutationObserver" in window) {
  if ("loading" === document.readyState) {
    document.addEventListener("DOMContentLoaded", init, { once: true });
  } else {
    init();
  }
}
