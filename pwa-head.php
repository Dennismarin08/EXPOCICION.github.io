<!-- Configuración PWA para RUGAL -->
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#667eea">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<!-- Adaptado para usar tu logo existente -->
<link rel="apple-touch-icon" href="assets/images/logo.png">

<script>
// Detección de iOS
const isIos = () => {
  const userAgent = window.navigator.userAgent.toLowerCase();
  return /iphone|ipad|ipod/.test(userAgent);
};

// Verifica si la PWA ya está instalada o en modo standalone (iOS)
const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone);

// Mostrar los botones de instalación (si existen en el DOM)
function showInstallButtons() {
  const indexBtn = document.getElementById('pwa-install-index-btn');
  if (indexBtn) indexBtn.style.display = 'inline-flex';
  
  const sidebarBtn = document.getElementById('pwa-install-btn');
  if (sidebarBtn) sidebarBtn.style.display = 'flex';
  
  const dashboardBanner = document.getElementById('pwa-install-banner');
  if (dashboardBanner) dashboardBanner.style.display = 'block';
}

// Ocultar los botones de instalación una vez se acepta la instalación
function hideInstallButtons() {
  const indexBtn = document.getElementById('pwa-install-index-btn');
  if (indexBtn) indexBtn.style.display = 'none';
  
  const sidebarBtn = document.getElementById('pwa-install-btn');
  if (sidebarBtn) sidebarBtn.style.display = 'none';
  
  const dashboardBanner = document.getElementById('pwa-install-banner');
  if (dashboardBanner) dashboardBanner.style.display = 'none';
}

// Registrar el Service Worker para permitir la instalación
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('service-worker.js')
      .then(reg => console.log('App lista para instalar', reg.scope))
      .catch(err => console.log('Error PWA:', err));
  });
}

// Manejar el evento de instalación de la PWA (Android / Chrome)
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
  // Prevenir que Chrome 67 y anteriores muestren el prompt automáticamente
  e.preventDefault();
  // Guardar el evento para poder usarlo luego
  deferredPrompt = e;
  // Mostrar los botones de instalación
  showInstallButtons();
});

// Función para invocar el prompt de instalación o mostrar instrucciones
function installPWA() {
  if (isIos() && !isInStandaloneMode()) {
    // Si es iOS, mostrar alerta de instrucción (ya que no soporta beforeinstallprompt)
    alert("📱 Para instalar esta app en tu iPhone/iPad:\n\n1. Toca el ícono 'Compartir' en la barra inferior (cuadrado con la flecha hacia arriba).\n2. Desplázate y selecciona 'Agregar a Inicio' ➕.\n3. Toca 'Agregar' en la esquina superior derecha.");
    return;
  }

  if (deferredPrompt) {
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then((choiceResult) => {
      if (choiceResult.outcome === 'accepted') {
        console.log('El usuario aceptó instalar la app');
        hideInstallButtons();
      } else {
        console.log('El usuario canceló la instalación');
      }
      deferredPrompt = null;
    });
  } else {
    // Si la app ya está instalada o no es compatible
    if (!isIos()) {
        alert("La aplicación ya está instalada o tu navegador no soporta la instalación directa automática.");
    }
  }
}

// Agregar Event Listeners a los botones de instalar
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
      // Si es iOS y no está en pantalla completa, forzamos mostrar los botones de instalación
      if (isIos() && !isInStandaloneMode()) {
        showInstallButtons();
      }

      const btns = [
          document.getElementById('pwa-install-index-btn'),
          document.getElementById('pwa-install-btn'),
          document.getElementById('btn-install-dashboard')
      ];
      
      btns.forEach(btn => {
          if (btn) {
              btn.addEventListener('click', (e) => {
                  e.preventDefault();
                  installPWA();
              });
          }
      });
  }, 500); // Esperar un poco a que el DOM y el beforeinstallprompt se resuelvan
});
</script>