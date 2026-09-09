/* Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
/**
 * Blog list and details — Arena API.
 */
(function (global) {
  "use strict";

  if (!global.api) {
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

  function loadBlogList() {
    if (page() !== "blog.html") {
      return;
    }
    global.api.blog.listPosts().then(function (res) {
      if (!res.ok) {
        return;
      }
      var items = (res.data && res.data.data) || [];
      var grid =
        document.querySelector(".blog-area .row") ||
        document.querySelector(".space .row");
      if (!grid) {
        return;
      }
      if (!items.length) {
        if (!document.getElementById("blog-empty-hint")) {
          var empty = document.createElement("p");
          empty.id = "blog-empty-hint";
          empty.className = "text-center text-theme mb-30";
          empty.textContent =
            "No blog posts published yet. Check back soon or add posts from the admin panel.";
          grid.parentElement.insertBefore(empty, grid);
        }
        return;
      }
      grid.querySelectorAll(".col-md-6, .col-xl-4").forEach(function (col) {
        if (col.querySelector(".blog-card, .blog-grid")) {
          col.remove();
        }
      });
      var html = items
        .slice(0, 9)
        .map(function (p) {
          var date = (p.published_at || p.created_at || "").slice(0, 10);
          return (
            '<div class="col-md-6 col-xl-4"><div class="blog-card"><div class="blog-content">' +
            '<h3 class="blog-title"><a href="blog-details.html?slug=' +
            esc(p.slug) +
            '">' +
            esc(p.title) +
            "</a></h3>" +
            '<p class="blog-meta">' +
            esc(date) +
            "</p></div></div></div>"
          );
        })
        .join("");
      grid.insertAdjacentHTML("afterbegin", html);
    });
  }

  function loadBlogDetails() {
    if (page() !== "blog-details.html") {
      return;
    }
    var slug = new URLSearchParams(global.location.search).get("slug");
    if (!slug) {
      var hint = document.querySelector(".space .container");
      if (hint && !document.getElementById("blog-slug-hint")) {
        var p = document.createElement("p");
        p.id = "blog-slug-hint";
        p.className = "text-center text-theme mb-30";
        p.textContent = "Add ?slug=post-slug to load this article from the API.";
        hint.insertBefore(p, hint.firstChild);
      }
      return;
    }
    global.api.blog.getPost(slug).then(function (res) {
      if (!res.ok) {
        return;
      }
      var p = res.data;
      document.querySelectorAll(".breadcumb-title, h2.sec-title, .blog-title").forEach(function (el, i) {
        if (i === 0 || el.classList.contains("breadcumb-title")) {
          el.textContent = p.title || "";
        }
      });
      var body = document.querySelector(".blog-content-detail p, .blog-single-content p");
      if (body && p.content) {
        body.textContent = p.content;
      }
      var comments = (p.comments || []).slice(0, 10);
      var list = document.querySelector(".comment-list");
      if (list && comments.length) {
        list.innerHTML = comments
          .map(function (c) {
            var u = c.user || {};
            return (
              '<li class="review th-comment-item"><div class="th-post-comment"><div class="comment-content">' +
              "<h4>" +
              esc(u.username) +
              "</h4><p>" +
              esc(c.content) +
              "</p></div></div></li>"
            );
          })
          .join("");
      }
      var cform = document.querySelector(".th-comment-form form, #blog-comment-form");
      if (cform && !cform.dataset.arenaBound) {
        cform.dataset.arenaBound = "1";
        cform.addEventListener("submit", function (e) {
          e.preventDefault();
          if (!global.api.getAuthToken()) {
            global.location.href = "login.html";
            return;
          }
          var ta = cform.querySelector("textarea");
          global.api.blog.addComment(p.id, { content: ta ? ta.value : "" }).then(function (r) {
            alert(r.ok ? r.data.message || "Comment submitted." : (r.data && r.data.message) || "Failed.");
          });
        });
      }
    });
  }

  function init() {
    loadBlogList();
    loadBlogDetails();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(typeof window !== "undefined" ? window : this);
