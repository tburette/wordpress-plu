(function () {
  "use strict";

  /* ---------------------------------------------------------
     Stand-in for the WordPress menu.
     In the real theme this whole block doesn't exist: the nav
     walker just streams out <li>s in admin order and starts a
     new <ul> the moment it passes the item flagged as the logo.
     Here we only have a flat array, so we approximate that same
     split by index — see splitAroundLogo() below.
     --------------------------------------------------------- */
  const MENUS = {
    few: [
      { label: "Le projet", href: "#" },
      { label: "Nos fermes", href: "#", hasChildren: true },
      { label: "Agir avec nous", href: "#" },
      { label: "Contact", href: "#" },
    ],
    many: [
      { label: "Qui sommes-nous", href: "#", hasChildren: true },
      { label: "Nos activités", href: "#", hasChildren: true },
      { label: "Nos cultures", href: "#", hasChildren: true },
      { label: "Nos projets et initiatives", href: "#", hasChildren: true },
      { label: "Infos pratiques", href: "#", hasChildren: true },
    ],
    stress: [
      { label: "Qui sommes-nous", href: "#", hasChildren: true },
      { label: "Nos activités", href: "#", hasChildren: true },
      { label: "Nos cultures", href: "#", hasChildren: true },
      { label: "Nos fermes", href: "#", hasChildren: true },
      { label: "Nos projets et initiatives", href: "#", hasChildren: true },
      { label: "Infos pratiques", href: "#", hasChildren: true },
      { label: "Actualités", href: "#" },
      { label: "Boutique", href: "#" },
      { label: "Contact", href: "#" },
    ],
  };

  const header = document.getElementById("siteHeader");
  const navLeft = document.getElementById("navLeft");
  const navRight = document.getElementById("navRight");
  const mainNav = document.getElementById("mainNav");
  const hamburgerBtn = document.getElementById("hamburgerBtn");
  const overlay = document.getElementById("mobileOverlay");
  const overlayList = document.getElementById("overlayList");
  const overlayClose = document.getElementById("overlayClose");

  const CHEVRON_SVG =
    '<svg class="chevron" viewBox="0 0 10 6" aria-hidden="true"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

  function linkHTML(item) {
    return (
      '<a href="' +
      item.href +
      '">' +
      item.label +
      (item.hasChildren ? CHEVRON_SVG : "") +
      "</a>"
    );
  }

  /* No item explicitly marked "this is the logo" in this demo
     data, so split by count, extra item going right (the
     agreed default when the count is odd). In the real walker
     this function doesn't exist at all — the split is just
     "whatever came before/after the logo item in wp-admin". */
  function splitAroundLogo(items) {
    const leftCount = Math.floor(items.length / 2);
    return { left: items.slice(0, leftCount), right: items.slice(leftCount) };
  }

  function renderMenu(key) {
    const items = MENUS[key];
    const { left, right } = splitAroundLogo(items);

    navLeft.innerHTML = left
      .map((i) => "<li>" + linkHTML(i) + "</li>")
      .join("");
    navRight.innerHTML = right
      .map((i) => "<li>" + linkHTML(i) + "</li>")
      .join("");
    overlayList.innerHTML = items
      .map((i) => "<li>" + linkHTML(i) + "</li>")
      .join("");

    checkOverflow();
  }

  /* ---------------------------------------------------------
     Overflow detection.
     nav-left / nav-right have no min-width override, so a nowrap
     group never shrinks narrower than its own content — meaning
     that whenever things don't fit, .main-nav's content genuinely
     exceeds its box and scrollWidth > clientWidth tells us so,
     with no manual width bookkeeping.
     To also detect "there's room again" while currently collapsed
     (nav-groups are display:none then, so they read as 0-width),
     we briefly remove the collapsed class, take the reading, and
     only then decide — that flip happens synchronously, before
     the browser paints, so nothing actually flashes on screen.
     --------------------------------------------------------- */
  function checkOverflow() {
    header.classList.remove("is-collapsed");
    const overflowing = mainNav.scrollWidth > mainNav.clientWidth + 1;
    header.classList.toggle("is-collapsed", overflowing);
    hamburgerBtn.setAttribute("aria-hidden", overflowing ? "false" : "true");

    // If space reappeared, make sure the overlay isn't left open
    // over a now-fitting, fully expanded desktop nav.
    if (!overflowing) closeOverlay();
  }

  let raf = null;
  function scheduleCheck() {
    if (raf) cancelAnimationFrame(raf);
    raf = requestAnimationFrame(checkOverflow);
  }

  new ResizeObserver(scheduleCheck).observe(header);
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(scheduleCheck);
  }

  /* ---------------------------------------------------------
     Overlay open/close
     --------------------------------------------------------- */
  function openOverlay() {
    overlay.classList.add("is-open");
    overlay.setAttribute("aria-hidden", "false");
    hamburgerBtn.setAttribute("aria-expanded", "true");
    document.body.classList.add("overlay-open");
    overlayClose.focus();
  }

  function closeOverlay() {
    overlay.classList.remove("is-open");
    overlay.setAttribute("aria-hidden", "true");
    hamburgerBtn.setAttribute("aria-expanded", "false");
    document.body.classList.remove("overlay-open");
  }

  hamburgerBtn.addEventListener("click", openOverlay);
  overlayClose.addEventListener("click", closeOverlay);
  overlay.addEventListener("click", function (e) {
    if (e.target.tagName === "A") closeOverlay();
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") closeOverlay();
  });

  /* ---------------------------------------------------------
     Demo harness controls
     --------------------------------------------------------- */
  // Scoped to .demo__panel: .site-header also carries a data-theme
  // attribute (that's how its own CSS variant is selected), so an
  // unscoped "[data-theme]" query would pick up the header itself.
  document.querySelectorAll(".demo__panel [data-theme]").forEach((btn) => {
    btn.addEventListener("click", function () {
      header.setAttribute("data-theme", btn.dataset.theme);
      document
        .querySelectorAll(".demo__panel [data-theme]")
        .forEach((b) => b.classList.toggle("is-active", b === btn));
    });
  });

  document.querySelectorAll(".demo__panel [data-menu]").forEach((btn) => {
    btn.addEventListener("click", function () {
      renderMenu(btn.dataset.menu);
      document
        .querySelectorAll(".demo__panel [data-menu]")
        .forEach((b) => b.classList.toggle("is-active", b === btn));
    });
  });

  renderMenu("few");
})();
