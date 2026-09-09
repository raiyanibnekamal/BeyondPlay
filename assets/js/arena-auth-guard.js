/* Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
/**
 * Redirect to login when a protected page is opened without auth.
 * Load after api.js.
 */
(function (global) {
  "use strict";

  var page = (global.location.pathname.split("/").pop() || "").toLowerCase();

  var protectedPages = [
    "wishlist.html",
    "checkout.html",
    "bracket-prediction.html",
    "dispute.html",
    "checkin.html",
    "suggest-game.html",
    "my-profile.html",
    "my-tournaments.html",
    "my-orders.html",
    "my-notifications.html",
    "achievements.html",
    "friends.html",
    "activity-feed.html",
    "stats.html",
    "tournament-history.html",
    "head-to-head.html",
  ];

  if (protectedPages.indexOf(page) < 0) {
    return;
  }

  var authed =
    global.api &&
    typeof global.api.isAuthenticated === "function" &&
    global.api.isAuthenticated();

  if (!authed) {
    global.location.href =
      "login.html?redirect=" +
      encodeURIComponent(global.location.pathname + global.location.search);
  }
})(typeof window !== "undefined" ? window : this);
