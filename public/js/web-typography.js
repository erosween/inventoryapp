(() => {
 'use strict';
 const applyChartFont = () => {
  const defaults = window.Chart?.defaults;
  if (!defaults) return;
  const family = '"Geist Variable", Geist, sans-serif';
  if (defaults.font) defaults.font.family = family;
  if (defaults.global) defaults.global.defaultFontFamily = family;
 };
 applyChartFont();
 if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',applyChartFont,{once:true});
})();
