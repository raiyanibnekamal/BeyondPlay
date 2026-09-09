/**
 * Match check-in countdown and button state.
 */
(function () {
  "use strict";

  function pad(n) {
    return n < 10 ? "0" + n : String(n);
  }

  function initCountdown(el, targetMs) {
    if (!el) return;

    function tick() {
      var diff = targetMs - Date.now();
      if (diff <= 0) {
        el.textContent = "00:00:00";
        return;
      }
      var h = Math.floor(diff / 3600000);
      var m = Math.floor((diff % 3600000) / 60000);
      var s = Math.floor((diff % 60000) / 1000);
      el.textContent = pad(h) + ":" + pad(m) + ":" + pad(s);
    }

    tick();
    setInterval(tick, 1000);
  }

  function initCheckin() {
    var countdown = document.getElementById("arena-checkin-countdown");
    var btn = document.getElementById("arena-checkin-btn");
    var myStatus = document.getElementById("arena-checkin-my-status");
    var oppStatus = document.getElementById("arena-checkin-opp-status");

    var hours = parseInt(countdown && countdown.getAttribute("data-hours") || "2", 10);
    initCountdown(countdown, Date.now() + hours * 3600000);

    if (!btn) return;
    btn.addEventListener("click", function () {
      if (btn.dataset.checkedIn === "1") return;
      btn.dataset.checkedIn = "1";
      btn.textContent = "\u2705 Checked In \u2014 Waiting for Opponent";
      btn.classList.remove("arena-checkin-ready");
      btn.classList.add("arena-checkin-done");
      btn.disabled = true;
      if (myStatus) myStatus.innerHTML = "Checked In \u2705";
      if (oppStatus) oppStatus.innerHTML = "Waiting \u23F3";
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCheckin);
  } else {
    initCheckin();
  }
})();
