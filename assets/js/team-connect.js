/**
 * Teams list + details (ARENA E1).
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

  function loadTeams() {
    if (page() !== "team.html") {
      return;
    }
    global.api.teams.list().then(function (res) {
      if (!res.ok) {
        return;
      }
      var items = (res.data && res.data.data) || res.data || [];
      if (!Array.isArray(items)) {
        items = [];
      }
      var wrap = document.querySelector(".team-card, .swiper-wrapper, .row.gy-40");
      if (!wrap || !items.length) {
        return;
      }
      var parent = wrap.closest(".row") || wrap.parentElement;
      if (parent) {
        parent.insertAdjacentHTML(
          "afterbegin",
          items
            .slice(0, 8)
            .map(function (t) {
              return (
                '<div class="col-md-6 col-lg-3"><div class="team-card2"><h4><a href="team-details.html?slug=' +
                esc(t.slug) +
                '">' +
                esc(t.name) +
                "</a></h4><p>" +
                (t.members_count != null ? t.members_count + " members" : "") +
                "</p></div></div>"
              );
            })
            .join("")
        );
      }
      var createForm = document.getElementById("arena-create-team-form");
      if (createForm && !createForm.dataset.arenaBound) {
        createForm.dataset.arenaBound = "1";
        createForm.addEventListener("submit", function (e) {
          e.preventDefault();
          if (!global.api.getAuthToken()) {
            global.location.href = "login.html";
            return;
          }
          var name = createForm.querySelector('[name="name"]');
          global.api.teams
            .create({ name: name ? name.value : "My Team" })
            .then(function (r) {
              if (r.ok) {
                alert("Team created.");
                global.location.reload();
              } else {
                alert((r.data && r.data.message) || "Failed.");
              }
            });
        });
      }
    });
  }

  function loadTeamDetails() {
    if (page() !== "team-details.html") {
      return;
    }
    var slug = new URLSearchParams(global.location.search).get("slug");
    if (!slug) {
      return;
    }
    global.api.teams.get(slug).then(function (res) {
      if (!res.ok) {
        return;
      }
      var t = res.data;
      document.querySelectorAll(".team-details-name, .breadcumb-title").forEach(function (el) {
        el.textContent = t.name || "";
      });
      var members = t.members || [];
      var list = document.querySelector(".team-member-list, .arena-team-members");
      if (list && members.length) {
        list.innerHTML = members
          .map(function (m) {
            return "<li>" + esc(m.username) + " (" + esc(m.pivot?.role || "member") + ")</li>";
          })
          .join("");
      }
      bindTeamInviteForm(t);
    });
    loadPendingInvites();
  }

  function bindTeamInviteForm(team) {
    if (!global.api.getAuthToken()) {
      return;
    }
    global.api.auth.me().then(function (me) {
      if (!me.ok || !me.data || !me.data.user) {
        return;
      }
      var captain = team.captain_id === me.data.user.id;
      if (!captain) {
        return;
      }
      var form = document.getElementById("arena-team-invite-form");
      if (!form) {
        var host = document.querySelector(".arena-team-members")?.parentElement || document.querySelector(".space .container");
        if (!host) {
          return;
        }
        host.insertAdjacentHTML(
          "beforeend",
          '<div class="mt-40"><h4>Invite player</h4><form id="arena-team-invite-form" class="row g-3">' +
            '<div class="col-md-8"><input class="form-control" name="user_id" type="number" min="1" placeholder="User ID to invite" required></div>' +
            '<div class="col-md-4"><button type="submit" class="th-btn w-100">Send invite</button></div>' +
            "</form></div>"
        );
        form = document.getElementById("arena-team-invite-form");
      }
      if (!form || form.dataset.arenaBound) {
        return;
      }
      form.dataset.arenaBound = "1";
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var uid = parseInt(form.querySelector('[name="user_id"]').value, 10);
        global.api.teams.invite(team.id, { user_id: uid }).then(function (r) {
          alert(r.ok ? "Invitation sent." : (r.data && r.data.message) || "Invite failed.");
        });
      });
    });
  }

  function loadPendingInvites() {
    if (!global.api.getAuthToken()) {
      return;
    }
    global.api.teams.myInvites().then(function (res) {
      if (!res.ok) {
        return;
      }
      var invites = (res.data && res.data.invites) || [];
      var pending = invites.filter(function (i) {
        return i.status === "pending";
      });
      if (!pending.length) {
        return;
      }
      var host =
        document.querySelector(".arena-team-members")?.parentElement ||
        document.getElementById("arena-notif-list") ||
        document.querySelector(".space .container");
      if (!host) {
        return;
      }
      var box = document.getElementById("arena-pending-invites");
      if (!box) {
        box = document.createElement("div");
        box.id = "arena-pending-invites";
        box.className = "mt-40";
        host.insertBefore(box, host.firstChild);
      }
      box.innerHTML =
        "<h4>Pending team invites</h4><ul class=\"list-unstyled\">" +
        pending
          .map(function (inv) {
            var teamName = inv.team ? inv.team.name : "Team";
            return (
              "<li class=\"mb-3 p-3\" style=\"border:1px solid rgba(255,255,255,.1);border-radius:8px\">" +
              "<strong>" +
              esc(teamName) +
              "</strong> " +
              '<button type="button" class="th-btn btn-sm ms-2 arena-accept-invite" data-id="' +
              inv.id +
              '">Accept</button> ' +
              '<button type="button" class="th-btn style2 btn-sm arena-decline-invite" data-id="' +
              inv.id +
              '">Decline</button></li>'
            );
          })
          .join("") +
        "</ul>";
      box.querySelectorAll(".arena-accept-invite").forEach(function (btn) {
        btn.addEventListener("click", function () {
          global.api.teams.acceptInvite(btn.getAttribute("data-id")).then(function (r) {
            alert(r.ok ? "You joined the team!" : (r.data && r.data.message) || "Failed.");
            if (r.ok) {
              global.location.reload();
            }
          });
        });
      });
      box.querySelectorAll(".arena-decline-invite").forEach(function (btn) {
        btn.addEventListener("click", function () {
          global.api.teams.declineInvite(btn.getAttribute("data-id")).then(function (r) {
            if (r.ok) {
              btn.closest("li").remove();
            }
          });
        });
      });
    });
  }

  function init() {
    loadTeams();
    loadTeamDetails();
    if (page() === "my-notifications.html") {
      loadPendingInvites();
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(typeof window !== "undefined" ? window : this);
