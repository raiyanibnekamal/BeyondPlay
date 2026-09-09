/**
 * Arena frontend form validation.
 */
(function (root) {
  "use strict";

  const PASSWORD_RULE_MSG = "Password must be at least 8 characters.";

  const SUCCESS_MSG = "Unable to submit this form. Please try again or contact support.";

  function passwordMeetsArenaRules(value) {
    return String(value || "").length >= 8;
  }

  root.arenaPasswordMeetsRules = passwordMeetsArenaRules;
  root.arenaPasswordRuleMessage = PASSWORD_RULE_MSG;

  /** Handled by arena-connect.js / user-connect.js — do not block their submit handlers */
  const DELEGATED_FORM_IDS = [
    "login-form",
    "register-form",
    "forgot-password-form",
    "reset-password-form",
    "profile-form",
  ];

  function isDelegatedForm(form) {
    return DELEGATED_FORM_IDS.indexOf(form.id) >= 0;
  }

  const FORM_SELECTORS = [
    ".ajax-contact",
    ".newsletter-form",
    ".th-comment-form",
    "#login-form",
    "#register-form",
    "#forgot-password-form",
    "#reset-password-form",
    ".login-form",
    ".register-form",
    ".forgot-password-form",
    ".reset-password-form",
    'form[action*="login"]',
    'form[action*="register"]',
    'form[action*="forgot"]',
  ];

  function getFieldLabel(field) {
    const id = field.getAttribute("id");
    if (id) {
      const label = document.querySelector('label[for="' + id + '"]');
      if (label) return label.textContent.replace("*", "").trim();
    }
    return (
      field.getAttribute("placeholder") ||
      field.getAttribute("name") ||
      "This field"
    );
  }

  function showError(field, message) {
    field.classList.add("is-invalid");
    field.classList.remove("is-valid");
    let err = field.parentElement.querySelector(".arena-field-error");
    if (!err) {
      err = document.createElement("span");
      err.className = "arena-field-error";
      err.style.cssText = "display:block;color:#ff6b6b;font-size:13px;margin-top:6px;";
      field.parentElement.appendChild(err);
    }
    err.textContent = message;
  }

  function clearError(field) {
    field.classList.remove("is-invalid");
    const err = field.parentElement.querySelector(".arena-field-error");
    if (err) err.remove();
  }

  function showFormSuccess(form, message) {
    let box =
      form.querySelector(".form-messages") ||
      form.querySelector(".arena-form-success");
    if (!box) {
      box = document.createElement("p");
      box.className = "form-messages arena-form-success mt-3 mb-0 success";
      form.appendChild(box);
    }
    box.classList.remove("error");
    box.classList.add("success");
    box.textContent = message || SUCCESS_MSG;
    box.style.color = "var(--theme-color)";
  }

  function isEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value).trim());
  }

  function validateField(field, form) {
    const type = (field.getAttribute("type") || "").toLowerCase();
    const name = (field.getAttribute("name") || "").toLowerCase();
    const tag = field.tagName.toLowerCase();
    const value = tag === "select" ? field.value : String(field.value || "").trim();
    const required =
      field.hasAttribute("required") ||
      field.classList.contains("required") ||
      (field.getAttribute("placeholder") || "").indexOf("*") !== -1;

    if (field.type === "checkbox" && name.indexOf("terms") !== -1 && !field.checked) {
      var termsRow = field.closest(".arena-terms-row");
      if (termsRow) {
        var hint = termsRow.querySelector(".arena-terms-hint-error");
        if (!hint) {
          hint = document.createElement("p");
          hint.className = "arena-terms-hint-error mb-0 mt-2";
          hint.style.cssText = "color:#ff6b6b;font-size:13px;";
          termsRow.appendChild(hint);
        }
        hint.textContent = "You must accept the Terms & Conditions to register.";
      } else {
        showError(field, "You must accept the Terms & Conditions to register.");
      }
      return false;
    }
    if (field.type === "checkbox" && name.indexOf("terms") !== -1 && field.checked) {
      var termsErr = field.closest(".arena-terms-row");
      if (termsErr) {
        var errBox = termsErr.querySelector(".arena-terms-hint-error");
        if (errBox) {
          errBox.remove();
        }
      }
    }

    if (type === "checkbox" || type === "radio" || type === "hidden" || type === "submit") {
      clearError(field);
      return true;
    }

    if (required && !value) {
      showError(field, getFieldLabel(field) + " is required.");
      return false;
    }

    if (!value) {
      clearError(field);
      return true;
    }

    if (type === "email" || name === "email") {
      if (!isEmail(value)) {
        showError(field, "Please enter a valid email address.");
        return false;
      }
    }

    var minLen = parseInt(field.getAttribute("minlength") || "", 10);
    if (minLen > 0 && value.length < minLen) {
      showError(
        field,
        getFieldLabel(field) + " must be at least " + minLen + " characters."
      );
      return false;
    }

    if (
      (type === "url" || name.indexOf("url") !== -1) &&
      value &&
      !/^https?:\/\/.+/i.test(value)
    ) {
      showError(field, "Please enter a valid URL (http:// or https://).");
      return false;
    }

    if (name === "username" && value.length < 3) {
      showError(field, "Username must be at least 3 characters.");
      return false;
    }

    if (type === "password" || name === "password") {
      if (!passwordMeetsArenaRules(value)) {
        showError(field, PASSWORD_RULE_MSG);
        return false;
      }
    }

    if (
      name === "password_confirmation" ||
      name === "confirm_password" ||
      name === "confirm-password"
    ) {
      const pass =
        form.querySelector('[name="password"]') ||
        form.querySelector('[type="password"]');
      if (pass && value !== pass.value) {
        showError(field, "Passwords do not match.");
        return false;
      }
    }

    clearError(field);
    field.classList.add("is-valid");
    return true;
  }

  function validateForm(form) {
    const fields = form.querySelectorAll("input, textarea, select");
    let valid = true;
    fields.forEach(function (field) {
      if (!validateField(field, form)) valid = false;
    });
    return valid;
  }

  function bindForm(form) {
    if (form.dataset.arenaValidationBound) return;
    form.dataset.arenaValidationBound = "1";

    const fields = form.querySelectorAll("input, textarea, select");
    fields.forEach(function (field) {
      field.addEventListener("blur", function () {
        validateField(field, form);
      });
      field.addEventListener("input", function () {
        if (field.classList.contains("is-invalid")) {
          validateField(field, form);
        }
      });
    });

    if (isDelegatedForm(form)) {
      return;
    }

    form.addEventListener(
      "submit",
      function (e) {
        e.preventDefault();
        if (!validateForm(form)) {
          e.stopImmediatePropagation();
          return false;
        }
        e.stopImmediatePropagation();
        showFormSuccess(form, SUCCESS_MSG);
        if (window.api && form.classList.contains("newsletter-form")) {
          const email = form.querySelector('[type="email"]');
          if (email) {
            window.api.contact.subscribeNewsletter({ email: email.value });
          }
        }
        if (
          window.api &&
          form.classList.contains("ajax-contact") &&
          form.id === "contact-form"
        ) {
          window.api.public.submitContact({
            name: form.querySelector('[name="name"]')?.value || "",
            email: form.querySelector('[name="email"]')?.value || "",
            subject: form.querySelector('[name="subject"]')?.value || "Contact",
            message: form.querySelector('[name="message"]')?.value || "",
          });
        }
        return false;
      },
      true
    );
  }

  function init() {
    const seen = new Set();
    FORM_SELECTORS.forEach(function (selector) {
      document.querySelectorAll(selector).forEach(function (form) {
        if (seen.has(form)) return;
        seen.add(form);
        bindForm(form);
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(typeof window !== "undefined" ? window : this);
