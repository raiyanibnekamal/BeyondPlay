/**
 * Shop details and wishlist — Arena API.
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

  function querySlug() {
    var params = new URLSearchParams(global.location.search);
    return params.get("slug") || params.get("id") || "";
  }

  function formatPrice(p) {
    var n = parseFloat(p);
    return isNaN(n) ? "0.00" : n.toFixed(2);
  }

  function loadShopDetails() {
    if (page() !== "shop-details.html") {
      return;
    }
    var slug = querySlug();
    if (!slug) {
      return;
    }
    global.api.shop.getProduct(slug).then(function (res) {
      if (!res.ok) {
        return;
      }
      var p = res.data.product || res.data;
      if (!p) {
        return;
      }
      document.querySelectorAll(".product-title").forEach(function (el) {
        el.textContent = p.name || "";
      });
      document.querySelectorAll(".product-about .text, #description p").forEach(function (el, i) {
        if (i === 0 && p.description) {
          el.textContent = p.description;
        }
      });
      var priceEl = document.querySelector(".product-about .price");
      if (priceEl) {
        var eff = p.effective_price != null ? p.effective_price : p.sale_price || p.price;
        if (p.sale_price && p.price > p.sale_price) {
          priceEl.innerHTML =
            "$" +
            esc(formatPrice(eff)) +
            '<del>$' +
            esc(formatPrice(p.price)) +
            "</del>";
        } else {
          priceEl.textContent = "$" + formatPrice(eff);
        }
      }
      var img = document.querySelector(".product-big-img img");
      if (img && p.image) {
        img.src = p.image.indexOf("http") === 0 ? p.image : p.image;
        img.alt = p.name || "";
      }
      var sku = document.querySelector(".sku");
      if (sku) {
        sku.textContent = p.sku || p.slug || "";
      }
      var stock = document.querySelector(".stock");
      if (stock) {
        stock.innerHTML =
          p.stock > 0
            ? '<i class="far fa-check-square me-2 ms-1"></i>In Stock'
            : '<i class="far fa-times-circle me-2 ms-1"></i>Out of Stock';
      }

      var productId = p.id;
      document.querySelectorAll(".product-about .actions .th-btn").forEach(function (btn) {
        if (btn.textContent.indexOf("Add to Cart") < 0) {
          return;
        }
        if (btn.dataset.arenaBound) {
          return;
        }
        btn.dataset.arenaBound = "1";
        btn.addEventListener("click", function (e) {
          e.preventDefault();
          var qtyInput = document.querySelector(".qty-input");
          var qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;
          if (global.ArenaCart) {
            global.ArenaCart.add(
              productId,
              p.name,
              p.effective_price || p.sale_price || p.price,
              qty,
              p.image
            );
            alert("Added to cart.");
          }
        });
      });

      document.querySelectorAll(".product-about .icon-btn .fa-heart").forEach(function (icon) {
        var link = icon.closest("a");
        if (!link || link.dataset.arenaBound) {
          return;
        }
        link.dataset.arenaBound = "1";
        link.href = "#";
        link.addEventListener("click", function (e) {
          e.preventDefault();
          if (!global.api.getAuthToken()) {
            global.location.href =
              "login.html?redirect=" + encodeURIComponent(global.location.href);
            return;
          }
          global.api.shop.addWishlist(productId).then(function (r) {
            alert(r.ok ? "Added to wishlist." : (r.data && r.data.message) || "Failed.");
          });
        });
      });
    });
  }

  function loadWishlist() {
    if (page() !== "wishlist.html") {
      return;
    }
    if (!global.api.getAuthToken()) {
      global.location.href =
        "login.html?redirect=" + encodeURIComponent("wishlist.html");
      return;
    }
    var tbody = document.querySelector(".tinvwl-table-manage-list tbody");
    if (!tbody) {
      return;
    }
    global.api.shop.getWishlist().then(function (res) {
      if (!res.ok) {
        return;
      }
      var items = (res.data && res.data.wishlist) || [];
      if (!items.length) {
        tbody.innerHTML =
          '<tr><td colspan="8" class="text-center">Your wishlist is empty.</td></tr>';
        return;
      }
      tbody.innerHTML = items
        .map(function (p) {
          var price = p.effective_price != null ? p.effective_price : p.price;
          var img = global.ArenaEscape
            ? global.ArenaEscape.safeMediaSrc(p.image, "assets/img/product/product_thumb_1_1.png")
            : (p.image || "assets/img/product/product_thumb_1_1.png");
          var added = (p.added_at || "").slice(0, 10);
          return (
            '<tr class="wishlist_item"><td class="product-cb"></td><td class="product-remove">' +
            '<button type="button" class="arena-wl-remove" data-id="' +
            esc(p.id) +
            '" title="Remove"><i class="fal fa-times"></i></button></td>' +
            '<td class="product-thumbnail"><a href="shop-details.html?slug=' +
            esc(p.slug) +
            '"><img src="' +
            esc(img) +
            '" alt=""></a></td>' +
            '<td class="product-name"><a href="shop-details.html?slug=' +
            esc(p.slug) +
            '">' +
            esc(p.name) +
            "</a></td>" +
            '<td class="product-price"><span class="woocommerce-Price-amount amount">$' +
            esc(formatPrice(price)) +
            "</span></td>" +
            '<td class="product-date"><time>' +
            esc(added) +
            "</time></td>" +
            '<td class="product-stock"><p class="stock in-stock">In stock</p></td>' +
            '<td class="product-action"><button type="button" class="button icon-btn arena-wl-cart" data-id="' +
            esc(p.id) +
            '" data-name="' +
            esc(p.name) +
            '" data-price="' +
            esc(price) +
            '" data-image="' +
            esc(img) +
            '"><i class="fal fa-shopping-cart"></i></button></td></tr>"
          );
        })
        .join("");

      tbody.querySelectorAll(".arena-wl-remove").forEach(function (btn) {
        btn.addEventListener("click", function () {
          global.api.shop.removeWishlist(btn.getAttribute("data-id")).then(function () {
            loadWishlist();
          });
        });
      });
      tbody.querySelectorAll(".arena-wl-cart").forEach(function (btn) {
        btn.addEventListener("click", function () {
          if (global.ArenaCart) {
            global.ArenaCart.add(
              btn.getAttribute("data-id"),
              btn.getAttribute("data-name"),
              btn.getAttribute("data-price"),
              1,
              btn.getAttribute("data-image")
            );
            alert("Added to cart.");
          }
        });
      });
    });
  }

  function init() {
    loadShopDetails();
    loadWishlist();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(typeof window !== "undefined" ? window : this);
