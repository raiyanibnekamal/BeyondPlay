/* Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
/**
 * Arena admin panel UI helpers.
 */
(function () {
  "use strict";

  function initNav() {
    var page = document.body.getAttribute("data-admin-page");
    if (!page) return;
    document.querySelectorAll("[data-nav]").forEach(function (link) {
      if (link.getAttribute("data-nav") === page) {
        link.classList.add("active");
      }
    });
  }

  function initConfirm() {
    document.querySelectorAll("[data-confirm]").forEach(function (el) {
      el.addEventListener("click", function (e) {
        var msg = el.getAttribute("data-confirm");
        if (msg && !window.confirm(msg)) {
          e.preventDefault();
          e.stopPropagation();
        }
      });
    });
  }

  function initPlaceholderActions() {
    document.querySelectorAll("[data-admin-action]").forEach(function (el) {
      el.addEventListener("click", function (e) {
        if (el.getAttribute("data-confirm")) return;
        var action = el.getAttribute("data-admin-action");
        if (
          action === "score" ||
          action === "vod" ||
          action === "details" ||
          action === "toggle"
        ) {
          e.preventDefault();
          alert("This feature will be available soon.");
        }
      });
    });
  }

  function initModals() {
    function openModal(id) {
      var modal = document.getElementById(id);
      if (modal) modal.classList.add("is-open");
    }
    function closeModal(modal) {
      if (modal) modal.classList.remove("is-open");
    }

    document.querySelectorAll("[data-open-modal]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        openModal(btn.getAttribute("data-open-modal"));
      });
    });

    document.querySelectorAll(".arena-admin-modal").forEach(function (modal) {
      modal.addEventListener("click", function (e) {
        if (
          e.target === modal ||
          e.target.hasAttribute("data-close-modal")
        ) {
          closeModal(modal);
        }
      });
    });

    document.querySelectorAll("[data-confirm-reject]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var modal = document.getElementById("arena-reject-modal");
        var reason = modal && modal.querySelector("[name=reject_reason]");
        if (reason && !reason.value.trim()) {
          alert("Please enter a rejection reason.");
          return;
        }
        closeModal(modal);
        alert("Suggestion rejected. This feature will be available soon.");
      });
    });

    document.querySelectorAll("[data-confirm-coupon]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        closeModal(document.getElementById("arena-coupon-modal"));
        alert("Coupon saved. This feature will be available soon.");
      });
    });
  }

  function initFilterTabs() {
    document.querySelectorAll("[data-admin-filter-tabs]").forEach(function (wrap) {
      var rows = wrap.querySelectorAll("[data-filter-row]");
      wrap.querySelectorAll("[data-filter-tab]").forEach(function (tab) {
        tab.addEventListener("click", function () {
          wrap.querySelectorAll("[data-filter-tab]").forEach(function (t) {
            t.classList.remove("active");
          });
          tab.classList.add("active");
          var filter = tab.getAttribute("data-filter-tab");
          rows.forEach(function (row) {
            var status = row.getAttribute("data-filter-row");
            row.style.display =
              filter === "all" || status === filter ? "" : "none";
          });
        });
      });
      var activeTab = wrap.querySelector("[data-filter-tab].active");
      if (activeTab) {
        var f = activeTab.getAttribute("data-filter-tab");
        rows.forEach(function (row) {
          var status = row.getAttribute("data-filter-row");
          row.style.display =
            f === "all" || status === f ? "" : "none";
        });
      }
    });
  }

  function initOrderStatus() {
    document.querySelectorAll("[data-order-status]").forEach(function (sel) {
      sel.addEventListener("change", function () {
        alert(
          "Order status updated to " +
            sel.value +
            ". This feature will be available soon."
        );
      });
    });
  }

  function initResolveDispute() {
    document.querySelectorAll("[data-confirm-resolve]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var modal = document.getElementById("arena-resolve-modal");
        var note = modal && modal.querySelector("[name=resolution_note]");
        if (note && !note.value.trim()) {
          alert("Please enter a resolution note.");
          return;
        }
        if (modal) modal.classList.remove("is-open");
        alert("Dispute resolved. This feature will be available soon.");
      });
    });
  }

  function initBannerPreview() {
    var typeSel = document.getElementById("banner-type-select");
    var preview = document.getElementById("banner-type-preview");
    if (!typeSel || !preview) return;
    function update() {
      preview.className =
        "arena-admin-banner-preview " + (typeSel.value || "info");
      preview.textContent =
        "Preview: " + typeSel.options[typeSel.selectedIndex].text;
    }
    typeSel.addEventListener("change", update);
    update();
  }

  function initBulkEmail() {
    var form = document.getElementById("arena-bulk-email-form");
    if (!form) return;
    var subject = form.querySelector("[name=subject]");
    var body = form.querySelector("[name=body]");
    var sendBtn = document.getElementById("arena-bulk-send");
    var previewBtn = document.getElementById("arena-bulk-preview");
    var previewBox = document.getElementById("arena-email-preview");

    function checkReady() {
      if (!sendBtn) return;
      var ok =
        subject &&
        body &&
        subject.value.trim() &&
        body.value.trim();
      sendBtn.disabled = !ok;
    }

    if (subject) subject.addEventListener("input", checkReady);
    if (body) body.addEventListener("input", checkReady);
    checkReady();

    if (previewBtn && previewBox) {
      previewBtn.addEventListener("click", function () {
        previewBox.classList.add("is-visible");
        var sub = previewBox.querySelector("[data-preview-subject]");
        var bod = previewBox.querySelector("[data-preview-body]");
        if (sub && subject) sub.textContent = subject.value || "(No subject)";
        if (bod && body) bod.textContent = body.value || "(No body)";
      });
    }

    if (sendBtn) {
      sendBtn.addEventListener("click", function () {
        var audience =
          form.querySelector("[name=audience]:checked") || {};
        var labels = {
          all: "2,847",
          active: "1,920",
          newsletter: "640",
        };
        var key = audience.value || "all";
        var count = labels[key] || "2,847";
        if (
          window.confirm(
            "You are about to send email to " + count + " users. Are you sure?"
          )
        ) {
          alert("Email queued. This feature will be available soon.");
        }
      });
    }
  }

  function initNotifications() {
    var form = document.getElementById("arena-notif-form");
    if (!form) return;
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      alert("Notification sent. This feature will be available soon.");
    });
  }

  function initSettings() {
    var form = document.getElementById("arena-settings-form");
    if (!form || form.dataset.arenaSettingsBound) return;
  }

  function initSaveBanner() {
    document.querySelectorAll("[data-confirm-banner]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var modal = document.getElementById("arena-banner-modal");
        if (modal) modal.classList.remove("is-open");
        alert("Banner saved. This feature will be available soon.");
      });
    });
    document.querySelectorAll("[data-confirm-sponsor]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var modal = document.getElementById("arena-sponsor-modal");
        if (modal) modal.classList.remove("is-open");
        alert("Sponsor saved. This feature will be available soon.");
      });
    });
  }

  function initApprove() {
    document.querySelectorAll("[data-approve-suggestion]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var msg =
          btn.getAttribute("data-approve-suggestion") ||
          "Approving will add this game to the games list. Continue?";
        if (window.confirm(msg)) {
          alert("Game approved. This feature will be available soon.");
        }
      });
    });
  }

  function boot() {
    initNav();
    initConfirm();
    initModals();
    initFilterTabs();
    if (typeof window.api !== "undefined") {
      return;
    }
    initPlaceholderActions();
    initApprove();
    initOrderStatus();
    initResolveDispute();
    initBannerPreview();
    initBulkEmail();
    initNotifications();
    initSettings();
    initSaveBanner();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
