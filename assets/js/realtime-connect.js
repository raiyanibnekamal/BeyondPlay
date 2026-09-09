/**
 * Laravel Echo + Pusher — live notifications when ARENA_PUSHER_KEY is set in config.js.
 */
(function (global) {
  "use strict";

  if (!global.api || typeof global.api.initEcho !== "function") {
    return;
  }

  var echoStarted = false;

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      if (document.querySelector('script[src="' + src + '"]')) {
        resolve();
        return;
      }
      var s = document.createElement("script");
      s.src = src;
      s.onload = resolve;
      s.onerror = reject;
      document.head.appendChild(s);
    });
  }

  function loadEchoLibs() {
    return loadScript("https://js.pusher.com/8.2.0/pusher.min.js").then(function () {
      return loadScript(
        "https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"
      );
    });
  }

  function startEcho() {
    if (echoStarted) {
      return;
    }
    var key = global.ARENA_PUSHER_KEY || "";
    if (!key || !global.api.getAuthToken()) {
      return;
    }
    echoStarted = true;
    loadEchoLibs()
      .then(function () {
        if (typeof global.Echo === "undefined" || typeof global.Pusher === "undefined") {
          return;
        }
        global.Pusher = global.Pusher;
        return global.api.auth.me();
      })
      .then(function (res) {
        if (!res || !res.ok || !res.data || !res.data.user) {
          return;
        }
        global.api.initEcho(res.data.user.id, {
          onNotification: function () {
            if (typeof global.loadNotificationBadge === "function") {
              global.loadNotificationBadge();
            }
          },
        });
      })
      .catch(function () {
        echoStarted = false;
      });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", startEcho);
  } else {
    startEcho();
  }
})(typeof window !== "undefined" ? window : this);
