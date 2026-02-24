(function () {
  "use strict";

  var root = document.querySelector(".bk-admin-wrap");
  if (!root) return;

  root.classList.add("bk-js");

  var tabs = Array.prototype.slice.call(document.querySelectorAll(".bk-tab[data-tab]"));
  var panels = {};
  var tabInput = document.getElementById("barakah_tab");

  tabs.forEach(function (tab) {
    var slug = tab.getAttribute("data-tab");
    var panel = document.getElementById("bk-panel-" + slug);
    if (panel) panels[slug] = panel;
  });

  function activateTab(slug, focusTab) {
    if (!panels[slug]) slug = "general";

    tabs.forEach(function (tab) {
      var isActive = tab.getAttribute("data-tab") === slug;
      tab.classList.toggle("is-active", isActive);
      tab.setAttribute("aria-selected", isActive ? "true" : "false");
      tab.setAttribute("tabindex", isActive ? "0" : "-1");

      var panel = panels[tab.getAttribute("data-tab")];
      if (!panel) return;
      panel.classList.toggle("is-active", isActive);
      if (isActive) {
        panel.removeAttribute("hidden");
        if (focusTab) tab.focus();
      } else {
        panel.setAttribute("hidden", "hidden");
      }
    });

    if (tabInput) tabInput.value = slug;

    var nextUrl = new URL(window.location.href);
    nextUrl.searchParams.set("tab", slug);
    window.history.replaceState({}, "", nextUrl.toString());
  }

  function onTabClick(event) {
    var tab = event.currentTarget;
    var slug = tab.getAttribute("data-tab");
    if (!slug) return;
    event.preventDefault();
    activateTab(slug, false);
  }

  function onTabKeydown(event) {
    var current = event.currentTarget;
    var idx = tabs.indexOf(current);
    if (idx < 0) return;

    var nextIdx = idx;

    if (event.key === "ArrowRight") {
      nextIdx = (idx + 1) % tabs.length;
    } else if (event.key === "ArrowLeft") {
      nextIdx = (idx - 1 + tabs.length) % tabs.length;
    } else if (event.key === "Home") {
      nextIdx = 0;
    } else if (event.key === "End") {
      nextIdx = tabs.length - 1;
    } else if (event.key !== "Enter" && event.key !== " ") {
      return;
    }

    event.preventDefault();

    if (event.key === "Enter" || event.key === " ") {
      activateTab(current.getAttribute("data-tab"), false);
      return;
    }

    activateTab(tabs[nextIdx].getAttribute("data-tab"), true);
  }

  tabs.forEach(function (tab) {
    tab.addEventListener("click", onTabClick);
    tab.addEventListener("keydown", onTabKeydown);
  });

  activateTab(root.getAttribute("data-active-tab") || "general", false);

  var popupEnable = document.getElementById("barakah_greeting_popup_enable");
  var popupSettings = document.getElementById("bk-popup-settings");
  var popupScopeInputs = document.querySelectorAll('input[name="barakah_greeting_popup_scope"]');
  var pagePicker = document.getElementById("bk-page-picker");

  function togglePopupSettings() {
    if (!popupEnable || !popupSettings) return;
    popupSettings.hidden = !popupEnable.checked;
  }

  function togglePagePicker() {
    if (!pagePicker) return;

    var scope = "all";
    popupScopeInputs.forEach(function (input) {
      if (input.checked) scope = input.value;
    });

    pagePicker.hidden = scope !== "specific";
  }

  if (popupEnable) {
    popupEnable.addEventListener("change", togglePopupSettings);
    togglePopupSettings();
  }

  popupScopeInputs.forEach(function (input) {
    input.addEventListener("change", togglePagePicker);
  });
  togglePagePicker();

  var stickyEnable = document.getElementById("barakah_sticky_bar_enable");
  var stickySettings = document.getElementById("bk-sticky-settings");
  var stickyScopeInputs = document.querySelectorAll('input[name="barakah_sticky_scope"]');
  var stickyPagePicker = document.getElementById("bk-sticky-page-picker");

  function toggleStickySettings() {
    if (!stickyEnable || !stickySettings) return;
    stickySettings.hidden = !stickyEnable.checked;
  }

  function toggleStickyPagePicker() {
    if (!stickyPagePicker) return;

    var scope = "all";
    stickyScopeInputs.forEach(function (input) {
      if (input.checked) scope = input.value;
    });

    stickyPagePicker.hidden = scope !== "specific";
  }

  if (stickyEnable) {
    stickyEnable.addEventListener("change", toggleStickySettings);
    toggleStickySettings();
  }

  stickyScopeInputs.forEach(function (input) {
    input.addEventListener("change", toggleStickyPagePicker);
  });
  toggleStickyPagePicker();

  var previewBtn = document.getElementById("bk-preview-popup-btn");

  function escHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/\"/g, "&quot;");
  }

  function closePreview() {
    var overlay = document.getElementById("bk-admin-popup-overlay");
    if (overlay) overlay.remove();
  }

  function openPreview() {
    closePreview();

    var titleEl = document.getElementById("barakah_greeting_popup_title");
    var msgEl = document.getElementById("barakah_greeting_popup_msg");
    var title = titleEl && titleEl.value ? titleEl.value : "Ramadan Mubarak";
    var msg = msgEl && msgEl.value ? msgEl.value : "Wishing you and your family a blessed month of Ramadan!";

    var overlay = document.createElement("div");
    overlay.id = "bk-admin-popup-overlay";
    overlay.className = "bk-greeting-overlay";
    overlay.innerHTML =
      '<div class="bk-greeting-panel" role="dialog" aria-modal="true" aria-label="Greeting popup preview">' +
      '<div class="bk-greeting-deco">' +
      '<div class="bk-greeting-ornament bk-greeting-ornament-l"></div>' +
      '<div class="bk-greeting-ornament bk-greeting-ornament-r"></div>' +
      '<div class="bk-greeting-moon" aria-hidden="true">☄</div>' +
      '<div class="bk-greeting-lanterns">' +
      '<span class="bk-lantern bk-lantern-1"></span>' +
      '<span class="bk-lantern bk-lantern-2"></span>' +
      '<span class="bk-lantern bk-lantern-3"></span>' +
      "</div>" +
      "</div>" +
      '<div class="bk-greeting-bismillah">بِسمِ اللّهِ الرَّحمٰنِ الرَّحِيمِ</div>' +
      '<div class="bk-greeting-kareem">رمضان كريم</div>' +
      '<div class="bk-greeting-title">' + escHtml(title) + "</div>" +
      (msg ? '<div class="bk-greeting-msg">' + escHtml(msg) + "</div>" : "") +
      '<div class="bk-greeting-border-art"></div>' +
      "</div>";

    overlay.addEventListener("click", function (event) {
      if (event.target === overlay) closePreview();
    });

    document.body.appendChild(overlay);

    setTimeout(closePreview, 6000);
  }

  if (previewBtn) {
    previewBtn.addEventListener("click", openPreview);
  }
})();
