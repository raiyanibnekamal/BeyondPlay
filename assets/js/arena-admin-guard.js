/**
 * Admin panel gate — hide UI until admin API auth succeeds.
 */
(function (global) {
  "use strict";

  if (!/\/admin\//i.test(global.location.pathname)) {
    return;
  }

  var style = document.createElement("style");
  style.textContent =
    "body.arena-admin-guard-pending .arena-admin-layout{visibility:hidden}";
  document.head.appendChild(style);
  document.body.classList.add("arena-admin-guard-pending");

  function reveal() {
    document.body.classList.remove("arena-admin-guard-pending");
  }

  function deny(msg) {
    reveal();
    alert(msg || "Admin access required.");
    global.location.href = "../index.html";
  }

  function waitForApi(tries) {
    if (global.api && typeof global.api.auth !== "undefined") {
      if (!global.api.getAuthToken()) {
        global.location.href =
          "../login.html?redirect=" + encodeURIComponent(global.location.pathname);
        return;
      }
      global.api.auth.me().then(function (res) {
        if (!res.ok) {
          global.api.setAuthToken("");
          global.location.href = "../login.html";
          return;
        }
        var roles = res.data.roles || [];
        var user = res.data.user;
        if (roles.indexOf("admin") >= 0 || (user && user.role === "admin")) {
          reveal();
          return;
        }
        deny("Admin access required.");
      });
      return;
    }
    if (tries > 40) {
      deny("Could not load admin session.");
      return;
    }
    setTimeout(function () {
      waitForApi(tries + 1);
    }, 50);
  }

  waitForApi(0);
})(typeof window !== "undefined" ? window : this);
