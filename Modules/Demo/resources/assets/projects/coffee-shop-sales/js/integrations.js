/* Coffee Shop Sales theme selector. Business data and menu JSON are managed elsewhere. */
(() => {
  'use strict';
  const storageKey = 'coffee_shop_sales_theme_v1';
  const palettes = {
    original: { label: 'Màu gốc', dark: '#0B2B5C', light: '#1565C0' },
    ruby: { label: 'Ruby', dark: '#8E1B1B', light: '#C62828' },
    ocean: { label: 'Ocean', dark: '#07556E', light: '#087E9B' },
    emerald: { label: 'Emerald', dark: '#0F6247', light: '#16835D' },
    indigo: { label: 'Indigo', dark: '#3730A3', light: '#4F46E5' }
  };

  function apply(name) {
    if (!Object.hasOwn(palettes, name)) name = 'original';
    document.documentElement.dataset.coffeeTheme = name;
    try { localStorage.setItem(storageKey, name); } catch (_) {}
    document.querySelectorAll('[data-coffee-theme]').forEach(button => {
      button.setAttribute('aria-pressed', String(button.dataset.coffeeTheme === name));
    });
  }

  function mount() {
    const settings = document.getElementById('page-settings');
    if (!settings || settings.querySelector('#coffeeThemePanel')) return;
    const grid = settings.querySelector('.set-grid') || settings;
    const panel = document.createElement('section');
    panel.id = 'coffeeThemePanel';
    panel.className = 'card coffee-theme-panel noprint';
    const heading = document.createElement('h4');
    heading.textContent = 'Màu sắc giao diện';
    const hint = document.createElement('p');
    hint.textContent = 'Chọn một trong 5 giao diện. Lựa chọn được lưu trên trình duyệt.';
    const options = document.createElement('div');
    options.className = 'coffee-theme-options';
    for (const [id, palette] of Object.entries(palettes)) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'coffee-theme-option';
      button.dataset.coffeeTheme = id;
      button.setAttribute('aria-label', 'Chọn giao diện ' + palette.label);
      button.setAttribute('aria-pressed', 'false');
      const swatch = document.createElement('span');
      swatch.className = 'coffee-theme-swatch';
      swatch.setAttribute('aria-hidden', 'true');
      swatch.style.setProperty('--theme-dark', palette.dark);
      swatch.style.setProperty('--theme-light', palette.light);
      const label = document.createElement('span');
      label.className = 'coffee-theme-name';
      label.textContent = palette.label;
      button.append(swatch, label);
      button.addEventListener('click', () => apply(id));
      options.append(button);
    }
    panel.append(heading, hint, options);
    grid.append(panel);
    let selected = 'original';
    try { selected = localStorage.getItem(storageKey) || selected; } catch (_) {}
    apply(selected);
  }

  if (typeof APP !== 'undefined' && APP.pages?.settings) {
    const original = APP.pages.settings[2];
    APP.pages.settings[2] = function () { original(); mount(); };
    if (typeof CUR !== 'undefined' && CUR === 'settings') mount();
  }
})();
