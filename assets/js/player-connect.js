/**
 * Public player profile — stats, friend request, 1v1 challenge.
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

  function mediaSrc(url) {
    if (global.api && typeof global.api.resolveMediaUrl === "function") {
      return global.api.resolveMediaUrl(url) || "assets/img/team/1-1.png";
    }
    return url || "assets/img/team/1-1.png";
  }

  function page() {
    return (global.location.pathname.split("/").pop() || "").toLowerCase();
  }

  function resolveTargetUser() {
    var params = new URLSearchParams(global.location.search);
    var userId = params.get("id") || params.get("user");
    var username = params.get("username");
    if (!userId && username) {
      userId = username;
    }
    if (!userId) {
      return Promise.resolve(null);
    }
    if (/^\d+$/.test(String(userId))) {
      return Promise.resolve(parseInt(userId, 10));
    }
    return global.api.public.lookupUser(userId).then(function (lu) {
      return lu.ok && lu.data && lu.data.id ? lu.data.id : null;
    });
  }

  function setBtn(btn, label, disabled, extraClass) {
    if (!btn) {
      return;
    }
    btn.textContent = label;
    btn.disabled = !!disabled;
    btn.classList.remove("style2", "arena-pending-action");
    if (extraClass) {
      btn.classList.add(extraClass);
    }
  }

  function updateFriendButton(btn, status, requestId) {
    if (!btn) {
      return;
    }
    btn.dataset.requestId = requestId || "";
    if (status === "self") {
      btn.style.display = "none";
      return;
    }
    btn.style.display = "";
    if (status === "friends") {
      setBtn(btn, "Friends ✓", true, "style2");
      return;
    }
    if (status === "pending_sent") {
      setBtn(btn, "Cancel Request", false, "style2 arena-pending-action");
      return;
    }
    if (status === "pending_received") {
      setBtn(btn, "Respond on Friends page", true, "style2");
      return;
    }
    setBtn(btn, "Add Friend", false);
  }

  function updateChallengeButton(btn, status, challengeId) {
    if (!btn) {
      return;
    }
    btn.dataset.challengeId = challengeId || "";
    if (status === "self") {
      btn.style.display = "none";
      return;
    }
    btn.style.display = "";
    if (status === "pending_sent") {
      setBtn(btn, "Challenge Pending — Cancel", false, "style2 arena-pending-action");
      return;
    }
    if (status === "pending_received") {
      setBtn(btn, "Challenge Received — View", false, "style2");
      return;
    }
    setBtn(btn, "Challenge to 1v1", false);
  }

  function bindSocialActions(targetUserId, friendBtn, challengeBtn) {
    if (!global.api.getAuthToken()) {
      if (friendBtn) {
        friendBtn.onclick = function () {
          global.location.href = "login.html?redirect=" + encodeURIComponent(global.location.pathname + global.location.search);
        };
      }
      if (challengeBtn) {
        challengeBtn.onclick = friendBtn ? friendBtn.onclick : null;
      }
      return;
    }

    function refreshSocial() {
      global.api.social.getFriendStatus(targetUserId).then(function (fr) {
        updateFriendButton(friendBtn, fr.ok ? fr.data.status : "none", fr.data && fr.data.request_id);
      });
      global.api.social.getChallengeStatus(targetUserId).then(function (cr) {
        updateChallengeButton(challengeBtn, cr.ok ? cr.data.status : "none", cr.data && cr.data.challenge_id);
      });
    }

    refreshSocial();

    if (friendBtn) {
      friendBtn.onclick = function () {
        var status = friendBtn.classList.contains("arena-pending-action") ? "pending_sent" : "none";
        if (friendBtn.textContent.indexOf("Cancel") >= 0 || status === "pending_sent") {
          var rid = friendBtn.dataset.requestId;
          if (!rid) {
            return;
          }
          global.api.social.cancelFriendRequest(rid).then(function (res) {
            alert((res.data && res.data.message) || (res.ok ? "Request cancelled." : "Failed."));
            refreshSocial();
          });
          return;
        }
        global.api.social.sendFriendRequest(targetUserId).then(function (res) {
          alert((res.data && res.data.message) || (res.ok ? "Friend request sent!" : "Failed."));
          refreshSocial();
        });
      };
    }

    if (challengeBtn) {
      challengeBtn.onclick = function () {
        if (challengeBtn.textContent.indexOf("Cancel") >= 0) {
          var cid = challengeBtn.dataset.challengeId;
          if (!cid) {
            return;
          }
          global.api.social.cancelChallenge(cid).then(function (res) {
            alert((res.data && res.data.message) || (res.ok ? "Challenge cancelled." : "Failed."));
            refreshSocial();
          });
          return;
        }
        if (challengeBtn.textContent.indexOf("Received") >= 0) {
          global.location.href = "friends.html";
          return;
        }
        global.api.games.list().then(function (gr) {
          var games = (gr.data && gr.data.data) || gr.data || [];
          var gameId = games[0] && games[0].id ? games[0].id : 1;
          global.api.social.sendChallenge({
            challenged_id: targetUserId,
            game_id: gameId,
            message: "1v1 challenge from BeyondPlay profile",
          }).then(function (res) {
            alert((res.data && res.data.message) || (res.ok ? "Challenge sent!" : "Failed."));
            refreshSocial();
          });
        });
      };
    }
  }

  function init() {
    if (page() !== "player-profile.html") {
      return;
    }

    var friendBtn = document.getElementById("addFriendBtn");
    var challengeBtn = document.getElementById("challengeBtn");
    var statusEl = document.getElementById("arena-profile-status");
    if (!statusEl) {
      statusEl = document.createElement("p");
      statusEl.id = "arena-profile-status";
      statusEl.className = "text-muted mt-2 mb-0";
      var btnWrap = friendBtn && friendBtn.parentElement;
      if (btnWrap) {
        btnWrap.parentElement.insertBefore(statusEl, btnWrap.nextSibling);
      }
    }

    resolveTargetUser().then(function (targetUserId) {
      if (!targetUserId) {
        statusEl.textContent = "Player not found.";
        return;
      }

      global.api.stats.getPlayer(targetUserId).then(function (res) {
        if (!res.ok) {
          statusEl.textContent = "Could not load player profile.";
          return;
        }
        var t = res.data.totals || res.data;
        var user = res.data.user;
        if (user) {
          document.querySelectorAll(".player-name, h2.sec-title").forEach(function (el) {
            if (el.textContent.indexOf("Player") >= 0 || el.classList.contains("player-name")) {
              el.textContent = user.username || el.textContent;
            }
          });
          var av = document.querySelector(".arena-player-avatar");
          if (av && user.avatar) {
            av.src = mediaSrc(user.avatar);
            av.alt = user.username || "Player";
          }
          var gamingId = document.querySelector(".text-theme.mb-20");
          if (gamingId && user.gaming_id) {
            gamingId.textContent = "Gaming ID: " + user.gaming_id;
          }
          var profileLink = document.querySelector('a[href="player-profile.html"]');
          if (profileLink && global.api.auth && global.api.getAuthToken()) {
            global.api.auth.me().then(function (me) {
              if (me.ok && me.data && me.data.user && me.data.user.id === user.id) {
                profileLink.setAttribute("href", "my-profile.html");
                profileLink.innerHTML = '<i class="far fa-user me-1"></i> My Profile';
              } else {
                profileLink.setAttribute(
                  "href",
                  "player-profile.html?user=" + encodeURIComponent(user.username)
                );
              }
            });
          }
        }
        document.querySelectorAll(".arena-dash-stat h4").forEach(function (el, i) {
          var vals = [
            t.matches_played,
            t.wins,
            t.losses,
            t.win_rate != null ? t.win_rate + "%" : null,
          ];
          if (vals[i] != null) {
            el.textContent = vals[i];
          }
        });

        bindSocialActions(targetUserId, friendBtn, challengeBtn);

        if (global.api.getAuthToken()) {
          Promise.all([
            global.api.social.getFriendStatus(targetUserId),
            global.api.social.getChallengeStatus(targetUserId),
          ]).then(function (results) {
            var fs = results[0].ok ? results[0].data.status : "none";
            var cs = results[1].ok ? results[1].data.status : "none";
            var parts = [];
            if (fs === "pending_sent") {
              parts.push("Friend request sent — waiting for response.");
            } else if (fs === "friends") {
              parts.push("You are friends.");
            }
            if (cs === "pending_sent") {
              parts.push("1v1 challenge sent — waiting for response.");
            } else if (cs === "pending_received") {
              parts.push("This player challenged you to 1v1.");
            }
            statusEl.textContent = parts.join(" ");
          });
        }
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(typeof window !== "undefined" ? window : this);
