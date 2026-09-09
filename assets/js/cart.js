/* Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
/**
 * Arena localStorage cart — synced to server at checkout via Order API.
 * Cart state is browser-local until place order; use the same browser to checkout.
 */
(function () {
  "use strict";

  const CART_KEY = "arena_cart";
  const COUPON_KEY = "arena_coupon";

  function parsePrice(text) {
    if (text == null) return 0;
    const n = parseFloat(String(text).replace(/[^0-9.]/g, ""));
    return isNaN(n) ? 0 : n;
  }

  function getCart() {
    try {
      const raw = localStorage.getItem(CART_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch (e) {
      return [];
    }
  }

  function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCartBadge();
    scheduleServerSync();
  }

  var syncTimer = null;
  function scheduleServerSync() {
    if (!window.api || typeof window.api.getAuthToken !== "function" || !window.api.getAuthToken()) {
      return;
    }
    if (syncTimer) {
      clearTimeout(syncTimer);
    }
    syncTimer = setTimeout(function () {
      window.api.user
        .syncCart({
          items: getCart(),
          coupon_code: getCoupon() || null,
        })
        .catch(function () {});
    }, 400);
  }

  function pullServerCart() {
    if (!window.api || typeof window.api.getAuthToken !== "function" || !window.api.getAuthToken()) {
      return;
    }
    window.api.user.getCart().then(function (res) {
      if (!res.ok || !res.data) {
        return;
      }
      var items = res.data.items;
      if (Array.isArray(items) && items.length) {
        localStorage.setItem(CART_KEY, JSON.stringify(items));
        if (res.data.coupon_code) {
          localStorage.setItem(COUPON_KEY, res.data.coupon_code);
        }
        updateCartBadge();
        if (document.querySelector(".cart_table")) {
          renderCartPage();
        }
      } else if (getCart().length) {
        scheduleServerSync();
      }
    });
  }

  function addToCart(productId, name, price, quantity, image) {
    const cart = getCart();
    const id = String(productId);
    const qty = Math.max(1, parseInt(quantity, 10) || 1);
    const unitPrice = parsePrice(price);
    const existing = cart.find(function (item) {
      return item.id === id;
    });
    if (existing) {
      existing.quantity += qty;
    } else {
      cart.push({
        id: id,
        name: name || "Product",
        price: unitPrice,
        quantity: qty,
        image: image || "assets/img/product/product_1_1.png",
      });
    }
    saveCart(cart);
    return cart;
  }

  function removeFromCart(productId) {
    const id = String(productId);
    const cart = getCart().filter(function (item) {
      return item.id !== id;
    });
    saveCart(cart);
    return cart;
  }

  function updateCartQuantity(productId, quantity) {
    const id = String(productId);
    const qty = Math.max(1, parseInt(quantity, 10) || 1);
    const cart = getCart().map(function (item) {
      if (item.id === id) {
        item.quantity = qty;
      }
      return item;
    });
    saveCart(cart);
    return cart;
  }

  function clearCart() {
    localStorage.removeItem(CART_KEY);
    updateCartBadge();
    return [];
  }

  function getCartCount() {
    return getCart().reduce(function (sum, item) {
      return sum + (item.quantity || 1);
    }, 0);
  }

  function getCartSubtotal() {
    return getCart().reduce(function (sum, item) {
      return sum + item.price * (item.quantity || 1);
    }, 0);
  }

  function updateCartBadge() {
    const count = getCartCount();
    document.querySelectorAll(".arena-cart-badge, .cart-count").forEach(function (el) {
      el.textContent = String(count);
      el.style.display = count > 0 ? "inline-flex" : "none";
    });
  }

  function ensureCartBadge() {
    if (document.querySelector(".cart-count, .arena-cart-badge")) {
      updateCartBadge();
      return;
    }
    const wrap =
      document.querySelector(".arena-nav-icons") ||
      document.querySelector(".arena-nav-actions") ||
      document.querySelector(".header-button");
    if (!wrap) return;
    const link = document.createElement("a");
    link.href = "cart.html";
    link.className = "simple-icon position-relative arena-cart-link";
    link.title = "Cart";
    link.innerHTML =
      '<i class="far fa-shopping-cart"></i><span class="cart-count arena-cart-badge" style="display:none;position:absolute;top:-6px;right:-8px;min-width:18px;height:18px;padding:0 5px;border-radius:9px;background:var(--theme-color2);color:#fff;font-size:10px;font-weight:700;align-items:center;justify-content:center;">0</span>';
    const search = wrap.querySelector(".searchBoxToggler");
    if (search && search.nextSibling) {
      wrap.insertBefore(link, search.nextSibling);
    } else {
      wrap.appendChild(link);
    }
    updateCartBadge();
  }

  function slugifyId(text) {
    return String(text)
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-|-$/g, "") || "product";
  }

  function extractProductFromCard(card) {
    const titleEl = card.querySelector(".product-title");
    const priceEl = card.querySelector(".price");
    const imgEl = card.querySelector(".product-img img, img");
    const name = titleEl ? titleEl.textContent.trim() : "Product";
    const price = priceEl ? priceEl.textContent.trim() : "0";
    const image = imgEl ? imgEl.getAttribute("src") : "";
    const id =
      (imgEl && imgEl.getAttribute("src") && imgEl.getAttribute("src").match(/product[^.]*|\d+_\d+/)) ||
      slugifyId(name);
    const productId = Array.isArray(id) ? id[0] : id;
    return { id: productId, name: name, price: price, image: image };
  }

  function hookAddToCartButtons() {
    document.querySelectorAll(".th-product").forEach(function (card) {
      const cartLink = card.querySelector('a[href*="cart"], .fa-cart-plus');
      if (!cartLink) return;
      const anchor = cartLink.closest("a") || cartLink;
      if (anchor.dataset.arenaCartBound) return;
      anchor.dataset.arenaCartBound = "1";
      anchor.addEventListener("click", function (e) {
        e.preventDefault();
        const p = extractProductFromCard(card);
        addToCart(p.id, p.name, p.price, 1, p.image);
        console.log("Added to cart:", p.name);
      });
    });

    document.querySelectorAll(".product-about").forEach(function (about) {
      const btn = about.querySelector(".th-btn");
      if (!btn || btn.textContent.indexOf("Add to Cart") === -1) return;
      if (btn.dataset.arenaCartBound) return;
      btn.dataset.arenaCartBound = "1";
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        const title = about.querySelector(".product-title");
        const price = about.querySelector(".price");
        const img =
          document.querySelector(".product-big-img img") ||
          document.querySelector(".product-img img");
        const name = title ? title.textContent.trim() : "Product";
        const id = slugifyId(name);
        addToCart(
          id,
          name,
          price ? price.textContent : 0,
          about.querySelector(".qty-input")
            ? about.querySelector(".qty-input").value
            : 1,
          img ? img.getAttribute("src") : ""
        );
        console.log("Added to cart:", name);
      });
    });
  }

  function applyCoupon(code) {
    const trimmed = (code || "").trim();
    if (!trimmed) return false;
    localStorage.setItem(COUPON_KEY, trimmed);
    return true;
  }

  function getCoupon() {
    return localStorage.getItem(COUPON_KEY) || "";
  }

  function clearCoupon() {
    localStorage.removeItem(COUPON_KEY);
  }

  function showCouponMessage(container, message, isSuccess) {
    if (!container) return;
    let el = container.querySelector(".arena-coupon-message");
    if (!el) {
      el = document.createElement("p");
      el.className = "arena-coupon-message mt-2 mb-0";
      container.appendChild(el);
    }
    el.textContent = message;
    el.style.color = isSuccess ? "var(--theme-color)" : "#ff6b6b";
  }

  function hookCouponInputs() {
    document.querySelectorAll(".coupon-form").forEach(function (form) {
      if (form.dataset.arenaCouponBound) return;
      form.dataset.arenaCouponBound = "1";
      const input = form.querySelector("input");
      const btn = form.querySelector("button");
      const wrap = form.closest(".coupon-apply-wrap") || form.parentElement;
      if (getCoupon()) {
        showCouponMessage(wrap, 'Coupon applied: "' + getCoupon() + '"', true);
      }
      if (btn) {
        btn.addEventListener("click", function () {
          if (applyCoupon(input ? input.value : "")) {
            showCouponMessage(wrap, 'Coupon applied: "' + getCoupon() + '"', true);
          } else {
            showCouponMessage(wrap, "Please enter a coupon code.", false);
          }
        });
      }
    });
  }

  function renderCartPage() {
    const tbody = document.querySelector(".cart_table tbody");
    if (!tbody) return;

    const cart = getCart();
    if (cart.length === 0) {
      tbody.innerHTML =
        '<tr><td colspan="6" class="text-center py-5">Your cart is empty. <a href="shop.html">Continue shopping</a></td></tr>';
      updateCartTotals(0);
      return;
    }

    tbody.innerHTML = cart
      .map(function (item) {
        const lineTotal = (item.price * item.quantity).toFixed(2);
        return (
          '<tr class="cart_item" data-product-id="' +
          item.id +
          '">' +
          '<td data-title="Product"><a class="cart-productimage" href="shop-details.html"><img width="91" height="91" src="' +
          item.image +
          '" alt=""></a></td>' +
          '<td data-title="Name"><a class="cart-productname" href="shop-details.html">' +
          item.name +
          "</a></td>" +
          '<td data-title="Price"><span class="amount"><bdi><span>$</span>' +
          item.price.toFixed(2) +
          "</bdi></span></td>" +
          '<td data-title="Quantity"><div class="quantity">' +
          '<button type="button" class="quantity-minus qty-btn arena-cart-qty-minus"><i class="far fa-minus"></i></button>' +
          '<input type="number" class="qty-input arena-cart-qty" value="' +
          item.quantity +
          '" min="1" max="99" data-product-id="' +
          item.id +
          '">' +
          '<button type="button" class="quantity-plus qty-btn arena-cart-qty-plus"><i class="far fa-plus"></i></button>' +
          "</div></td>" +
          '<td data-title="Total"><span class="amount"><bdi><span>$</span>' +
          lineTotal +
          "</bdi></span></td>" +
          '<td data-title="Remove"><a href="#" class="remove arena-cart-remove" data-product-id="' +
          item.id +
          '"><i class="fal fa-trash-alt"></i></a></td>' +
          "</tr>"
        );
      })
      .join("");

    tbody.querySelectorAll(".arena-cart-remove").forEach(function (link) {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        removeFromCart(link.getAttribute("data-product-id"));
        renderCartPage();
      });
    });

    tbody.querySelectorAll(".arena-cart-qty").forEach(function (input) {
      input.addEventListener("change", function () {
        updateCartQuantity(input.getAttribute("data-product-id"), input.value);
        renderCartPage();
      });
    });

    tbody.querySelectorAll(".arena-cart-qty-minus").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const row = btn.closest("tr");
        const input = row.querySelector(".arena-cart-qty");
        const v = Math.max(1, parseInt(input.value, 10) - 1);
        updateCartQuantity(input.getAttribute("data-product-id"), v);
        renderCartPage();
      });
    });

    tbody.querySelectorAll(".arena-cart-qty-plus").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const row = btn.closest("tr");
        const input = row.querySelector(".arena-cart-qty");
        const v = Math.min(99, parseInt(input.value, 10) + 1);
        updateCartQuantity(input.getAttribute("data-product-id"), v);
        renderCartPage();
      });
    });

    updateCartTotals(getCartSubtotal());
    hookCouponInputs();
  }

  function updateCartTotals(subtotal) {
    const totals = document.querySelector(".cart_totals");
    if (!totals) return;
    const subEl = totals.querySelector("tr:first-child .amount bdi, tr:first-child .amount");
    if (subEl) {
      subEl.innerHTML = "<span>$</span>" + subtotal.toFixed(2);
    }
    const orderTotal = totals.querySelector(".order-total .amount bdi, .order-total .amount");
    if (orderTotal) {
      orderTotal.innerHTML = "<span>$</span>" + subtotal.toFixed(2);
    }
  }

  function init() {
    ensureCartBadge();
    if (typeof window.arenaRebuildNavbar === "function") {
      window.arenaRebuildNavbar();
    }
    updateCartBadge();
    hookAddToCartButtons();
    hookCouponInputs();
    pullServerCart();
    if (document.querySelector(".cart_table")) {
      renderCartPage();
    }
  }

  window.addToCart = addToCart;
  window.removeFromCart = removeFromCart;
  window.getCart = getCart;
  window.clearCart = clearCart;
  window.updateCartBadge = updateCartBadge;
  window.updateCartQuantity = updateCartQuantity;
  window.ArenaCart = {
    getCart: getCart,
    clearCart: clearCart,
    getCoupon: getCoupon,
    clearCoupon: clearCoupon,
    applyCoupon: applyCoupon,
    getCartSubtotal: getCartSubtotal,
    addToCart: addToCart,
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
