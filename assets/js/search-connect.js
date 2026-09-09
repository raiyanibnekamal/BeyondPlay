/* Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
/**
 * Site search — header box + search.html results.
 */
(function (global) {
  "use strict";

  if (!global.api || !global.api.search) {
    return;
  }

  function esc(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;");
  }

  function page() {
    return (global.location.pathname.split("/").pop() || "").toLowerCase();
  }

  function bindHeaderSearch() {
    document.querySelectorAll(".popup-search-box form").forEach(function (form) {
      if (form.dataset.arenaSearchBound) {
        return;
      }
      form.dataset.arenaSearchBound = "1";
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var input = form.querySelector('input[type="text"]');
        var q = input ? input.value.trim() : "";
        if (q.length < 2) {
          return;
        }
        global.location.href = "search.html?q=" + encodeURIComponent(q);
      });
    });
  }

  function renderSection(title, items, linkFn) {
    if (!items || !items.length) {
      return "";
    }
    return (
      '<div class="mb-40"><h3 class="sec-title h4">' +
      esc(title) +
      '</h3><ul class="list-unstyled">' +
      items
        .map(function (item) {
          return (
            '<li class="mb-2"><a href="' +
            linkFn(item) +
            '" class="text-theme">' +
            esc(item.name || item.username) +
            "</a></li>"
          );
        })
        .join("") +
      "</ul></div>"
    );
  }

  function loadSearchPage() {
    if (page() !== "search.html") {
      return;
    }
    var params = new URLSearchParams(global.location.search);
    var q = params.get("q") || "";
    var input = document.getElementById("arena-search-input");
    var results = document.getElementById("arena-search-results");
    if (input) {
      input.value = q;
    }
    if (!results || q.length < 2) {
      if (results) {
        results.innerHTML = '<p class="text-muted">Enter at least 2 characters to search.</p>';
      }
      return;
    }
    results.innerHTML = "<p>Searching…</p>";
    global.api.search(q).then(function (res) {
      if (!res.ok) {
        results.innerHTML = '<p class="text-danger">Search failed.</p>';
        return;
      }
      var d = res.data || {};
      var html =
        renderSection("Tournaments", d.tournaments, function (t) {
          return "tournament-details.html?slug=" + encodeURIComponent(t.slug);
        }) +
        renderSection("Games", d.games, function (g) {
          return "game-details.html?slug=" + encodeURIComponent(g.slug);
        }) +
        renderSection("Products", d.products, function (p) {
          return "shop-details.html?slug=" + encodeURIComponent(p.slug);
        }) +
        renderSection("Teams", d.teams, function (t) {
          return "team-details.html?slug=" + encodeURIComponent(t.slug);
        }) +
        renderSection("Players", d.users, function (u) {
          return "player-profile.html?user=" + encodeURIComponent(u.username);
        });
      results.innerHTML = html || '<p class="text-muted">No results for "' + esc(q) + '".</p>';
    });
  }

  function bindSearchForm() {
    var form = document.getElementById("arena-search-form");
    if (!form || form.dataset.arenaSearchBound) {
      return;
    }
    form.dataset.arenaSearchBound = "1";
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var q = (document.getElementById("arena-search-input") || {}).value || "";
      global.location.href = "search.html?q=" + encodeURIComponent(q.trim());
    });
  }

  function init() {
    bindHeaderSearch();
    bindSearchForm();
    loadSearchPage();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(typeof window !== "undefined" ? window : this);
