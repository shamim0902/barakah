/**
 * Barakah - Countdown, date browser, current prayer highlight.
 */
(function () {
	'use strict';

	var config = typeof barakahConfig !== 'undefined' ? barakahConfig : {};
	var ajaxurl = config.ajaxurl || '';
	var nonce = config.nonce || '';
	var defaultCity = config.city || 'Dhaka';
	var defaultCountry = config.country || 'Bangladesh';
	var defaultMethod = config.method || 1;

	/**
	 * Parse "HH:MM" in local date to get today's time in ms from midnight (simplified: use date with today's date).
	 */
	function timeStringToMinutes(timeStr) {
		if (!timeStr || typeof timeStr !== 'string') return null;
		var parts = timeStr.trim().match(/^(\d{1,2}):(\d{2})$/);
		if (!parts) return null;
		return parseInt(parts[1], 10) * 60 + parseInt(parts[2], 10);
	}

	/**
	 * Get current time in minutes from midnight (local).
	 */
	function nowMinutes() {
		var d = new Date();
		return d.getHours() * 60 + d.getMinutes() + d.getSeconds() / 60;
	}

	/**
	 * Find next prayer key from timings (Fajr, Sunrise, Dhuhr, Asr, Maghrib, Isha).
	 */
	function getNextPrayer(timings) {
		var order = ['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'];
		var now = nowMinutes();
		for (var i = 0; i < order.length; i++) {
			var key = order[i];
			var mins = timeStringToMinutes(timings[key]);
			if (mins != null && mins > now) return { key: key, minutes: mins };
		}
		// Next is tomorrow Fajr
		var fajr = timeStringToMinutes(timings.Fajr);
		if (fajr != null) return { key: 'Fajr', minutes: fajr + 24 * 60 };
		return null;
	}

	/**
	 * Update countdown to a target time (minutes from midnight, same day or next day).
	 */
	function updateCountdown(targetMinutes, countdownEl) {
		if (!countdownEl) return;
		var now = nowMinutes();
		var totalSecs = (targetMinutes - now) * 60;
		if (targetMinutes < now) totalSecs += 24 * 3600;
		if (totalSecs < 0) totalSecs = 0;

		var h = Math.floor(totalSecs / 3600);
		var m = Math.floor((totalSecs % 3600) / 60);
		var s = Math.floor(totalSecs % 60);

		var hoursEl = countdownEl.querySelector('.barakah-widget__countdown-hours');
		var minsEl = countdownEl.querySelector('.barakah-widget__countdown-mins');
		var secsEl = countdownEl.querySelector('.barakah-widget__countdown-secs');
		if (hoursEl) hoursEl.textContent = String(h).padStart(2, '0');
		if (minsEl) minsEl.textContent = String(m).padStart(2, '0');
		if (secsEl) secsEl.textContent = String(s).padStart(2, '0');
	}

	/**
	 * Update fasting progress bar (Imsak to Maghrib).
	 */
	function updateProgress(imsakMins, maghribMins, progressEl) {
		if (!progressEl) return;
		var now = nowMinutes();
		var start = imsakMins;
		var end = maghribMins;
		if (end <= start) end += 24 * 60;
		if (now < start) now += 24 * 60;
		var total = end - start;
		var elapsed = Math.min(Math.max(now - start, 0), total);
		var pct = total > 0 ? Math.round((elapsed / total) * 100) : 0;
		var bar = progressEl.querySelector('.barakah-widget__progress-bar');
		var pctLabel = progressEl.querySelector('.barakah-widget__progress-pct');
		if (bar) {
			bar.style.width = pct + '%';
			bar.setAttribute('aria-valuenow', pct);
		}
		if (pctLabel) pctLabel.textContent = pct + '% ' + (config.labels && config.labels.fastingCompleted ? config.labels.fastingCompleted : 'fasting completed');
	}

	/**
	 * Run countdown and progress for a widget.
	 */
	function runCountdown(widget, timings) {
		if (!timings) return;
		var countdownEl = widget.querySelector('.barakah-widget__countdown');
		if (!countdownEl) return;

		var imsak = timeStringToMinutes(timings.Imsak);
		var maghrib = timeStringToMinutes(timings.Maghrib);
		var next = getNextPrayer(timings);

		// Prefer countdown to Iftar (Maghrib) during fasting period (after Imsak, before Maghrib)
		var now = nowMinutes();
		var targetMinutes = maghrib;
		if (imsak != null && maghrib != null) {
			var imsakNorm = imsak;
			var maghribNorm = maghrib;
			if (maghribNorm <= imsakNorm) maghribNorm += 24 * 60;
			if (now >= imsakNorm && now < maghribNorm) {
				targetMinutes = maghribNorm > 24 * 60 ? maghrib - 0 : maghrib;
			} else if (next && next.key === 'Maghrib') {
				targetMinutes = next.minutes > 24 * 60 ? next.minutes - 24 * 60 : next.minutes;
			} else if (next) {
				targetMinutes = next.minutes > 24 * 60 ? next.minutes - 24 * 60 : next.minutes;
			}
		} else if (next) {
			targetMinutes = next.minutes > 24 * 60 ? next.minutes - 24 * 60 : next.minutes;
		}

		updateCountdown(targetMinutes, countdownEl);
		var progressWrap = countdownEl.querySelector('.barakah-widget__progress');
		if (progressWrap && imsak != null && maghrib != null) {
			updateProgress(imsak, maghrib, progressWrap);
		}
	}

	/**
	 * Highlight current and next prayer in grid.
	 */
	function highlightPrayers(widget, timings) {
		if (!timings) return;
		var grid = widget.querySelector('.barakah-widget__prayer-grid');
		if (!grid) return;
		var items = grid.querySelectorAll('.barakah-widget__prayer-item');
		var order = ['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'];
		var now = nowMinutes();
		var currentKey = null;
		var nextKey = null;
		for (var i = 0; i < order.length; i++) {
			var key = order[i];
			var mins = timeStringToMinutes(timings[key]);
			if (mins == null) continue;
			if (mins > now) {
				nextKey = key;
				break;
			}
			currentKey = key;
		}
		if (!nextKey && order.length) nextKey = order[0];
		items.forEach(function (item) {
			var prayer = item.getAttribute('data-prayer');
			item.classList.remove('barakah-widget__prayer-item--current', 'barakah-widget__prayer-item--next');
			if (prayer === currentKey) item.classList.add('barakah-widget__prayer-item--current');
			else if (prayer === nextKey) item.classList.add('barakah-widget__prayer-item--next');
		});
	}

	/**
	 * Get timings from widget (data attr or embedded config).
	 */
	function getTimingsFromWidget(widget) {
		var grid = widget.querySelector('.barakah-widget__prayer-grid');
		if (grid && grid.getAttribute('data-timings')) {
			try {
				return JSON.parse(grid.getAttribute('data-timings'));
			} catch (e) {}
		}
		return config.timings || null;
	}

	/**
	 * Initialize one widget: countdown tick + prayer highlight.
	 */
	function initWidget(widget) {
		var timings = getTimingsFromWidget(widget);
		runCountdown(widget, timings);
		highlightPrayers(widget, timings);
		setInterval(function () {
			runCountdown(widget, getTimingsFromWidget(widget));
		}, 1000);
		setInterval(function () {
			highlightPrayers(widget, getTimingsFromWidget(widget));
		}, 60000);
	}

	/**
	 * Date browser: fetch new times and update widget.
	 */
	function setupDateBrowser(widget) {
		var dateInput = widget.querySelector('.barakah-widget__date-input');
		var datePrev = widget.querySelector('.barakah-widget__date-prev');
		var dateNext = widget.querySelector('.barakah-widget__date-next');
		if (!dateInput) return;

		var city = widget.getAttribute('data-city') || defaultCity;
		var country = widget.getAttribute('data-country') || defaultCountry;
		var method = parseInt(widget.getAttribute('data-method'), 10) || defaultMethod;

		function currentDateStr() {
			return dateInput.value || dateInput.getAttribute('value') || '';
		}

		function isoToDmy(iso) {
			if (!iso) return '';
			var p = iso.split('-');
			if (p.length !== 3) return iso;
			return p[2] + '-' + p[1] + '-' + p[0];
		}

		function dmyToIso(dmy) {
			if (!dmy) return '';
			var p = dmy.split('-');
			if (p.length !== 3) return dmy;
			return p[2] + '-' + p[1] + '-' + p[0];
		}

		function fetchAndUpdate(dateIso) {
			var formData = new FormData();
			formData.append('action', 'barakah_get_times');
			formData.append('nonce', nonce);
			formData.append('date', dateIso);
			formData.append('city', city);
			formData.append('country', country);
			formData.append('method', method);

			var req = new XMLHttpRequest();
			req.open('POST', ajaxurl);
			req.onload = function () {
				try {
					var res = JSON.parse(req.responseText);
					if (res.success && res.data) {
						applyAjaxData(widget, res.data);
					}
				} catch (e) {}
			};
			req.send(formData);
		}

		if (datePrev) {
			datePrev.addEventListener('click', function () {
				var d = dateInput.value ? new Date(dateInput.value + 'T12:00:00') : new Date();
				d.setDate(d.getDate() - 1);
				var iso = d.toISOString().slice(0, 10);
				dateInput.value = iso;
				fetchAndUpdate(iso);
			});
		}
		if (dateNext) {
			dateNext.addEventListener('click', function () {
				var d = dateInput.value ? new Date(dateInput.value + 'T12:00:00') : new Date();
				d.setDate(d.getDate() + 1);
				var iso = d.toISOString().slice(0, 10);
				dateInput.value = iso;
				fetchAndUpdate(iso);
			});
		}
		dateInput.addEventListener('change', function () {
			fetchAndUpdate(dateInput.value);
		});
	}

	/**
	 * Apply AJAX response data to widget: replace header, ramadan, countdown, grid.
	 */
	function applyAjaxData(widget, data) {
		var header = widget.querySelector('.barakah-widget__header');
		if (data.header && header) header.outerHTML = data.header;

		var ramadanWrap = widget.querySelector('.barakah-widget__ramadan-cards');
		if (data.ramadan_cards && ramadanWrap) ramadanWrap.outerHTML = data.ramadan_cards;

		var countdownWrap = widget.querySelector('.barakah-widget__countdown');
		if (data.countdown && countdownWrap) countdownWrap.outerHTML = data.countdown;

		var gridWrap = widget.querySelector('.barakah-widget__prayer-grid');
		var ctaWrap = widget.querySelector('.barakah-widget__prayer-cta');
		if (data.prayer_grid) {
			var temp = document.createElement('div');
			temp.innerHTML = data.prayer_grid;
			var newGrid = temp.querySelector('.barakah-widget__prayer-grid');
			var newCta = temp.querySelector('.barakah-widget__prayer-cta');
			if (newGrid && gridWrap) gridWrap.outerHTML = newGrid.outerHTML;
			if (newCta && ctaWrap) ctaWrap.outerHTML = newCta.outerHTML;
		}

		if (data.date_dmy) {
			var input = widget.querySelector('.barakah-widget__date-input');
			if (input && data.date_dmy) {
				var p = data.date_dmy.split('-'); /* d-m-Y -> Y-m-d */
				if (p.length === 3) input.value = p[2] + '-' + p[1] + '-' + p[0];
			}
		}

		// Update data-timings on new grid for countdown
		var newGridEl = widget.querySelector('.barakah-widget__prayer-grid');
		if (newGridEl && data.timings) {
			newGridEl.setAttribute('data-timings', JSON.stringify(data.timings));
		}

		runCountdown(widget, data.timings || null);
		highlightPrayers(widget, data.timings || null);
	}

	/**
	 * Format "HH:MM" to 12h (e.g. 05:22 -> "05:22 AM").
	 */
	function formatTime12(timeStr) {
		if (!timeStr || typeof timeStr !== 'string') return '--:--';
		var parts = timeStr.trim().match(/^(\d{1,2}):(\d{2})$/);
		if (!parts) return timeStr;
		var h = parseInt(parts[1], 10);
		var m = parseInt(parts[2], 10);
		var ampm = h >= 12 ? 'PM' : 'AM';
		h = h % 12;
		if (h === 0) h = 12;
		return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ' ' + ampm;
	}

	/**
	 * Ramadan full month modal: open/close and load calendar.
	 */
	function setupRamadanModal(widget) {
		var planBlock = widget.querySelector('.barakah-widget__ramadan-plan');
		if (!planBlock) return;

		var btn = planBlock.querySelector('.barakah-widget__ramadan-plan-btn');
		var modal = planBlock.querySelector('.barakah-widget__ramadan-modal');
		var closeBtn = planBlock.querySelector('.barakah-widget__ramadan-modal-close');
		var backdrop = planBlock.querySelector('.barakah-widget__ramadan-modal-backdrop');
		var loadBtn = planBlock.querySelector('.barakah-widget__ramadan-load-btn');
		var monthSelect = planBlock.querySelector('.barakah-widget__ramadan-month-select');
		var yearSelect = planBlock.querySelector('.barakah-widget__ramadan-year-select');
		var container = planBlock.querySelector('.barakah-widget__ramadan-table-container');
		var placeholder = planBlock.querySelector('.barakah-widget__ramadan-table-placeholder');

		if (!btn || !modal || !container) return;

		function openModal() {
			modal.setAttribute('aria-hidden', 'false');
			if (btn) btn.setAttribute('aria-expanded', 'true');
			document.body.style.overflow = 'hidden';
		}

		function closeModal() {
			modal.setAttribute('aria-hidden', 'true');
			if (btn) btn.setAttribute('aria-expanded', 'false');
			document.body.style.overflow = '';
		}

		btn.addEventListener('click', openModal);
		if (closeBtn) closeBtn.addEventListener('click', closeModal);
		if (backdrop) backdrop.addEventListener('click', closeModal);

		modal.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') closeModal();
		});

		function todayYMD() {
			var d = new Date();
			return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
		}

		function buildTable(days, city) {
			var today = todayYMD();
			var html = '<table class="barakah-ramadan-table"><thead><tr>';
			html += '<th>Date</th><th>Day (Hijri)</th><th>Sehri</th><th>Iftar</th></tr></thead><tbody>';
			for (var i = 0; i < days.length; i++) {
				var day = days[i];
				var timings = day.timings || {};
				var gregorian = day.gregorian || {};
				var hijri = day.hijri || {};
				var gDate = gregorian.date || '';
				var gParts = gDate ? gDate.split('-') : [];
				var ymd = gParts.length === 3 ? gParts[2] + '-' + gParts[1] + '-' + gParts[0] : '';
				var readable = day.readable || ymd || '';
				var hijriDay = hijri.day || '';
				var hijriMonth = (hijri.month && hijri.month.en) ? hijri.month.en : '';
				var hijriYear = hijri.year || '';
				var hijriLabel = hijriDay && hijriMonth ? hijriDay + ' ' + hijriMonth + (hijriYear ? ', ' + hijriYear : '') : '';
				var isRamadan = hijri.month && parseInt(hijri.month.number, 10) === 9;
				var sehri = formatTime12(timings.Imsak || '');
				var iftar = formatTime12(timings.Maghrib || '');
				var rowClass = 'barakah-ramadan-row';
				if (ymd === today) rowClass += ' barakah-ramadan-row--today';
				html += '<tr class="' + rowClass + '">';
				html += '<td class="barakah-ramadan-cell--day">' + (readable || '—') + '</td>';
				html += '<td class="barakah-ramadan-cell--hijri' + (isRamadan ? ' barakah-ramadan-cell--ramadan' : '') + '">' + (hijriLabel || '—') + '</td>';
				html += '<td>' + sehri + '</td><td>' + iftar + '</td>';
				html += '</tr>';
			}
			html += '</tbody></table>';
			return html;
		}

		if (loadBtn && monthSelect && yearSelect) {
			loadBtn.addEventListener('click', function () {
				var city = btn.getAttribute('data-city') || defaultCity;
				var country = btn.getAttribute('data-country') || defaultCountry;
				var method = parseInt(btn.getAttribute('data-method'), 10) || defaultMethod;
				var month = parseInt(monthSelect.value, 10) || 1;
				var year = parseInt(yearSelect.value, 10) || new Date().getFullYear();

				loadBtn.disabled = true;
				loadBtn.textContent = 'Loading…';

				var formData = new FormData();
				formData.append('action', 'barakah_get_calendar_month');
				formData.append('nonce', nonce);
				formData.append('month', month);
				formData.append('year', year);
				formData.append('city', city);
				formData.append('country', country);
				formData.append('method', method);

				var req = new XMLHttpRequest();
				req.open('POST', ajaxurl);
				req.onload = function () {
					loadBtn.disabled = false;
					loadBtn.textContent = 'Load';
					try {
						var res = JSON.parse(req.responseText);
						if (res.success && res.data && res.data.days) {
							container.innerHTML = buildTable(res.data.days, res.data.city || city);
							if (placeholder) placeholder.style.display = 'none';
						} else if (res.success === false && res.data && res.data.message) {
							container.innerHTML = '<p class="barakah-widget__ramadan-table-placeholder">' + res.data.message + '</p>';
							if (placeholder) placeholder.style.display = 'none';
						}
					} catch (e) {
						container.innerHTML = '<p class="barakah-widget__ramadan-table-placeholder">Error loading calendar.</p>';
						if (placeholder) placeholder.style.display = 'none';
					}
				};
				req.onerror = function () {
					loadBtn.disabled = false;
					loadBtn.textContent = 'Load';
					container.innerHTML = '<p class="barakah-widget__ramadan-table-placeholder">Network error.</p>';
					if (placeholder) placeholder.style.display = 'none';
				};
				req.send(formData);
			});
		}
	}

	/**
	 * Init all widgets on page.
	 */
	function init() {
		var widgets = document.querySelectorAll('.barakah-widget');
		widgets.forEach(function (widget) {
			initWidget(widget);
			setupDateBrowser(widget);
			setupRamadanModal(widget);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
