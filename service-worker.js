const CACHE_NAME = 'rugal-v1';

// Evento de instalación
self.addEventListener('install', (event) => {
  self.skipWaiting();
  console.log('RUGAL Service Worker instalado');
});

// Evento de activación
self.addEventListener('activate', (event) => {
  console.log('RUGAL Service Worker activado');
});

// Interceptar peticiones (necesario para PWA)
self.addEventListener('fetch', (event) => {
  // Estrategia simple: Network First (Intenta internet, si falla, busca en caché)
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});