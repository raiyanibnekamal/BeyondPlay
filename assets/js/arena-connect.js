/**
 * Arena — wire key pages to the Laravel API (STEP 20).
 * Requires api.js loaded first.
 */
(function (global) {
  "use strict";

  if (!global.api) {
    return;
  }

  function esc(s) {
    if (s === null || s === undefined) {
      return "";
    }
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/"/g, "&quot;");
  }

  function page() {
    return (global.location.pathname.split("/").pop() || "index.html").toLowerCase();
  }

  function loadBanners() {
    if (typeof global.arenaRepositionHomeAnnouncement === "function") {
      global.arenaRepositionHomeAnnouncement();
    }
    var bar = document.getElementById("announcementBar");
    if (!bar) {
      return;
    }
    global.api.public.getActiveBanners().then(function (res) {
      if (!res.ok) {
        return;
      }
      var banners = (res.data && res.data.banners) || [];
      if (!banners.length) {
        bar.style.display = "none";
        var zone = document.getElementById("arenaAnnouncementZone");
        if (zone) {
          zone.style.display = "none";
        }
        return;
      }
      var zoneShow = document.getElementById("arenaAnnouncementZone");
      if (zoneShow) {
        zoneShow.style.display = "";
      }
      var b = banners[0];
      var safeLink = global.ArenaEscape ? global.ArenaEscape.safeHref(b.link) : esc(b.link);
      var linkColor = page() === "index.html" ? "#0b0e13" : "#fff";
      var link =
        b.link && safeLink !== "#"
          ? '<a href="' + safeLink + '" style="color:' + linkColor + ';text-decoration:underline;">Learn more</a>'
          : "";
      bar.querySelector("p").innerHTML =
        "<strong>" + esc(b.title) + ":</strong> " + esc(b.message) + (link ? " — " + link : "");
      bar.style.display = "block";
      if (typeof global.arenaLayoutHomeAnnouncementGap === "function") {
        global.arenaLayoutHomeAnnouncementGap();
      }
    });
    var closeBtn = document.getElementById("announcementClose");
    if (closeBtn) {
      closeBtn.addEventListener("click", function () {
        bar.style.display = "none";
      });
    }
  }

  function loadSponsors() {
    var slider = document.querySelector(".client-slider1 .swiper-wrapper");
    if (!slider || page() !== "about.html") {
      return;
    }
    global.api.public.getSponsors().then(function (res) {
      if (!res.ok) {
        return;
      }
      var sponsors = (res.data && res.data.sponsors) || [];
      if (!sponsors.length) {
        return;
      }
      slider.innerHTML = sponsors
        .map(function (s) {
          var href = global.ArenaEscape ? global.ArenaEscape.safeHref(s.website_url || "#") : esc(s.website_url || "#");
          var logo = global.ArenaEscape
            ? global.ArenaEscape.safeMediaSrc(s.logo, "assets/img/logo.svg")
            : esc(s.logo || "assets/img/logo.svg");
          return (
            '<div class="swiper-slide"><a href="' +
            href +
            '" class="client-card" target="_blank" rel="noopener"><img src="' +
            logo +
            '" alt="' +
            esc(s.name) +
            '"></a></div>'
          );
        })
        .join("");
    });
  }

  function bindResetPassword() {
    if (page() !== "reset-password.html") {
      return;
    }
    var form = document.getElementById("reset-password-form");
    if (!form || form.dataset.arenaAuthBound) {
      return;
    }
    form.dataset.arenaAuthBound = "1";
    var params = new URLSearchParams(global.location.search);
    var tokenInput = form.querySelector('[name="token"]') || document.getElementById("reset_token");
    var emailInput = form.querySelector('[name="email"]');
    if (tokenInput && params.get("token")) {
      tokenInput.value = params.get("token");
    }
    if (emailInput && params.get("email")) {
      emailInput.value = params.get("email");
    }
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var token = tokenInput ? tokenInput.value : "";
      var email = emailInput ? emailInput.value.trim() : "";
      var pass = form.querySelector('[name="password"]');
      var confirm = form.querySelector('[name="password_confirmation"]');
      if (!token || !email || !pass) {
        formMessage(form, "Missing reset token or email. Use the link from your email.", true);
        return;
      }
      global.api.auth
        .resetPassword({
          token: token,
          email: email,
          password: pass.value,
          password_confirmation: confirm ? confirm.value : pass.value,
        })
        .then(function (res) {
          if (res.ok) {
            formMessage(form, "Password updated. Redirecting to login…", false);
            setTimeout(function () {
              global.location.href = "login.html";
            }, 1500);
            return;
          }
          formMessage(form, apiErrorMessage(res, "Reset failed."), true);
        });
    });
  }

  function formMessage(form, text, isError) {
    var box = form.querySelector(".form-messages");
    if (!box) {
      return;
    }
    box.textContent = text;
    box.style.color = isError ? "#ff6b6b" : "#4ade80";
    box.style.fontWeight = "600";
    box.style.fontSize = "14px";
    box.style.display = "block";
    box.style.padding = "10px 14px";
    box.style.marginTop = "12px";
    box.style.borderRadius = "6px";
    box.style.background = isError ? "rgba(255,107,107,0.1)" : "rgba(74,222,128,0.1)";
    box.style.border = isError ? "1px solid rgba(255,107,107,0.3)" : "1px solid rgba(74,222,128,0.3)";
    box.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  function apiErrorMessage(res, fallback) {
    if (res.data && res.data.errors) {
      var errors = res.data.errors;
      var parts = [];
      Object.keys(errors).forEach(function (key) {
        (errors[key] || []).forEach(function (msg) {
          parts.push(msg);
        });
      });
      var text = parts.join(" ");
      if (/already been taken/i.test(text)) {
        text += ' Try logging in instead, or use a different email/username.';
      }
      return text || fallback;
    }
    return (res.data && res.data.message) || fallback;
  }

  function userIsAdmin(meRes) {
    if (!meRes.ok || !meRes.data) {
      return false;
    }
    var roles = meRes.data.roles || [];
    var user = meRes.data.user;
    return roles.indexOf("admin") >= 0 || (user && user.role === "admin");
  }

  function redirectAfterAuth(fallbackUrl) {
    return global.api.auth.me()
      .then(function (meRes) {
        if (userIsAdmin(meRes)) {
          global.location.href = "admin/index.html";
          return;
        }
        if (
          fallbackUrl &&
          fallbackUrl.indexOf("login") < 0 &&
          fallbackUrl.indexOf("register") < 0
        ) {
          global.location.href = fallbackUrl;
          return;
        }
        global.location.href = "my-profile.html";
      })
      .catch(function () {
        global.location.href = "my-profile.html";
      });
  }

  function bindLogin() {
    var form = document.getElementById("login-form");
    if (!form || form.dataset.arenaAuthBound) {
      return;
    }
    form.dataset.arenaAuthBound = "1";
    form.addEventListener(
      "submit",
      function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var email = form.querySelector('[name="email"]');
        var password = form.querySelector('[name="password"]');
        var btn = form.querySelector('[type="submit"]');
        if (!email || !password) {
          return;
        }
        if (btn) {
          btn.disabled = true;
        }
        formMessage(form, "Signing in…", false);
        global.api.auth
          .login({ email: email.value.trim(), password: password.value })
          .then(function (res) {
            if (btn) {
              btn.disabled = false;
            }
            if (res.ok && global.api.isAuthenticated()) {
              formMessage(form, "Login successful! Redirecting…", false);
              var params = new URLSearchParams(global.location.search);
              redirectAfterAuth(params.get("redirect"));
              return;
            }
            var errMsg = apiErrorMessage(res, "Login failed. Check email and password.");
            formMessage(form, errMsg, true);
            if (/verify your email/i.test(errMsg) && !document.getElementById("arena-login-resend")) {
              var hint = document.createElement("p");
              hint.id = "arena-login-resend";
              hint.className = "mt-3";
              hint.innerHTML =
                'Did not get the email? Log in is blocked until verified — contact support or use <a href="my-profile.html">profile</a> after a partial session if available.';
              var box = form.querySelector(".form-messages");
              if (box && box.parentElement) {
                box.parentElement.appendChild(hint);
              }
            }
          });
      },
      true
    );
  }

  function bindRegister() {
    var form = document.getElementById("register-form");
    if (!form || form.dataset.arenaAuthBound) {
      return;
    }
    form.dataset.arenaAuthBound = "1";
    form.addEventListener(
      "submit",
      function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();
      if (form.dataset.arenaSubmitting === "1") {
        return;
      }
      var username = form.querySelector('[name="username"]');
      var email = form.querySelector('[name="email"]');
      var password = form.querySelector('[name="password"]');
      var confirm = form.querySelector('[name="password_confirmation"]');
      var terms = form.querySelector('[name="terms"]');
      var btn = form.querySelector('[type="submit"]');
      if (!username || !email || !password) {
        return;
      }
      if (terms && !terms.checked) {
        formMessage(
          form,
          "Please check the box to accept the Terms & Conditions.",
          true
        );
        try {
          terms.focus();
        } catch (err) {}
        return;
      }
      var passVal = password.value;
      var confirmVal = confirm ? confirm.value : passVal;
      if (passVal !== confirmVal) {
        formMessage(form, "Passwords do not match.", true);
        return;
      }
      if (!confirmVal) {
        formMessage(form, "Please confirm your password.", true);
        return;
      }
      var pwCheck =
        typeof global.arenaPasswordMeetsRules === "function"
          ? global.arenaPasswordMeetsRules(passVal)
          : passVal.length >= 8;
      if (!pwCheck) {
        formMessage(
          form,
          global.arenaPasswordRuleMessage ||
            "Password must be at least 8 characters.",
          true
        );
        return;
      }
      var userVal = username.value.trim();
      var emailVal = email.value.trim();
      if (userVal.length < 3) {
        formMessage(form, "Username must be at least 3 characters.", true);
        return;
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
        formMessage(form, "Please enter a valid email address.", true);
        return;
      }
      form.dataset.arenaSubmitting = "1";
      if (btn) {
        btn.disabled = true;
      }
      formMessage(form, "Creating your account…", false);
      global.api.auth
        .register({
          username: userVal,
          email: emailVal,
          password: passVal,
          password_confirmation: confirmVal,
        })
        .then(function (res) {
          form.dataset.arenaSubmitting = "0";
          if (btn) {
            btn.disabled = false;
          }
          if (res.ok) {
            formMessage(
              form,
              "Account created! Check your email to verify, then log in.",
              false
            );
            global.api.setAuthToken("");
            global.location.href = "login.html";
            return;
          }
          formMessage(form, apiErrorMessage(res, "Registration failed. Please try again."), true);
        })
        .catch(function () {
          form.dataset.arenaSubmitting = "0";
          if (btn) {
            btn.disabled = false;
          }
          formMessage(
            form,
            "Cannot reach the API. Start the backend with php artisan serve.",
            true
          );
        });
    },
      true
    );
  }

  function bindForgotPassword() {
    var form = document.getElementById("forgot-password-form");
    if (!form || form.dataset.arenaAuthBound) {
      return;
    }
    form.dataset.arenaAuthBound = "1";
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var email = form.querySelector('[name="email"]');
      if (!email) {
        return;
      }
      global.api.auth.forgotPassword({ email: email.value.trim() }).then(function (res) {
        formMessage(
          form,
          res.ok
            ? (res.data && res.data.message) || "Check your email for reset instructions."
            : apiErrorMessage(res, "Request failed."),
          !res.ok
        );
      });
    });
  }

  function loadTournamentList() {
    if (page() !== "tournament.html") {
      return;
    }
    var container = document.querySelector(".filter-active");
    if (!container) {
      return;
    }
    global.api.tournaments.list().then(function (res) {
      if (!res.ok) {
        return;
      }
      var items = res.data.data || res.data || [];
      if (!items.length) {
        return;
      }
      var html = items
        .slice(0, 6)
        .map(function (t) {
          var status = t.status || "open";
          var tag = status === "completed" ? "Finished" : "Upcoming";
          var slug = t.slug || "";
          var start = t.start_date ? new Date(t.start_date).toLocaleDateString() : "";
          return (
            '<div class="col-12 filter-item"><div class="tournament-card style2">' +
            '<div class="tournament-card-content"><div class="tournament-card-details">' +
            '<h3 class="tournament-card-title"><a href="tournament-details.html?slug=' +
            esc(slug) +
            '">' +
            esc(t.name) +
            "</a></h3>" +
            '<p class="tournament-card-date">' +
            esc(start) +
            ' <span class="text-theme">' +
            esc(tag) +
            "</span></p>" +
            '<span class="tournament-card-tag gradient-border">' +
            esc(tag) +
            "</span>" +
            "</div></div></div></div>"
          );
        })
        .join("");
      var notice = document.createElement("p");
      notice.className = "col-12 mb-30 text-center text-theme";
      notice.textContent = "Live from API: " + items.length + " tournament(s)";
      container.insertBefore(notice, container.firstChild);
      container.insertAdjacentHTML("afterbegin", html);
    });
  }

  var cachedTournament = null;

  function updateHeaderAuth() {
    var token = global.api.getAuthToken();
    if (!token) {
      document.querySelectorAll('a[href="register.html"].th-btn.style2').forEach(function (a) {
        if (!a.classList.contains("arena-logout-bound")) {
          a.style.display = "";
        }
      });
      return;
    }
    global.api.auth.me().then(function (res) {
      if (!res.ok || !res.data || !res.data.user) {
        if (res.status === 401) {
          global.api.setAuthToken("");
        }
        return;
      }
      var user = res.data.user;
      document.querySelectorAll(".arena-notif-badge").forEach(function (el) {
        el.style.display = "inline-flex";
      });
      document.querySelectorAll('a[href="login.html"].th-btn').forEach(function (a) {
        if (/login/i.test(a.textContent)) {
          a.textContent = user.username;
          a.setAttribute("href", "my-profile.html");
          a.setAttribute("title", "My profile");
        }
      });
      document.querySelectorAll('a[href="register.html"].th-btn.style2').forEach(function (a) {
        if (!/register/i.test(a.textContent) || a.classList.contains("arena-logout-bound")) {
          return;
        }
        a.classList.add("arena-logout-bound");
        a.textContent = "Logout";
        a.setAttribute("href", "#");
        a.addEventListener("click", function (ev) {
          ev.preventDefault();
          global.api.auth.logout().finally(function () {
            global.api.setAuthToken("");
            global.location.href = "index.html";
          });
        });
      });
    });
  }

  function loadNotificationBadge() {
    if (!global.api.getAuthToken()) {
      return;
    }
    global.api.user.getNotifications().then(function (res) {
      if (!res.ok) {
        return;
      }
      var rows = (res.data && res.data.data) || [];
      var unread = rows.filter(function (n) {
        return !n.is_read;
      }).length;
      document.querySelectorAll(".arena-notif-badge").forEach(function (el) {
        el.textContent = unread > 99 ? "99+" : String(unread);
        el.style.display = unread > 0 ? "inline-flex" : "none";
      });
    });
  }

  function loadShopProducts() {
    if (page() !== "shop.html" && page() !== "index.html") {
      return;
    }
    global.api.shop.listProducts().then(function (res) {
      if (!res.ok) {
        return;
      }
      var items = (res.data && res.data.data) || res.data || [];
      if (!items.length) {
        return;
      }
      var grid = document.querySelector(".space-top .row.gy-40, .shop-area .row, #productSlider1 .swiper-wrapper");
      if (!grid) {
        return;
      }
      var html = items
        .slice(0, 8)
        .map(function (p) {
          var price = p.effective_price || p.sale_price || p.price;
          return (
            '<div class="col-xl-3 col-md-6"><div class="th-product product-grid">' +
            '<div class="product-content"><h3 class="product-title"><a href="shop-details.html?slug=' +
            esc(p.slug) +
            '">' +
            esc(p.name) +
            '</a></h3><span class="price">$' +
            esc(price) +
            "</span></div></div></div>"
          );
        })
        .join("");
      if (page() === "shop.html" && grid.classList.contains("row")) {
        grid.innerHTML =
          '<div class="col-12 mb-20"><p class="text-theme text-center">Showing ' +
          items.length +
          " products from API</p></div>" +
          html;
      }
    });
  }

  function loadGameDetails() {
    if (page() !== "game-details.html") {
      return;
    }
    var params = new URLSearchParams(global.location.search);
    var slug = params.get("slug") || params.get("id") || "";
    if (!slug) {
      return;
    }
    global.api.games.get(slug).then(function (res) {
      if (!res.ok || !res.data) {
        return;
      }
      var g = res.data;
      document.querySelectorAll(".breadcumb-title, .sec-title").forEach(function (el, i) {
        if (i < 2 && g.name) {
          el.textContent = g.name;
        }
      });
      var desc = document.querySelector(".game-details-text, .about-text, .sec-text");
      if (desc && g.description) {
        desc.textContent = g.description;
      }
    });
  }

  function loadGames() {
    if (page() !== "game.html") {
      return;
    }
    global.api.games.list().then(function (res) {
      if (!res.ok) {
        return;
      }
      var games = res.data || [];
      var wrap = document.querySelector(".game-card, .swiper-wrapper");
      if (!games.length || !wrap) {
        return;
      }
      var parent = wrap.closest(".row") || wrap.parentElement;
      if (parent) {
        var note = document.createElement("p");
        note.className = "text-center text-theme mb-30";
        note.textContent = games.length + " games loaded from API";
        parent.insertBefore(note, parent.firstChild);
      }
    });
  }

  function bindContact() {
    var form = document.getElementById("contact-form") || document.querySelector(".contact-form");
    if (!form) {
      return;
    }
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      global.api.contact
        .submit({
          name: form.querySelector('[name="name"]')?.value || "",
          email: form.querySelector('[name="email"]')?.value || "",
          subject: form.querySelector('[name="subject"]')?.value || "Contact",
          message: form.querySelector('[name="message"]')?.value || "",
        })
        .then(function (res) {
          alert(res.ok ? res.data.message || "Message sent!" : (res.data && res.data.message) || "Failed to send.");
          if (res.ok) {
            form.reset();
          }
        });
    });
  }

  function bindSuggestGame() {
    var form = document.getElementById("suggest-game-form");
    if (!form) {
      return;
    }
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!global.api.getAuthToken()) {
        global.location.href = "login.html";
        return;
      }
      global.api.games
        .suggest({
          name: form.querySelector('[name="name"]')?.value,
          genre: form.querySelector('[name="genre"]')?.value,
          description: form.querySelector('[name="description"]')?.value,
        })
        .then(function (res) {
          alert(res.ok ? "Suggestion submitted!" : (res.data && res.data.message) || "Failed.");
          if (res.ok) {
            form.reset();
          }
        });
    });
  }

  function wireStaticPlayerLinks() {
    document.querySelectorAll(".tournament-single-team").forEach(function (li) {
      if (li.querySelector("a.arena-player-link")) {
        return;
      }
      var name = "";
      li.childNodes.forEach(function (n) {
        if (n.nodeType === 3) {
          name += n.textContent;
        }
      });
      name = name.trim();
      if (!name) {
        return;
      }
      li.childNodes.forEach(function (n) {
        if (n.nodeType === 3) {
          li.removeChild(n);
        }
      });
      var a = document.createElement("a");
      a.className = "arena-player-link text-theme";
      a.href = "player-profile.html?user=" + encodeURIComponent(name);
      a.textContent = " " + name;
      li.appendChild(a);
    });
  }

  function loadTournamentDetails() {
    if (page() !== "tournament-details.html") {
      return;
    }
    var params = new URLSearchParams(global.location.search);
    var slug = params.get("slug") || "valorant-open-series";
    global.api.tournaments.get(slug).then(function (res) {
      if (!res.ok || !res.data) {
        wireStaticPlayerLinks();
        return;
      }
      cachedTournament = res.data;
      var t = res.data;
      document.title = "BeyondPlay | " + (t.name || "Tournament Details");
      var crumb = document.querySelector(".breadcumb-title");
      if (crumb) {
        crumb.textContent = t.name || crumb.textContent;
      }
      var crumbLi = document.querySelector(".breadcumb-menu li:last-child");
      if (crumbLi) {
        crumbLi.textContent = t.name || crumbLi.textContent;
      }
      document.querySelectorAll(".sec-title.page-title, h2.sec-title.page-title").forEach(function (el) {
        el.textContent = t.name || el.textContent;
      });
      document.querySelectorAll(".breadcumb-title, .sec-title").forEach(function (el, i) {
        if (i < 2 && !el.classList.contains("page-title")) {
          el.textContent = t.name || el.textContent;
        }
      });
      wireStaticPlayerLinks();
      global.api.tournaments.getMatches(t.id).then(function (mr) {
        if (!mr.ok || !mr.data || !mr.data.length) {
          return;
        }
        var m = mr.data[0];
        var t1 = m.team1;
        var t2 = m.team2;
        if (t1) {
          document.querySelectorAll(".tournament-card-title a").forEach(function (a, idx) {
            if (idx === 0) {
              a.textContent = t1.name;
              a.setAttribute("href", "team-details.html?id=" + t1.id);
            }
          });
        }
        if (t2) {
          document.querySelectorAll(".tournament-card-title a").forEach(function (a, idx) {
            if (idx === 1) {
              a.textContent = t2.name;
              a.setAttribute("href", "team-details.html?id=" + t2.id);
            }
          });
        }
      });
      var regBtn = document.getElementById("arena-register-btn");
      if (!regBtn) {
        regBtn = document.createElement("button");
        regBtn.id = "arena-register-btn";
        regBtn.className = "th-btn mt-20";
        regBtn.textContent = "Register for Tournament";
        var area = document.querySelector(".title-area, .tournament-card-content");
        if (area) {
          area.appendChild(regBtn);
        }
      }
      regBtn.onclick = function () {
        if (!global.api.getAuthToken()) {
          global.location.href = "login.html";
          return;
        }
        var fee = parseFloat(t.entry_fee || 0) || 0;
        function doRegister(paymentId) {
          var body = {};
          if (paymentId) {
            body.payment_id = paymentId;
          }
          global.api.tournaments.register(t.id, body).then(function (r) {
            alert(r.ok ? "Registered!" : (r.data && r.data.message) || "Registration failed.");
          });
        }
        if (fee > 0 && global.ArenaStripe) {
          global.ArenaStripe.isEnabled().then(function (on) {
            if (!on) {
              alert(
                "This tournament has an entry fee ($" +
                  fee.toFixed(2) +
                  "). Stripe is not configured on the server."
              );
              return;
            }
            var host = document.querySelector(".title-area, .tournament-card-content");
            if (host && !document.getElementById("arena-stripe-element")) {
              var panel = document.createElement("div");
              panel.id = "arena-stripe-element";
              panel.className = "arena-stripe-panel mt-20";
              host.appendChild(panel);
            }
            global.ArenaStripe.collectPayment(fee, "tournament_entry", t.id, "arena-stripe-element")
              .then(doRegister)
              .catch(function (err) {
                alert((err && err.message) || "Payment failed.");
              });
          });
          return;
        }
        doRegister(null);
      };
    });
  }

  function bindCheckin() {
    var btn = document.getElementById("arena-checkin-btn");
    if (!btn) {
      return;
    }
    var matchId = new URLSearchParams(global.location.search).get("match_id") || "1";
    btn.addEventListener("click", function () {
      if (!global.api.getAuthToken()) {
        global.location.href = "login.html";
        return;
      }
      global.api.matches.checkin(matchId, {}).then(function (res) {
        var status = document.getElementById("arena-checkin-my-status");
        if (res.ok) {
          if (status) {
            status.textContent = "Checked In";
          }
          btn.disabled = true;
          alert("Check-in successful!");
        } else {
          alert((res.data && res.data.message) || "Check-in failed.");
        }
      });
    });
  }

  function bindDispute() {
    var form = document.getElementById("dispute-form");
    if (!form) {
      return;
    }
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!global.api.getAuthToken()) {
        global.location.href = "login.html";
        return;
      }
      var matchId = new URLSearchParams(global.location.search).get("match_id") || "1";
      global.api.matches
        .dispute(matchId, {
          issue_type: form.querySelector('[name="issue_type"]')?.value,
          description: form.querySelector('[name="description"]')?.value,
          evidence_url: form.querySelector('[name="evidence_url"]')?.value,
        })
        .then(function (res) {
          alert(res.ok ? "Dispute submitted." : (res.data && res.data.message) || "Failed.");
        });
    });
  }

  function init() {
    loadBanners();
    loadSponsors();
    bindLogin();
    bindRegister();
    bindForgotPassword();
    bindResetPassword();
    loadTournamentList();
    loadTournamentDetails();
    updateHeaderAuth();
    loadNotificationBadge();
    loadShopProducts();
    loadGames();
    loadGameDetails();
    bindContact();
    bindSuggestGame();
    bindCheckin();
    bindDispute();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(typeof window !== "undefined" ? window : this);
