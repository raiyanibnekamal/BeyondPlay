/**
 * Shared XSS helpers for Arena connect scripts (F1).
 */
(function (global) {
  "use strict";

  function escHtml(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function safeHref(url) {
    var u = String(url || "").trim();
    if (!u || u === "#") {
      return "#";
    }
    if (u.charAt(0) === "/" || u.indexOf("assets/") === 0) {
      return escHtml(u);
    }
    if (/^https?:\/\//i.test(u)) {
      return escHtml(u);
    }
    return "#";
  }

  function safeMediaSrc(url, fallback) {
    fallback = fallback || "assets/img/team/1-1.png";
    if (global.api && typeof global.api.resolveMediaUrl === "function") {
      var resolved = global.api.resolveMediaUrl(url);
      return resolved || fallback;
    }
    var u = String(url || "").trim();
    if (!u) {
      return fallback;
    }
    if (u.charAt(0) === "/" || u.indexOf("assets/") === 0) {
      if (u.indexOf("/storage/") === 0 && typeof global.location !== "undefined") {
        return global.location.protocol + "//" + global.location.hostname + ":8000" + escHtml(u);
      }
      return escHtml(u);
    }
    if (/^https?:\/\//i.test(u)) {
      return escHtml(u);
    }
    return fallback;
  }

  global.ArenaEscape = {
    esc: escHtml,
    safeHref: safeHref,
    safeMediaSrc: safeMediaSrc,
  };
})(typeof window !== "undefined" ? window : this);
