/**
 * Arena UI fixes — navbar layout, performance, titles, checkout.
 */
(function (global) {
  "use strict";

  function page() {
    return (global.location.pathname.split("/").pop() || "index.html").toLowerCase();
  }

  /* Remove preloader immediately */
  function killPreloader() {
    document.querySelectorAll(".preloader, .preloaderCls").forEach(function (el) {
      el.remove();
    });
  }

  /* Scroll lag: disable heavy cursor, WOW, scroll listeners */
  function boostScrollPerf() {
    document.body.classList.add("arena-perf");
    document.querySelectorAll(".cursor-animation").forEach(function (el) {
      el.remove();
    });
    if (global.WOW) {
      try {
        new global.WOW({ live: false, mobile: true, offset: 0 }).init();
      } catch (e) {}
    }
    if (global.jQuery) {
      global.jQuery(".circle-title-anime").each(function () {
        this.style.animation = "none";
      });
    }
  }

  function fixMojibakeText(text) {
    return String(text || "")
      .replace(/\u00C3\u0192\u00C2\u00A2\u2014\u00C2\u009D/g, "\u2014")
      .replace(/\u00C3\u00A2\u00E2\u201A\u00AC\u00E2\u20AC\u009D/g, "\u2014")
      .replace(/\u00C3\u00A2\u00E2\u201A\u00AC\u00C2\u009D/g, "\u2014")
      .replace(/\u00C3\u00A2\u00E2\u201A\u00AC\u00E2\u201E\u00A2/g, "'")
      .replace(/\u00C3\u0192\u00E2\u20AC\u201D/g, "\u00D7")
      .replace(/\u00E2\u20AC\u201D/g, "\u2014")
      .replace(/\u00E2\u20AC\u201C/g, "\u201c")
      .replace(/\u00E2\u20AC\u2122/g, "'")
      .replace(/\u00E2\u20AC\u2019/g, "'")
      .replace(/Today !/g, "Today!");
  }

  function fixVisibleCopy() {
    if (!document.body) {
      return;
    }
    var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    var node;
    while ((node = walker.nextNode())) {
      var parent = node.parentElement;
      if (!parent || parent.tagName === "SCRIPT" || parent.tagName === "STYLE") {
        continue;
      }
      var fixed = fixMojibakeText(node.textContent);
      if (fixed !== node.textContent) {
        node.textContent = fixed;
      }
    }
  }

  /* Fix garbled document titles */
  function fixPageTitle() {
    var t = fixMojibakeText(document.title || "");
    t = t
      .replace(/BeyondPlay\s*[\u00e2\u20ac\u201d\u00a2\u2014]+/gi, "BeyondPlay —")
      .replace(/BeyondPlay\s*â€[\u201c\u201d]?/gi, "BeyondPlay —")
      .replace(/BeyondPlay\s*—\s*Esports Tournament Platform\s*\|\s*/i, "BeyondPlay | ")
      .replace(/BeyondPlay\s*—\s*/gi, "BeyondPlay — ");
    if (t !== document.title) {
      document.title = t;
    }
  }

  /* Navbar: left = logo + icons, right = menu top + CTA buttons bottom */
  function buildNavbarLayout() {
    if (!global.matchMedia("(min-width: 992px)").matches) {
      return;
    }
    document.querySelectorAll(".th-header .menu-area .container > .row").forEach(function (row) {
      var cols = Array.prototype.filter.call(row.children, function (el) {
        return el.classList && el.classList.contains("col-auto");
      });
      if (cols.length < 3) {
        return;
      }
      var logoCol = cols[0];
      var btnCol = cols[cols.length - 1];
      var headerButton = btnCol.querySelector(".header-button");
      if (!logoCol || !headerButton) {
        return;
      }

      row.dataset.arenaNavBuilt = "1";
      row.classList.add("arena-nav-grid");
      logoCol.classList.add("arena-nav-left");

      var icons = logoCol.querySelector(".arena-nav-icons");
      if (!icons) {
        icons = document.createElement("div");
        icons.className = "arena-nav-icons";
        logoCol.appendChild(icons);
      }

      btnCol.querySelectorAll(".header-button .simple-icon").forEach(function (el) {
        if (!icons.contains(el)) {
          icons.appendChild(el);
        }
      });

      var actions = headerButton.querySelector(".arena-nav-actions");
      if (!actions) {
        actions = document.createElement("div");
        actions.className = "arena-nav-actions";
        headerButton.appendChild(actions);
      }
      Array.prototype.slice.call(headerButton.childNodes).forEach(function (node) {
        if (node === actions) {
          return;
        }
        if (node.nodeType === 1 && node.classList && node.classList.contains("simple-icon")) {
          if (!icons.contains(node)) {
            icons.appendChild(node);
          }
          return;
        }
        if (node.nodeType === 1) {
          actions.appendChild(node);
        }
      });
    });
  }

  function fixLogoDisplay() {
    document.querySelectorAll(".th-header .header-logo a").forEach(function (link) {
      if (link.dataset.arenaLogoFixed === "1") {
        return;
      }
      link.dataset.arenaLogoFixed = "1";

      var mask = link.querySelector(".logo-mask");
      if (mask) {
        mask.remove();
      }

      var img = link.querySelector("img");
      if (!img) {
        return;
      }
      img.style.display = "block";
      img.style.maxHeight = "58px";
      img.style.width = "auto";
      img.setAttribute("alt", "BeyondPlay");
    });
  }

  function trimNavMenus() {
    document.querySelectorAll(".main-menu a").forEach(function (a) {
      var href = (a.getAttribute("href") || "").toLowerCase();
      var text = (a.textContent || "").trim().toUpperCase();
      if (
        href === "login.html" ||
        href === "register.html" ||
        text === "LOGIN" ||
        text === "REGISTER"
      ) {
        var li = a.closest("li");
        if (li) li.remove();
      }
    });
  }

  function layoutHomeAnnouncementGap() {
    if (page() !== "index.html") {
      return;
    }
    var zone = document.getElementById("arenaAnnouncementZone");
    if (!zone) {
      return;
    }
    var h = Math.round(Math.max(168, Math.min(224, global.innerHeight * 0.17)));
    zone.style.minHeight = h + "px";
  }

  function repositionHomeAnnouncement() {
    if (page() !== "index.html") {
      return;
    }
    var bar = document.getElementById("announcementBar");
    var container = document.querySelector("#hero .container");
    var heroContent = container && container.querySelector(".hero-style1");
    if (!bar || !container || !heroContent) {
      return;
    }

    bar.classList.add("arena-home-announcement");
    bar.removeAttribute("style");

    var zone = document.getElementById("arenaAnnouncementZone");
    if (!zone) {
      zone = document.createElement("div");
      zone.id = "arenaAnnouncementZone";
      zone.className = "arena-hero-top-gap";
      var topSpacer = document.createElement("div");
      topSpacer.className = "arena-gap-spacer";
      topSpacer.setAttribute("aria-hidden", "true");
      var bottomSpacer = document.createElement("div");
      bottomSpacer.className = "arena-gap-spacer";
      bottomSpacer.setAttribute("aria-hidden", "true");
      zone.appendChild(topSpacer);
      zone.appendChild(bar);
      zone.appendChild(bottomSpacer);
      container.insertBefore(zone, heroContent);
    } else if (bar.parentElement !== zone) {
      var lastSpacer = zone.querySelector(".arena-gap-spacer:last-child");
      if (lastSpacer) {
        zone.insertBefore(bar, lastSpacer);
      } else {
        zone.appendChild(bar);
      }
    }

    layoutHomeAnnouncementGap();
  }

  global.arenaRepositionHomeAnnouncement = repositionHomeAnnouncement;
  global.arenaLayoutHomeAnnouncementGap = layoutHomeAnnouncementGap;

  function showLiveStreaming() {
    document.querySelectorAll("a").forEach(function (a) {
      if (/live streaming/i.test(a.textContent || "")) {
        a.setAttribute("href", "live-streaming.html");
        a.classList.remove("d-none");
        a.style.display = "inline-flex";
      }
    });
  }

  /* Tournament details — player name links */
  function wireTournamentPlayerLinks() {
    if (page() !== "tournament-details.html") {
      return;
    }
    document.querySelectorAll(".tournament-single-team").forEach(function (li) {
      if (li.querySelector("a.arena-player-link")) {
        return;
      }
      var name = "";
      li.childNodes.forEach(function (n) {
        if (n.nodeType === 3) {
          name += n.textContent;
        }
      });
      name = name.trim();
      if (!name) {
        return;
      }
      li.childNodes.forEach(function (n) {
        if (n.nodeType === 3) {
          li.removeChild(n);
        }
      });
      var a = document.createElement("a");
      a.className = "arena-player-link";
      a.href = "player-profile.html?user=" + encodeURIComponent(name);
      a.textContent = " " + name;
      li.appendChild(a);
    });
  }

  /* Checkout — single billing, required fields, native validation */
  function fixCheckoutForm() {
    if (page() !== "checkout.html") {
      return;
    }
    var form = document.getElementById("checkout-form");
    if (!form || form.dataset.arenaUiFixed) {
      return;
    }
    form.dataset.arenaUiFixed = "1";
    form.classList.add("arena-checkout-single");
    form.setAttribute("novalidate", "");

    var shipCb = form.querySelector("#ship-to-different-address-checkbox");
    if (shipCb) {
      shipCb.checked = false;
      shipCb.closest("p")?.classList.add("d-none");
    }
    var shipBlock = form.querySelector(".shipping_address");
    if (shipBlock) {
      shipBlock.style.display = "none";
    }

    var billing = form.querySelector(".col-lg-6");
    if (!billing) {
      billing = form;
    }

    var fields = [
      { sel: 'input[placeholder="First Name"]', name: "billing_first_name", req: true },
      { sel: 'input[placeholder="Last Name"]', name: "billing_last_name", req: true },
      { sel: 'input[placeholder="Street Address"]', name: "billing_address_1", req: true },
      { sel: 'input[placeholder="Town / City"]', name: "billing_city", req: true },
      { sel: 'input[placeholder="Postcode / Zip"]', name: "billing_postcode", req: true },
      { sel: 'input[placeholder="Email Address"]', name: "billing_email", type: "email", req: true },
      { sel: 'input[placeholder="Phone number"]', name: "billing_phone", req: true },
    ];

    fields.forEach(function (f) {
      var inputs = billing.querySelectorAll(f.sel);
      if (!inputs.length) {
        inputs = form.querySelectorAll(f.sel);
      }
      inputs.forEach(function (input, idx) {
        if (idx > 0) {
          input.closest(".form-group")?.remove();
          return;
        }
        input.setAttribute("name", f.name);
        input.setAttribute("id", f.name);
        if (f.type) {
          input.setAttribute("type", f.type);
        }
        if (f.req) {
          input.setAttribute("required", "required");
        }
        var ph = input.getAttribute("placeholder") || f.name;
        var label = document.createElement("label");
        label.setAttribute("for", f.name);
        label.className = "text-white small d-block mb-1";
        label.textContent = ph + (f.req ? " *" : "");
        input.parentNode.insertBefore(label, input);
      });
    });

    document.querySelectorAll('button[type="submit"]').forEach(function (btn) {
      if (!/place order/i.test(btn.textContent || "")) {
        return;
      }
      btn.setAttribute("form", "checkout-form");
      btn.addEventListener("click", function (e) {
        if (!form.checkValidity()) {
          e.preventDefault();
          form.reportValidity();
        }
      });
    });
  }

  function init() {
    killPreloader();
    fixPageTitle();
    fixVisibleCopy();
    fixLogoDisplay();
    repositionHomeAnnouncement();
    buildNavbarLayout();
    trimNavMenus();
    showLiveStreaming();
    wireTournamentPlayerLinks();
    fixCheckoutForm();
    boostScrollPerf();
  }

  global.arenaRebuildNavbar = function () {
    buildNavbarLayout();
    trimNavMenus();
    showLiveStreaming();
    fixLogoDisplay();
  };

  killPreloader();
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
  global.addEventListener("load", function () {
    killPreloader();
    arenaRebuildNavbar();
    layoutHomeAnnouncementGap();
  });
  global.addEventListener("resize", layoutHomeAnnouncementGap);
})(window);
