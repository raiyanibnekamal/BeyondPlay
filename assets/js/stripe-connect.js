/**
 * Stripe PaymentIntents — shop checkout & tournament entry fees.
 * Requires api.js. Loads Stripe.js when STRIPE_KEY is configured on the API.
 */
(function (global) {
  "use strict";

  if (!global.api || !global.api.payments) {
    return;
  }

  var configCache = null;
  var stripeInstance = null;
  var elementsInstance = null;
  var paymentElement = null;

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      if (document.querySelector('script[src="' + src + '"]')) {
        resolve();
        return;
      }
      var s = document.createElement("script");
      s.src = src;
      s.onload = resolve;
      s.onerror = reject;
      document.head.appendChild(s);
    });
  }

  function loadStripeJs() {
    return loadScript("https://js.stripe.com/v3/");
  }

  function getConfig() {
    if (configCache) {
      return Promise.resolve(configCache);
    }
    return global.api.payments.getConfig().then(function (res) {
      if (res.ok && res.data) {
        configCache = res.data;
      }
      return configCache || {};
    });
  }

  function isEnabled() {
    return getConfig().then(function (cfg) {
      return !!(cfg && cfg.stripe_enabled && cfg.stripe_publishable_key);
    });
  }

  function ensureContainer(containerId) {
    var el = document.getElementById(containerId);
    if (!el) {
      el = document.createElement("div");
      el.id = containerId;
      el.className = "arena-stripe-panel mb-30";
      var host =
        document.querySelector(".woocommerce-checkout-payment") ||
        document.querySelector("#checkout-form") ||
        document.body;
      host.insertBefore(el, host.firstChild);
    }
    return el;
  }

  function mountPaymentElement(containerId, clientSecret, publishableKey) {
    return loadStripeJs().then(function () {
      if (!global.Stripe) {
        throw new Error("Stripe.js failed to load.");
      }
      stripeInstance = global.Stripe(publishableKey);
      elementsInstance = stripeInstance.elements({ clientSecret: clientSecret });
      if (paymentElement) {
        try {
          paymentElement.unmount();
        } catch (e) {}
      }
      paymentElement = elementsInstance.create("payment");
      var container = ensureContainer(containerId);
      container.innerHTML = "<h4 class=\"text-white mb-3\">Card payment</h4>";
      paymentElement.mount(container);
      return { stripe: stripeInstance, elements: elementsInstance };
    });
  }

  /**
   * Create intent, mount Elements, confirm payment. Resolves with payment intent id.
   */
  function collectPayment(amount, purpose, referenceId, containerId) {
    containerId = containerId || "arena-stripe-element";
    return getConfig().then(function (cfg) {
      if (!cfg.stripe_enabled || !cfg.stripe_publishable_key) {
        return null;
      }
      if (amount <= 0) {
        return null;
      }
      return global.api.payments
        .createStripeIntent({
          amount: amount,
          purpose: purpose,
          reference_id: referenceId || undefined,
        })
        .then(function (res) {
          if (!res.ok || !res.data || !res.data.client_secret) {
            throw new Error(
              (res.data && res.data.message) || "Could not start payment."
            );
          }
          return mountPaymentElement(
            containerId,
            res.data.client_secret,
            cfg.stripe_publishable_key
          ).then(function (ctx) {
            return ctx.stripe
              .confirmPayment({
                elements: ctx.elements,
                confirmParams: {
                  return_url: global.location.href,
                },
                redirect: "if_required",
              })
              .then(function (result) {
                if (result.error) {
                  throw new Error(result.error.message || "Payment failed.");
                }
                var intent = result.paymentIntent;
                if (!intent || intent.status !== "succeeded") {
                  throw new Error("Payment was not completed.");
                }
                return intent.id;
              });
          });
        });
    });
  }

  global.ArenaStripe = {
    getConfig: getConfig,
    isEnabled: isEnabled,
    mountPaymentElement: mountPaymentElement,
    collectPayment: collectPayment,
    ensureContainer: ensureContainer,
  };
})(typeof window !== "undefined" ? window : this);
