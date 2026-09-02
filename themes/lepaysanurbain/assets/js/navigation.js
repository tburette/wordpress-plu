/**
 * Switch the header navigation to its compact (hamburger) presentation when
 * the desktop version can no longer fit.
 *
 * The desktop three-region layout is pure CSS. Core Navigation owns every
 * interaction (overlay, submenus, focus, Escape). This file only checks
 * whether a group (the nav-group left and right of the logo) overflows
 * its track and toggles the compact class when it does.
 *
 * Below Core's 600px breakpoint the closed container is hidden and then
 * restored by pure CSS in theme.css (@media screen and (max-width: 600px)),
 * so the compact phone presentation does not need this script at all.
 */

// we expect to be ins a ES module
// this is undefined at the top level in a ES modules.
if (this !== undefined) {
  throw new Error(
    "Must be run as an ESM module (see PHP wp_enqueue_script_module())",
  );
}

// Parent <nav>. Only the header navigation is checked; other
// navigations (eg. footer) must not be impacted.
const navigationSelector =
  ".lpu-header nav.wp-block-navigation.lpu-header__navigation";
// <ul> containing the top-level groups.
const listSelector =
  ".wp-block-navigation__responsive-container-content > .wp-block-navigation__container";

function getList(navigation) {
  return navigation.querySelector(listSelector);
}

/**
 * The full-screen overlay opens on mobile when the
 * hamburger is pressed. This is handled by WordPress Core native code.
 * the (mobile) overlay is not to be confused with the desktop mega-menu.
 */
function isMobileOverlayOpen(navigation) {
  const container = navigation.querySelector(
    ".wp-block-navigation__responsive-container",
  );

  return Boolean(container?.classList.contains("is-menu-open"));
}

/**
 * Check whether the header navigation fits.
 *
 * The desktop layout of the navigation is a '1fr auto 1fr' grid (see theme.css
 * "Desktop three-region layout"): one (1fr) track per group and one (auto)
 * track for the logo.
 *
 * Done by checking if one of the two nav-group size no longer fits inside its
 * grid track (its cell).
 *
 * This check compares :
 * - scrollWidth => how wide a group's content really is (even when clipped
 * because otherwise it would be overflowing)
 * - clientWidth => how wide the track actually is (might be clipped).
 * If any group's content is wider than its track, the menu cannot present
 * all its items in the desktop layout.
 *
 * For the check to be able to detect the overlow, the two nav groups must
 * possess the following CSS(see theme.css "Desktop nav-group"):
 *   - min-width: 0 lets its 1fr track shrink below the natural width of the
 *     text;
 *   - white-space: nowrap on the labels and flex-wrap: nowrap on the group
 *     list prevent wrapping onto extra lines (that would make the menu
 *     taller instead of overflowing);
 *   - overflow: hidden clips the too-wide content so it never spills onto
 *     the logo while this script performs the check.
 * Removing any of these rules would silently break the measurement: the
 * check could stop detecting overflow and the menu mught not compact.
 */
function isOverflowing(list) {
  const groups = Array.from(list.children).filter((group) =>
    group.classList.contains("lpu-nav-group"),
  );
  // +1px is a small margin against rounding/flicker.
  return groups.some((group) => group.scrollWidth > group.clientWidth + 1);
}

function updateNavigation(navigation) {
  const list = getList(navigation);

  if (!list || isMobileOverlayOpen(navigation)) {
    return;
  }

  // Measure in the desktop presentation so the decision reflects what the
  // closed menu could actually show, not the compact layout below it.
  navigation.classList.remove("lpu-navigation--compact");
  const enableCompact = isOverflowing(list);
  navigation.classList.toggle("lpu-navigation--compact", enableCompact);
}

function scheduleUpdate(navigation) {
  // Debounce consecutive resize/font events into a single frame.
  if (navigation.lpuLayoutFrame) {
    window.cancelAnimationFrame(navigation.lpuLayoutFrame);
  }

  navigation.lpuLayoutFrame = window.requestAnimationFrame(function () {
    updateNavigation(navigation);
  });
}

function observeNavigation(navigation) {
  // On the nav (not the window) so menu resizes are tracked without
  // re-running for unrelated viewport changes.
  const resizeObserver = new ResizeObserver(function () {
    scheduleUpdate(navigation);
  });
  resizeObserver.observe(navigation);

  const list = getList(navigation);
  if (!list) {
    console.error(`navigation (${navigationSelector}) has no list \
		(${listSelector}), navigation.js cannot perform needed change detection adequately`);
  } else {
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

  // webfonts can change group widths after load.
  if (document.fonts?.ready) {
    document.fonts.ready
      .then(function () {
        scheduleUpdate(navigation);
      })
      .catch(function () {});
  }

  // makes sure it runs at least once
  scheduleUpdate(navigation);
}

function init() {
  document.querySelectorAll(navigationSelector).forEach(observeNavigation);
}

if ("loading" === document.readyState) {
  document.addEventListener("DOMContentLoaded", init, { once: true });
} else {
  init();
}
