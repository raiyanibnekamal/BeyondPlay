/* Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
/**
 * Arena API client — Laravel backend (set window.ARENA_API_BASE in config.js for production).
 */
(function (global) {
  "use strict";

  function resolveApiBase() {
    if (global.ARENA_API_BASE) {
      return String(global.ARENA_API_BASE).replace(/\/$/, "");
    }
    if (typeof global.location !== "undefined" && /^https?:$/i.test(global.location.protocol)) {
      return global.location.protocol + "//" + global.location.hostname + ":8000/api/v1";
    }
    return "http://127.0.0.1:8000/api/v1";
  }

  function resolveApiOrigin() {
    return resolveApiBase().replace(/\/api\/v1\/?$/, "");
  }

  function resolveMediaUrl(url) {
    var u = String(url || "").trim();
    if (!u) {
      return "";
    }
    if (/^https?:\/\//i.test(u)) {
      return u;
    }
    if (u.indexOf("/storage/") === 0) {
      return resolveApiOrigin() + u;
    }
    if (u.charAt(0) === "/") {
      return resolveApiOrigin() + u;
    }
    return u;
  }

  const API_BASE = resolveApiBase();
  const AUTH_TOKEN_KEY = "arena_auth_token";
  const AUTH_SESSION_FLAG = "arena_auth_session";
  const USE_COOKIE_AUTH = !!global.ARENA_USE_COOKIE_AUTH;

  function fetchCredentials() {
    return USE_COOKIE_AUTH ? "include" : "same-origin";
  }

  function isAuthenticated() {
    return !!getAuthToken();
  }

  function getAuthToken() {
    if (USE_COOKIE_AUTH) {
      try {
        return sessionStorage.getItem(AUTH_SESSION_FLAG) === "1" ? "__cookie__" : "";
      } catch (e) {
        return "";
      }
    }
    try {
      return localStorage.getItem(AUTH_TOKEN_KEY) || "";
    } catch (e) {
      return "";
    }
  }

  function setAuthToken(token) {
    if (USE_COOKIE_AUTH) {
      try {
        if (token) {
          sessionStorage.setItem(AUTH_SESSION_FLAG, "1");
        } else {
          sessionStorage.removeItem(AUTH_SESSION_FLAG);
        }
      } catch (e) {
        /* ignore */
      }
      return;
    }
    try {
      if (token) {
        localStorage.setItem(AUTH_TOKEN_KEY, token);
      } else {
        localStorage.removeItem(AUTH_TOKEN_KEY);
      }
    } catch (e) {
      /* ignore */
    }
  }

  function handleAuthSuccess(res) {
    if (!res.ok || !res.data) {
      return;
    }
    if (res.data.token && typeof res.data.token === "string" && res.data.token.length > 0) {
      setAuthToken(res.data.token);
    }
  }

  function ensureCsrfCookie() {
    if (!USE_COOKIE_AUTH) {
      return Promise.resolve();
    }
    var origin = API_BASE.replace(/\/api\/v1\/?$/, "");
    return fetch(origin + "/sanctum/csrf-cookie", {
      method: "GET",
      credentials: "include",
    }).catch(function () {
      return null;
    });
  }

  function authHeaders() {
    const headers = {
      "Content-Type": "application/json",
      Accept: "application/json",
    };
    const token = getAuthToken();
    if (!USE_COOKIE_AUTH && token && token !== "__cookie__") {
      headers.Authorization = "Bearer " + token;
    }
    return headers;
  }

  function parseJsonResponse(res) {
    return res.json().then(function (body) {
      return { ok: res.ok, status: res.status, data: body };
    });
  }

  function withQuery(path, params) {
    if (!params || typeof params !== "object") {
      return path;
    }
    const parts = [];
    Object.keys(params).forEach(function (key) {
      if (params[key] !== undefined && params[key] !== null) {
        parts.push(encodeURIComponent(key) + "=" + encodeURIComponent(params[key]));
      }
    });
    return parts.length ? path + "?" + parts.join("&") : path;
  }

  function currentPagePath() {
    if (typeof global.location === "undefined") {
      return "";
    }
    var parts = global.location.pathname.split("/");
    return parts[parts.length - 1] || "index.html";
  }

  function buildHeaders(auth) {
    const headers = auth ? authHeaders() : {
      "Content-Type": "application/json",
      Accept: "application/json",
    };
    const page = currentPagePath();
    if (page) {
      headers["X-Arena-Page"] = page;
    }
    return headers;
  }

  function handleUnauthorized() {
    setAuthToken("");
    if (typeof global.location !== "undefined" && !/login\.html$/i.test(global.location.pathname)) {
      global.location.href = "login.html";
    }
  }

  function requestMultipart(path, formData, auth) {
    const headers = { Accept: "application/json" };
    const token = getAuthToken();
    if (auth && token && !USE_COOKIE_AUTH && token !== "__cookie__") {
      headers.Authorization = "Bearer " + token;
    }
    const page = currentPagePath();
    if (page) {
      headers["X-Arena-Page"] = page;
    }
    return fetch(API_BASE + path, {
      method: "POST",
      headers: headers,
      body: formData,
      credentials: fetchCredentials(),
    })
      .then(parseJsonResponse)
      .then(function (res) {
        if (res.status === 401 && auth) {
          handleUnauthorized();
        }
        return res;
      })
      .catch(function () {
        return {
          ok: false,
          data: { message: "Cannot reach the API." },
        };
      });
  }

  function request(method, path, data, auth) {
    const endpoint = method + " " + path;
    let urlPath = path;
    if (method === "GET" && data && typeof data === "object") {
      urlPath = withQuery(path, data);
      data = undefined;
    }
    const opts = {
      method: method,
      headers: buildHeaders(auth),
      credentials: fetchCredentials(),
    };
    if (data !== undefined && method !== "GET") {
      opts.body = JSON.stringify(data);
    }
    return fetch(API_BASE + urlPath, opts)
      .then(parseJsonResponse)
      .then(function (res) {
        if (res.status === 401 && auth) {
          handleUnauthorized();
        }
        return res;
      })
      .catch(function () {
        console.log("API: " + endpoint + " failed (offline)");
        return {
          ok: false,
          offline: true,
          endpoint: endpoint,
          data: {
            message:
              "Cannot reach the API. Run php artisan serve in tournament-backend and open this site at http://127.0.0.1:5500 (not file://).",
          },
        };
      });
  }

  /**
   * Laravel Echo + Pusher (configure BROADCAST_* and VITE_PUSHER_* when enabling realtime).
   * Subscribes to private user.{id} for notification.new events.
   */
  function initEcho(userId, callbacks) {
    if (!userId || typeof window.Echo === "undefined") {
      console.log("API: Echo not configured — realtime notifications disabled");
      return null;
    }
    const echo = new window.Echo({
      broadcaster: "pusher",
      key: window.ARENA_PUSHER_KEY || "",
      cluster: window.ARENA_PUSHER_CLUSTER || "mt1",
      forceTLS: true,
      authEndpoint: API_BASE.replace("/api/v1", "") + "/broadcasting/auth",
      auth: {
        headers: {
          Authorization: "Bearer " + getAuthToken(),
          Accept: "application/json",
        },
      },
    });
    echo
      .private("user." + userId)
      .listen(".notification.new", function (payload) {
        if (callbacks && typeof callbacks.onNotification === "function") {
          callbacks.onNotification(payload);
        }
      });
    return echo;
  }

  const api = {
    base: API_BASE,
    origin: resolveApiOrigin(),
    resolveMediaUrl: resolveMediaUrl,
    request: request,
    requestMultipart: requestMultipart,
    getAuthToken: getAuthToken,
    setAuthToken: setAuthToken,
    isAuthenticated: isAuthenticated,
    useCookieAuth: USE_COOKIE_AUTH,
    initEcho: initEcho,

    auth: {
      register: function (data) {
        return ensureCsrfCookie().then(function () {
          return request("POST", "/auth/register", data);
        }).then(function (res) {
          handleAuthSuccess(res);
          return res;
        });
      },
      login: function (data) {
        return ensureCsrfCookie().then(function () {
          return request("POST", "/auth/login", data);
        }).then(function (res) {
          handleAuthSuccess(res);
          return res;
        });
      },
      logout: function () {
        return request("POST", "/auth/logout", undefined, true).then(function (res) {
          setAuthToken("");
          return res;
        });
      },
      forgotPassword: function (data) {
        return request("POST", "/auth/forgot-password", data);
      },
      resetPassword: function (data) {
        return request("POST", "/auth/reset-password", data);
      },
      me: function () {
        return request("GET", "/auth/me", undefined, true);
      },
      resendVerification: function () {
        return request("POST", "/auth/resend-verification", undefined, true);
      },
    },

    public: {
      getActiveBanners: function () {
        return request("GET", "/banners/active");
      },
      getSponsors: function () {
        return request("GET", "/sponsors");
      },
      lookupUser: function (username) {
        return request("GET", "/users/lookup?username=" + encodeURIComponent(username));
      },
      submitContact: function (data) {
        return request("POST", "/contact", data);
      },
      subscribeNewsletter: function (data) {
        return request("POST", "/newsletter/subscribe", data);
      },
    },

    user: {
      getProfile: function () {
        return request("GET", "/auth/me", undefined, true);
      },
      updateProfile: function (data) {
        return request("PUT", "/user/profile", data, true);
      },
      uploadAvatar: function (file) {
        var fd = new FormData();
        fd.append("avatar", file);
        return requestMultipart("/user/avatar", fd, true);
      },
      getTournaments: function () {
        return request("GET", "/user/tournaments", undefined, true);
      },
      getOrders: function () {
        return request("GET", "/user/orders", undefined, true);
      },
      getNotifications: function () {
        return request("GET", "/user/notifications", undefined, true);
      },
      markNotificationsRead: function () {
        return request("PUT", "/user/notifications/read", undefined, true);
      },
      getAchievements: function () {
        return request("GET", "/user/achievements", undefined, true);
      },
      getStats: function (params) {
        return request("GET", "/user/stats", params, true);
      },
      getActivityFeed: function (params) {
        return request("GET", "/user/activity-feed", params, true);
      },
      getCart: function () {
        return request("GET", "/user/cart", undefined, true);
      },
      syncCart: function (data) {
        return request("PUT", "/user/cart", data, true);
      },
    },

    payments: {
      getConfig: function () {
        return request("GET", "/payments/config");
      },
      createStripeIntent: function (data) {
        return request("POST", "/payments/stripe/intent", data, true);
      },
    },

    search: function (q) {
      return request("GET", "/search", { q: q });
    },

    tournaments: {
      list: function (params) {
        return request("GET", "/tournaments", params);
      },
      history: function (params) {
        return request("GET", "/tournaments/history", params);
      },
      get: function (slug) {
        return request("GET", "/tournaments/" + slug);
      },
      getRules: function (id) {
        return request("GET", "/tournaments/" + id + "/rules");
      },
      create: function (data) {
        return request("POST", "/admin/tournaments", data, true);
      },
      register: function (id, data) {
        return request("POST", "/tournaments/" + id + "/register", data, true);
      },
      withdraw: function (id) {
        return request("DELETE", "/tournaments/" + id + "/register", undefined, true);
      },
      getBracket: function (id) {
        return request("GET", "/tournaments/" + id + "/bracket");
      },
      getMatches: function (id) {
        return request("GET", "/tournaments/" + id + "/matches");
      },
      getStandings: function (id) {
        return request("GET", "/tournaments/" + id + "/standings");
      },
      predict: function (id, data) {
        return request("POST", "/tournaments/" + id + "/predict", data, true);
      },
      getPredictions: function (id) {
        return request("GET", "/tournaments/" + id + "/predictions");
      },
      getHistory: function () {
        return request("GET", "/tournaments/history");
      },
    },

    matches: {
      get: function (id) {
        return request("GET", "/matches/" + id);
      },
      getReplay: function (id) {
        return request("GET", "/matches/" + id + "/replay");
      },
      checkin: function (id, data) {
        return request("POST", "/matches/" + id + "/checkin", data || {}, true);
      },
      dispute: function (id, data) {
        return request("POST", "/matches/" + id + "/dispute", data, true);
      },
    },

    teams: {
      list: function (params) {
        return request("GET", "/teams", params, true);
      },
      get: function (slug) {
        return request("GET", "/teams/" + slug);
      },
      create: function (data) {
        return request("POST", "/teams", data, true);
      },
      update: function (id, data) {
        return request("PUT", "/teams/" + id, data, true);
      },
      invite: function (id, data) {
        return request("POST", "/teams/" + id + "/invite", data, true);
      },
      myInvites: function () {
        return request("GET", "/teams/invites/mine", undefined, true);
      },
      acceptInvite: function (inviteId) {
        return request("PUT", "/teams/invites/" + inviteId + "/accept", undefined, true);
      },
      declineInvite: function (inviteId) {
        return request("PUT", "/teams/invites/" + inviteId + "/decline", undefined, true);
      },
    },

    games: {
      list: function () {
        return request("GET", "/games");
      },
      get: function (slug) {
        return request("GET", "/games/" + slug);
      },
      suggest: function (data) {
        return request("POST", "/games/suggest", data, true);
      },
      getMySuggestions: function () {
        return request("GET", "/games/suggestions/mine", undefined, true);
      },
    },

    stats: {
      getPlayer: function (userId, params) {
        return request("GET", "/stats/player/" + userId, params);
      },
      headToHead: function (params) {
        return request("GET", "/stats/head-to-head", params);
      },
    },

    social: {
      getFriends: function () {
        return request("GET", "/friends", undefined, true);
      },
      getFriendRequests: function () {
        return request("GET", "/friends/requests", undefined, true);
      },
      getSentFriendRequests: function () {
        return request("GET", "/friends/requests/sent", undefined, true);
      },
      getFriendStatus: function (userId) {
        return request("GET", "/friends/status/" + userId, undefined, true);
      },
      sendFriendRequest: function (userId) {
        return request("POST", "/friends/request/" + userId, undefined, true);
      },
      acceptFriendRequest: function (id) {
        return request("PUT", "/friends/request/" + id + "/accept", undefined, true);
      },
      declineFriendRequest: function (id) {
        return request("PUT", "/friends/request/" + id + "/decline", undefined, true);
      },
      cancelFriendRequest: function (id) {
        return request("DELETE", "/friends/request/" + id + "/cancel", undefined, true);
      },
      removeFriend: function (userId) {
        return request("DELETE", "/friends/" + userId, undefined, true);
      },
      sendChallenge: function (data) {
        return request("POST", "/challenges", data, true);
      },
      getIncomingChallenges: function () {
        return request("GET", "/challenges/incoming", undefined, true);
      },
      getSentChallenges: function () {
        return request("GET", "/challenges/sent", undefined, true);
      },
      getChallengeStatus: function (userId) {
        return request("GET", "/challenges/status/" + userId, undefined, true);
      },
      acceptChallenge: function (id) {
        return request("PUT", "/challenges/" + id + "/accept", undefined, true);
      },
      declineChallenge: function (id) {
        return request("PUT", "/challenges/" + id + "/decline", undefined, true);
      },
      cancelChallenge: function (id) {
        return request("DELETE", "/challenges/" + id + "/cancel", undefined, true);
      },
    },

    streams: {
      getLive: function () {
        return request("GET", "/streams/live");
      },
    },

    shop: {
      listProducts: function (params) {
        return request("GET", "/products", params);
      },
      getProduct: function (slug) {
        return request("GET", "/products/" + slug);
      },
      validateCoupon: function (data) {
        return request("POST", "/cart/validate-coupon", data, true);
      },
      createOrder: function (data) {
        return request("POST", "/orders", data, true);
      },
      getOrder: function (id) {
        return request("GET", "/orders/" + id, undefined, true);
      },
      addWishlist: function (productId) {
        return request("POST", "/wishlist/" + productId, undefined, true);
      },
      removeWishlist: function (productId) {
        return request("DELETE", "/wishlist/" + productId, undefined, true);
      },
      getWishlist: function () {
        return request("GET", "/wishlist", undefined, true);
      },
    },

    blog: {
      listPosts: function (params) {
        return request("GET", "/posts", params);
      },
      getPost: function (slug) {
        return request("GET", "/posts/" + slug);
      },
      addComment: function (id, data) {
        return request("POST", "/posts/" + id + "/comments", data, true);
      },
    },

    contact: {
      submit: function (data) {
        return request("POST", "/contact", data);
      },
      subscribeNewsletter: function (data) {
        return request("POST", "/newsletter/subscribe", data);
      },
    },

    admin: {
      getStats: function () {
        return request("GET", "/admin/stats", undefined, true);
      },
      getSettings: function () {
        return request("GET", "/admin/settings", undefined, true);
      },
      updateSettings: function (data) {
        return request("PUT", "/admin/settings", data, true);
      },
      listUsers: function (params) {
        return request("GET", "/admin/users", params, true);
      },
      updateUserStatus: function (id, data) {
        return request("PUT", "/admin/users/" + id + "/status", data, true);
      },
      banUser: function (id) {
        return request("PUT", "/admin/users/" + id + "/status", { status: "banned" }, true);
      },
      promoteUser: function (id) {
        return request("PUT", "/admin/users/" + id + "/promote", undefined, true);
      },
      listTournaments: function (params) {
        return request("GET", "/admin/tournaments", params, true);
      },
      createTournament: function (data) {
        return request("POST", "/admin/tournaments", data, true);
      },
      updateTournament: function (id, data) {
        return request("PUT", "/admin/tournaments/" + id, data, true);
      },
      deleteTournament: function (id) {
        return request("DELETE", "/admin/tournaments/" + id, undefined, true);
      },
      saveTournamentRules: function (id, data) {
        return request("POST", "/admin/tournaments/" + id + "/rules", data, true);
      },
      generateBracket: function (id) {
        return request("POST", "/admin/tournaments/" + id + "/generate-bracket", undefined, true);
      },
      listMatches: function (params) {
        return request("GET", "/admin/matches", params, true);
      },
      updateMatchScore: function (id, data) {
        return request("PUT", "/admin/matches/" + id + "/score", data, true);
      },
      addMatchReplay: function (id, data) {
        return request("POST", "/admin/matches/" + id + "/replay", data, true);
      },
      listGameSuggestions: function (params) {
        return request("GET", "/admin/game-suggestions", params, true);
      },
      approveGameSuggestion: function (id) {
        return request("PUT", "/admin/game-suggestions/" + id + "/approve", undefined, true);
      },
      rejectGameSuggestion: function (id, data) {
        return request("PUT", "/admin/game-suggestions/" + id + "/reject", data, true);
      },
      listGames: function (params) {
        return request("GET", "/admin/games", params, true);
      },
      createGame: function (data) {
        return request("POST", "/admin/games", data, true);
      },
      updateGame: function (id, data) {
        return request("PUT", "/admin/games/" + id, data, true);
      },
      deleteGame: function (id) {
        return request("DELETE", "/admin/games/" + id, undefined, true);
      },
      listDisputes: function (params) {
        return request("GET", "/admin/disputes", params, true);
      },
      resolveDispute: function (id, data) {
        return request("PUT", "/admin/disputes/" + id + "/resolve", data, true);
      },
      listProducts: function (params) {
        return request("GET", "/admin/products", params, true);
      },
      createProduct: function (data) {
        return request("POST", "/admin/products", data, true);
      },
      updateProduct: function (id, data) {
        return request("PUT", "/admin/products/" + id, data, true);
      },
      deleteProduct: function (id) {
        return request("DELETE", "/admin/products/" + id, undefined, true);
      },
      listOrders: function (params) {
        return request("GET", "/admin/orders", params, true);
      },
      updateOrderStatus: function (id, data) {
        return request("PUT", "/admin/orders/" + id + "/status", data, true);
      },
      listCoupons: function (params) {
        return request("GET", "/admin/coupons", params, true);
      },
      createCoupon: function (data) {
        return request("POST", "/admin/coupons", data, true);
      },
      updateCoupon: function (id, data) {
        return request("PUT", "/admin/coupons/" + id, data, true);
      },
      deleteCoupon: function (id) {
        return request("DELETE", "/admin/coupons/" + id, undefined, true);
      },
      listPosts: function (params) {
        return request("GET", "/admin/posts", params, true);
      },
      createPost: function (data) {
        return request("POST", "/admin/posts", data, true);
      },
      updatePost: function (id, data) {
        return request("PUT", "/admin/posts/" + id, data, true);
      },
      publishPost: function (id, data) {
        return request("PUT", "/admin/posts/" + id + "/publish", data, true);
      },
      deletePost: function (id) {
        return request("DELETE", "/admin/posts/" + id, undefined, true);
      },
      approveComment: function (id) {
        return request("PUT", "/admin/comments/" + id + "/approve", undefined, true);
      },
      deleteComment: function (id) {
        return request("DELETE", "/admin/comments/" + id, undefined, true);
      },
      sendNotification: function (data) {
        return request("POST", "/admin/notifications/send", data, true);
      },
      bulkEmail: function (data) {
        return request("POST", "/admin/bulk-email", data, true);
      },
      listBanners: function () {
        return request("GET", "/admin/banners", undefined, true);
      },
      createBanner: function (data) {
        return request("POST", "/admin/banners", data, true);
      },
      updateBanner: function (id, data) {
        return request("PUT", "/admin/banners/" + id, data, true);
      },
      deleteBanner: function (id) {
        return request("DELETE", "/admin/banners/" + id, undefined, true);
      },
      listSponsors: function () {
        return request("GET", "/admin/sponsors", undefined, true);
      },
      createSponsor: function (data) {
        return request("POST", "/admin/sponsors", data, true);
      },
      updateSponsor: function (id, data) {
        return request("PUT", "/admin/sponsors/" + id, data, true);
      },
      deleteSponsor: function (id) {
        return request("DELETE", "/admin/sponsors/" + id, undefined, true);
      },
      analyticsOverview: function () {
        return request("GET", "/admin/analytics/overview", undefined, true);
      },
      analyticsTournaments: function () {
        return request("GET", "/admin/analytics/tournaments", undefined, true);
      },
      listMessages: function (params) {
        return request("GET", "/admin/contact-messages", params, true);
      },
      markMessageRead: function (id) {
        return request("PUT", "/admin/contact-messages/" + id + "/read", undefined, true);
      },
    },
  };

  global.api = api;
})(typeof window !== "undefined" ? window : this);
