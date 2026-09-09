/* Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
/**
 * Bracket, point table, and bracket prediction pages.
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

  function resolveId() {
    var params = new URLSearchParams(global.location.search);
    var id = params.get("id");
    if (id) {
      return Promise.resolve(parseInt(id, 10));
    }
    var slug = params.get("slug");
    if (!slug) {
      return Promise.resolve(null);
    }
    return global.api.tournaments.get(slug).then(function (res) {
      return res.ok && res.data ? res.data.id : null;
    });
  }

  function renderBracketRounds(root, rounds, pickable) {
    if (!rounds.length) {
      root.innerHTML =
        '<p class="text-center text-muted">No bracket generated yet for this tournament.</p>';
      return;
    }
    root.innerHTML = rounds
      .map(function (round) {
        var matches = (round.matches || [])
          .map(function (m) {
            var t1 = m.team1;
            var t2 = m.team2;
            var n1 = t1 ? t1.name : "TBD";
            var n2 = t2 ? t2.name : "TBD";
            var score =
              m.team1_score != null && m.team2_score != null
                ? " (" + m.team1_score + "-" + m.team2_score + ")"
                : "";
            var done = m.status === "completed" ? " done" : "";
            var pick = pickable ? " data-pickable" : "";
            var tid1 = t1 && t1.id ? ' data-team-id="' + esc(t1.id) + '"' : "";
            var tid2 = t2 && t2.id ? ' data-team-id="' + esc(t2.id) + '"' : "";
            return (
              '<div class="arena-bracket-match' +
              done +
              '" data-match-id="' +
              esc(m.id) +
              '"><div class="arena-bracket-team' +
              pick +
              tid1 +
              ">" +
              esc(n1) +
              '</div><div class="arena-bracket-team' +
              pick +
              tid2 +
              ">" +
              esc(n2) +
              score +
              " <small>(" +
              esc(m.status) +
              ")</small></div></div>"
            );
          })
          .join("");
        return (
          '<div class="arena-bracket-round"><h4 class="arena-round-title">Round ' +
          esc(round.round) +
          "</h4>" +
          matches +
          "</div>"
        );
      })
      .join("");
  }

  function loadPredictionLeaderboard(tid) {
    global.api.tournaments.getPredictions(tid).then(function (res) {
      if (!res.ok) {
        return;
      }
      var rows = res.data.leaderboard || [];
      var section = document.querySelector(".sec-title.h4");
      var tbody = null;
      document.querySelectorAll(".sec-title.h4").forEach(function (h) {
        if (h.textContent.indexOf("Leaderboard") >= 0) {
          tbody = h.parentElement && h.parentElement.querySelector("tbody");
        }
      });
      if (!tbody) {
        tbody = document.querySelector(".arena-table tbody");
      }
      if (!tbody) {
        return;
      }
      if (!rows.length) {
        tbody.innerHTML =
          '<tr><td colspan="4" class="text-center">No predictions yet.</td></tr>';
        return;
      }
      tbody.innerHTML = rows
        .map(function (row, i) {
          var u = row.user || {};
          return (
            "<tr><td>" +
            (i + 1) +
            '</td><td><a href="player-profile.html?id=' +
            esc(u.id) +
            '" class="text-theme">' +
            esc(u.username) +
            "</a></td><td>—</td><td>" +
            esc(row.score != null ? row.score : 0) +
            "</td></tr>"
          );
        })
        .join("");
    });
  }

  global.loadPredictionLeaderboard = loadPredictionLeaderboard;

  function loadBracketPage(pickable) {
    var pageName = page();
    if (pageName !== "bracket.html" && pageName !== "bracket-prediction.html") {
      return;
    }
    resolveId().then(function (tid) {
      var root = document.querySelector("[data-arena-bracket]");
      if (!root) {
        return;
      }
      if (!tid) {
        if (pageName === "bracket-prediction.html") {
          var note = document.getElementById("arena-predict-hint");
          if (!note) {
            note = document.createElement("p");
            note.id = "arena-predict-hint";
            note.className = "text-center text-theme mb-20";
            note.textContent =
              "Add ?id=TOURNAMENT_ID to load the live bracket and submit predictions.";
            root.parentElement.insertBefore(note, root);
          }
        }
        return;
      }
      root.setAttribute("data-tournament-id", String(tid));
      global.api.tournaments.getBracket(tid).then(function (res) {
        if (!res.ok) {
          return;
        }
        var rounds = res.data.rounds || [];
        renderBracketRounds(root, rounds, pickable);
        if (typeof global.initBracket === "function") {
          global.initBracket(root, {
            alwaysPredict: pickable,
            tournamentId: tid,
          });
        }
        if (pickable) {
          loadPredictionLeaderboard(tid);
        }
      });
    });
  }

  function loadStandings() {
    if (page() !== "point-table.html") {
      return;
    }
    resolveId().then(function (tid) {
      if (!tid) {
        return;
      }
      global.api.tournaments.getStandings(tid).then(function (res) {
        if (!res.ok) {
          return;
        }
        var rows = Array.isArray(res.data) ? res.data : res.data.data || [];
        var tbody =
          document.querySelector("#arena-standings-tbody") ||
          document.querySelector("table tbody");
        if (!tbody) {
          var wrap = document.querySelector(".space .container") || document.body;
          var table = document.createElement("div");
          table.className = "arena-table-wrap mt-30";
          table.innerHTML =
            '<table class="arena-table"><thead><tr><th>#</th><th>Team</th><th>P</th><th>W</th><th>L</th><th>PTS</th></tr></thead><tbody id="arena-standings-tbody"></tbody></table>';
          wrap.appendChild(table);
          tbody = document.getElementById("arena-standings-tbody");
        }
        if (!tbody) {
          return;
        }
        if (!rows.length) {
          tbody.innerHTML =
            '<tr><td colspan="6" class="text-center">No standings for this tournament yet.</td></tr>';
          return;
        }
        tbody.innerHTML = rows
          .map(function (row, i) {
            var team = row.team ? row.team.name : "—";
            var teamId = row.team && row.team.id ? row.team.id : null;
            var teamCell = teamId
              ? '<a href="team-details.html?id=' + esc(teamId) + '" class="text-theme">' + esc(team) + "</a>"
              : esc(team);
            return (
              "<tr><td>" +
              (i + 1) +
              "</td><td>" +
              teamCell +
              "</td><td>" +
              esc(row.played) +
              "</td><td>" +
              esc(row.wins) +
              "</td><td>" +
              esc(row.losses) +
              "</td><td>" +
              esc(row.points) +
              "</td></tr>"
            );
          })
          .join("");
      });
    });
  }

  function loadRulesPage() {
    if (page() !== "tournament-rules.html") {
      return;
    }
    resolveId().then(function (id) {
      if (!id) {
        return;
      }
      var back = document.querySelector('.container a[href="tournament-details.html"]');
      if (back) {
        back.setAttribute("href", "tournament-details.html?id=" + encodeURIComponent(id));
      }
      global.api.tournaments.getRules(id).then(function (res) {
        var wrap =
          document.querySelector(".arena-rules-section") ||
          document.querySelector(".space .container");
        if (!wrap || !res.ok) {
          return;
        }
        var r = res.data || {};
        wrap.innerHTML =
          (r.format_details
            ? '<div class="arena-rules-section"><h3>Format</h3><p>' +
              esc(r.format_details) +
              "</p></div>"
            : "") +
          (r.schedule_info
            ? '<div class="arena-rules-section"><h3>Schedule</h3><p>' +
              esc(r.schedule_info) +
              "</p></div>"
            : "") +
          (r.scoring_rules
            ? '<div class="arena-rules-section"><h3>Scoring</h3><p>' +
              esc(r.scoring_rules) +
              "</p></div>"
            : "") +
          (r.code_of_conduct
            ? '<div class="arena-rules-section"><h3>Code of Conduct</h3><p>' +
              esc(r.code_of_conduct) +
              "</p></div>"
            : "") +
          (r.dispute_policy
            ? '<div class="arena-rules-section"><h3>Disputes</h3><p>' +
              esc(r.dispute_policy) +
              "</p></div>"
            : "") ||
          '<p class="text-muted">No rules published for this tournament yet.</p>';
      });
    });
  }

  function init() {
    var p = page();
    if (p === "bracket-prediction.html") {
      loadBracketPage(true);
    } else if (p === "bracket.html") {
      loadBracketPage(false);
    } else if (p === "tournament-rules.html") {
      loadRulesPage();
    }
    loadStandings();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(typeof window !== "undefined" ? window : this);
