/* Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
/**

 * Arena bracket view and prediction UI.

 */

(function (global) {

  "use strict";



  function initBracket(root, options) {

    if (!root) {

      return;

    }

    options = options || {};

    var alwaysPredict = options.alwaysPredict === true;

    var tournamentId = options.tournamentId || root.getAttribute("data-tournament-id");

    var totalMatches = root.querySelectorAll(".arena-bracket-match[data-match-id]").length;

    var predicted = 0;



    var progressEl = document.getElementById("arena-predict-progress");

    var progressText = document.getElementById("arena-predict-progress-text");

    var submitBtn = document.getElementById("arena-predict-submit");



    function updateProgress() {

      predicted = root.querySelectorAll(".arena-bracket-match.predicted").length;

      var pct = totalMatches ? Math.round((predicted / totalMatches) * 100) : 0;

      if (progressEl) {

        progressEl.style.width = pct + "%";

      }

      if (progressText) {

        progressText.textContent =

          predicted + " / " + totalMatches + " matches predicted";

      }

      if (submitBtn) {

        submitBtn.disabled = predicted < totalMatches;

      }

    }



    function bindTeamClicks(active) {

      root.querySelectorAll(".arena-bracket-team[data-pickable]").forEach(function (team) {

        team.style.cursor = active ? "pointer" : "";

        if (team.dataset.bracketBound) {

          return;

        }

        team.dataset.bracketBound = "1";

        team.addEventListener("click", function () {

          if (!root.classList.contains("arena-bracket-predict") && !alwaysPredict) {

            return;

          }

          var match = team.closest(".arena-bracket-match");

          if (!match) {

            return;

          }

          if (!alwaysPredict && match.classList.contains("done")) {

            return;

          }

          match.querySelectorAll(".arena-bracket-team").forEach(function (t) {

            t.classList.remove("picked");

          });

          team.classList.add("picked");

          match.classList.add("predicted");

          updateProgress();

        });

      });

    }



    if (alwaysPredict) {

      root.classList.add("arena-bracket-predict");

      bindTeamClicks(true);

      updateProgress();

    }



    var toggle = document.getElementById("arena-predict-toggle");

    if (toggle) {

      toggle.addEventListener("click", function () {

        var on = root.classList.toggle("arena-bracket-predict");

        toggle.textContent = on

          ? "Exit Prediction Mode"

          : "Switch to Prediction Mode";

        toggle.classList.toggle("style2", on);

        bindTeamClicks(on);

      });

    }



    if (submitBtn && !submitBtn.dataset.arenaBound) {

      submitBtn.dataset.arenaBound = "1";

      submitBtn.addEventListener("click", function () {

        if (submitBtn.disabled) {

          return;

        }

        if (!global.api || !global.api.getAuthToken()) {

          global.location.href =

            "login.html?redirect=" +

            encodeURIComponent(global.location.pathname + global.location.search);

          return;

        }

        if (!tournamentId) {

          alert("Open this page with ?id=TOURNAMENT_ID to submit predictions.");

          return;

        }

        var predictions = {};

        root.querySelectorAll(".arena-bracket-match.predicted").forEach(function (match) {

          var mid = match.getAttribute("data-match-id");

          var picked = match.querySelector(".arena-bracket-team.picked");

          var teamId = picked && picked.getAttribute("data-team-id");

          if (mid && teamId) {

            predictions["match_" + mid] = parseInt(teamId, 10);

          }

        });

        if (!Object.keys(predictions).length) {

          alert("Pick a winner for each match first.");

          return;

        }

        submitBtn.disabled = true;

        global.api.tournaments

          .predict(tournamentId, { predictions: predictions })

          .then(function (res) {

            submitBtn.disabled = false;

            if (res.ok) {

              alert((res.data && res.data.message) || "Predictions submitted!");

              if (typeof global.loadPredictionLeaderboard === "function") {

                global.loadPredictionLeaderboard(tournamentId);

              }

              return;

            }

            alert((res.data && res.data.message) || "Could not submit predictions.");

          });

      });

    }



    updateProgress();

  }



  global.initBracket = initBracket;



  function boot() {

    document.querySelectorAll("[data-arena-bracket]").forEach(function (root) {

      initBracket(root, {

        alwaysPredict: root.getAttribute("data-always-predict") === "1",

        tournamentId: root.getAttribute("data-tournament-id"),

      });

    });

  }



  if (document.readyState === "loading") {

    document.addEventListener("DOMContentLoaded", boot);

  } else {

    boot();

  }

})(typeof window !== "undefined" ? window : this);


