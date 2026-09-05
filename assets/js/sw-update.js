'use strict';

(() => {
  if (!('serviceWorker' in navigator) || !isSecureContext) return;

  const base = document.body.dataset.baseUrl || '/';
  let reloading = false;

  navigator.serviceWorker.addEventListener('controllerchange', () => {
    if (reloading) return;
    reloading = true;
    location.reload();
  });

  addEventListener('load', () => {
    navigator.serviceWorker
      .register(`${base}sw.js`, {scope: base, updateViaCache: 'none'})
      .then(registration => registration.update())
      .catch(() => {});
  });
})();
