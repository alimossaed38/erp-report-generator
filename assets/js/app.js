(function () {
  'use strict';

  var root = document.documentElement;
  var savedTheme = localStorage.getItem('aerp-theme');
  if (savedTheme === 'dark' || savedTheme === 'light') {
    root.setAttribute('data-theme', savedTheme);
  }

  document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      localStorage.setItem('aerp-theme', next);
      window.dispatchEvent(new Event('aerp-theme-change'));
    });
  });

  document.querySelectorAll('[data-print]').forEach(function (button) {
    button.addEventListener('click', function () { window.print(); });
  });

  var body = document.body;
  document.querySelectorAll('[data-sidebar-toggle]').forEach(function (button) {
    button.addEventListener('click', function () { body.classList.add('sidebar-open'); });
  });
  document.querySelectorAll('[data-sidebar-close]').forEach(function (button) {
    button.addEventListener('click', function () { body.classList.remove('sidebar-open'); });
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') body.classList.remove('sidebar-open');
  });

  var formatter = new Intl.NumberFormat('ar', { notation: 'compact', maximumFractionDigits: 1 });
  var plainFormatter = new Intl.NumberFormat('ar', { maximumFractionDigits: 0 });
  var tooltip = document.createElement('div');
  tooltip.className = 'chart-tooltip';
  tooltip.hidden = true;
  document.body.appendChild(tooltip);

  function css(name) {
    return getComputedStyle(root).getPropertyValue(name).trim();
  }

  function palette() {
    return {
      ink: css('--ink-soft'),
      muted: css('--muted'),
      grid: css('--line'),
      surface: css('--surface'),
      primary: css('--primary'),
      blue: css('--blue'),
      green: css('--green'),
      red: css('--red'),
      orange: css('--orange'),
      violet: css('--violet')
    };
  }

  function roundedRect(ctx, x, y, width, height, radius) {
    var r = Math.min(radius, width / 2, height / 2);
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + width, y, x + width, y + height, r);
    ctx.arcTo(x + width, y + height, x, y + height, r);
    ctx.arcTo(x, y + height, x, y, r);
    ctx.arcTo(x, y, x + width, y, r);
    ctx.closePath();
  }

  function rgba(hex, alpha) {
    if (!hex || hex.charAt(0) !== '#') return hex;
    var normalized = hex.length === 4
      ? '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3]
      : hex;
    var value = parseInt(normalized.slice(1), 16);
    return 'rgba(' + ((value >> 16) & 255) + ',' + ((value >> 8) & 255) + ',' + (value & 255) + ',' + alpha + ')';
  }

  function Chart(canvas) {
    this.canvas = canvas;
    this.ctx = canvas.getContext('2d');
    this.type = canvas.getAttribute('data-chart') || 'bar';
    this.label = canvas.getAttribute('data-label') || '';
    this.data = [];
    this.hits = [];
    try { this.data = JSON.parse(canvas.getAttribute('data-values') || '[]'); } catch (error) { this.data = []; }
    this.draw = this.draw.bind(this);
    this.onMove = this.onMove.bind(this);
    this.onLeave = this.onLeave.bind(this);
    canvas.addEventListener('mousemove', this.onMove);
    canvas.addEventListener('mouseleave', this.onLeave);
    this.resizeObserver = new ResizeObserver(this.draw);
    this.resizeObserver.observe(canvas.parentElement);
    window.addEventListener('aerp-theme-change', this.draw);
    this.draw();
  }

  Chart.prototype.prepare = function () {
    var rect = this.canvas.getBoundingClientRect();
    var ratio = Math.min(window.devicePixelRatio || 1, 2);
    var width = Math.max(280, Math.round(rect.width));
    var height = Math.max(210, Math.round(rect.height));
    if (this.canvas.width !== Math.round(width * ratio) || this.canvas.height !== Math.round(height * ratio)) {
      this.canvas.width = Math.round(width * ratio);
      this.canvas.height = Math.round(height * ratio);
    }
    this.ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    this.ctx.clearRect(0, 0, width, height);
    this.ctx.font = '10px "Segoe UI", Tahoma, Arial';
    this.ctx.textBaseline = 'middle';
    this.hits = [];
    return { width: width, height: height, colors: palette() };
  };

  Chart.prototype.draw = function () {
    var box = this.prepare();
    if (!this.data.length) {
      this.ctx.fillStyle = box.colors.muted;
      this.ctx.textAlign = 'center';
      this.ctx.fillText('لا توجد بيانات للعرض', box.width / 2, box.height / 2);
      return;
    }
    if (this.type === 'line') return this.drawLine(box);
    if (this.type === 'grouped') return this.drawGrouped(box);
    if (this.type === 'donut') return this.drawDonut(box);
    return this.drawBars(box);
  };

  Chart.prototype.axes = function (box, maxValue, left, top, right, bottom) {
    var ctx = this.ctx;
    var colors = box.colors;
    var plotWidth = box.width - left - right;
    var plotHeight = box.height - top - bottom;
    var steps = 4;
    ctx.lineWidth = 1;
    ctx.textAlign = 'left';
    for (var i = 0; i <= steps; i++) {
      var y = top + (plotHeight / steps) * i;
      ctx.strokeStyle = colors.grid;
      ctx.beginPath(); ctx.moveTo(left, y); ctx.lineTo(box.width - right, y); ctx.stroke();
      ctx.fillStyle = colors.muted;
      ctx.fillText(formatter.format(maxValue - (maxValue / steps) * i), 2, y);
    }
    return { x: left, y: top, width: plotWidth, height: plotHeight };
  };

  Chart.prototype.drawBars = function (box) {
    var ctx = this.ctx;
    var colors = box.colors;
    var values = this.data.map(function (item) { return Number(item.value) || 0; });
    var max = Math.max.apply(Math, values.concat([1])) * 1.12;
    var plot = this.axes(box, max, 55, 15, 12, 37);
    var slot = plot.width / values.length;
    var barWidth = Math.min(42, slot * .58);
    var self = this;

    values.forEach(function (value, index) {
      var height = (value / max) * plot.height;
      var x = plot.x + slot * index + (slot - barWidth) / 2;
      var y = plot.y + plot.height - height;
      var gradient = ctx.createLinearGradient(0, y, 0, plot.y + plot.height);
      gradient.addColorStop(0, colors.primary);
      gradient.addColorStop(1, rgba(colors.primary, .45));
      ctx.fillStyle = gradient;
      roundedRect(ctx, x, y, barWidth, Math.max(2, height), 6);
      ctx.fill();
      self.hits.push({ x: x, y: y, w: barWidth, h: Math.max(6, height), label: self.data[index].label, value: value, series: self.label });
      ctx.fillStyle = colors.muted;
      ctx.textAlign = 'center';
      ctx.fillText(shortLabel(self.data[index].label), x + barWidth / 2, box.height - 16);
    });
  };

  Chart.prototype.drawLine = function (box) {
    if (this.data.length && this.data[0] && this.data[0].series) return this.drawMultiLine(box);
    var ctx = this.ctx;
    var colors = box.colors;
    var values = this.data.map(function (item) { return Number(item.value) || 0; });
    var max = Math.max.apply(Math, values.concat([1])) * 1.12;
    var plot = this.axes(box, max, 55, 15, 13, 37);
    var step = values.length > 1 ? plot.width / (values.length - 1) : plot.width;
    var points = values.map(function (value, index) {
      return { x: plot.x + (values.length > 1 ? index * step : plot.width / 2), y: plot.y + plot.height - (value / max) * plot.height, value: value };
    });

    var gradient = ctx.createLinearGradient(0, plot.y, 0, plot.y + plot.height);
    gradient.addColorStop(0, rgba(colors.primary, .28));
    gradient.addColorStop(1, rgba(colors.primary, .015));
    ctx.beginPath();
    ctx.moveTo(points[0].x, plot.y + plot.height);
    points.forEach(function (point) { ctx.lineTo(point.x, point.y); });
    ctx.lineTo(points[points.length - 1].x, plot.y + plot.height);
    ctx.closePath();
    ctx.fillStyle = gradient;
    ctx.fill();

    ctx.beginPath();
    points.forEach(function (point, index) { index ? ctx.lineTo(point.x, point.y) : ctx.moveTo(point.x, point.y); });
    ctx.strokeStyle = colors.primary;
    ctx.lineWidth = 2.5;
    ctx.lineJoin = 'round';
    ctx.stroke();

    var self = this;
    points.forEach(function (point, index) {
      ctx.beginPath(); ctx.arc(point.x, point.y, 3.5, 0, Math.PI * 2); ctx.fillStyle = colors.surface; ctx.fill();
      ctx.lineWidth = 2; ctx.strokeStyle = colors.primary; ctx.stroke();
      self.hits.push({ x: point.x - 9, y: point.y - 9, w: 18, h: 18, label: self.data[index].label, value: point.value, series: self.label });
      ctx.fillStyle = colors.muted;
      ctx.textAlign = 'center';
      ctx.fillText(shortLabel(self.data[index].label), point.x, box.height - 16);
    });
  };

  Chart.prototype.drawMultiLine = function (box) {
    var ctx = this.ctx;
    var colors = box.colors;
    var self = this;
    var keys = Object.keys(this.data[0].series);
    var all = [];
    this.data.forEach(function (item) {
      keys.forEach(function (key) {
        var v = item.series[key];
        if (v !== null && v !== undefined) all.push(Number(v) || 0);
      });
    });
    var max = Math.max.apply(Math, all.concat([1])) * 1.12;
    var plot = this.axes(box, max, 55, 15, 13, 37);
    var step = this.data.length > 1 ? plot.width / (this.data.length - 1) : plot.width;
    var seriesColors = [colors.primary, colors.orange, colors.violet];

    keys.forEach(function (key, keyIndex) {
      var raw = self.data.map(function (item) {
        var v = item.series[key];
        return (v === null || v === undefined) ? null : (Number(v) || 0);
      });
      var points = raw.map(function (value, index) {
        if (value === null) return null;
        return { x: plot.x + (self.data.length > 1 ? index * step : plot.width / 2), y: plot.y + plot.height - (value / max) * plot.height, value: value };
      });

      if (keyIndex === 0) {
        var solid = points.filter(function (p) { return p; });
        if (solid.length) {
          var gradient = ctx.createLinearGradient(0, plot.y, 0, plot.y + plot.height);
          gradient.addColorStop(0, rgba(colors.primary, .28));
          gradient.addColorStop(1, rgba(colors.primary, .015));
          ctx.beginPath();
          ctx.moveTo(solid[0].x, plot.y + plot.height);
          solid.forEach(function (point) { ctx.lineTo(point.x, point.y); });
          ctx.lineTo(solid[solid.length - 1].x, plot.y + plot.height);
          ctx.closePath();
          ctx.fillStyle = gradient;
          ctx.fill();
        }
      }

      var color = seriesColors[keyIndex % seriesColors.length];
      ctx.beginPath();
      var started = false;
      points.forEach(function (point) {
        if (!point) { started = false; return; }
        if (!started) { ctx.moveTo(point.x, point.y); started = true; }
        else { ctx.lineTo(point.x, point.y); }
      });
      ctx.strokeStyle = color;
      ctx.lineWidth = keyIndex === 0 ? 2.5 : 2;
      ctx.lineJoin = 'round';
      if (keyIndex > 0) ctx.setLineDash([5, 4]);
      ctx.stroke();
      ctx.setLineDash([]);

      points.forEach(function (point, index) {
        if (!point) return;
        ctx.beginPath(); ctx.arc(point.x, point.y, 3.5, 0, Math.PI * 2); ctx.fillStyle = colors.surface; ctx.fill();
        ctx.lineWidth = 2; ctx.strokeStyle = color; ctx.stroke();
        self.hits.push({ x: point.x - 9, y: point.y - 9, w: 18, h: 18, label: self.data[index].label, value: point.value, series: key });
      });
    });

    this.data.forEach(function (item, index) {
      var x = plot.x + (self.data.length > 1 ? index * step : plot.width / 2);
      ctx.fillStyle = colors.muted;
      ctx.textAlign = 'center';
      ctx.fillText(shortLabel(item.label), x, box.height - 16);
    });
  };

  Chart.prototype.drawGrouped = function (box) {
    var ctx = this.ctx;
    var colors = box.colors;
    var keys = Object.keys((this.data[0] && this.data[0].series) || {});
    var all = [];
    this.data.forEach(function (item) { keys.forEach(function (key) { all.push(Number(item.series[key]) || 0); }); });
    var max = Math.max.apply(Math, all.concat([1])) * 1.12;
    var plot = this.axes(box, max, 55, 28, 12, 37);
    var slot = plot.width / this.data.length;
    var groupWidth = Math.min(56, slot * .68);
    var barWidth = groupWidth / Math.max(1, keys.length) - 2;
    var seriesColors = { income: colors.green, expense: colors.red };
    var self = this;

    keys.forEach(function (key, index) {
      ctx.fillStyle = seriesColors[key] || [colors.blue, colors.orange, colors.violet][index % 3];
      ctx.fillRect(10 + index * 82, 5, 9, 9);
      ctx.fillStyle = colors.muted;
      ctx.textAlign = 'right';
      ctx.fillText(key === 'income' ? 'الإيرادات' : (key === 'expense' ? 'المصروفات' : key), 76 + index * 82, 10);
    });

    this.data.forEach(function (item, itemIndex) {
      var groupX = plot.x + slot * itemIndex + (slot - groupWidth) / 2;
      keys.forEach(function (key, keyIndex) {
        var value = Number(item.series[key]) || 0;
        var height = (value / max) * plot.height;
        var x = groupX + keyIndex * (barWidth + 2);
        var y = plot.y + plot.height - height;
        ctx.fillStyle = seriesColors[key] || [colors.blue, colors.orange, colors.violet][keyIndex % 3];
        roundedRect(ctx, x, y, barWidth, Math.max(2, height), 4);
        ctx.fill();
        self.hits.push({ x: x, y: y, w: barWidth, h: Math.max(6, height), label: item.label, value: value, series: key === 'income' ? 'الإيرادات' : 'المصروفات' });
      });
      ctx.fillStyle = colors.muted;
      ctx.textAlign = 'center';
      ctx.fillText(shortLabel(item.label), plot.x + slot * itemIndex + slot / 2, box.height - 16);
    });
  };

  Chart.prototype.drawDonut = function (box) {
    var ctx = this.ctx;
    var colors = box.colors;
    var chartColors = [colors.primary, colors.green, colors.orange, colors.violet, colors.blue, colors.red];
    var total = this.data.reduce(function (sum, item) { return sum + (Number(item.value) || 0); }, 0) || 1;
    var compact = box.width < 520;
    var centerX = compact ? box.width / 2 : box.width * .68;
    var centerY = compact ? box.height * .38 : box.height / 2;
    var radius = Math.min(compact ? box.width * .24 : box.width * .18, box.height * .34, 105);
    var thickness = Math.max(19, radius * .27);
    var start = -Math.PI / 2;
    var self = this;

    this.data.forEach(function (item, index) {
      var value = Number(item.value) || 0;
      var angle = (value / total) * Math.PI * 2;
      ctx.beginPath();
      ctx.arc(centerX, centerY, radius, start, start + angle);
      ctx.strokeStyle = chartColors[index % chartColors.length];
      ctx.lineWidth = thickness;
      ctx.lineCap = 'butt';
      ctx.stroke();
      self.hits.push({ donut: true, cx: centerX, cy: centerY, inner: radius - thickness / 2, outer: radius + thickness / 2, start: start, end: start + angle, label: item.label, value: value, series: self.label });
      start += angle;
    });

    ctx.fillStyle = colors.muted;
    ctx.textAlign = 'center';
    ctx.font = '10px "Segoe UI", Tahoma, Arial';
    ctx.fillText('الإجمالي', centerX, centerY - 11);
    ctx.fillStyle = colors.ink;
    ctx.font = 'bold 15px "Segoe UI", Tahoma, Arial';
    ctx.fillText(formatter.format(total), centerX, centerY + 10);

    ctx.font = '10px "Segoe UI", Tahoma, Arial';
    var legendX = compact ? 20 : 20;
    var legendY = compact ? box.height * .72 : Math.max(25, centerY - (this.data.length * 25) / 2);
    var maxLegend = compact ? Math.min(this.data.length, 3) : this.data.length;
    for (var i = 0; i < maxLegend; i++) {
      var item = this.data[i];
      var y = legendY + i * 25;
      ctx.fillStyle = chartColors[i % chartColors.length];
      roundedRect(ctx, legendX, y - 5, 9, 9, 3); ctx.fill();
      ctx.fillStyle = colors.ink;
      ctx.textAlign = 'right';
      ctx.fillText(shortLabel(item.label, 17), compact ? box.width - 20 : 165, y);
      ctx.fillStyle = colors.muted;
      ctx.textAlign = 'left';
      ctx.fillText(plainFormatter.format(item.value), compact ? 20 : 180, y);
    }
  };

  Chart.prototype.onMove = function (event) {
    var rect = this.canvas.getBoundingClientRect();
    var x = event.clientX - rect.left;
    var y = event.clientY - rect.top;
    var hit = null;
    for (var i = 0; i < this.hits.length; i++) {
      var item = this.hits[i];
      if (item.donut) {
        var dx = x - item.cx, dy = y - item.cy;
        var distance = Math.sqrt(dx * dx + dy * dy);
        var angle = Math.atan2(dy, dx);
        if (angle < -Math.PI / 2) angle += Math.PI * 2;
        var start = item.start, end = item.end;
        if (start < -Math.PI / 2) { start += Math.PI * 2; end += Math.PI * 2; }
        if (distance >= item.inner && distance <= item.outer && angle >= start && angle <= end) { hit = item; break; }
      } else if (x >= item.x && x <= item.x + item.w && y >= item.y && y <= item.y + item.h) {
        hit = item; break;
      }
    }
    if (!hit) return this.onLeave();
    tooltip.hidden = false;
    tooltip.textContent = hit.label + ' · ' + (hit.series ? hit.series + ': ' : '') + plainFormatter.format(hit.value);
    tooltip.style.left = event.clientX + 'px';
    tooltip.style.top = event.clientY + 'px';
    this.canvas.style.cursor = 'pointer';
  };

  Chart.prototype.onLeave = function () {
    tooltip.hidden = true;
    this.canvas.style.cursor = 'default';
  };

  function shortLabel(value, max) {
    var text = String(value == null ? '' : value);
    max = max || 9;
    return text.length > max ? text.slice(0, max - 1) + '…' : text;
  }

  document.querySelectorAll('canvas[data-chart][data-values]').forEach(function (canvas) {
    new Chart(canvas);
  });

  /* ===== Report assistant widget ===== */
  (function () {
    var host = document.querySelector('[data-assistant]');
    if (!host) return;

    var toggle = host.querySelector('[data-assistant-toggle]');
    var panel = host.querySelector('[data-assistant-panel]');
    var closeBtn = host.querySelector('[data-assistant-close]');
    var form = host.querySelector('[data-assistant-form]');
    var input = host.querySelector('[data-assistant-input]');
    var log = host.querySelector('[data-assistant-log]');
    var send = form.querySelector('.assistant-send');
    var greeted = false;

    var WELCOME = [
      'مبيعات آخر ٩٠ يوم',
      'أعلى ٥ منتجات مبيعًا',
      'الذمم المتأخرة',
      'الأصناف النافدة',
      'مصروفات هذا الشهر',
      'أكثر ١٠ عملاء إنفاقًا'
    ];

    function el(tag, className, text) {
      var node = document.createElement(tag);
      if (className) node.className = className;
      if (text != null) node.textContent = text;
      return node;
    }

    function scrollDown() { log.scrollTop = log.scrollHeight; }

    function open() {
      panel.hidden = false;
      toggle.setAttribute('aria-expanded', 'true');
      if (!greeted) { greeted = true; greet(); }
      input.focus();
    }
    function close() {
      panel.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');
    }

    function chips(container, items) {
      var wrap = el('div', 'assistant-chips');
      items.forEach(function (text) {
        var chip = el('button', 'assistant-chip', text);
        chip.type = 'button';
        chip.addEventListener('click', function () { input.value = text; submit(text); });
        wrap.appendChild(chip);
      });
      container.appendChild(wrap);
    }

    function greet() {
      var card = el('div', 'assistant-card');
      card.appendChild(el('div', null, 'اكتب طلبك بالعربي وسأجلب لك التقرير المناسب. جرّب:'));
      chips(card, WELCOME);
      log.appendChild(card);
      scrollDown();
    }

    function addUser(text) {
      log.appendChild(el('div', 'assistant-msg is-user', text));
      scrollDown();
    }

    function renderResult(data) {
      var card = el('div', 'assistant-card');

      if (!data || data.ok !== true) {
        card.appendChild(el('div', null, (data && data.message) || 'تعذّر تنفيذ الطلب.'));
        if (data && data.suggestions && data.suggestions.length) chips(card, data.suggestions);
        log.appendChild(card);
        scrollDown();
        return;
      }

      var understood = el('div', 'assistant-understood');
      understood.appendChild(el('strong', null, 'فهمت طلبك: '));
      understood.appendChild(document.createTextNode(data.understood || ''));
      card.appendChild(understood);

      if (data.kpis && data.kpis.length) {
        var grid = el('div', 'assistant-kpis');
        data.kpis.forEach(function (k) {
          var box = el('div', 'assistant-kpi');
          box.appendChild(el('small', null, k.label));
          box.appendChild(el('strong', null, k.value));
          grid.appendChild(box);
        });
        card.appendChild(grid);
      }

      if (data.table && data.table.columns) {
        if (data.table.rows && data.table.rows.length) {
          var wrap = el('div', 'assistant-table-wrap');
          var table = el('table', 'assistant-table');
          var thead = el('thead');
          var htr = el('tr');
          data.table.columns.forEach(function (c) { htr.appendChild(el('th', null, c)); });
          thead.appendChild(htr);
          table.appendChild(thead);
          var tbody = el('tbody');
          data.table.rows.forEach(function (row) {
            var tr = el('tr');
            row.forEach(function (cell) { tr.appendChild(el('td', null, cell)); });
            tbody.appendChild(tr);
          });
          table.appendChild(tbody);
          wrap.appendChild(table);
          card.appendChild(wrap);
        } else {
          card.appendChild(el('div', 'assistant-empty', 'لا توجد بيانات مطابقة.'));
        }
      }

      var actions = el('div', 'assistant-actions');
      if (data.reportUrl) {
        var openLink = el('a', 'assistant-action', 'افتح التقرير كامل');
        openLink.setAttribute('href', data.reportUrl);
        actions.appendChild(openLink);
      }
      if (data.exportUrl) {
        var exportLink = el('a', 'assistant-action', 'تصدير');
        exportLink.setAttribute('href', data.exportUrl);
        actions.appendChild(exportLink);
      }
      if (actions.childNodes.length) card.appendChild(actions);

      log.appendChild(card);
      scrollDown();
    }

    function submit(q) {
      var query = (q != null ? q : input.value).trim();
      if (!query) return;
      addUser(query);
      input.value = '';
      send.disabled = true;

      var loading = el('div', 'assistant-msg assistant-loading', 'جارٍ البحث…');
      log.appendChild(loading);
      scrollDown();

      fetch('/assistant/ask?q=' + encodeURIComponent(query), { headers: { 'Accept': 'application/json' } })
        .then(function (res) { return res.json(); })
        .then(function (data) { log.removeChild(loading); renderResult(data); })
        .catch(function () {
          log.removeChild(loading);
          var card = el('div', 'assistant-card');
          card.appendChild(el('div', null, 'تعذّر الاتصال بالمساعد. حاول مرة أخرى.'));
          log.appendChild(card);
          scrollDown();
        })
        .then(function () { send.disabled = false; input.focus(); });
    }

    toggle.addEventListener('click', function () {
      if (panel.hidden) open(); else close();
    });
    closeBtn.addEventListener('click', close);
    form.addEventListener('submit', function (event) { event.preventDefault(); submit(); });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !panel.hidden) close();
    });
  })();
})();
