/**
 * Arena admin panel — API wiring.
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

  function requireAdmin() {
    return new Promise(function (resolve) {
      if (!global.api.getAuthToken()) {
        global.location.href =
          "../login.html?redirect=" + encodeURIComponent("../admin/index.html");
        resolve(false);
        return;
      }
      global.api.auth.me().then(function (res) {
        if (!res.ok) {
          global.api.setAuthToken("");
          global.location.href = "../login.html";
          resolve(false);
          return;
        }
        var roles = res.data.roles || [];
        var user = res.data.user;
        if (roles.indexOf("admin") >= 0 || (user && user.role === "admin")) {
          resolve(true);
          return;
        }
        alert("Admin access required.");
        global.location.href = "../index.html";
        resolve(false);
      });
    });
  }

  function loadAnalytics() {
    if (!document.querySelector('[data-admin-page="analytics"]')) {
      return;
    }
    global.api.admin.analyticsOverview().then(function (res) {
      if (!res.ok || !res.data.totals) {
        return;
      }
      var t = res.data.totals;
      document.querySelectorAll(".arena-admin-stat h3").forEach(function (el, i) {
        var vals = [
          t.page_views_30d,
          t.signups_30d,
          t.registrations_30d,
          t.orders_30d,
        ];
        if (vals[i] != null) {
          el.textContent = vals[i];
        }
      });
      document.querySelectorAll(".arena-admin-chart-note").forEach(function (n) {
        n.textContent = "Live data from API (last 30 days)";
      });
    });
  }

  function loadUsers() {
    if (!document.querySelector('[data-admin-page="users"]')) {
      return;
    }
    global.api.admin.listUsers().then(function (res) {
      if (!res.ok) {
        return;
      }
      var tbody = document.querySelector(".arena-admin-table tbody");
      if (!tbody) {
        return;
      }
      var rows = (res.data && res.data.data) || [];
      tbody.innerHTML = rows
        .map(function (u) {
          var banLabel = u.status === "banned" ? "Unban" : "Ban";
          var nextStatus = u.status === "banned" ? "active" : "banned";
          return (
            "<tr><td>" +
            esc(u.username) +
            "</td><td>" +
            esc(u.email) +
            "</td><td>" +
            esc(u.role) +
            '</td><td><span class="arena-admin-badge">' +
            esc(u.status) +
            '</span></td><td><button type="button" class="arena-admin-btn arena-admin-btn-sm arena-user-status" data-id="' +
            u.id +
            '" data-status="' +
            nextStatus +
            '">' +
            banLabel +
            "</button>" +
            (u.role !== "admin"
              ? ' <button type="button" class="arena-admin-btn arena-admin-btn-sm arena-promote-user" data-id="' +
                u.id +
                '">Promote</button>'
              : "") +
            "</td></tr>"
          );
        })
        .join("");
      tbody.querySelectorAll(".arena-promote-user").forEach(function (btn) {
        btn.addEventListener("click", function () {
          if (!confirm("Promote to admin?")) {
            return;
          }
          global.api.admin.promoteUser(btn.getAttribute("data-id")).then(function (r) {
            if (r.ok) {
              loadUsers();
            } else {
              alert((r.data && r.data.message) || "Failed.");
            }
          });
        });
      });
      tbody.querySelectorAll(".arena-user-status").forEach(function (btn) {
        btn.addEventListener("click", function () {
          var id = btn.getAttribute("data-id");
          var status = btn.getAttribute("data-status");
          global.api.admin.updateUserStatus(id, { status: status }).then(function (r) {
            if (r.ok) {
              loadUsers();
            } else {
              alert((r.data && r.data.message) || "Failed.");
            }
          });
        });
      });
    });
  }

  function loadProducts() {
    if (!document.querySelector('[data-admin-page="products"]')) {
      return;
    }
    global.api.admin.listProducts().then(function (res) {
      if (!res.ok) {
        return;
      }
      var tbody = document.querySelector(".arena-admin-table tbody");
      if (!tbody) {
        return;
      }
      var rows = (res.data && res.data.data) || [];
      tbody.innerHTML = rows
        .map(function (p) {
          return (
            "<tr><td>" +
            esc(p.name) +
            "</td><td>$" +
            esc(p.price) +
            "</td><td>" +
            esc(p.stock) +
            "</td><td>" +
            esc(p.type) +
            "</td><td>" +
            esc(p.status) +
            '</td><td><button type="button" class="arena-admin-btn arena-admin-btn-sm arena-edit-product" data-id="' +
            p.id +
            '">Edit</button> <button type="button" class="arena-admin-btn arena-admin-btn-sm arena-delete-product" data-id="' +
            p.id +
            '">Delete</button></td></tr>'
          );
        })
        .join("");
      tbody.querySelectorAll(".arena-delete-product").forEach(function (btn) {
        btn.addEventListener("click", function () {
          if (!confirm("Delete this product?")) {
            return;
          }
          global.api.admin.deleteProduct(btn.getAttribute("data-id")).then(function (r) {
            if (r.ok) {
              loadProducts();
            } else {
              alert((r.data && r.data.message) || "Delete failed.");
            }
          });
        });
      });
      tbody.querySelectorAll(".arena-edit-product").forEach(function (btn) {
        btn.addEventListener("click", function () {
          var id = btn.getAttribute("data-id");
          var row = rows.find(function (p) {
            return String(p.id) === String(id);
          });
          if (row) {
            openProductModal(row);
          }
        });
      });
    });
  }

  function ensureProductModal() {
    if (document.getElementById("arena-product-modal")) {
      return document.getElementById("arena-product-modal");
    }
    var modal = document.createElement("div");
    modal.id = "arena-product-modal";
    modal.className = "arena-admin-modal";
    modal.style.cssText =
      "display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center;padding:20px";
    modal.innerHTML =
      '<div class="arena-admin-card" style="max-width:480px;width:100%"><h3 id="arena-product-modal-title">Product</h3>' +
      '<form id="arena-product-form"><input type="hidden" name="id" value="">' +
      '<label>Name</label><input class="arena-admin-input" style="width:100%" name="name" required>' +
      '<label>Price</label><input class="arena-admin-input" style="width:100%" name="price" type="number" step="0.01" required>' +
      '<label>Stock</label><input class="arena-admin-input" style="width:100%" name="stock" type="number" value="10">' +
      '<label>Type</label><select class="arena-admin-select" style="width:100%" name="type"><option value="physical">Physical</option><option value="digital">Digital</option></select>' +
      '<label>Status</label><select class="arena-admin-select" style="width:100%" name="status"><option value="active">active</option><option value="inactive">inactive</option></select>' +
      '<div class="arena-admin-form-actions mt-20"><button type="submit" class="arena-admin-btn arena-admin-btn-primary">Save</button>' +
      '<button type="button" class="arena-admin-btn arena-admin-btn-secondary" id="arena-product-cancel">Cancel</button></div></form></div>';
    document.body.appendChild(modal);
    modal.addEventListener("click", function (ev) {
      if (ev.target === modal) {
        modal.style.display = "none";
      }
    });
    document.getElementById("arena-product-cancel").addEventListener("click", function () {
      modal.style.display = "none";
    });
    return modal;
  }

  function openProductModal(product) {
    var modal = ensureProductModal();
    var form = document.getElementById("arena-product-form");
    if (!form) {
      return;
    }
    document.getElementById("arena-product-modal-title").textContent = product
      ? "Edit Product"
      : "Add Product";
    form.querySelector('[name="id"]').value = product ? product.id : "";
    form.querySelector('[name="name"]').value = product ? product.name : "";
    form.querySelector('[name="price"]').value = product ? product.price : "29.99";
    form.querySelector('[name="stock"]').value = product ? product.stock : 10;
    form.querySelector('[name="type"]').value = product ? product.type : "physical";
    form.querySelector('[name="status"]').value = product ? product.status : "active";
    modal.style.display = "flex";
    if (!form.dataset.arenaBound) {
      form.dataset.arenaBound = "1";
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var id = form.querySelector('[name="id"]').value;
        var payload = {
          name: form.querySelector('[name="name"]').value,
          price: form.querySelector('[name="price"]').value,
          stock: form.querySelector('[name="stock"]').value,
          type: form.querySelector('[name="type"]').value,
          status: form.querySelector('[name="status"]').value,
        };
        var req = id
          ? global.api.admin.updateProduct(id, payload)
          : global.api.admin.createProduct(payload);
        req.then(function (r) {
          if (r.ok) {
            modal.style.display = "none";
            loadProducts();
          } else {
            alert((r.data && r.data.message) || "Save failed.");
          }
        });
      });
    }
  }

  function bindProductsAdmin() {
    if (!document.querySelector('[data-admin-page="products"]')) {
      return;
    }
    var addBtn = document.querySelector(".arena-admin-toolbar .arena-admin-btn-primary");
    if (!addBtn || addBtn.dataset.arenaProductAdd) {
      return;
    }
    addBtn.dataset.arenaProductAdd = "1";
    addBtn.addEventListener("click", function (e) {
      e.preventDefault();
      openProductModal(null);
    });
  }

  function loadOrders() {
    if (!document.querySelector('[data-admin-page="orders"]')) {
      return;
    }
    global.api.admin.listOrders().then(function (res) {
      if (!res.ok) {
        return;
      }
      var tbody = document.querySelector(".arena-admin-table tbody");
      if (!tbody) {
        return;
      }
      var rows = (res.data && res.data.data) || [];
      tbody.innerHTML = rows
        .map(function (o) {
          return (
            "<tr><td>#" +
            esc(o.order_number || o.id) +
            "</td><td>" +
            esc(o.user_id) +
            "</td><td>$" +
            esc(o.total) +
            '</td><td><select class="arena-order-status" data-id="' +
            o.id +
            '"><option' +
            (o.status === "pending" ? " selected" : "") +
            '>pending</option><option' +
            (o.status === "paid" ? " selected" : "") +
            '>paid</option><option' +
            (o.status === "shipped" ? " selected" : "") +
            '>shipped</option><option' +
            (o.status === "delivered" ? " selected" : "") +
            '>delivered</option><option' +
            (o.status === "cancelled" ? " selected" : "") +
            ">cancelled</option></select></td></tr>"
          );
        })
        .join("");
      tbody.querySelectorAll(".arena-order-status").forEach(function (sel) {
        sel.addEventListener("change", function () {
          global.api.admin
            .updateOrderStatus(sel.getAttribute("data-id"), { status: sel.value })
            .then(function (r) {
              if (!r.ok) {
                alert((r.data && r.data.message) || "Update failed.");
              }
            });
        });
      });
    });
  }

  function loadTournaments() {
    if (!document.querySelector('[data-admin-page="tournaments"]')) {
      return;
    }
    global.api.admin.listTournaments().then(function (res) {
      if (!res.ok) {
        return;
      }
      var tbody = document.querySelector(".arena-admin-table tbody");
      if (!tbody) {
        return;
      }
      var rows = (res.data && res.data.data) || res.data || [];
      if (!Array.isArray(rows)) {
        rows = [];
      }
      tbody.innerHTML = rows
        .map(function (t) {
          return (
            "<tr><td>" +
            esc(t.name) +
            "</td><td>" +
            esc(t.status) +
            "</td><td>" +
            esc(t.format) +
            '</td><td><a href="tournament-edit.html?id=' +
            t.id +
            '">Edit</a></td></tr>"
          );
        })
        .join("");
    });
  }

  function loadMatches() {
    if (!document.querySelector('[data-admin-page="matches"]')) {
      return;
    }
    global.api.admin.listMatches().then(function (res) {
      if (!res.ok) {
        return;
      }
      var tbody = document.querySelector(".arena-admin-table tbody");
      if (!tbody) {
        return;
      }
      var rows = (res.data && res.data.data) || res.data || [];
      if (!Array.isArray(rows)) {
        rows = [];
      }
      tbody.innerHTML = rows
        .map(function (m) {
          return (
            "<tr><td>#" +
            m.id +
            "</td><td>T" +
            esc(m.tournament_id) +
            "</td><td>" +
            esc(m.status) +
            '</td><td><a href="match-edit.html?id=' +
            m.id +
            '">Edit</a></td></tr>"
          );
        })
        .join("");
    });
  }

  function loadDisputes() {
    if (!document.querySelector('[data-admin-page="disputes"]')) {
      return;
    }
    global.api.admin.listDisputes().then(function (res) {
      if (!res.ok) {
        return;
      }
      var tbody = document.querySelector(".arena-admin-table tbody");
      if (!tbody) {
        return;
      }
      var rows = (res.data && res.data.data) || res.data || [];
      if (!Array.isArray(rows)) {
        rows = [];
      }
      tbody.innerHTML = rows
        .map(function (d) {
          return (
            "<tr><td>#" +
            d.id +
            "</td><td>" +
            esc(d.issue_type) +
            "</td><td>" +
            esc(d.status) +
            '</td><td><button type="button" class="arena-admin-btn arena-admin-btn-sm arena-resolve-dispute" data-id="' +
            d.id +
            '">Resolve</button></td></tr>"
          );
        })
        .join("");
      tbody.querySelectorAll(".arena-resolve-dispute").forEach(function (btn) {
        btn.addEventListener("click", function () {
          var id = btn.getAttribute("data-id");
          var note = prompt("Resolution notes:");
          if (note === null) {
            return;
          }
          var t1 = prompt("Team 1 score (optional):", "");
          var t2 = prompt("Team 2 score (optional):", "");
          var winner = prompt("Winner team ID (optional):", "");
          var payload = { resolution_note: note, status: "resolved" };
          if (t1 !== "" && t2 !== "") {
            payload.team1_score = parseInt(t1, 10) || 0;
            payload.team2_score = parseInt(t2, 10) || 0;
          }
          if (winner) {
            payload.winner_id = parseInt(winner, 10);
          }
          global.api.admin
            .resolveDispute(id, payload)
            .then(function (r) {
              if (r.ok) {
                loadDisputes();
              } else {
                alert((r.data && r.data.message) || "Failed.");
              }
            });
        });
      });
    });
  }

  function loadGameSuggestions() {
    if (!document.querySelector('[data-admin-page="game-suggestions"]')) {
      return;
    }
    global.api.admin.listGameSuggestions().then(function (res) {
      if (!res.ok) {
        return;
      }
      var tbody = document.querySelector(".arena-admin-table tbody");
      if (!tbody) {
        return;
      }
      var rows = (res.data && res.data.data) || res.data || [];
      if (!Array.isArray(rows)) {
        rows = [];
      }
      tbody.innerHTML = rows
        .map(function (s) {
          return (
            "<tr><td>" +
            esc(s.name) +
            "</td><td>" +
            esc(s.status) +
            '</td><td><button type="button" class="arena-approve-sug" data-id="' +
            s.id +
            '">Approve</button> <button type="button" class="arena-reject-sug" data-id="' +
            s.id +
            '">Reject</button></td></tr>"
          );
        })
        .join("");
      tbody.querySelectorAll(".arena-approve-sug").forEach(function (btn) {
        btn.addEventListener("click", function () {
          global.api.admin.approveGameSuggestion(btn.getAttribute("data-id")).then(function () {
            loadGameSuggestions();
          });
        });
      });
      tbody.querySelectorAll(".arena-reject-sug").forEach(function (btn) {
        btn.addEventListener("click", function () {
          global.api.admin
            .rejectGameSuggestion(btn.getAttribute("data-id"), { admin_notes: "Rejected" })
            .then(function () {
              loadGameSuggestions();
            });
        });
      });
    });
  }

  function loadCoupons() {
    if (!document.querySelector('[data-admin-page="coupons"]')) {
      return;
    }
    global.api.admin.listCoupons().then(function (res) {
      if (!res.ok) {
        return;
      }
      var tbody = document.querySelector(".arena-admin-table tbody");
      if (!tbody) {
        return;
      }
      var rows = (res.data && res.data.data) || res.data || [];
      if (!Array.isArray(rows)) {
        rows = [];
      }
      tbody.innerHTML = rows
        .map(function (c) {
          var typeLabel = c.type === "percentage" ? "%" : "Fixed";
          var val =
            c.type === "percentage" ? c.value + "%" : "$" + c.value;
          return (
            "<tr><td>" +
            esc(c.code) +
            "</td><td>" +
            esc(typeLabel) +
            "</td><td>" +
            esc(val) +
            "</td><td>$" +
            esc(c.min_order_amount) +
            "</td><td>" +
            esc(c.used_count) +
            "</td><td>" +
            esc(c.max_uses != null ? c.max_uses : "—") +
            "</td><td>" +
            esc((c.expires_at || "").slice(0, 10)) +
            '</td><td><span class="arena-admin-badge arena-admin-badge-' +
            (c.status === "active" ? "active" : "inactive") +
            '">' +
            esc(c.status) +
            '</span></td><td class="arena-admin-actions">' +
            '<button type="button" class="arena-admin-btn arena-admin-btn-secondary arena-admin-btn-sm arena-coupon-toggle" data-id="' +
            c.id +
            '" data-status="' +
            esc(c.status) +
            '">Toggle</button> ' +
            '<button type="button" class="arena-admin-btn arena-admin-btn-danger arena-admin-btn-sm arena-coupon-del" data-id="' +
            c.id +
            '">Delete</button></td></tr>"
          );
        })
        .join("");
      tbody.querySelectorAll(".arena-coupon-toggle").forEach(function (btn) {
        btn.addEventListener("click", function () {
          var id = btn.getAttribute("data-id");
          var next =
            btn.getAttribute("data-status") === "active" ? "inactive" : "active";
          global.api.admin.updateCoupon(id, { status: next }).then(function () {
            loadCoupons();
          });
        });
      });
      tbody.querySelectorAll(".arena-coupon-del").forEach(function (btn) {
        btn.addEventListener("click", function () {
          if (!confirm("Delete this coupon?")) {
            return;
          }
          global.api.admin.deleteCoupon(btn.getAttribute("data-id")).then(function () {
            loadCoupons();
          });
        });
      });
    });

    var modal = document.getElementById("arena-coupon-modal");
    var createBtn = document.querySelector("[data-confirm-coupon]");
    if (createBtn && !createBtn.dataset.arenaBound) {
      createBtn.dataset.arenaBound = "1";
      createBtn.addEventListener("click", function () {
        if (!modal) {
          return;
        }
        var form = modal.querySelector("form");
        if (!form) {
          return;
        }
        var inputs = form.querySelectorAll("input");
        var select = form.querySelector("select");
        var type =
          select && select.selectedIndex === 1 ? "fixed" : "percentage";
        global.api.admin
          .createCoupon({
            code: inputs[0] ? inputs[0].value : "",
            type: type,
            value: inputs[1] ? inputs[1].value : 0,
            min_order_amount: inputs[2] ? inputs[2].value : 0,
            max_uses: inputs[3] ? inputs[3].value : null,
            expires_at: inputs[4] ? inputs[4].value : null,
            status: "active",
          })
          .then(function (r) {
            if (r.ok) {
              loadCoupons();
              alert("Coupon created.");
            } else {
              alert((r.data && r.data.message) || "Failed.");
            }
          });
      });
    }
  }

  function loadContactMessages() {
    if (!document.querySelector('[data-admin-page="contact-messages"]')) {
      return;
    }
    global.api.admin.listMessages().then(function (res) {
      if (!res.ok) {
        return;
      }
      var tbody = document.querySelector(".arena-admin-table tbody");
      if (!tbody) {
        return;
      }
      var rows = (res.data && res.data.data) || [];
      tbody.innerHTML = rows
        .map(function (m) {
          return (
            "<tr><td>" +
            esc((m.created_at || "").slice(0, 16).replace("T", " ")) +
            "</td><td>" +
            esc(m.name) +
            "</td><td>" +
            esc(m.email) +
            "</td><td>" +
            esc(m.subject) +
            "</td><td>" +
            (m.is_read
              ? '<span class="arena-admin-badge arena-admin-badge-inactive">Read</span>'
              : '<span class="arena-admin-badge arena-admin-badge-active">Unread</span>') +
            '</td><td><button type="button" class="arena-admin-btn arena-admin-btn-sm arena-msg-read" data-id="' +
            m.id +
            '">Mark read</button></td></tr>"
          );
        })
        .join("");
      tbody.querySelectorAll(".arena-msg-read").forEach(function (btn) {
        btn.addEventListener("click", function () {
          global.api.admin.markMessageRead(btn.getAttribute("data-id")).then(function () {
            loadContactMessages();
          });
        });
      });
    });
  }

  function bindBulkEmail() {
    var form = document.getElementById("arena-bulk-email-form");
    if (!form) {
      return;
    }
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var audience = form.querySelector('input[name="audience"]:checked');
      global.api.admin
        .bulkEmail({
          subject: form.querySelector('[name="subject"]').value,
          body: form.querySelector("textarea").value,
          audience: audience ? audience.value : "all",
        })
        .then(function (res) {
          if (res.ok) {
            alert("Queued for " + res.data.recipient_count + " recipients.");
          } else {
            alert((res.data && res.data.message) || "Failed.");
          }
        });
    });
  }

  function init() {
    requireAdmin().then(function (ok) {
      if (!ok) {
        return;
      }
      loadAnalytics();
      loadUsers();
      loadProducts();
      bindProductsAdmin();
      loadOrders();
      loadTournaments();
      loadMatches();
      loadDisputes();
      loadGameSuggestions();
      loadCoupons();
      loadContactMessages();
      bindBulkEmail();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(typeof window !== "undefined" ? window : this);
