/* Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
/**
 * Arena user dashboard — profile, tournaments, orders, notifications.
 * Requires api.js + login token.
 */
(function (global) {
  "use strict";

  if (!global.api) {
    return;
  }

  var USER_PAGES = [
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

  function page() {
    return (global.location.pathname.split("/").pop() || "").toLowerCase();
  }

  function esc(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;");
  }

  function mediaSrc(url) {
    if (global.api && typeof global.api.resolveMediaUrl === "function") {
      return global.api.resolveMediaUrl(url) || "assets/img/team/1-1.png";
    }
    return global.ArenaEscape
      ? global.ArenaEscape.safeMediaSrc(url, "assets/img/team/1-1.png")
      : esc(url || "assets/img/team/1-1.png");
  }

  function requireAuth() {
    if (!global.api.getAuthToken()) {
      global.location.href = "login.html?redirect=" + encodeURIComponent(page());
      return false;
    }
    return true;
  }

  function bindAvatarUpload() {
    if (page() !== "my-profile.html") {
      return;
    }
    var btn = document.querySelector(".arena-avatar-btn");
    if (!btn || btn.dataset.arenaBound) {
      return;
    }
    btn.dataset.arenaBound = "1";
    var input = document.createElement("input");
    input.type = "file";
    input.accept = "image/jpeg,image/png,image/webp";
    input.style.display = "none";
    document.body.appendChild(input);
    btn.addEventListener("click", function () {
      input.click();
    });
    input.addEventListener("change", function () {
      if (!input.files || !input.files[0]) {
        return;
      }
      global.api.user.uploadAvatar(input.files[0]).then(function (res) {
        if (res.ok && res.data && res.data.avatar) {
          var img = document.querySelector(".arena-avatar-wrap img");
          var src = mediaSrc(res.data.avatar);
          if (img) {
            img.src = src + (src.indexOf("?") >= 0 ? "&" : "?") + "t=" + Date.now();
          }
          alert("Avatar updated.");
        } else {
          alert((res.data && res.data.message) || "Upload failed.");
        }
      });
    });
  }

  function loadProfile() {
    if (page() !== "my-profile.html") {
      return;
    }
    global.api.user.getProfile().then(function (res) {
      if (!res.ok || !res.data || !res.data.user) {
        return;
      }
      var user = res.data.user;
      var form = document.getElementById("profile-form");
      if (form) {
        var u = form.querySelector('[name="username"]');
        var g = form.querySelector('[name="gaming_id"]');
        var b = form.querySelector('[name="bio"]');
        var c = form.querySelector('[name="country"]');
        if (u) {
          u.value = user.username || "";
        }
        if (g) {
          g.value = user.gaming_id || "";
        }
        if (b) {
          b.value = user.bio || "";
        }
        if (c && user.country) {
          c.value = user.country;
        }
      }
      var avatar = document.querySelector(".arena-avatar-wrap img");
      if (avatar && user.avatar) {
        avatar.src = mediaSrc(user.avatar);
      }
      var stats = document.querySelectorAll(".arena-dash-stat h4");
      if (stats[0]) {
        stats[0].textContent = user.registrations_count != null ? user.registrations_count : "0";
      }
      global.api.user.getStats().then(function (st) {
        if (!st.ok || !st.data) {
          return;
        }
        var s = st.data.totals || st.data;
        if (stats[1] && s.wins != null) {
          stats[1].textContent = s.wins;
        }
        if (stats[2] && s.losses != null) {
          stats[2].textContent = s.losses;
        }
        if (stats[3] && s.win_rate != null) {
          stats[3].textContent = s.win_rate + "% WR";
        }
      });
      loadAchievementsBadges();
      bindEmailVerificationBanner(res.data);
    });
  }

  function bindEmailVerificationBanner(authPayload) {
    var verified =
      authPayload.email_verified === true ||
      (authPayload.user && authPayload.user.email_verified_at);
    if (verified) {
      return;
    }
    var host = document.querySelector(".arena-avatar-wrap")?.parentElement || document.querySelector(".space .container");
    if (!host || document.getElementById("arena-verify-banner")) {
      return;
    }
    host.insertAdjacentHTML(
      "afterbegin",
      '<div id="arena-verify-banner" class="alert alert-warning mb-30" style="background:rgba(255,193,7,.15);border:1px solid rgba(255,193,7,.4);padding:16px;border-radius:8px">' +
        "<strong>Verify your email</strong> — check your inbox for the verification link. " +
        '<button type="button" class="th-btn btn-sm ms-2" id="arena-resend-verify">Resend email</button></div>'
    );
    var btn = document.getElementById("arena-resend-verify");
    if (btn) {
      btn.addEventListener("click", function () {
        global.api.auth.resendVerification().then(function (r) {
          alert((r.data && r.data.message) || (r.ok ? "Email sent." : "Could not send."));
        });
      });
    }
  }

  function loadAchievementsBadges() {
    global.api.user.getAchievements().then(function (res) {
      if (!res.ok) {
        return;
      }
      var list = (res.data && res.data.achievements) || [];
      var wrap = document.querySelector(".arena-badge-icon")?.parentElement;
      if (!wrap || !list.length) {
        return;
      }
      wrap.innerHTML = list
        .map(function (a) {
          return (
            '<span class="arena-badge-icon" title="' +
            esc(a.name) +
            '"><i class="fas fa-medal"></i></span>'
          );
        })
        .join("");
    });
  }

  function bindProfileSave() {
    var form = document.getElementById("profile-form");
    if (!form || form.dataset.arenaProfileBound) {
      return;
    }
    form.dataset.arenaProfileBound = "1";
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var box = form.querySelector(".form-messages");
      global.api.user
        .updateProfile({
          username: form.querySelector('[name="username"]')?.value,
          gaming_id: form.querySelector('[name="gaming_id"]')?.value,
          bio: form.querySelector('[name="bio"]')?.value,
          country: form.querySelector('[name="country"]')?.value,
        })
        .then(function (res) {
          if (box) {
            box.textContent = res.ok
              ? (res.data && res.data.message) || "Profile saved."
              : (res.data && res.data.message) || "Could not save profile.";
            box.style.color = res.ok ? "var(--theme-color)" : "#ff6b6b";
          }
        });
    });
  }

  function bindCreateTournament() {
    if (page() !== "my-tournaments.html") {
      return;
    }
    var toggle = document.getElementById("arena-show-create-tournament");
    var panel = document.getElementById("arena-create-tournament-panel");
    var form = document.getElementById("arena-create-tournament-form");
    var gameSel = document.getElementById("arena-create-game");
    if (!toggle || !panel || !form) {
      return;
    }
    global.api.games.list({ per_page: 50 }).then(function (res) {
      var games = (res.data && res.data.data) || [];
      if (gameSel) {
        gameSel.innerHTML = games
          .map(function (g) {
            return '<option value="' + g.id + '">' + esc(g.name) + "</option>";
          })
          .join("");
      }
    });
    toggle.addEventListener("click", function () {
      global.api.auth.me().then(function (res) {
        var roles = (res.data && res.data.roles) || [];
        var user = res.data && res.data.user;
        var isAdmin = roles.indexOf("admin") >= 0 || (user && user.role === "admin");
        if (!isAdmin) {
          alert("Only admins can create tournaments. Contact support to publish an event.");
          return;
        }
        panel.style.display = panel.style.display === "none" ? "block" : "none";
      });
    });
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var msg = document.getElementById("arena-create-tournament-msg");
      var fd = new FormData(form);
      global.api.tournaments
        .create({
          name: fd.get("name"),
          game_id: parseInt(fd.get("game_id"), 10),
          max_participants: parseInt(fd.get("max_participants"), 10),
          prize_pool: fd.get("prize_pool") || 0,
          start_date: fd.get("start_date"),
          description: fd.get("description") || "",
          format: "single_elimination",
        })
        .then(function (r) {
          if (msg) {
            msg.textContent = r.ok
              ? "Tournament created (draft). Browse tournaments when it is published."
              : (r.data && r.data.message) || "Could not create tournament.";
            msg.style.color = r.ok ? "var(--theme-color)" : "#ff6b6b";
          }
          if (r.ok) {
            form.reset();
            panel.style.display = "none";
            loadTournaments();
          }
        });
    });
  }

  function loadTournaments() {
    if (page() !== "my-tournaments.html") {
      return;
    }
    var tbody = document.getElementById("arena-tournaments-tbody") || document.querySelector(".arena-table tbody");
    if (!tbody) {
      return;
    }
    global.api.user.getTournaments().then(function (res) {
      if (!res.ok) {
        return;
      }
      var rows = (res.data && res.data.registrations) || [];
      var empty = document.getElementById("tournaments-empty");
      if (!rows.length) {
        tbody.innerHTML = "";
        if (empty) {
          empty.classList.remove("d-none");
        }
        return;
      }
      if (empty) {
        empty.classList.add("d-none");
      }
      tbody.innerHTML = rows
        .map(function (r) {
          var t = r.tournament || {};
          var game = (t.game && t.game.name) || "—";
          var slug = t.slug || "";
          var status = t.status || r.status || "—";
          return (
            "<tr><td><a href=\"tournament-details.html?slug=" +
            esc(slug) +
            '" class="text-theme">' +
            esc(t.name || "Tournament") +
            "</a></td><td>" +
            esc(game) +
            '</td><td><span class="arena-badge">' +
            esc(status) +
            "</span></td><td>" +
            esc(r.team ? r.team.name : "Solo") +
            '</td><td><a href="tournament-details.html?slug=' +
            esc(slug) +
            '" class="link-btn style2">View</a></td></tr>'
          );
        })
        .join("");
    });
  }

  function loadOrders() {
    if (page() !== "my-orders.html") {
      return;
    }
    var tbody = document.getElementById("arena-orders-tbody") || document.querySelector(".arena-table tbody");
    if (!tbody) {
      return;
    }
    global.api.user.getOrders().then(function (res) {
      if (!res.ok) {
        return;
      }
      var rows = (res.data && res.data.data) || [];
      var empty = document.getElementById("orders-empty");
      if (!rows.length) {
        tbody.innerHTML = "";
        if (empty) {
          empty.classList.remove("d-none");
        }
        return;
      }
      if (empty) {
        empty.classList.add("d-none");
      }
      tbody.innerHTML = rows
        .map(function (o) {
          var names = (o.items || []).map(function (i) {
            return i.product ? i.product.name : "";
          }).filter(Boolean).join(", ");
          return (
            "<tr><td>#" +
            esc(o.order_number || o.id) +
            "</td><td>" +
            esc((o.created_at || "").slice(0, 10)) +
            "</td><td>" +
            esc(names || "—") +
            "</td><td>$" +
            esc(o.total) +
            '</td><td><span class="arena-badge">' +
            esc(o.status) +
            '</span></td><td><a href="shop.html" class="link-btn style2">View Shop</a></td></tr>'
          );
        })
        .join("");
    });
  }

  function notifIcon(type) {
    var map = {
      friend_request: "&#128276;",
      team_invite: "&#128101;",
      tournament_registration: "&#127942;",
      match_scheduled: "&#9876;",
      game_suggestion_approved: "&#9989;",
      order_shipped: "&#9989;",
    };
    return map[type] || "&#128276;";
  }

  function loadNotifications() {
    if (page() !== "my-notifications.html") {
      return;
    }
    var list = document.getElementById("arena-notif-list");
    if (!list) {
      var first = document.querySelector(".arena-notif-item");
      list = first ? first.parentElement : null;
    }
    if (!list) {
      return;
    }
    global.api.user.getNotifications().then(function (res) {
      if (!res.ok) {
        return;
      }
      var rows = (res.data && res.data.data) || [];
      if (!rows.length) {
        list.innerHTML = '<p class="text-center text-theme">No notifications yet.</p>';
        return;
      }
      list.innerHTML = rows
        .map(function (n) {
          return (
            '<div class="arena-notif-item' +
            (n.is_read ? "" : " unread") +
            '"><div class="arena-notif-icon">' +
            notifIcon(n.type) +
            "</div><div><strong>" +
            esc(n.title) +
            "</strong><p class=\"mb-0 mt-5\">" +
            esc(n.message) +
            '</p><span class="arena-notif-time">' +
            esc((n.created_at || "").slice(0, 16).replace("T", " ")) +
            "</span></div></div>"
          );
        })
        .join("");
    });
  }

  function bindMarkAllRead() {
    var btn = document.getElementById("markAllReadBtn");
    if (!btn || btn.dataset.arenaBound) {
      return;
    }
    btn.dataset.arenaBound = "1";
    btn.addEventListener("click", function () {
      global.api.user.markNotificationsRead().then(function (res) {
        if (res.ok) {
          document.querySelectorAll(".arena-notif-item.unread").forEach(function (el) {
            el.classList.remove("unread");
          });
          global.api.user.getNotifications().then(function () {
            if (typeof global.loadNotificationBadge === "function") {
              global.loadNotificationBadge();
            }
          });
        }
      });
    });
  }

  function loadFriends() {
    if (page() !== "friends.html") {
      return;
    }
    ensureSocialTabs();
    var friendsPanel = document.querySelector('[data-arena-panel="friends"]');
    var requestsPanel = document.querySelector('[data-arena-panel="requests"]');
    var sentPanel = document.querySelector('[data-arena-panel="sent"]');
    var challengesPanel = document.querySelector('[data-arena-panel="challenges"]');
    global.api.social.getFriends().then(function (res) {
      if (!res.ok || !friendsPanel) {
        return;
      }
      var list = (res.data && res.data.friends) || [];
      friendsPanel.innerHTML = list.length
        ? list
            .map(function (f) {
              var av = global.ArenaEscape
                ? global.ArenaEscape.safeMediaSrc(f.avatar, "assets/img/team/1-1.png")
                : esc(f.avatar || "assets/img/team/1-1.png");
              return (
                '<div class="arena-friend-card"><img src="' +
                av +
                '" alt=""><div class="flex-grow-1"><strong>' +
                esc(f.username) +
                '</strong><br><span class="arena-status-dot arena-status-offline"></span>Offline</div>' +
                '<button type="button" class="th-btn style2 arena-remove-friend" data-user-id="' +
                esc(f.id) +
                '">Remove</button></div>'
              );
            })
            .join("")
        : '<p class="text-muted">No friends yet. Search players to add friends.</p>';
      friendsPanel.querySelectorAll(".arena-remove-friend").forEach(function (btn) {
        btn.addEventListener("click", function () {
          global.api.social.removeFriend(btn.getAttribute("data-user-id")).then(function () {
            loadFriends();
          });
        });
      });
    });
    global.api.social.getFriendRequests().then(function (res) {
      if (!res.ok || !requestsPanel) {
        return;
      }
      var reqs = (res.data && res.data.requests) || [];
      requestsPanel.innerHTML = reqs.length
        ? reqs
            .map(function (r) {
              var u = r.requester || {};
              var av = global.ArenaEscape
                ? global.ArenaEscape.safeMediaSrc(u.avatar, "assets/img/team/1-1.png")
                : esc(u.avatar || "assets/img/team/1-1.png");
              return (
                '<div class="arena-friend-card"><img src="' +
                av +
                '" alt=""><div class="flex-grow-1"><strong>' +
                esc(u.username) +
                '</strong><br><small class="text-muted">Wants to be your friend</small></div>' +
                '<button type="button" class="th-btn me-2 arena-accept-request" data-id="' +
                esc(r.id) +
                '">Accept</button>' +
                '<button type="button" class="th-btn style2 arena-decline-request" data-id="' +
                esc(r.id) +
                '">Decline</button></div>'
              );
            })
            .join("")
        : '<p class="text-muted">No pending requests.</p>';
      requestsPanel.querySelectorAll(".arena-accept-request").forEach(function (btn) {
        btn.addEventListener("click", function () {
          global.api.social.acceptFriendRequest(btn.getAttribute("data-id")).then(function () {
            loadFriends();
          });
        });
      });
      requestsPanel.querySelectorAll(".arena-decline-request").forEach(function (btn) {
        btn.addEventListener("click", function () {
          global.api.social.declineFriendRequest(btn.getAttribute("data-id")).then(function () {
            loadFriends();
          });
        });
      });
    });

    if (sentPanel) {
      global.api.social.getSentFriendRequests().then(function (res) {
        if (!res.ok) {
          return;
        }
        var sent = (res.data && res.data.requests) || [];
        sentPanel.innerHTML = sent.length
          ? sent
              .map(function (r) {
                var u = r.receiver || {};
                var av = mediaSrc(u.avatar);
                return (
                  '<div class="arena-friend-card"><img src="' +
                  av +
                  '" alt=""><div class="flex-grow-1"><strong>' +
                  esc(u.username) +
                  '</strong><br><small class="text-theme">Request sent — waiting</small></div>' +
                  '<button type="button" class="th-btn style2 arena-cancel-request" data-id="' +
                  esc(r.id) +
                  '">Cancel Request</button></div>'
                );
              })
              .join("")
          : '<p class="text-muted">No pending sent requests.</p>';
        sentPanel.querySelectorAll(".arena-cancel-request").forEach(function (btn) {
          btn.addEventListener("click", function () {
            global.api.social.cancelFriendRequest(btn.getAttribute("data-id")).then(function () {
              loadFriends();
            });
          });
        });
      });
    }

    if (challengesPanel) {
      Promise.all([
        global.api.social.getIncomingChallenges(),
        global.api.social.getSentChallenges(),
      ]).then(function (results) {
        var incoming = (results[0].ok && results[0].data.challenges) || [];
        var outgoing = (results[1].ok && results[1].data.challenges) || [];
        var html = "";
        if (incoming.length) {
          html +=
            '<h4 class="h5 mb-3">Incoming 1v1</h4>' +
            incoming
              .map(function (c) {
                var u = c.challenger || {};
                return (
                  '<div class="arena-friend-card"><img src="' +
                  mediaSrc(u.avatar) +
                  '" alt=""><div class="flex-grow-1"><strong>' +
                  esc(u.username) +
                  "</strong><br><small>" +
                  esc((c.game && c.game.name) || "Game") +
                  '</small></div><button type="button" class="th-btn me-2 arena-accept-challenge" data-id="' +
                  esc(c.id) +
                  '">Accept</button><button type="button" class="th-btn style2 arena-decline-challenge" data-id="' +
                  esc(c.id) +
                  '">Decline</button></div>'
                );
              })
              .join("");
        }
        if (outgoing.length) {
          html +=
            '<h4 class="h5 mb-3 mt-4">Sent 1v1</h4>' +
            outgoing
              .map(function (c) {
                var u = c.challenged || {};
                return (
                  '<div class="arena-friend-card"><img src="' +
                  mediaSrc(u.avatar) +
                  '" alt=""><div class="flex-grow-1"><strong>' +
                  esc(u.username) +
                  "</strong><br><small class=\"text-theme\">Challenge sent — waiting</small></div>" +
                  '<button type="button" class="th-btn style2 arena-cancel-challenge" data-id="' +
                  esc(c.id) +
                  '">Cancel Challenge</button></div>'
                );
              })
              .join("");
        }
        challengesPanel.innerHTML =
          html || '<p class="text-muted">No pending 1v1 challenges.</p>';
        challengesPanel.querySelectorAll(".arena-accept-challenge").forEach(function (btn) {
          btn.addEventListener("click", function () {
            global.api.social.acceptChallenge(btn.getAttribute("data-id")).then(function () {
              loadFriends();
            });
          });
        });
        challengesPanel.querySelectorAll(".arena-decline-challenge, .arena-cancel-challenge").forEach(function (btn) {
          btn.addEventListener("click", function () {
            var id = btn.getAttribute("data-id");
            var fn = btn.classList.contains("arena-cancel-challenge")
              ? global.api.social.cancelChallenge
              : global.api.social.declineChallenge;
            fn(id).then(function () {
              loadFriends();
            });
          });
        });
      });
    }
  }

  function ensureSocialTabs() {
    var tabs = document.querySelector("[data-arena-tabs] .arena-tabs");
    var wrap = document.querySelector("[data-arena-tabs]");
    if (!tabs || !wrap) {
      return;
    }
    if (!document.querySelector('[data-arena-tab="sent"]')) {
      tabs.insertAdjacentHTML(
        "beforeend",
        '<button type="button" data-arena-tab="sent">Sent Requests</button>' +
          '<button type="button" data-arena-tab="challenges">1v1 Challenges</button>'
      );
      wrap.insertAdjacentHTML(
        "beforeend",
        '<div class="arena-tab-panel" data-arena-panel="sent"></div>' +
          '<div class="arena-tab-panel" data-arena-panel="challenges"></div>'
      );
      wrap.querySelectorAll("[data-arena-tab]").forEach(function (btn) {
        btn.addEventListener("click", function () {
          var id = btn.getAttribute("data-arena-tab");
          wrap.querySelectorAll("[data-arena-tab]").forEach(function (b) {
            b.classList.toggle("active", b === btn);
          });
          wrap.querySelectorAll("[data-arena-panel]").forEach(function (p) {
            p.classList.toggle("active", p.getAttribute("data-arena-panel") === id);
          });
        });
      });
    }
  }

  var activityFeedPage = 1;

  function renderActivityItem(item) {
    var when = (item.occurred_at || "").slice(0, 16).replace("T", " ");
    if (item.type === "friend_match_win" && item.user_id) {
      return (
        '<div class="arena-activity-card"><div class="arena-activity-icon"><i class="fas fa-trophy"></i></div><div>Friend won a match' +
        (item.tournament
          ? ' in <span class="text-theme">' + esc(item.tournament.name) + "</span>"
          : "") +
        '<br><span class="text-muted">' +
        esc(when) +
        "</span></div></div>"
      );
    }
    if (item.type === "friend_tournament_join" && item.user) {
      return (
        '<div class="arena-activity-card"><img src="' +
        mediaSrc(item.user.avatar) +
        '" alt=""><div><strong>' +
        esc(item.user.username) +
        "</strong> joined " +
        (item.tournament
          ? '<span class="text-theme">' + esc(item.tournament.name) + "</span>"
          : "a tournament") +
        '<br><span class="text-muted">' +
        esc(when) +
        "</span></div></div>"
      );
    }
    if (item.type === "friend_achievement" && item.user) {
      var ach = item.achievement ? item.achievement.name : "badge";
      return (
        '<div class="arena-activity-card"><img src="' +
        mediaSrc(item.user.avatar) +
        '" alt=""><div><strong>' +
        esc(item.user.username) +
        '</strong> earned <span class="text-theme">' +
        esc(ach) +
        '</span><br><span class="text-muted">' +
        esc(when) +
        "</span></div></div>"
      );
    }
    if (item.type === "match_win" && item.user) {
      return (
        '<div class="arena-activity-card"><img src="' +
        mediaSrc(item.user.avatar) +
        '" alt=""><div><strong>' +
        esc(item.user.username) +
        "</strong> won a match" +
        (item.tournament
          ? ' in <a href="tournament-details.html?slug=' +
            esc(item.tournament.slug) +
            '" class="text-theme">' +
            esc(item.tournament.name) +
            "</a>"
          : "") +
        '<br><span class="text-muted">' +
        esc(when) +
        "</span></div></div>"
      );
    }
    if (item.type === "tournament_registration" && item.user) {
      return (
        '<div class="arena-activity-card"><img src="' +
        mediaSrc(item.user.avatar) +
        '" alt=""><div><strong>' +
        esc(item.user.username) +
        "</strong> joined " +
        (item.tournament
          ? '<a href="tournament-details.html?slug=' +
            esc(item.tournament.slug) +
            '" class="text-theme">' +
            esc(item.tournament.name) +
            "</a>"
          : "a tournament") +
        '<br><span class="text-muted">' +
        esc(when) +
        "</span></div></div>"
      );
    }
    if (item.type === "new_tournament" && item.tournament) {
      return (
        '<div class="arena-activity-card"><div class="arena-activity-icon"><i class="fas fa-trophy"></i></div><div><strong>System:</strong> New tournament <a href="tournament-details.html?slug=' +
        esc(item.tournament.slug) +
        '" class="text-theme">' +
        esc(item.tournament.name) +
        '</a> is open<br><span class="text-muted">' +
        esc(when) +
        "</span></div></div>"
      );
    }
    if (item.type === "achievement" && item.user) {
      var badge = item.achievement ? item.achievement.name : "badge";
      return (
        '<div class="arena-activity-card"><img src="' +
        mediaSrc(item.user.avatar) +
        '" alt=""><div><strong>' +
        esc(item.user.username) +
        '</strong> earned <span class="text-theme">' +
        esc(badge) +
        '</span><br><span class="text-muted">' +
        esc(when) +
        "</span></div></div>"
      );
    }
    return (
      '<div class="arena-activity-card"><div class="arena-activity-icon"><i class="fas fa-bell"></i></div><div>' +
      esc(item.type || "Activity") +
      '<br><span class="text-muted">' +
      esc(when) +
      "</span></div></div>"
    );
  }

  function loadActivityFeed(append) {
    if (page() !== "activity-feed.html") {
      return;
    }
    var feed = document.getElementById("activityFeed");
    if (!feed) {
      return;
    }
    if (!append) {
      activityFeedPage = 1;
    }
    global.api.user.getActivityFeed({ page: activityFeedPage }).then(function (res) {
      if (!res.ok) {
        return;
      }
      var items = (res.data && res.data.feed) || [];
      if (!items.length && !append) {
        feed.innerHTML = '<p class="text-center text-muted">No recent activity.</p>';
        return;
      }
      var html = items
        .map(renderActivityItem)
        .join("");
      if (append) {
        feed.insertAdjacentHTML("beforeend", html);
      } else {
        feed.innerHTML = html;
      }
      var loadBtn = document.getElementById("loadMoreActivity");
      if (loadBtn) {
        loadBtn.style.display =
          res.data && res.data.current_page < res.data.last_page ? "" : "none";
      }
    });
  }

  function bindActivityFeedMore() {
    if (page() !== "activity-feed.html") {
      return;
    }
    var loadBtn = document.getElementById("loadMoreActivity");
    if (!loadBtn || loadBtn.dataset.arenaBound) {
      return;
    }
    loadBtn.dataset.arenaBound = "1";
    loadBtn.addEventListener("click", function () {
      activityFeedPage += 1;
      loadActivityFeed(true);
    });
  }

  function resolveUserId(input) {
    var raw = (input || "").trim();
    if (/^\d+$/.test(raw)) {
      return Promise.resolve(parseInt(raw, 10));
    }
    return global.api.public.lookupUser(raw).then(function (res) {
      return res.ok && res.data ? res.data.id : null;
    });
  }

  function loadTournamentHistory() {
    if (page() !== "tournament-history.html") {
      return;
    }
    var tbody = document.querySelector(".arena-table tbody");
    if (!tbody) {
      return;
    }
    global.api.tournaments.history({ per_page: 50 }).then(function (res) {
      if (!res.ok) {
        return;
      }
      var rows = (res.data && res.data.data) || [];
      if (!rows.length) {
        tbody.innerHTML =
          '<tr><td colspan="7" class="text-center">No completed tournaments yet.</td></tr>';
        return;
      }
      tbody.innerHTML = rows
        .map(function (t) {
          var game = (t.game && t.game.name) || "—";
          var end = t.end_date ? String(t.end_date).slice(0, 10) : "—";
          return (
            "<tr><td>" +
            esc(t.name) +
            "</td><td>" +
            esc(game) +
            "</td><td>" +
            esc(t.format || "—") +
            '</td><td>—</td><td>' +
            esc(end) +
            "</td><td>$" +
            esc(t.prize_pool || 0) +
            '</td><td><a href="bracket.html?id=' +
            esc(t.id) +
            '" class="th-btn style2" style="padding:6px 14px;font-size:12px">View Bracket</a></td></tr>'
          );
        })
        .join("");
    });
  }

  function bindHeadToHead() {
    if (page() !== "head-to-head.html") {
      return;
    }
    var btn = document.getElementById("h2h-compare-btn");
    var results = document.getElementById("h2h-results");
    if (!btn || !results || btn.dataset.arenaBound) {
      return;
    }
    btn.dataset.arenaBound = "1";
    btn.addEventListener("click", function () {
      var p1 = document.getElementById("h2h-p1");
      var p2 = document.getElementById("h2h-p2");
      if (!p1 || !p2) {
        return;
      }
      Promise.all([resolveUserId(p1.value), resolveUserId(p2.value)]).then(function (ids) {
        if (!ids[0] || !ids[1]) {
          alert("Could not find one or both players. Use exact usernames.");
          return;
        }
        global.api.stats.headToHead({ player1: ids[0], player2: ids[1] }).then(function (res) {
          if (!res.ok || !res.data) {
            alert((res.data && res.data.message) || "Comparison failed.");
            return;
          }
          var d = res.data;
          var p1Name = d.player1.username;
          var p2Name = d.player2.username;
          var s1 = (d.player1.stats || []).reduce(
            function (a, s) {
              a.matches += s.matches_played || 0;
              a.wins += s.wins || 0;
              return a;
            },
            { matches: 0, wins: 0 }
          );
          var s2 = (d.player2.stats || []).reduce(
            function (a, s) {
              a.matches += s.matches_played || 0;
              a.wins += s.wins || 0;
              return a;
            },
            { matches: 0, wins: 0 }
          );
          var wr1 = s1.matches ? Math.round((s1.wins / s1.matches) * 100) : 0;
          var wr2 = s2.matches ? Math.round((s2.wins / s2.matches) * 100) : 0;
          var h2h = d.head_to_head || {};
          var record =
            (h2h.player1_wins || 0) +
            " - " +
            (h2h.player2_wins || 0) +
            (h2h.draws ? " (" + h2h.draws + " draws)" : "");
          function h2hRow(label, v1, v2) {
            var n1 = parseFloat(v1);
            var n2 = parseFloat(v2);
            var c1 = n1 > n2 ? " arena-h2h-better" : "";
            var c2 = n2 > n1 ? " arena-h2h-better" : "";
            return (
              "<tr><td class=\"" +
              c1 +
              '">' +
              esc(String(v1)) +
              '</td><td class="stat-label">' +
              esc(label) +
              '</td><td class="' +
              c2 +
              '">' +
              esc(String(v2)) +
              "</td></tr>"
            );
          }
          results.innerHTML =
            '<table class="arena-table arena-h2h-table"><thead><tr><th>' +
            esc(p1Name) +
            "</th><th>Stat</th><th>" +
            esc(p2Name) +
            "</th></tr></thead><tbody>" +
            h2hRow("Win Rate", wr1 + "%", wr2 + "%") +
            h2hRow("Matches Played", s1.matches, s2.matches) +
            h2hRow("Total Wins", s1.wins, s2.wins) +
            "<tr><td colspan=\"3\" class=\"text-center stat-label\">Head-to-Head: " +
            esc(record) +
            "</td></tr></tbody></table>";
        });
      });
    });
  }

  function loadStatsPage() {
    if (page() !== "stats.html") {
      return;
    }
    global.api.user.getStats().then(function (res) {
      if (!res.ok) {
        return;
      }
      var t = res.data.totals || res.data;
      var stats = document.querySelectorAll(".arena-dash-stat h4");
      if (stats[0] && t.matches_played != null) {
        stats[0].textContent = t.matches_played;
      }
      if (stats[1] && t.win_rate != null) {
        stats[1].textContent = t.win_rate + "%";
      }
      if (stats[2] && t.kd_ratio != null) {
        stats[2].textContent = t.kd_ratio;
      }
      if (stats[3] && t.avg_score != null) {
        stats[3].textContent = t.avg_score;
      }
      if (stats[4] && t.tournament_wins != null) {
        stats[4].textContent = t.tournament_wins;
      }
      if (stats[5] && t.wins != null) {
        stats[5].textContent = t.wins;
      }
      var chartHost = document.querySelector(".arena-chart-box");
      if (chartHost && t.wins != null) {
        var losses = t.losses != null ? t.losses : 0;
        var wins = t.wins || 0;
        var total = wins + losses || 1;
        var winPct = Math.round((wins / total) * 100);
        chartHost.innerHTML =
          '<div class="arena-predict-bar" style="margin-top:12px;height:12px"><div style="width:' +
          winPct +
          '%;height:100%;background:var(--theme-color)"></div></div>' +
          '<p class="text-muted mt-2 mb-0">Wins ' +
          wins +
          " · Losses " +
          losses +
          " (" +
          winPct +
          "% win rate)</p>";
      }
    });
  }

  function init() {
    var p = page();
    if (USER_PAGES.indexOf(p) < 0) {
      return;
    }
    if (!requireAuth()) {
      return;
    }
    loadProfile();
    bindProfileSave();
    loadTournaments();
    bindCreateTournament();
    loadOrders();
    loadNotifications();
    bindMarkAllRead();
    if (page() === "my-notifications.html" && global.api.getAuthToken()) {
      global.api.auth.me().then(function (res) {
        if (res.ok && res.data && res.data.user && typeof global.api.initEcho === "function") {
          global.api.initEcho(res.data.user.id, {
            onNotification: function () {
              loadNotifications();
              if (typeof global.loadNotificationBadge === "function") {
                global.loadNotificationBadge();
              }
            },
          });
        }
      });
    }
    bindAvatarUpload();
    loadFriends();
    loadActivityFeed();
    bindActivityFeedMore();
    bindHeadToHead();
    loadTournamentHistory();
    loadStatsPage();
    if (page() === "achievements.html") {
      loadAchievementsBadges();
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(typeof window !== "undefined" ? window : this);
