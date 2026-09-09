/**
 * Live streaming page — loads matches with stream_url from API.
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

  function toEmbedUrl(url) {
    var u = String(url || "").trim();
    if (!u) {
      return "";
    }
    if (u.indexOf("youtube.com/embed/") >= 0 || u.indexOf("player.twitch.tv") >= 0) {
      return u;
    }
    var ytWatch = u.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]+)/i);
    if (ytWatch) {
      return "https://www.youtube.com/embed/" + ytWatch[1];
    }
    var twitch = u.match(/twitch\.tv\/([\w]+)/i);
    if (twitch) {
      return "https://player.twitch.tv/?channel=" + encodeURIComponent(twitch[1]) + "&parent=" + global.location.hostname;
    }
    return u;
  }

  function renderStreams(streams) {
    var root = document.getElementById("arena-live-streams");
    if (!root) {
      return;
    }
    if (!streams.length) {
      root.innerHTML =
        '<div class="text-center py-5"><p class="text-muted mb-3">No live streams right now.</p><a href="tournament.html" class="th-btn">Browse Tournaments</a></div>';
      return;
    }

    root.innerHTML = streams
      .map(function (s, idx) {
        var t1 = (s.team1 && s.team1.name) || "TBD";
        var t2 = (s.team2 && s.team2.name) || "TBD";
        var tour = (s.tournament && s.tournament.name) || "Tournament";
        var slug = (s.tournament && s.tournament.slug) || "";
        var embed = toEmbedUrl(s.stream_url);
        var liveBadge =
          s.status === "live"
            ? '<span class="arena-live-badge"><i class="fas fa-circle me-1"></i>LIVE</span>'
            : '<span class="arena-scheduled-badge">Scheduled</span>';
        var player =
          embed.indexOf("http") === 0
            ? '<div class="arena-stream-player ratio ratio-16x9"><iframe src="' +
              esc(embed) +
              '" title="' +
              esc(t1 + " vs " + t2) +
              '" allowfullscreen loading="lazy"></iframe></div>'
            : '<p class="text-muted">Stream link unavailable.</p>';
        return (
          '<div class="arena-stream-card mb-4">' +
          '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">' +
          "<div><h3 class=\"h5 mb-1\">" +
          esc(t1) +
          " vs " +
          esc(t2) +
          "</h3>" +
          '<p class="mb-0 text-muted small">' +
          esc(tour) +
          "</p></div>" +
          liveBadge +
          "</div>" +
          player +
          (slug
            ? '<div class="mt-3"><a href="tournament-details.html?slug=' +
              encodeURIComponent(slug) +
              '" class="th-btn style-border"><span class="btn-border">View Tournament</span></a></div>'
            : "") +
          "</div>"
        );
      })
      .join("");
  }

  function init() {
    if (page() !== "live-streaming.html") {
      return;
    }

    var root = document.getElementById("arena-live-streams");
    if (root) {
      root.innerHTML = '<p class="text-muted text-center py-5">Loading streams…</p>';
    }

    global.api.streams.getLive().then(function (res) {
      var streams = (res.data && res.data.streams) || [];
      renderStreams(streams);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(typeof window !== "undefined" ? window : this);
