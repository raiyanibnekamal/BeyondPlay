/**
 * Arena admin — extended CRUD (D1–D4, blog, games, notifications).
 * Requires admin-connect.js (requireAdmin).
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

  function queryId() {
    var p = new URLSearchParams(global.location.search);
    return p.get("id") ? parseInt(p.get("id"), 10) : null;
  }

  function loadDashboard() {
    if (!document.querySelector('[data-admin-page="dashboard"]')) {
      return;
    }
    global.api.admin.getStats().then(function (res) {
      if (!res.ok || !res.data.totals) {
        return;
      }
      var t = res.data.totals;
      var vals = [
        t.users,
        t.active_tournaments,
        t.matches_today,
        t.orders,
        t.open_disputes,
        t.pending_suggestions,
      ];
      document.querySelectorAll(".arena-admin-stat h3").forEach(function (el, i) {
        if (vals[i] != null) {
          el.textContent = vals[i];
        }
      });
      var tbody = document.querySelector(".arena-admin-card .arena-admin-table tbody");
      var recent = res.data.recent_registrations || [];
      if (tbody && recent.length) {
        tbody.innerHTML = recent
          .map(function (r) {
            var date = r.registered_at ? String(r.registered_at).slice(0, 10) : "—";
            return (
              "<tr><td>" +
              esc(r.username) +
              "</td><td>" +
              esc(r.tournament) +
              "</td><td>" +
              esc(date) +
              "</td></tr>"
            );
          })
          .join("");
      }
    });
  }

  function loadBanners() {
    if (!document.querySelector('[data-admin-page="banners"]')) {
      return;
    }
    global.api.admin.listBanners().then(function (res) {
      if (!res.ok) {
        return;
      }
      var tbody = document.querySelector(".arena-admin-table tbody");
      if (!tbody) {
        return;
      }
      var rows = (res.data && res.data.data) || [];
      tbody.innerHTML = rows
        .map(function (b) {
          return (
            "<tr><td>" +
            esc(b.title) +
            "</td><td>" +
            (b.is_active ? "Active" : "Off") +
            '</td><td><button type="button" class="arena-admin-btn arena-admin-btn-sm arena-del-banner" data-id="' +
            b.id +
            '">Delete</button></td></tr>"
          );
        })
        .join("");
      tbody.querySelectorAll(".arena-del-banner").forEach(function (btn) {
        btn.addEventListener("click", function () {
          if (!confirm("Delete banner?")) {
            return;
          }
          global.api.admin.deleteBanner(btn.getAttribute("data-id")).then(function () {
            loadBanners();
          });
        });
      });
    });
    var createBtn = document.querySelector("[data-open-modal='arena-banner-modal']");
    if (createBtn && !createBtn.dataset.arenaBound) {
      createBtn.dataset.arenaBound = "1";
      document.querySelector("[data-confirm-banner]")?.addEventListener("click", function () {
        var modal = document.getElementById("arena-banner-modal");
        var form = modal && modal.querySelector("form");
        if (!form) {
          return;
        }
        var inputs = form.querySelectorAll("input, textarea");
        global.api.admin
          .createBanner({
            title: inputs[0] ? inputs[0].value : "Announcement",
            message: inputs[1] ? inputs[1].value : "",
            link: inputs[2] ? inputs[2].value : null,
            is_active: true,
          })
          .then(function (r) {
            if (r.ok) {
              loadBanners();
              alert("Banner created.");
            }
          });
      });
    }
  }

  function loadSponsorsAdmin() {
    if (!document.querySelector('[data-admin-page="sponsors"]')) {
      return;
    }
    global.api.admin.listSponsors().then(function (res) {
      if (!res.ok) {
        return;
      }
      var grid = document.querySelector(".arena-admin-sponsor-grid");
      if (!grid) {
        return;
      }
      var rows = (res.data && res.data.data) || res.data || [];
      if (!Array.isArray(rows)) {
        rows = [];
      }
      grid.innerHTML = rows
        .map(function (s) {
          return (
            '<div class="arena-admin-sponsor-card"><img src="' +
            esc(s.logo || "../assets/img/logo.svg") +
            '" alt=""><h4>' +
            esc(s.name) +
            '</h4><button type="button" class="arena-admin-btn arena-admin-btn-sm arena-del-sponsor" data-id="' +
            s.id +
            '">Delete</button></div>'
          );
        })
        .join("");
      grid.querySelectorAll(".arena-del-sponsor").forEach(function (btn) {
        btn.addEventListener("click", function () {
          if (!confirm("Delete sponsor?")) {
            return;
          }
          global.api.admin.deleteSponsor(btn.getAttribute("data-id")).then(function () {
            loadSponsorsAdmin();
          });
        });
      });
    });
  }

  function loadBlog() {
    if (!document.querySelector('[data-admin-page="blog"]')) {
      return;
    }
    global.api.admin.listPosts().then(function (res) {
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
            esc(p.title) +
            "</td><td>" +
            esc(p.status) +
            '</td><td><a href="blog-edit.html?id=' +
            p.id +
            '">Edit</a> <button type="button" class="arena-publish-post" data-id="' +
            p.id +
            '">Publish</button> <button type="button" class="arena-del-post" data-id="' +
            p.id +
            '">Delete</button></td></tr>"
          );
        })
        .join("");
      tbody.querySelectorAll(".arena-publish-post").forEach(function (btn) {
        btn.addEventListener("click", function () {
          global.api.admin.publishPost(btn.getAttribute("data-id")).then(function () {
            loadBlog();
          });
        });
      });
      tbody.querySelectorAll(".arena-del-post").forEach(function (btn) {
        btn.addEventListener("click", function () {
          global.api.admin.deletePost(btn.getAttribute("data-id")).then(function () {
            loadBlog();
          });
        });
      });
    });
  }

  function bindBlogEdit() {
    if (!document.querySelector('[data-admin-page="blog-edit"]')) {
      return;
    }
    var form = document.querySelector(".arena-admin-form");
    if (!form || form.dataset.arenaBlogBound) {
      return;
    }
    form.dataset.arenaBlogBound = "1";
    var id = queryId();
    var title = form.querySelector('input[type="text"]');
    var area = form.querySelector("textarea");
    if (!id) {
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var t = title ? title.value.trim() : "";
        if (!t) {
          alert("Title is required.");
          return;
        }
        global.api.admin
          .createPost({
            title: t,
            content: area ? area.value : "",
            status: "draft",
          })
          .then(function (r) {
            if (r.ok && r.data && r.data.id) {
              global.location.href = "blog-edit.html?id=" + r.data.id;
              return;
            }
            alert((r.data && r.data.message) || "Could not create post.");
          });
      });
      return;
    }
    global.api.admin.listPosts({ per_page: 100 }).then(function (res) {
      var rows = (res.data && res.data.data) || [];
      var post = rows.find(function (p) {
        return p.id === id;
      });
      if (!post) {
        return;
      }
      var form = document.querySelector(".arena-admin-form");
      if (!form) {
        return;
      }
      var title = form.querySelector('input[type="text"]');
      var area = form.querySelector("textarea");
      if (title) {
        title.value = post.title || "";
      }
      if (area) {
        area.value = post.content || post.excerpt || "";
      }
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        global.api.admin
          .updatePost(id, {
            title: title ? title.value : post.title,
            content: area ? area.value : "",
          })
          .then(function (r) {
            alert(r.ok ? "Post saved." : (r.data && r.data.message) || "Failed.");
          });
      });
    });
  }

  function loadGamesAdmin() {
    if (!document.querySelector('[data-admin-page="games"]')) {
      return;
    }
    global.api.admin.listGames().then(function (res) {
      if (!res.ok) {
        return;
      }
      var tbody = document.querySelector(".arena-admin-table tbody");
      if (!tbody) {
        return;
      }
      var rows = (res.data && res.data.data) || [];
      tbody.innerHTML = rows
        .map(function (g) {
          return (
            "<tr><td>" +
            esc(g.name) +
            "</td><td>" +
            esc(g.genre) +
            "</td><td>" +
            esc(g.status) +
            '</td><td><button type="button" class="arena-del-game" data-id="' +
            g.id +
            '">Delete</button></td></tr>"
          );
        })
        .join("");
      tbody.querySelectorAll(".arena-del-game").forEach(function (btn) {
        btn.addEventListener("click", function () {
          global.api.admin.deleteGame(btn.getAttribute("data-id")).then(function () {
            loadGamesAdmin();
          });
        });
      });
    });
    var addBtn = document.querySelector(
      '[data-admin-page="games"] .arena-admin-btn-primary'
    );
    if (addBtn && !addBtn.dataset.arenaBound) {
      addBtn.dataset.arenaBound = "1";
      addBtn.addEventListener("click", function () {
        var name = prompt("Game name:");
        if (!name || !name.trim()) {
          return;
        }
        var genre = prompt("Genre (optional):") || "Other";
        global.api.admin
          .createGame({ name: name.trim(), genre: genre, status: "active" })
          .then(function (r) {
            if (r.ok) {
              loadGamesAdmin();
              alert("Game added.");
            } else {
              alert((r.data && r.data.message) || "Failed.");
            }
          });
      });
    }
  }

  function formatToApi(label) {
    var map = {
      "Single Elimination": "single_elimination",
      "Double Elimination": "double_elimination",
      "Round Robin": "round_robin",
      Swiss: "round_robin",
    };
    return map[label] || "single_elimination";
  }

  function dtLocal(val) {
    if (!val) {
      return "";
    }
    var d = new Date(val);
    if (isNaN(d.getTime())) {
      return "";
    }
    var pad = function (n) {
      return n < 10 ? "0" + n : String(n);
    };
    return (
      d.getFullYear() +
      "-" +
      pad(d.getMonth() + 1) +
      "-" +
      pad(d.getDate()) +
      "T" +
      pad(d.getHours()) +
      ":" +
      pad(d.getMinutes())
    );
  }

  function readTournamentForm(form) {
    var texts = form.querySelectorAll('input[type="text"]');
    var numbers = form.querySelectorAll('input[type="number"]');
    var datetimes = form.querySelectorAll('input[type="datetime-local"]');
    var selects = form.querySelectorAll("select");
    var desc = form.querySelector("textarea");
    return {
      name: texts[0] ? texts[0].value.trim() : "",
      game_id: selects[0] ? parseInt(selects[0].value, 10) : null,
      description: desc ? desc.value : "",
      entry_fee: numbers[0] ? numbers[0].value : 0,
      prize_pool: numbers[1] ? numbers[1].value : 0,
      max_participants: numbers[2] ? numbers[2].value : 32,
      format: selects[1] ? formatToApi(selects[1].value) : "single_elimination",
      registration_start: datetimes[0] ? datetimes[0].value : null,
      registration_end: datetimes[1] ? datetimes[1].value : null,
      start_date: datetimes[2] ? datetimes[2].value : null,
      end_date: datetimes[3] ? datetimes[3].value : null,
      checkin_minutes_before: numbers[3] ? numbers[3].value : 15,
      cover_image: texts[1] ? texts[1].value : null,
      status: selects[2]
        ? String(selects[2].value).toLowerCase().replace(/\s+/g, "_")
        : "draft",
    };
  }

  function fillTournamentForm(form, t) {
    var texts = form.querySelectorAll('input[type="text"]');
    var numbers = form.querySelectorAll('input[type="number"]');
    var datetimes = form.querySelectorAll('input[type="datetime-local"]');
    var selects = form.querySelectorAll("select");
    var desc = form.querySelector("textarea");
    if (texts[0]) {
      texts[0].value = t.name || "";
    }
    if (desc) {
      desc.value = t.description || "";
    }
    if (numbers[0]) {
      numbers[0].value = t.entry_fee != null ? t.entry_fee : "";
    }
    if (numbers[1]) {
      numbers[1].value = t.prize_pool != null ? t.prize_pool : "";
    }
    if (numbers[2]) {
      numbers[2].value = t.max_participants != null ? t.max_participants : "";
    }
    if (numbers[3]) {
      numbers[3].value = t.checkin_minutes_before != null ? t.checkin_minutes_before : "";
    }
    if (texts[1]) {
      texts[1].value = t.cover_image || "";
    }
    if (datetimes[0]) {
      datetimes[0].value = dtLocal(t.registration_start);
    }
    if (datetimes[1]) {
      datetimes[1].value = dtLocal(t.registration_end);
    }
    if (datetimes[2]) {
      datetimes[2].value = dtLocal(t.start_date);
    }
    if (datetimes[3]) {
      datetimes[3].value = dtLocal(t.end_date);
    }
    if (selects[1] && t.format) {
      selects[1].value = t.format.replace(/_/g, " ");
    }
    if (selects[2] && t.status) {
      selects[2].value = t.status.charAt(0).toUpperCase() + t.status.slice(1);
    }
    if (selects[0] && t.game_id) {
      selects[0].value = String(t.game_id);
    }
  }

  function bindTournamentEdit() {
    if (!document.querySelector('[data-admin-page="tournament-edit"]')) {
      return;
    }
    var form = document.getElementById("tournament-form") || document.querySelector(".arena-admin-form");
    if (!form || form.dataset.arenaTournamentBound) {
      return;
    }
    form.dataset.arenaTournamentBound = "1";
    var tournamentId = queryId();

    global.api.admin.listGames({ per_page: 100 }).then(function (gr) {
      var games = (gr.data && gr.data.data) || [];
      var gameSel = form.querySelector("select");
      if (gameSel && games.length) {
        gameSel.innerHTML = games
          .map(function (g) {
            return '<option value="' + g.id + '">' + esc(g.name) + "</option>";
          })
          .join("");
      }
      if (tournamentId) {
        global.api.admin.listTournaments({ per_page: 200 }).then(function (res) {
          var rows = (res.data && res.data.data) || [];
          var t = rows.find(function (x) {
            return String(x.id) === String(tournamentId);
          });
          if (t) {
            fillTournamentForm(form, t);
          }
        });
      } else {
        form.querySelectorAll("input, textarea, select").forEach(function (el) {
          if (el.tagName === "SELECT") {
            return;
          }
          el.value = "";
        });
      }
    });

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var data = readTournamentForm(form);
      if (!data.name || !data.game_id || !data.start_date) {
        alert("Name, game, and tournament start date are required.");
        return;
      }
      var req = tournamentId
        ? global.api.admin.updateTournament(tournamentId, data)
        : global.api.admin.createTournament(data);
      req.then(function (r) {
        if (r.ok) {
          alert(tournamentId ? "Tournament updated." : "Tournament created.");
          if (!tournamentId) {
            global.location.href = "tournaments.html";
          }
          return;
        }
        var msg = (r.data && r.data.message) || "Failed to save tournament.";
        if (r.data && r.data.errors) {
          msg = Object.values(r.data.errors).flat().join(" ");
        }
        alert(msg);
      });
    });

    if (tournamentId) {
      var genBtn = form.querySelector("[data-generate-bracket], .arena-generate-bracket");
      if (!genBtn) {
        Array.prototype.forEach.call(form.querySelectorAll("button"), function (btn) {
          if (/bracket/i.test(btn.textContent)) {
            genBtn = btn;
          }
        });
      }
      if (genBtn && !genBtn.dataset.arenaBound) {
        genBtn.dataset.arenaBound = "1";
        genBtn.addEventListener("click", function (ev) {
          ev.preventDefault();
          global.api.admin.generateBracket(tournamentId).then(function (r) {
            alert(r.ok ? "Bracket generated." : (r.data && r.data.message) || "Failed.");
          });
        });
      }
      var rulesForm = document.getElementById("arena-rules-form");
      if (rulesForm) {
        rulesForm.addEventListener("submit", function (e) {
          e.preventDefault();
          var tas = rulesForm.querySelectorAll("textarea");
          global.api.admin
            .saveTournamentRules(tournamentId, {
              format_details: tas[0] ? tas[0].value : "",
              scoring_rules: tas[1] ? tas[1].value : "",
            })
            .then(function (r) {
              alert(r.ok ? "Rules saved." : "Failed.");
            });
        });
      }
    }
  }

  function bindMatchEdit() {
    if (!document.querySelector('[data-admin-page="match-edit"]')) {
      return;
    }
    var id = queryId();
    if (!id) {
      return;
    }
    global.api.matches.get(id).then(function (res) {
      if (!res.ok) {
        return;
      }
      var m = res.data;
      var form = document.querySelector(".arena-admin-form");
      if (!form) {
        return;
      }
      var nums = form.querySelectorAll('input[type="number"]');
      if (nums[0]) {
        nums[0].value = m.team1_score != null ? m.team1_score : 0;
      }
      if (nums[1]) {
        nums[1].value = m.team2_score != null ? m.team2_score : 0;
      }
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var winnerSel = form.querySelector("select");
        global.api.admin
          .updateMatchScore(id, {
            team1_score: nums[0] ? parseInt(nums[0].value, 10) : 0,
            team2_score: nums[1] ? parseInt(nums[1].value, 10) : 0,
            winner_id: winnerSel && winnerSel.value ? parseInt(winnerSel.value, 10) : null,
            status: "completed",
          })
          .then(function (r) {
            alert(r.ok ? "Match updated." : (r.data && r.data.message) || "Failed.");
          });
      });
      var replayInput = form.querySelector('input[type="url"], input[name="replay_url"]');
      if (replayInput) {
        var replayBtn = document.createElement("button");
        replayBtn.type = "button";
        replayBtn.className = "arena-admin-btn arena-admin-btn-secondary mt-10";
        replayBtn.textContent = "Save replay URL";
        replayBtn.addEventListener("click", function () {
          global.api.admin
            .addMatchReplay(id, { replay_url: replayInput.value })
            .then(function (r) {
              alert(r.ok ? "Replay saved." : "Failed.");
            });
        });
        replayInput.parentElement.appendChild(replayBtn);
      }
    });
  }

  function bindAdminNotifications() {
    var form = document.getElementById("arena-notif-form");
    if (!form || form.dataset.arenaBound) {
      return;
    }
    form.dataset.arenaBound = "1";
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var title = form.querySelector('[name="title"]') || form.querySelectorAll("input")[1];
      var body = form.querySelector("textarea");
      global.api.admin
        .sendNotification({
          title: title ? title.value : "BeyondPlay",
          message: body ? body.value : "",
        })
        .then(function (r) {
          alert(
            r.ok
              ? "Sent to " + (r.data.recipient_count || 0) + " users."
              : (r.data && r.data.message) || "Failed."
          );
        });
    });
  }

  function enhanceUsersPromote() {
    if (!document.querySelector('[data-admin-page="users"]')) {
      return;
    }
    document.querySelectorAll(".arena-admin-table tbody tr").forEach(function (row) {
      if (row.querySelector(".arena-promote-user")) {
        return;
      }
      var banBtn = row.querySelector(".arena-user-status");
      if (!banBtn) {
        return;
      }
      var id = banBtn.getAttribute("data-id");
      var roleCell = row.cells[2];
      if (roleCell && /admin/i.test(roleCell.textContent)) {
        return;
      }
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "arena-admin-btn arena-admin-btn-sm arena-promote-user ms-1";
      btn.textContent = "Promote";
      btn.setAttribute("data-id", id);
      btn.addEventListener("click", function () {
        if (!confirm("Promote this user to admin?")) {
          return;
        }
        global.api.admin.promoteUser(id).then(function (r) {
          alert(r.ok ? "Promoted." : (r.data && r.data.message) || "Failed.");
          if (r.ok) {
            global.location.reload();
          }
        });
      });
      banBtn.parentElement.appendChild(btn);
    });
  }

  function bindAdminSettings() {
    if (!document.querySelector('[data-admin-page="settings"]')) {
      return;
    }
    var form = document.getElementById("arena-settings-form");
    if (!form || form.dataset.arenaSettingsBound) {
      return;
    }
    form.dataset.arenaSettingsBound = "1";
    var keys = [
      "site_name",
      "site_url",
      "contact_email",
      "twitter_url",
      "discord_url",
      "youtube_url",
      "instagram_url",
    ];
    var textInputs = form.querySelectorAll(
      ".arena-admin-input[type='text'], .arena-admin-input[type='url'], .arena-admin-input[type='email']"
    );
    var maintenance = form.querySelector("#maintenance-mode");

    global.api.admin.getSettings().then(function (res) {
      if (!res.ok || !res.data.settings) {
        return;
      }
      var s = res.data.settings;
      keys.forEach(function (key, i) {
        if (textInputs[i] && s[key] != null) {
          textInputs[i].value = s[key];
        }
      });
      if (maintenance) {
        maintenance.checked = s.maintenance_mode === "1" || s.maintenance_mode === true;
      }
    });

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var payload = {};
      keys.forEach(function (key, i) {
        if (textInputs[i]) {
          payload[key] = textInputs[i].value;
        }
      });
      payload.maintenance_mode = maintenance ? maintenance.checked : false;
      global.api.admin.updateSettings(payload).then(function (r) {
        alert(r.ok ? (r.data.message || "Settings saved.") : (r.data && r.data.message) || "Failed.");
      });
    });
  }

  function runExtra() {
    bindAdminSettings();
    loadDashboard();
    loadBanners();
    loadSponsorsAdmin();
    loadBlog();
    bindBlogEdit();
    loadGamesAdmin();
    bindTournamentEdit();
    bindMatchEdit();
    bindAdminNotifications();
    setTimeout(enhanceUsersPromote, 500);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", runExtra);
  } else {
    runExtra();
  }
})(typeof window !== "undefined" ? window : this);
