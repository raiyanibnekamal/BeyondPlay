/* Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
/**

 * Checkout — place order via Arena API (Stripe when configured).

 */

(function (global) {

  "use strict";



  if (!global.api || !global.ArenaCart) {

    return;

  }



  function page() {

    return (global.location.pathname.split("/").pop() || "").toLowerCase();

  }



  function msg(box, text, isError) {

    if (!box) {

      return;

    }

    box.textContent = text;

    box.style.color = isError ? "#ff6b6b" : "var(--theme-color)";

  }



  function resolveLineItems(cart) {

    return global.api.shop.listProducts({ per_page: 100 }).then(function (res) {

      var catalog = (res.data && res.data.data) || [];

      var items = [];

      cart.forEach(function (line) {

        var pid = parseInt(line.id, 10);

        if (!isNaN(pid) && pid > 0) {

          items.push({ product_id: pid, quantity: line.quantity || 1 });

          return;

        }

        var match = catalog.find(function (p) {

          return (

            p.slug === line.id ||

            p.name === line.name ||

            String(p.id) === String(line.id)

          );

        });

        if (match) {

          items.push({ product_id: match.id, quantity: line.quantity || 1 });

        }

      });

      return { items: items, catalog: catalog };

    });

  }



  function cartSubtotal(cart) {

    return cart.reduce(function (s, l) {

      return s + (l.price || 0) * (l.quantity || 1);

    }, 0);

  }



  function renderCartSummary() {

    var cart = global.ArenaCart.getCart();

    var list =

      document.querySelector(".woocommerce-checkout-review-order-table tbody") ||

      document.querySelector(".order-review tbody") ||

      document.querySelector(".cart_table tbody");

    if (!list || !cart.length) {

      return;

    }

    list.innerHTML = cart

      .map(function (line) {

        var price = (line.price || 0) * (line.quantity || 1);

        return (

          "<tr><td>" +

          (line.name || "Item") +

          " × " +

          (line.quantity || 1) +

          "</td><td>$" +

          price.toFixed(2) +

          "</td></tr>"

        );

      })

      .join("");

    var totalEl =

      document.querySelector(".order-total .amount") ||

      document.querySelector(".woocommerce-Price-amount.amount");

    if (totalEl) {

      totalEl.textContent = "$" + cartSubtotal(cart).toFixed(2);

    }

  }



  function prepareStripeUI() {

    if (!global.ArenaStripe) {

      return;

    }

    global.ArenaStripe.isEnabled().then(function (enabled) {

      if (!enabled) {

        return;

      }

      var bank = document.querySelector(".payment_method_bacs");

      if (bank) {

        bank.style.display = "none";

      }

      global.ArenaStripe.ensureContainer("arena-stripe-element");

    });

  }



  function placeOrder(form, triggerBtn) {

    var box =

      form.querySelector(".form-messages") ||

      document.querySelector(".woocommerce-checkout-payment .form-messages");

    if (!box) {

      box = document.createElement("p");

      box.className = "form-messages mt-3";

      var host = document.querySelector(".woocommerce-checkout-payment") || form;

      host.appendChild(box);

    }



    var cart = global.ArenaCart.getCart();

    if (!cart.length) {

      msg(box, "Your cart is empty.", true);

      return;

    }



    if (triggerBtn) {

      triggerBtn.disabled = true;

    }

    msg(box, "Placing order…", false);



    resolveLineItems(cart).then(function (resolved) {

      var items = resolved.items;

      if (!items.length) {

        msg(

          box,

          "Could not match cart items to products. Add items from the shop page.",

          true

        );

        if (triggerBtn) {

          triggerBtn.disabled = false;

        }

        return;

      }



      var total = cartSubtotal(cart);

      var payload = {

        items: items,

        coupon_code: global.ArenaCart.getCoupon() || undefined,

        payment_method: "stripe",

        shipping_name:

          form.querySelector('[name="shipping_name"]')?.value ||

          form.querySelector('[name="billing_first_name"]')?.value ||

          "Customer",

        shipping_address:

          form.querySelector('[name="shipping_address"]')?.value ||

          form.querySelector('[name="billing_address_1"]')?.value ||

          "N/A",

        shipping_city:

          form.querySelector('[name="shipping_city"]')?.value ||

          form.querySelector('[name="billing_city"]')?.value ||

          "N/A",

        shipping_phone:

          form.querySelector('[name="shipping_phone"]')?.value ||

          form.querySelector('[name="billing_phone"]')?.value ||

          "000",

      };



      function submitOrder(paymentId) {

        if (paymentId) {

          payload.payment_id = paymentId;

          payload.payment_method = "stripe";

        } else {

          payload.payment_method = "manual";

        }

        return global.api.shop.createOrder(payload).then(function (res) {

          if (triggerBtn) {

            triggerBtn.disabled = false;

          }

          if (res.ok) {

            var pending = res.data && res.data.payment_status === "pending";

            if (!pending) {

              global.ArenaCart.clearCart();

              global.ArenaCart.clearCoupon();

            }

            msg(

              box,

              pending

                ? "Order placed — payment pending. Complete payment or wait for admin confirmation."

                : "Order placed! Redirecting…",

              false

            );

            if (!pending) {

              global.location.href = "my-orders.html";

            }

            return;

          }

          var err =

            (res.data && res.data.message) ||

            (res.data && res.data.errors

              ? Object.values(res.data.errors).flat().join(" ")

              : "Order failed.");

          msg(box, err, true);

        });

      }



      var stripeFlow =

        global.ArenaStripe && total > 0

          ? global.ArenaStripe.isEnabled().then(function (on) {

              if (!on) {

                return null;

              }

              msg(box, "Complete card payment…", false);

              return global.ArenaStripe.collectPayment(

                total,

                "shop_order",

                undefined,

                "arena-stripe-element"

              );

            })

          : Promise.resolve(null);



      stripeFlow.then(submitOrder).catch(function (err) {

        if (triggerBtn) {

          triggerBtn.disabled = false;

        }

        msg(box, (err && err.message) || "Payment failed.", true);

      });

    });

  }



  function bindCheckout() {

    if (page() !== "checkout.html") {

      return;

    }



    if (!global.api.getAuthToken()) {

      global.location.href =

        "login.html?redirect=" + encodeURIComponent("checkout.html");

      return;

    }



    renderCartSummary();

    prepareStripeUI();



    var form =

      document.getElementById("checkout-form") ||

      document.querySelector("form.checkout-form, .woocommerce-checkout");

    var placeBtn = document.querySelector(".place-order .th-btn, .woocommerce-checkout-payment .th-btn");



    if (form && !form.dataset.arenaCheckoutBound) {

      form.dataset.arenaCheckoutBound = "1";



      var couponBtn = form.querySelector("#arena-apply-coupon, .arena-apply-coupon");

      if (couponBtn) {

        couponBtn.addEventListener("click", function (e) {

          e.preventDefault();

          var codeInput = form.querySelector('[name="coupon_code"], #coupon_code');

          var code = codeInput ? codeInput.value.trim() : global.ArenaCart.getCoupon();

          if (!code) {

            alert("Enter a coupon code.");

            return;

          }

          resolveLineItems(global.ArenaCart.getCart()).then(function (resolved) {

            return global.api.shop.validateCoupon({

              coupon_code: code,

              items: resolved.items,

            });

          }).then(function (res) {

            if (res.ok) {

              global.ArenaCart.applyCoupon(code);

              alert((res.data && res.data.message) || "Coupon applied.");

            } else {

              alert((res.data && res.data.message) || "Invalid coupon.");

            }

          });

        });

      }



      form.addEventListener(

        "submit",

        function (e) {

          e.preventDefault();

          e.stopImmediatePropagation();

          if (!form.checkValidity()) {

            form.reportValidity();

            return;

          }

          placeOrder(form, placeBtn);

        },

        true

      );

    }



    if (placeBtn && !placeBtn.dataset.arenaCheckoutBound) {

      placeBtn.dataset.arenaCheckoutBound = "1";

      placeBtn.addEventListener("click", function (e) {

        e.preventDefault();

        placeOrder(form || document, placeBtn);

      });

    }

  }



  if (document.readyState === "loading") {

    document.addEventListener("DOMContentLoaded", bindCheckout);

  } else {

    bindCheckout();

  }

})(typeof window !== "undefined" ? window : this);

