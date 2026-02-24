/* Barakah – Ramadan Prayer Times Widget v1.0.0
   Fetches live prayer times from the Aladhan API (api.aladhan.com)
   No external dependencies. Pure vanilla JS.
*/
(function () {
  "use strict";

  /* ── Dua / Dhikr data ───────────────────────────────────────────────────── */

  var DUAS = [
    {
      arabic:         "اللَّهُمَّ إِنَّكَ عَفُوٌّ تُحِبُّ الْعَفْوَ فَاعْفُ عَنِّي",
      transliteration:"Allahumma innaka 'afuwwun tuhibbul-'afwa fa'fu 'anni",
      translation:    "O Allah, You are the Pardoner and You love to pardon, so pardon me.",
      source:         "Tirmidhi — Laylatul Qadr Dua",
    },
    {
      arabic:         "رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً وَقِنَا عَذَابَ النَّارِ",
      transliteration:"Rabbana atina fid-dunya hasanatan wa fil-akhirati hasanatan wa qina 'adhaban-nar",
      translation:    "Our Lord! Grant us good in this world and good in the Hereafter, and protect us from the Fire.",
      source:         "Surah Al-Baqarah 2:201",
    },
    {
      arabic:         "اللَّهُمَّ إِنِّي أَسْأَلُكَ الْجَنَّةَ وَأَعُوذُ بِكَ مِنَ النَّارِ",
      transliteration:"Allahumma inni as'alukal-jannata wa a'udhu bika minan-nar",
      translation:    "O Allah! I ask You for Paradise and I seek Your protection from the Fire.",
      source:         "Abu Dawud",
    },
    {
      arabic:         "سُبْحَانَ اللهِ وَبِحَمْدِهِ سُبْحَانَ اللهِ الْعَظِيم",
      transliteration:"Subhanallahi wa bihamdihi, Subhanallahil-'Adheem",
      translation:    "Glory be to Allah and His is the praise; Glory be to Allah the Magnificent.",
      source:         "Bukhari & Muslim",
    },
    {
      arabic:         "اللَّهُمَّ أَعِنِّي عَلَى ذِكْرِكَ وَشُكْرِكَ وَحُسْنِ عِبَادَتِكَ",
      transliteration:"Allahumma a'inni 'ala dhikrika wa shukrika wa husni 'ibadatik",
      translation:    "O Allah, help me to remember You, to thank You, and to worship You in the best manner.",
      source:         "Abu Dawud & An-Nasa'i",
    },
    {
      arabic:         "رَبِّ اغْفِرْ لِي وَتُبْ عَلَيَّ إِنَّكَ أَنتَ التَّوَّابُ الرَّحِيم",
      transliteration:"Rabbighfirli wa tub 'alayya innaka antat-tawwabur-rahim",
      translation:    "My Lord, forgive me and accept my repentance. Verily You are the Oft-Returning, Most Merciful.",
      source:         "Tirmidhi",
    },
    {
      arabic:         "اللَّهُمَّ إِنِّي أَعُوذُ بِكَ مِنَ الْهَمِّ وَالْحَزَنِ",
      transliteration:"Allahumma inni a'udhu bika minal-hammi wal-hazan",
      translation:    "O Allah! I seek refuge in You from worry and grief.",
      source:         "Bukhari",
    },
    {
      arabic:         "حَسْبُنَا اللَّهُ وَنِعْمَ الْوَكِيل",
      transliteration:"Hasbunallahu wa ni'mal-wakeel",
      translation:    "Allah is sufficient for us, and He is the best Disposer of affairs.",
      source:         "Surah Al-Imran 3:173",
    },
  ];

  /* ── Server-side data from PHP (via wp_localize_script) ────────────────── */

  var serverData  = (typeof barakahData !== "undefined") ? barakahData : null;
  var BANGLA_DUAS = (serverData && serverData.banglaDuas) ? serverData.banglaDuas : [];

  /* ── Merge English + Bangla duas ─────────────────────────────────────── */

  (function mergeDuas() {
    if (!BANGLA_DUAS.length) return;

    function norm(s) { return (s || "").replace(/\s+/g, " ").trim(); }

    var banglaMap = {};
    var i, j, k, key;
    for (i = 0; i < BANGLA_DUAS.length; i++) {
      key = norm(BANGLA_DUAS[i].arabic);
      if (key) banglaMap[key] = BANGLA_DUAS[i];
    }

    var matched = {};
    for (j = 0; j < DUAS.length; j++) {
      key = norm(DUAS[j].arabic);
      if (banglaMap[key]) {
        DUAS[j].bangla_pronunciation = banglaMap[key].bangla_pronunciation || "";
        DUAS[j].bangla_meaning       = banglaMap[key].bangla_meaning || "";
        DUAS[j].category             = banglaMap[key].category || "";
        matched[key] = true;
      }
    }

    for (k = 0; k < BANGLA_DUAS.length; k++) {
      key = norm(BANGLA_DUAS[k].arabic);
      if (!matched[key]) {
        DUAS.push({
          arabic:               BANGLA_DUAS[k].arabic || "",
          transliteration:      "",
          translation:          "",
          source:               BANGLA_DUAS[k].category || "",
          bangla_pronunciation: BANGLA_DUAS[k].bangla_pronunciation || "",
          bangla_meaning:       BANGLA_DUAS[k].bangla_meaning || "",
          category:             BANGLA_DUAS[k].category || "",
        });
      }
    }
  })();

  /* ── State ───────────────────────────────────────────────────────────────── */

  var duaIndex      = 0;
  var clockInterval = null;
  var duaInterval   = null;
  var starsAnimId   = null;
  var monthCache    = null;
  var monthCacheKey = null;
  var allowLocationChange = (serverData && serverData.allowLocationChange === "1");
  var hijriAdjustDir  = (serverData && serverData.hijriAdjustDirection) ? serverData.hijriAdjustDirection : "none";
  var hijriAdjustDays = (serverData && serverData.hijriAdjustDays) ? parseInt(serverData.hijriAdjustDays, 10) : 0;
  var sehriCautionMin = (serverData && serverData.sehriCautionMinutes) ? parseInt(serverData.sehriCautionMinutes, 10) : 0;
  var iftarCautionMin = (serverData && serverData.iftarCautionMinutes) ? parseInt(serverData.iftarCautionMinutes, 10) : 0;
  var greetingEscHandler  = null;
  var greetingTimerId     = null;
  var confettiRAF         = null;

  /* ── Helpers ─────────────────────────────────────────────────────────────── */

  function pad(n) { return String(n).padStart(2, "0"); }

  function adjustTimeStr(timeStr, offsetMin) {
    if (!offsetMin) return timeStr;
    var p = parseTime(timeStr);
    var total = toMinutes(p.h, p.m) + offsetMin;
    if (total < 0)    total += 1440;
    if (total >= 1440) total -= 1440;
    var nh = Math.floor(total / 60);
    var nm = total % 60;
    return pad(nh) + ":" + pad(nm);
  }

  function applyCautionTimings(timings) {
    var adjusted = {};
    for (var key in timings) {
      if (timings.hasOwnProperty(key)) adjusted[key] = timings[key];
    }
    if (sehriCautionMin > 0) adjusted.Fajr    = adjustTimeStr(adjusted.Fajr, -sehriCautionMin);
    if (iftarCautionMin > 0) adjusted.Maghrib = adjustTimeStr(adjusted.Maghrib, iftarCautionMin);
    return adjusted;
  }

  function applyHijriAdjust(day) {
    if (hijriAdjustDir === "none" || hijriAdjustDays === 0) return day;
    if (hijriAdjustDir === "after")  return day - hijriAdjustDays;
    if (hijriAdjustDir === "before") return day + hijriAdjustDays;
    return day;
  }

  // Strip timezone suffix: "05:08 (+06)" → {h:5, m:8}
  function parseTime(t) {
    var clean = (t || "00:00").trim().split(" ")[0];
    var parts = clean.split(":");
    return { h: parseInt(parts[0], 10) || 0, m: parseInt(parts[1], 10) || 0 };
  }

  function toMinutes(h, m) { return h * 60 + m; }

  function formatTime12(t) {
    var p      = parseTime(t);
    var suffix = p.h >= 12 ? "PM" : "AM";
    var hour   = p.h % 12 === 0 ? 12 : p.h % 12;
    return hour + ":" + pad(p.m) + " " + suffix;
  }

  /* ── API fetch ───────────────────────────────────────────────────────────── */

  function fetchPrayerTimes(city, country, method, container) {
    var url =
      "https://api.aladhan.com/v1/timingsByCity" +
      "?city="    + encodeURIComponent(city)    +
      "&country=" + encodeURIComponent(country) +
      "&method="  + encodeURIComponent(method);

    fetch(url)
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (json.code === 200 && json.data) {
          renderWidget(container, json.data, city, country, method);
        } else {
          showError(container, "Could not load prayer times. Please verify the city and country in Barakah settings.");
        }
      })
      .catch(function () {
        showError(container, "Network error. Make sure the site can reach api.aladhan.com.");
      });
  }

  function showError(container, msg) {
    container.innerHTML =
      '<div class="bk-error">' +
        '<div class="bk-error-icon">🌙</div>' +
        '<p>' + escHtml(msg) + '</p>' +
      '</div>';
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  /* ── Main render ─────────────────────────────────────────────────────────── */

  function renderWidget(container, data, city, country, method) {
    var timings    = applyCautionTimings(data.timings);
    var hijri      = data.date.hijri;
    var gregorian  = data.date.gregorian;
    var rawHijriDay = parseInt(hijri.day, 10) || 1;
    var ramadanDay  = applyHijriAdjust(rawHijriDay);
    var displayHijriDay = (hijriAdjustDir !== "none" && hijriAdjustDays > 0) ? ramadanDay : rawHijriDay;
    var hijriMonth = (hijri && hijri.month && hijri.month.en) ? hijri.month.en : "";
    var hijriYear  = (hijri && hijri.year) ? String(hijri.year) : "";
    var hijriLabel = displayHijriDay + " " + escHtml(hijriMonth) + " " + escHtml(hijriYear) + " AH";

    var gregWeekday = (gregorian && gregorian.weekday && gregorian.weekday.en) ? gregorian.weekday.en : "";
    var gregDay     = (gregorian && gregorian.day) ? String(gregorian.day) : "";
    var gregMonth   = (gregorian && gregorian.month && gregorian.month.en) ? gregorian.month.en : "";
    var gregYear    = (gregorian && gregorian.year) ? String(gregorian.year) : "";
    var dateLabel   = escHtml(gregWeekday) + ", " + escHtml(gregDay) + " " + escHtml(gregMonth) + " " + escHtml(gregYear);

    /* Prayer list definition */
    var prayers = [
      { name:"Sehri / Fajr",    arabic:"الفجر",   time:timings.Fajr,    rgb:"107,140,222", color:"#6B8CDE", emoji:"🌙"  },
      { name:"Sunrise",         arabic:"الشروق",  time:timings.Sunrise,  rgb:"232,168,124", color:"#E8A87C", emoji:"🌅"  },
      { name:"Dhuhr",           arabic:"الظهر",   time:timings.Dhuhr,   rgb:"245,200,66",  color:"#F5C842", emoji:"☀️" },
      { name:"Asr",             arabic:"العصر",   time:timings.Asr,     rgb:"224,123,84",  color:"#E07B54", emoji:"🌤️" },
      { name:"Maghrib / Iftar", arabic:"المغرب",  time:timings.Maghrib, rgb:"255,140,66",  color:"#FF8C42", emoji:"🌇"  },
      { name:"Isha",            arabic:"العشاء",  time:timings.Isha,    rgb:"155,143,214", color:"#9B8FD6", emoji:"🌃"  },
    ];

    /* Build HTML */
    var prayerRows = prayers.map(function (p, i) {
      return (
        '<div class="bk-prayer-row" id="bkpr-' + i + '">' +
          '<div class="bk-prayer-icon" style="background:rgba(' + p.rgb + ',0.18);color:' + p.color + '">' + p.emoji + '</div>' +
          '<div class="bk-prayer-info">' +
            '<div class="bk-prayer-name">' + escHtml(p.name) + ' <span class="bk-next-pill" id="bknp-' + i + '">NEXT</span></div>' +
            '<div class="bk-prayer-arabic">' + p.arabic + '</div>' +
          '</div>' +
          '<div class="bk-prayer-time" id="bkpt-' + i + '" style="color:' + p.color + '">' + formatTime12(p.time) + '</div>' +
          '<div class="bk-prayer-dot" id="bkpd-' + i + '"></div>' +
        '</div>'
      );
    }).join("");

    var isLight  = container._bkMode === "light";
    var twocol   = container.getAttribute("data-columns") === "1";
    var rootCls  = "bk-root" + (isLight ? " bk-light" : "");
    var bodyCls  = "bk-body" + (twocol ? " bk-two-col" : "");

    /* ── Left column: cards + countdown + prayer times ── */
    var leftHtml =
      /* ── Sehri & Iftar Cards ── */
      '<div class="bk-main-cards bk-anim bk-d1">' +
        '<div class="bk-card bk-card-sehri" id="bk-card-sehri">' +
          '<div class="bk-card-emoji">🌙</div>' +
          '<div class="bk-card-label">Sehri <span class="bk-card-location">' + escHtml(city) + '</span></div>' +
          '<div class="bk-card-ar">الفجر</div>' +
          '<div class="bk-card-time">' + formatTime12(timings.Fajr) + '</div>' +
          '<div class="bk-badge bk-badge-blue" id="bk-badge-sehri">–</div>' +
        '</div>' +
        '<div class="bk-card bk-card-iftar" id="bk-card-iftar">' +
          '<div class="bk-card-emoji">🌅</div>' +
          '<div class="bk-card-label">Iftar <span class="bk-card-location">' + escHtml(city) + '</span></div>' +
          '<div class="bk-card-ar">المغرب</div>' +
          '<div class="bk-card-time">' + formatTime12(timings.Maghrib) + '</div>' +
          '<div class="bk-badge bk-badge-orange" id="bk-badge-iftar">–</div>' +
          '<button class="bk-month-btn bk-other-days-btn" id="bk-other-days-btn" title="View upcoming days">\uD83D\uDCC5</button>' +
        '</div>' +
      '</div>' +

      /* ── Countdown ── */
      '<div class="bk-countdown bk-anim bk-d2">' +
        '<div>' +
          '<div class="bk-cd-label">Next Prayer</div>' +
          '<div class="bk-cd-name" id="bk-cd-name">—</div>' +
          '<div class="bk-cd-arabic" id="bk-cd-arabic">—</div>' +
        '</div>' +
        '<div class="bk-cd-timer">' +
          '<div class="bk-cd-timelabel">Remaining</div>' +
          '<div class="bk-cd-display" id="bk-cd-display">00<span>h</span> 00<span>m</span> 00<span>s</span></div>' +
        '</div>' +
      '</div>' +

      /* ── Full Prayer Times ── */
      '<div class="bk-prayers bk-anim bk-d3">' +
        '<div class="bk-section-head">' +
          '<span>🕌</span> Prayer Times Today' +
        '</div>' +
        prayerRows +
      '</div>';

    /* ── Right column: dua cards ── */
    var rightHtml =
      /* ── Iftar Dua ── */
      '<div class="bk-dua-card bk-dua-iftar bk-anim bk-d4">' +
        '<div class="bk-dua-head"><span>🤲</span> Dua at Iftar</div>' +
        '<div class="bk-dua-arabic">اللَّهُمَّ لَكَ صُمْتُ وَعَلَى رِزْقِكَ أَفْطَرْتُ</div>' +
        '<div class="bk-dua-trans">Allahumma laka sumtu wa \'ala rizqika aftartu</div>' +
        '<div class="bk-dua-meaning">"O Allah! For You I have fasted and upon Your provision I break my fast."</div>' +
        '<div class="bk-dua-source">— Dua at Iftar · Abu Dawud</div>' +
      '</div>' +

      /* ── Daily Dua ── */
      '<div class="bk-dua-card bk-anim bk-d5">' +
        '<div class="bk-dua-top">' +
          '<div class="bk-dua-head"><span>✨</span> Daily Dua &amp; Dhikr</div>' +
          '<div class="bk-dua-controls">' +
            '<div class="bk-dots" id="bk-dua-dots"></div>' +
            '<button class="bk-refresh" id="bk-refresh" title="Next Dua" aria-label="Next Dua">&#8635;</button>' +
          '</div>' +
        '</div>' +
        '<div id="bk-dua-body" class="bk-dua-body">' +
          '<div class="bk-dua-arabic-box" id="bk-dua-arabic-text"></div>' +
          '<div class="bk-dua-trans" id="bk-dua-trans"></div>' +
          '<div class="bk-dua-meaning" id="bk-dua-meaning"></div>' +
          '<div class="bk-dua-divider"><span id="bk-dua-source"></span></div>' +
        '</div>' +
      '</div>' +

      /* ── Sehri Niyyah ── */
      '<div class="bk-dua-card bk-dua-sehri bk-anim bk-d5">' +
        '<div class="bk-dua-head"><span>🌙</span> Niyyah for Sehri</div>' +
        '<div class="bk-dua-arabic">وَبِصَوْمِ غَدٍ نَّوَيْتُ مِنْ شَهْرِ رَمَضَانَ</div>' +
        '<div class="bk-dua-trans">Wa bisawmi ghadin nawaytu min shahri Ramadan</div>' +
        '<div class="bk-dua-meaning">"I intend to keep the fast for tomorrow in the month of Ramadan."</div>' +
      '</div>';

    /* ── Assemble body: wrap in column divs when two-col is on ── */
    var bodyContent;
    if (twocol) {
      bodyContent =
        '<div class="bk-col-left">' + leftHtml + '</div>' +
        '<div class="bk-col-right">' + rightHtml + '</div>';
    } else {
      bodyContent = leftHtml + rightHtml;
    }

    container.innerHTML =
      '<div class="' + rootCls + '">' +
        '<canvas class="bk-stars" id="bk-stars"></canvas>' +
        '<div class="bk-orb bk-orb1"></div>' +
        '<div class="bk-orb bk-orb2"></div>' +

        '<div class="' + bodyCls + '">' +

          /* ── Header ── */
          '<div class="bk-header bk-anim">' +
            '<div class="bk-logo-row">' +
              '<svg class="bk-crescent" width="42" height="42" viewBox="0 0 44 44" fill="none">' +
                '<path d="M32 22C32 27.523 27.523 32 22 32C16.477 32 12 27.523 12 22C12 16.477 16.477 12 22 12C19.239 12 17 14.686 17 18C17 21.314 19.239 24 22 24C25.314 24 28 21.761 28 19C28 18.332 27.868 17.695 27.628 17.113C30.2 18.429 32 21.01 32 22Z" fill="#F5C842"/>' +
              '</svg>' +
              '<div>' +
                '<div class="bk-brand-name">Ramadan Times</div>' +
                '<div class="bk-brand-ar">رمضان المبارك</div>' +
              '</div>' +
              '<button class="bk-mode-toggle" id="bk-mode-toggle" title="Toggle light/dark mode" aria-label="Toggle light/dark mode">' +
                '<span class="bk-mode-toggle-icon">' + (isLight ? "\uD83C\uDF19" : "\u2600\uFE0F") + '</span>' +
                '<span class="bk-mode-toggle-label">' + (isLight ? "Dark" : "Light") + '</span>' +
              '</button>' +
              '<div class="bk-day-badge">Day ' + ramadanDay + '</div>' +
            '</div>' +

            '<div class="bk-location' + (allowLocationChange ? ' bk-location-active' : '') + '"' +
              (allowLocationChange ? ' title="Click to change location"' : '') + '>' +
              '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#F5C842" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>' +
              escHtml(city) + ', ' + escHtml(country) +
              '<span class="bk-sep">·</span>' + dateLabel +
            '</div>' +

            '<div class="bk-clock" id="bk-clock">00:00:00</div>' +
            '<div class="bk-hijri">' + hijriLabel + '</div>' +
            '<div class="bk-header-kareem">\u0631\u0645\u0636\u0627\u0646 \u0643\u0631\u064A\u0645 \u00B7 Ramadan Kareem \uD83C\uDF19</div>' +
            (serverData && serverData.headerGreeting
              ? '<div class="bk-header-greeting">' + escHtml(serverData.headerGreeting) + '</div>'
              : '') +
          '</div>' +

          bodyContent +

          /* ── Footer ── */
          '<div class="bk-footer">' +
            '\u0631\u0645\u0636\u0627\u0646 \u0643\u0631\u064A\u0645 \u00B7 Ramadan Kareem \uD83C\uDF19' +
            (serverData && serverData.greeting
              ? '<div class="bk-footer-greeting">' + escHtml(serverData.greeting) + '</div>'
              : '') +
          '</div>' +

        '</div>' + /* bk-body */
      '</div>';   /* bk-root */

    /* Store prayers & hijri info on container */
    container._bkPrayers = prayers;
    /* Extract Hijri year/month from hijri.date ("DD-MM-YYYY") for reliability */
    var hDateParts = (hijri.date || "").split("-");
    container._bkHijriYear  = hDateParts.length === 3 ? parseInt(hDateParts[2], 10) : parseInt(hijri.year, 10);
    container._bkHijriMonth = hDateParts.length === 3 ? parseInt(hDateParts[1], 10) : parseInt(hijri.month.number, 10);
    container._bkHijriMonthName = (hijri.month && hijri.month.en) ? hijri.month.en : "";

    if (!isLight) initStars();
    renderDua();
    renderDuaDots();
    bindDuaControls();
    bindModeToggle(container);
    bindMonthBtn(container, city, country, method);
    bindLocationChange(container, method);

    if (clockInterval) clearInterval(clockInterval);
    updateClock(container);
    clockInterval = setInterval(function () { updateClock(container); }, 1000);

    /* Auto-rotate dua every 30s */
    if (duaInterval) clearInterval(duaInterval);
    duaInterval = setInterval(function () { nextDua(); }, 30000);
  }

  /* ── Stars canvas ────────────────────────────────────────────────────────── */

  function initStars() {
    if (starsAnimId) cancelAnimationFrame(starsAnimId);

    var canvas = document.getElementById("bk-stars");
    if (!canvas) return;
    var ctx  = canvas.getContext("2d");
    var root = canvas.closest(".bk-root");

    function resize() {
      canvas.width  = root.offsetWidth  || 480;
      canvas.height = root.offsetHeight || 800;
    }
    resize();

    var stars = [];
    for (var i = 0; i < 110; i++) {
      stars.push({
        x:     Math.random() * canvas.width,
        y:     Math.random() * canvas.height,
        r:     Math.random() * 1.5 + 0.3,
        alpha: Math.random(),
        speed: Math.random() * 0.008 + 0.003,
        dir:   Math.random() > 0.5 ? 1 : -1,
      });
    }

    function draw() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      for (var j = 0; j < stars.length; j++) {
        var s = stars[j];
        s.alpha += s.speed * s.dir;
        if (s.alpha >= 1)   { s.alpha = 1;   s.dir = -1; }
        if (s.alpha <= 0.1) { s.alpha = 0.1; s.dir =  1; }
        ctx.beginPath();
        ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
        ctx.fillStyle = "rgba(255,235,180," + s.alpha + ")";
        ctx.fill();
      }
      starsAnimId = requestAnimationFrame(draw);
    }
    draw();
  }

  /* ── Live clock & prayer highlight ──────────────────────────────────────── */

  function updateClock(container) {
    var prayers = container._bkPrayers;
    if (!prayers) return;

    var now    = new Date();
    var h      = now.getHours();
    var m      = now.getMinutes();
    var s      = now.getSeconds();
    var ampm   = h >= 12 ? "PM" : "AM";
    var h12    = h % 12 || 12;
    var nowMin = toMinutes(h, m);

    /* Clock display */
    var clockEl = document.getElementById("bk-clock");
    if (clockEl) clockEl.textContent = pad(h12) + ":" + pad(m) + ":" + pad(s) + " " + ampm;

    /* Active / Next prayer */
    var activeIdx = -1;
    for (var i = 0; i < prayers.length; i++) {
      var p  = parseTime(prayers[i].time);
      if (nowMin >= toMinutes(p.h, p.m)) activeIdx = i;
    }
    var nextIdx = (activeIdx === prayers.length - 1) ? 0 : activeIdx + 1;

    /* Update row styles */
    for (var j = 0; j < prayers.length; j++) {
      var row   = document.getElementById("bkpr-" + j);
      var dot   = document.getElementById("bkpd-" + j);
      var badge = document.getElementById("bknp-" + j);
      var pt    = parseTime(prayers[j].time);
      var passed = toMinutes(pt.h, pt.m) <= nowMin;
      if (!row) continue;

      row.classList.remove("bkpr-next","bkpr-active","bkpr-passed");
      if (badge) badge.style.display = "none";

      if (j === nextIdx) {
        row.classList.add("bkpr-next");
        if (badge) badge.style.display = "inline";
        if (dot)   dot.style.background = "#F5C842";
      } else if (j === activeIdx) {
        row.classList.add("bkpr-active");
        if (dot) dot.style.background = "rgba(255,255,255,0.2)";
      } else if (passed) {
        row.classList.add("bkpr-passed");
        if (dot) dot.style.background = "rgba(255,255,255,0.12)";
      }
    }

    /* Countdown */
    var np  = prayers[nextIdx];
    var nEl = document.getElementById("bk-cd-name");
    var aEl = document.getElementById("bk-cd-arabic");
    var cEl = document.getElementById("bk-cd-display");
    if (nEl) nEl.textContent = np.name;
    if (aEl) aEl.textContent = np.arabic;

    var npt     = parseTime(np.time);
    var target  = toMinutes(npt.h, npt.m);
    if (nextIdx === 0 && activeIdx === prayers.length - 1) target += 24 * 60;
    var diffSec = Math.max((target - nowMin) * 60 - s, 0);
    var ch = Math.floor(diffSec / 3600);
    var cm = Math.floor((diffSec % 3600) / 60);
    var cs = diffSec % 60;
    if (cEl) cEl.innerHTML = pad(ch) + "<span>h</span> " + pad(cm) + "<span>m</span> " + pad(cs) + "<span>s</span>";

    /* Sehri / Iftar badges */
    var fajrMin   = toMinutes(parseTime(prayers[0].time).h, parseTime(prayers[0].time).m);
    var maghribMin= toMinutes(parseTime(prayers[4].time).h, parseTime(prayers[4].time).m);

    var bsEl = document.getElementById("bk-badge-sehri");
    var biEl = document.getElementById("bk-badge-iftar");
    var csEl = document.getElementById("bk-card-sehri");
    var ciEl = document.getElementById("bk-card-iftar");

    if (bsEl) bsEl.textContent = nowMin < fajrMin    ? "⏳ Upcoming" : "✓ Done";
    if (biEl) biEl.textContent = nowMin < maghribMin  ? "⏳ Upcoming" : "✓ Done";
    if (csEl) csEl.classList.toggle("bk-card-glow-blue",   nowMin < fajrMin);
    if (ciEl) ciEl.classList.toggle("bk-card-glow-orange",  nowMin < maghribMin);
  }

  /* ── Dua helpers ─────────────────────────────────────────────────────────── */

  function renderDua() {
    var d = DUAS[duaIndex];
    var aEl = document.getElementById("bk-dua-arabic-text");
    var tEl = document.getElementById("bk-dua-trans");
    var mEl = document.getElementById("bk-dua-meaning");
    var sEl = document.getElementById("bk-dua-source");

    if (aEl) aEl.textContent = d.arabic;

    /* Transliteration (English) */
    if (tEl) {
      tEl.textContent = d.transliteration || "";
      tEl.style.display = d.transliteration ? "" : "none";
    }

    /* Meaning block: English translation + Bangla pronunciation + Bangla meaning */
    if (mEl) {
      mEl.innerHTML = "";
      if (d.translation) {
        var enDiv = document.createElement("div");
        enDiv.textContent = "\u201c" + d.translation + "\u201d";
        enDiv.style.marginBottom = (d.bangla_pronunciation || d.bangla_meaning) ? "6px" : "0";
        mEl.appendChild(enDiv);
      }
      if (d.bangla_pronunciation) {
        var bpDiv = document.createElement("div");
        bpDiv.textContent = d.bangla_pronunciation;
        bpDiv.className = "bk-dua-bangla-pron";
        mEl.appendChild(bpDiv);
      }
      if (d.bangla_meaning) {
        var bmDiv = document.createElement("div");
        bmDiv.textContent = d.bangla_meaning;
        bmDiv.className = "bk-dua-bangla-meaning";
        mEl.appendChild(bmDiv);
      }
      mEl.style.display = (d.translation || d.bangla_pronunciation || d.bangla_meaning) ? "" : "none";
    }

    /* Source / category */
    if (sEl) {
      var src = d.source || d.category || "";
      sEl.textContent = src;
      if (sEl.parentElement) sEl.parentElement.style.display = src ? "" : "none";
    }
  }

  function renderDuaDots() {
    var wrap = document.getElementById("bk-dua-dots");
    if (!wrap) return;
    wrap.innerHTML = "";

    var total = DUAS.length;
    var maxVisible = 8;
    var start = 0;
    var end = total;

    if (total > maxVisible) {
      var half = Math.floor(maxVisible / 2);
      start = Math.max(0, Math.min(duaIndex - half, total - maxVisible));
      end = Math.min(start + maxVisible, total);
    }

    for (var i = start; i < end; i++) {
      (function (idx) {
        var dot = document.createElement("div");
        dot.className = "bk-dot" + (idx === duaIndex ? " bk-dot-on" : "");
        dot.addEventListener("click", function () {
          duaIndex = idx;
          renderDua();
          renderDuaDots();
        });
        wrap.appendChild(dot);
      })(i);
    }
  }

  function nextDua() {
    var body = document.getElementById("bk-dua-body");
    if (body) {
      body.style.opacity   = "0";
      body.style.transform = "translateY(8px)";
    }
    setTimeout(function () {
      duaIndex = (duaIndex + 1) % DUAS.length;
      renderDua();
      renderDuaDots();
      if (body) {
        body.style.opacity   = "1";
        body.style.transform = "translateY(0)";
      }
    }, 300);
  }

  function bindDuaControls() {
    var btn = document.getElementById("bk-refresh");
    if (btn) btn.addEventListener("click", nextDua);
  }

  /* ── Mode toggle ─────────────────────────────────────────────────────────── */

  var STORAGE_KEY = "barakah_mode";

  function getStoredMode() {
    try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; }
  }

  function setStoredMode(mode) {
    try { localStorage.setItem(STORAGE_KEY, mode); } catch (e) { /* silent */ }
  }

  function resolveMode(shortcodeDefault) {
    return getStoredMode() || shortcodeDefault || "dark";
  }

  function bindModeToggle(container) {
    var btn = document.getElementById("bk-mode-toggle");
    if (!btn) return;
    btn.addEventListener("click", function () {
      var root = container.querySelector(".bk-root");
      if (!root) return;
      var nowLight = root.classList.contains("bk-light");
      var newMode  = nowLight ? "dark" : "light";

      root.classList.toggle("bk-light", !nowLight);
      container._bkMode = newMode;
      setStoredMode(newMode);

      /* Update button icon & label */
      var icon  = btn.querySelector(".bk-mode-toggle-icon");
      var label = btn.querySelector(".bk-mode-toggle-label");
      if (icon)  icon.textContent  = newMode === "light" ? "\uD83C\uDF19" : "\u2600\uFE0F";
      if (label) label.textContent = newMode === "light" ? "Dark" : "Light";

      /* Start or stop stars */
      if (newMode === "dark") {
        initStars();
      } else {
        if (starsAnimId) { cancelAnimationFrame(starsAnimId); starsAnimId = null; }
      }
    });
  }

  /* ── Full Month Modal ───────────────────────────────────────────────────── */

  function openMonthModal(city, country, method, isLight, hijriYear) {
    var existing = document.getElementById("bk-month-overlay");
    if (existing) existing.remove();

    var now   = new Date();
    var year  = (hijriYear > 1000) ? hijriYear : 1447; /* fallback to 1447 AH */
    var month = 9; /* Ramadan is always Hijri month 9 */

    /* Today's date in DD-MM-YYYY — matches Aladhan's gregorian.date field */
    var todayStr = pad(now.getDate()) + "-" + pad(now.getMonth() + 1) + "-" + now.getFullYear();

    var cacheKey     = city + "|" + country + "|" + method + "|H|" + year + "|9";
    var displayTitle = "Ramadan " + year + " AH";

    var overlay = document.createElement("div");
    overlay.id        = "bk-month-overlay";
    overlay.className = "bk-month-overlay" + (isLight ? " bk-light-modal" : "");
    overlay.innerHTML =
      '<div class="bk-month-panel">' +
        '<div class="bk-month-head">' +
          '<div class="bk-month-title">\uD83D\uDCC5 ' + displayTitle +
            '<span>\u00B7 ' + escHtml(city) + '</span>' +
          '</div>' +
          '<button class="bk-month-close" id="bk-month-close" aria-label="Close">\u2715</button>' +
        '</div>' +
        '<div class="bk-month-scroll" id="bk-month-scroll">' +
          '<div class="bk-month-loading">\uD83C\uDF19 Loading prayer times\u2026</div>' +
        '</div>' +
      '</div>';

    document.body.appendChild(overlay);

    document.getElementById("bk-month-close").addEventListener("click", closeMonthModal);
    overlay.addEventListener("click", function (e) { if (e.target === overlay) closeMonthModal(); });
    document.addEventListener("keydown", onMonthEsc);

    if (monthCache && monthCacheKey === cacheKey) {
      renderMonthContent(todayStr, monthCache);
      return;
    }

    var url =
      "https://api.aladhan.com/v1/hijriCalendarByCity/" + year + "/9" +
      "?city="    + encodeURIComponent(city)    +
      "&country=" + encodeURIComponent(country) +
      "&method="  + encodeURIComponent(method);

    fetch(url)
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (json.code === 200 && json.data) {
          monthCache    = json.data;
          monthCacheKey = cacheKey;
          renderMonthContent(todayStr, json.data);
        } else {
          var scr = document.getElementById("bk-month-scroll");
          if (scr) scr.innerHTML = '<div class="bk-month-loading">Could not load monthly data.</div>';
        }
      })
      .catch(function () {
        var scr = document.getElementById("bk-month-scroll");
        if (scr) scr.innerHTML = '<div class="bk-month-loading">Network error — could not reach api.aladhan.com.</div>';
      });
  }

  function renderMonthContent(todayStr, data) {
    var scr = document.getElementById("bk-month-scroll");
    if (!scr) return;

    /* Update title from first day's Hijri year (always Ramadan) */
    var titleEl = document.querySelector("#bk-month-overlay .bk-month-title");
    if (titleEl && data.length > 0) {
      var firstHijri = data[0].date.hijri;
      if (firstHijri && firstHijri.year) {
        var citySpan = titleEl.querySelector("span");
        var cityHtml = citySpan ? citySpan.outerHTML : "";
        titleEl.innerHTML = "\uD83D\uDCC5 Ramadan " + escHtml(String(firstHijri.year)) + " AH " + cityHtml;
      }
    }

    var rows = [];
    for (var di = 0; di < data.length; di++) {
      var day   = data[di];
      var greg      = day.date.gregorian;
      var hijri     = day.date.hijri;
      var t         = applyCautionTimings(day.timings);
      var isToday   = greg.date === todayStr;
      var hijriDay   = applyHijriAdjust(parseInt(hijri.day, 10));
      if (hijriDay <= 0) continue;
      var weekday    = greg.weekday ? escHtml(greg.weekday.en.slice(0, 3)) : "";
      var gregMonth  = greg.month ? escHtml(greg.month.en.slice(0, 3)) : "";
      var gregDay    = pad(parseInt(greg.day, 10));
      var hijriMonth = (hijri.month && hijri.month.en) ? hijri.month.en : "";
      var dateTd =
        '<span class="bk-mdate-hijri">' + hijriDay + ' ' + escHtml(hijriMonth) + '</span>' +
        '<span class="bk-mdate-greg">' + weekday + ' ' + gregDay + ' ' + gregMonth + '</span>';
      rows.push(
        '<tr class="' + (isToday ? "bk-mrow-today" : "") + '">' +
          '<td>' + dateTd + '</td>' +
          '<td class="bk-mcell-fajr">'    + formatTime12(t.Fajr)    + '</td>' +
          '<td class="bk-mcell-maghrib">' + formatTime12(t.Maghrib) + '</td>' +
        '</tr>'
      );
    }

    scr.innerHTML =
      '<table class="bk-month-table">' +
        '<thead><tr>' +
          '<th>Date</th>' +
          '<th>\uD83C\uDF19 Sehri Last Time</th>' +
          '<th>\uD83C\uDF07 Iftar Time</th>' +
        '</tr></thead>' +
        '<tbody>' + rows.join("") + '</tbody>' +
      '</table>';

    var todayRow = scr.querySelector(".bk-mrow-today");
    if (todayRow) {
      setTimeout(function () {
        todayRow.scrollIntoView({ behavior: "smooth", block: "start" });
      }, 80);
    }
  }

  function closeMonthModal() {
    var overlay = document.getElementById("bk-month-overlay");
    if (overlay) overlay.remove();
    document.removeEventListener("keydown", onMonthEsc);
  }

  function onMonthEsc(e) {
    if (e.key === "Escape") closeMonthModal();
  }

  function bindMonthBtn(container, city, country, method) {
    var btn = document.getElementById("bk-other-days-btn");
    if (!btn) return;
    btn.addEventListener("click", function () {
      var hijriYear = container._bkHijriYear || new Date().getFullYear();
      openMonthModal(city, country, method, container._bkMode === "light", hijriYear);
    });
  }

  /* ── Location Change Modal ─────────────────────────────────────────────── */

  function bindLocationChange(container, method) {
    if (!allowLocationChange) return;
    var locationEl = container.querySelector(".bk-location");
    if (!locationEl) return;
    locationEl.addEventListener("click", function () {
      openLocationModal(container, method);
    });
  }

  function openLocationModal(container, method) {
    var existing = document.getElementById("bk-location-overlay");
    if (existing) existing.remove();

    var isLight = container._bkMode === "light";

    var overlay = document.createElement("div");
    overlay.id = "bk-location-overlay";
    overlay.className = "bk-location-overlay" + (isLight ? " bk-light-modal" : "");
    overlay.innerHTML =
      '<div class="bk-location-panel">' +
        '<div class="bk-location-head">' +
          '<div class="bk-location-title">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>' +
            ' Change Location' +
          '</div>' +
          '<button class="bk-month-close" id="bk-location-close" aria-label="Close">\u2715</button>' +
        '</div>' +
        '<div class="bk-location-body">' +
          '<div class="bk-location-field">' +
            '<label for="bk-loc-city">City</label>' +
            '<input type="text" id="bk-loc-city" placeholder="e.g. London, Karachi, Istanbul" />' +
          '</div>' +
          '<div class="bk-location-field">' +
            '<label for="bk-loc-country">Country</label>' +
            '<input type="text" id="bk-loc-country" placeholder="e.g. UK, Pakistan, Turkey" />' +
          '</div>' +
          '<div class="bk-location-error" id="bk-location-error"></div>' +
          '<button class="bk-location-submit" id="bk-location-submit">Get Prayer Times</button>' +
        '</div>' +
      '</div>';

    document.body.appendChild(overlay);

    /* Close handlers */
    document.getElementById("bk-location-close").addEventListener("click", closeLocationModal);
    overlay.addEventListener("click", function (e) { if (e.target === overlay) closeLocationModal(); });
    document.addEventListener("keydown", onLocationEsc);

    /* Focus city input */
    var cityInput = document.getElementById("bk-loc-city");
    if (cityInput) cityInput.focus();

    /* Submit handler */
    document.getElementById("bk-location-submit").addEventListener("click", function () {
      var newCity    = (document.getElementById("bk-loc-city").value || "").trim();
      var newCountry = (document.getElementById("bk-loc-country").value || "").trim();
      var errorEl    = document.getElementById("bk-location-error");

      if (!newCity || !newCountry) {
        if (errorEl) { errorEl.textContent = "Please enter both city and country."; errorEl.style.display = "block"; }
        return;
      }

      /* Hide error, show loading */
      if (errorEl) errorEl.style.display = "none";
      var btn = document.getElementById("bk-location-submit");
      var originalText = btn.textContent;
      btn.textContent = "Loading\u2026";
      btn.disabled = true;

      var url =
        "https://api.aladhan.com/v1/timingsByCity" +
        "?city="    + encodeURIComponent(newCity) +
        "&country=" + encodeURIComponent(newCountry) +
        "&method="  + encodeURIComponent(method);

      fetch(url)
        .then(function (res) {
          if (!res.ok && res.status !== 400) throw new Error("HTTP " + res.status);
          return res.json();
        })
        .then(function (json) {
          if (json.code === 200 && json.data && json.data.timings) {
            closeLocationModal();
            container.setAttribute("data-city", newCity);
            container.setAttribute("data-country", newCountry);
            /* Clear month cache so it refetches for new location */
            monthCache = null;
            monthCacheKey = null;
            renderWidget(container, json.data, newCity, newCountry, method);
          } else {
            /* Use API error message when available (e.g. "Unable to geocode address: ...") */
            var msg = (typeof json.data === "string" && json.data)
              ? json.data
              : "Could not find prayer times for \"" + newCity + ", " + newCountry + "\". Please check the spelling.";
            if (errorEl) { errorEl.textContent = msg; errorEl.style.display = "block"; }
            btn.textContent = originalText;
            btn.disabled = false;
          }
        })
        .catch(function () {
          if (errorEl) { errorEl.textContent = "Network error — could not reach the prayer times API. Please try again."; errorEl.style.display = "block"; }
          btn.textContent = originalText;
          btn.disabled = false;
        });
    });

    /* Enter key support */
    var countryInput = document.getElementById("bk-loc-country");
    function onEnter(e) { if (e.key === "Enter") document.getElementById("bk-location-submit").click(); }
    if (cityInput)    cityInput.addEventListener("keydown", onEnter);
    if (countryInput) countryInput.addEventListener("keydown", onEnter);
  }

  function closeLocationModal() {
    var overlay = document.getElementById("bk-location-overlay");
    if (overlay) overlay.remove();
    document.removeEventListener("keydown", onLocationEsc);
  }

  function onLocationEsc(e) {
    if (e.key === "Escape") closeLocationModal();
  }

  /* ── Global Greeting Popup ──────────────────────────────────────────────── */

  function initGreeting() {
    var config = (typeof barakahGreetingConfig !== "undefined") ? barakahGreetingConfig : null;
    var forceTest = (window.location.search.indexOf("barakah_test_popup=1") !== -1);

    if (forceTest) {
      var testConfig = config || { enabled: "1", title: "\u0631\u0645\u0636\u0627\u0646 \u0645\u0628\u0627\u0631\u0643 \u00B7 Ramadan Mubarak \uD83C\uDF19", msg: "Wishing you and your family a blessed month of Ramadan!" };
      openGreetingPopup(testConfig);
      return;
    }

    if (!config || config.enabled !== "1") return;

    /* Page scope check */
    if (config.scope === "specific" && config.pageIds && config.pageIds.length > 0) {
      var curId = parseInt(config.currentId, 10) || 0;
      var allowed = false;
      for (var pi = 0; pi < config.pageIds.length; pi++) {
        if (parseInt(config.pageIds[pi], 10) === curId) { allowed = true; break; }
      }
      if (!allowed) return;
    }

    var GREETING_KEY = "barakah_greeting_ts";
    var ONE_HOUR_MS  = 60 * 60 * 1000;
    var lastShown    = null;
    try { lastShown = localStorage.getItem(GREETING_KEY); } catch (e) { /* silent */ }

    var now = Date.now();
    if (lastShown && (now - parseInt(lastShown, 10)) <= ONE_HOUR_MS) return;

    openGreetingPopup(config);
    try { localStorage.setItem(GREETING_KEY, String(now)); } catch (e) { /* silent */ }
  }

  function openGreetingPopup(config) {
    var existing = document.getElementById("bk-greeting-overlay");
    if (existing) existing.remove();

    var overlay = document.createElement("div");
    overlay.id        = "bk-greeting-overlay";
    overlay.className = "bk-greeting-overlay";
    overlay.innerHTML =
      '<div class="bk-greeting-panel">' +
        '<div class="bk-greeting-deco">' +
          '<div class="bk-greeting-ornament bk-greeting-ornament-l"></div>' +
          '<div class="bk-greeting-ornament bk-greeting-ornament-r"></div>' +
          '<div class="bk-greeting-moon">\u2604</div>' +
          '<div class="bk-greeting-lanterns">' +
            '<span class="bk-lantern bk-lantern-1"></span>' +
            '<span class="bk-lantern bk-lantern-2"></span>' +
            '<span class="bk-lantern bk-lantern-3"></span>' +
          '</div>' +
        '</div>' +
        '<div class="bk-greeting-bismillah">\u0628\u0650\u0633\u0645\u0650 \u0627\u0644\u0644\u0651\u0647\u0650 \u0627\u0644\u0631\u0651\u064E\u062D\u0645\u0670\u0646\u0650 \u0627\u0644\u0631\u0651\u064E\u062D\u0650\u064A\u0645\u0650</div>' +
        '<div class="bk-greeting-kareem">\u0631\u0645\u0636\u0627\u0646 \u0643\u0631\u064A\u0645</div>' +
        '<div class="bk-greeting-title">' + escHtml(config.title) + '</div>' +
        (config.msg ? '<div class="bk-greeting-msg">' + escHtml(config.msg) + '</div>' : '') +
        '<div class="bk-greeting-border-art"></div>' +
      '</div>';

    document.body.appendChild(overlay);
    overlay.addEventListener("click", function (e) { if (e.target === overlay) closeGreetingPopup(); });
    greetingEscHandler = onGreetingEsc;
    document.addEventListener("keydown", greetingEscHandler);

    /* Auto fade-out after 5s, then remove */
    greetingTimerId = setTimeout(function () {
      overlay.classList.add("bk-greeting-fadeout");
      setTimeout(closeGreetingPopup, 800);
    }, 5000);

    startConfetti();
  }

  function closeGreetingPopup() {
    if (greetingTimerId) { clearTimeout(greetingTimerId); greetingTimerId = null; }
    stopConfetti();
    var overlay = document.getElementById("bk-greeting-overlay");
    if (overlay) overlay.remove();
    if (greetingEscHandler) {
      document.removeEventListener("keydown", greetingEscHandler);
      greetingEscHandler = null;
    }
  }

  function onGreetingEsc(e) {
    if (e.key === "Escape") closeGreetingPopup();
  }

  function startConfetti() {
    var shapes  = ["\uD83C\uDF19", "\u2B50", "\u2728", "\u2746"];
    var colors  = ["#FFD700", "#F8F8FF", "#E6E6FA", "#20B2AA"];
    var interval = null;

    function spawnPiece() {
      var el = document.createElement("div");
      el.className = "bk-ramadan-confetti";
      el.textContent = shapes[Math.floor(Math.random() * shapes.length)];
      el.style.color    = colors[Math.floor(Math.random() * colors.length)];
      el.style.left     = (Math.random() * 100) + "vw";
      el.style.fontSize = (Math.random() * 20 + 15) + "px";
      var dur = (Math.random() * 3 + 3);
      el.style.animationDuration = dur + "s";
      document.body.appendChild(el);
      setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, dur * 1000 + 200);
    }

    // Spawn immediately and then every 150 ms
    spawnPiece();
    interval = setInterval(spawnPiece, 150);
    confettiRAF = interval; // reuse variable to store interval id for stopConfetti
  }

  function stopConfetti() {
    if (confettiRAF) { clearInterval(confettiRAF); confettiRAF = null; }
    // Remove any remaining falling pieces
    var pieces = document.querySelectorAll(".bk-ramadan-confetti");
    for (var i = 0; i < pieces.length; i++) {
      if (pieces[i].parentNode) pieces[i].parentNode.removeChild(pieces[i]);
    }
  }

  /* ── Boot ────────────────────────────────────────────────────────────────── */

  function init() {
    var container = document.getElementById("barakah-widget");
    if (!container) return;
    var city    = container.getAttribute("data-city")    || "Dhaka";
    var country = container.getAttribute("data-country") || "Bangladesh";
    var method  = container.getAttribute("data-method")  || "1";
    var shortcodeMode = container.getAttribute("data-mode") || "dark";
    var mode = resolveMode(shortcodeMode);
    container._bkMode = mode;
    if (mode === "light") {
      var loading = container.querySelector(".bk-loading");
      if (loading) loading.classList.add("bk-light");
    }

    /* Use server-side cached data if available, otherwise fallback to client fetch */
    if (serverData && serverData.hasData && serverData.timings && serverData.date) {
      renderWidget(container, { timings: serverData.timings, date: serverData.date }, city, country, method);
    } else {
      fetchPrayerTimes(city, country, method, container);
    }
  }

  /* ── Sticky Prayer Bar ──────────────────────────────────────────────────── */

  function initStickyBar() {
    var config = (typeof barakahGreetingConfig !== "undefined") ? barakahGreetingConfig : null;
    if (!config || config.stickyBar !== "1") return;

    /* Page scope check */
    if (config.stickyScope === "specific") {
      var stickyCurId = parseInt(config.currentId, 10) || 0;
      var stickyAllowed = false;
      var stickyIds = Array.isArray(config.stickyPageIds) ? config.stickyPageIds : [];
      for (var si = 0; si < stickyIds.length; si++) {
        if (parseInt(stickyIds[si], 10) === stickyCurId) { stickyAllowed = true; break; }
      }
      if (!stickyAllowed) return;
    }

    var timings = config.stickyTimings;
    if (!timings || !timings.Fajr || !timings.Maghrib) return;

    /* Apply caution adjustments */
    var adjusted  = applyCautionTimings(timings);
    var sehriTime = formatTime12(adjusted.Fajr);
    var iftarTime = formatTime12(adjusted.Maghrib);
    var greeting  = config.stickyGreeting || "Ramadan Mubarak!";
    var posClass   = config.stickyPos === "header" ? "bk-sticky-top" : "bk-sticky-bottom";
    var themeClass = config.stickyTheme === "light" ? "bk-sticky-light" : "";

    /* Date & location info */
    var dateData = config.stickyDate  || {};
    var greg     = dateData.gregorian || {};
    var hijri    = dateData.hijri     || {};
    var city     = config.stickyCity  || "";

    var weekday  = (greg.weekday && greg.weekday.en) ? greg.weekday.en.slice(0, 3) : "";
    var gregDay  = greg.day || "";
    var gregMon  = (greg.month && greg.month.en) ? greg.month.en.slice(0, 3) : "";
    var dateStr  = weekday + (weekday ? " " : "") + gregDay + (gregDay ? " " : "") + gregMon;

    /* Ramadan day count */
    var hijriMonthNum = hijri.month ? parseInt(hijri.month.number || hijri.month.number, 10) : 0;
    var isRamadan     = hijriMonthNum === 9;
    var ramadanDay    = isRamadan ? applyHijriAdjust(parseInt(hijri.day, 10) || 1) : 0;

    var bar = document.createElement("div");
    bar.id        = "bk-sticky-bar";
    bar.className = ("bk-sticky-bar " + posClass + (themeClass ? " " + themeClass : "")).trim();
    bar.innerHTML =
      '<span class="bk-sbar-icon">🌙</span>' +
      '<span class="bk-sbar-greeting">' + escHtml(greeting) + '</span>' +
      (dateStr ? '<span class="bk-sbar-divider bk-sbar-hide-xs"></span><span class="bk-sbar-meta bk-sbar-hide-xs">📅 ' + escHtml(dateStr) + '</span>' : '') +
      (city ? '<span class="bk-sbar-divider bk-sbar-hide-sm"></span><span class="bk-sbar-meta bk-sbar-hide-sm">📍 ' + escHtml(city) + '</span>' : '') +
      (isRamadan && ramadanDay > 0 ? '<span class="bk-sbar-divider bk-sbar-hide-xs"></span><span class="bk-sbar-ramadan bk-sbar-hide-xs">Ramadan Day ' + ramadanDay + '</span>' : '') +
      '<span class="bk-sbar-divider"></span>' +
      '<div class="bk-sbar-block">' +
        '<span class="bk-sbar-block-icon">🌙</span>' +
        '<div class="bk-sbar-block-text">' +
          '<span class="bk-sbar-label">Sehri</span>' +
          '<span class="bk-sbar-time">' + sehriTime + '</span>' +
        '</div>' +
      '</div>' +
      '<span class="bk-sbar-divider"></span>' +
      '<div class="bk-sbar-block">' +
        '<span class="bk-sbar-block-icon">🌅</span>' +
        '<div class="bk-sbar-block-text">' +
          '<span class="bk-sbar-label">Iftar</span>' +
          '<span class="bk-sbar-time">' + iftarTime + '</span>' +
        '</div>' +
      '</div>';

    /* ── Force-collapse chevron on the left side ── */
    var collapseBtn = document.createElement("button");
    collapseBtn.className = "bk-sbar-collapse";
    collapseBtn.title = "Collapse";
    collapseBtn.setAttribute("aria-label", "Collapse bar");
    collapseBtn.innerHTML = '\u203A';
    bar.insertBefore(collapseBtn, bar.firstChild);

    /* ── Roll tab element (visible at right edge when rolled) ── */
    var tabEl = document.createElement("div");
    tabEl.className = "bk-sbar-roll-tab";
    tabEl.title = "Show prayer times";
    tabEl.innerHTML =
      '<span class="bk-sbar-roll-star">\u2726\u2726</span>' +
      '<span class="bk-sbar-roll-tab-icon">\uD83C\uDF19</span>' +
      '<div class="bk-sbar-roll-dots">' +
        '<span class="bk-sbar-roll-dot"></span>' +
        '<span class="bk-sbar-roll-dot"></span>' +
        '<span class="bk-sbar-roll-dot"></span>' +
      '</div>';
    bar.appendChild(tabEl);

    /* ── Roll / Unroll behaviour ── */
    var OPEN_MIN_DELAY = 5000;          /* auto-open delay min */
    var OPEN_MAX_DELAY = 7000;          /* auto-open delay max */
    var AUTO_ROLL_AFTER_OPEN = 10000;   /* roll back after first open */
    var HOVER_SHOW = 10000;             /* ms to stay open on hover / tap */
    var rollTimer  = null;
    var moodTimer  = null;

    function resetMoodState() {
      if (moodTimer) {
        clearTimeout(moodTimer);
        moodTimer = null;
      }
      bar.classList.remove("bk-is-collapsing", "bk-is-expanding");
    }

    function rollBar() {
      resetMoodState();
      bar.classList.add("bk-is-collapsing");
      requestAnimationFrame(function () {
        bar.classList.add("bk-rolled");
      });
      moodTimer = setTimeout(resetMoodState, 950);
    }
    function unrollBar() {
      clearTimeout(rollTimer);
      resetMoodState();
      bar.classList.add("bk-is-expanding");
      requestAnimationFrame(function () {
        bar.classList.remove("bk-rolled");
      });
      moodTimer = setTimeout(resetMoodState, 1100);
    }
    function scheduleRoll(ms) {
      clearTimeout(rollTimer);
      rollTimer = setTimeout(rollBar, ms);
    }

    /* Show collapsed immediately at right edge */
    bar.classList.add("bk-rolled");
    document.body.appendChild(bar);

    if (config.stickyPos === "header") {
      document.body.style.paddingTop = (parseFloat(document.body.style.paddingTop || 0) + 60) + "px";
    } else {
      document.body.style.paddingBottom = (parseFloat(document.body.style.paddingBottom || 0) + 60) + "px";
    }

    /* Auto-open after 5-7 seconds */
    var firstOpenDelay = OPEN_MIN_DELAY + Math.floor(Math.random() * (OPEN_MAX_DELAY - OPEN_MIN_DELAY + 1));
    setTimeout(function () {
      unrollBar();
      scheduleRoll(AUTO_ROLL_AFTER_OPEN);
    }, firstOpenDelay);

    bar.addEventListener("mouseenter", function () {
      unrollBar();
    });
    bar.addEventListener("mouseleave", function () {
      scheduleRoll(HOVER_SHOW);
    });

    /* Tap support for touch devices */
    tabEl.addEventListener("click", function (e) {
      e.stopPropagation();
      unrollBar();
      scheduleRoll(HOVER_SHOW);
    });

    /* Force-collapse button */
    collapseBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      rollBar();
    });
  }

  function bootBarakah() {
    init();
    initGreeting();
    initStickyBar();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bootBarakah);
  } else {
    bootBarakah();
  }

})();
