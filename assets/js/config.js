/* Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
/**
 * Arena frontend configuration — load before api.js.
 *
 * PRODUCTION: set ARENA_API_BASE to your live API URL before deploy.
 * Local dev: leave unset; api.js uses http://127.0.0.1:8000/api/v1
 */
(function (win) {
  "use strict";

  var host = win.location && win.location.hostname ? win.location.hostname : "";
  var isLocal =
    host === "127.0.0.1" ||
    host === "localhost" ||
    host === "";

  /** @type {string|undefined} Replace with your production API (required on live domain). */
  win.ARENA_API_BASE = isLocal
    ? undefined
    : "https://api.yourdomain.com/api/v1";

  /**
   * Pusher (optional) — set key + cluster for live notifications.
   * Backend: BROADCAST_CONNECTION=pusher, PUSHER_* in Laravel .env
   */
  win.ARENA_PUSHER_KEY = isLocal ? "" : "";
  win.ARENA_PUSHER_CLUSTER = "mt1";

  /**
   * HttpOnly cookie auth (set true when frontend + API share parent domain & HTTPS).
   * Local dev keeps Bearer tokens in memory only (not localStorage when cookie mode).
   */
  win.ARENA_USE_COOKIE_AUTH = !isLocal && !!win.ARENA_API_BASE;

  if (!isLocal) {
    var api = win.ARENA_API_BASE || "";
    if (
      !api ||
      api.indexOf("127.0.0.1") !== -1 ||
      api.indexOf("localhost") !== -1 ||
      api.indexOf("yourdomain.com") !== -1
    ) {
      console.error(
        "[Arena] PRODUCTION MISCONFIG: Set window.ARENA_API_BASE in assets/js/config.js to your real API URL before go-live."
      );
    }
  }
})(typeof window !== "undefined" ? window : this);
