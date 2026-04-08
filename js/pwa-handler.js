let deferredPrompt;

// Detectar si es iOS (iPhone/iPad)
const isIos = () => {
  const userAgent = window.navigator.userAgent.toLowerCase();
  return /iphone|ipad|ipod/.test(userAgent);
};

// Detectar si ya está instalada (Standalone)
const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone);

// 1. Lógica de Instalación (Android/PC)
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    mostrarBotonInstalar();
});

// Lógica para iOS (Mostrar botón siempre si no está instalada)
if (isIos() && !isInStandaloneMode()) {
    mostrarBotonInstalar();
}

function mostrarBotonInstalar() {
    // Mostrar banner del dashboard
    const banner = document.getElementById('pwa-install-banner');
    if (banner) banner.style.display = 'block';
    
    // Mostrar botón del sidebar (si existe)
    const sidebarBtn = document.getElementById('pwa-install-btn');
    if (sidebarBtn) sidebarBtn.style.display = 'flex';

    const indexBtn = document.getElementById('pwa-install-index-btn');
    if (indexBtn) indexBtn.style.display = 'inline-flex';
}

// Handler para el botón del Dashboard
document.getElementById('btn-install-dashboard')?.addEventListener('click', handleInstallClick);
// Handler para el botón del Sidebar
document.getElementById('pwa-install-btn')?.addEventListener('click', handleInstallClick);
// Handler para el botón de la página de inicio
document.getElementById('pwa-install-index-btn')?.addEventListener('click', handleInstallClick);


async function handleInstallClick(e) {
    e.preventDefault();
    
    // Manejo especial para iOS
    if (isIos()) {
        alert("📲 Para instalar en iPhone/iPad:\n\n1. Toca el botón 'Compartir' (cuadrado con flecha) en la barra inferior.\n2. Desliza hacia arriba y toca 'Agregar a Inicio'.");
        return;
    }

    if (deferredPrompt) {
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        if (outcome === 'accepted') {
            console.log('Usuario aceptó instalar');
            ocultarBotonInstalar();
        }
        deferredPrompt = null;
    } else {
        alert("Para instalar, busca la opción 'Instalar aplicación' o 'Agregar a inicio' en el menú de tu navegador (tres puntos arriba a la derecha).");
    }
}

function ocultarBotonInstalar() {
    const banner = document.getElementById('pwa-install-banner');
    if (banner) banner.style.display = 'none';
    const sidebarBtn = document.getElementById('pwa-install-btn');
    if (sidebarBtn) sidebarBtn.style.display = 'none';
    const indexBtn = document.getElementById('pwa-install-index-btn');
    if (indexBtn) indexBtn.style.display = 'none';
}

// 2. Lógica de Notificaciones
document.addEventListener('DOMContentLoaded', () => {
    const notifBanner = document.getElementById('pwa-notif-banner');
    const notifBtn = document.getElementById('btn-enable-notif');
    const sidebarNotifBtn = document.getElementById('pwa-notif-btn');

    // Verificar si el navegador soporta notificaciones
    if (!("Notification" in window)) {
        console.log("Navegador no soporta notificaciones");
        return;
    }

    // Verificar estado actual
    if (Notification.permission === 'default') {
        // Mostrar banner para pedir permiso
        if (notifBanner) notifBanner.style.display = 'block';
    } else if (Notification.permission === 'granted') {
        // Ya activas
        if (sidebarNotifBtn) {
            sidebarNotifBtn.innerHTML = '<i class="fas fa-bell-slash" style="color: #10b981;"></i><span>Notificaciones Activas</span>';
            sidebarNotifBtn.style.opacity = '0.7';
        }
    }

    // Handler para activar
    const requestPermission = () => {
        if (Notification.permission === 'denied') {
            alert('⚠️ Las notificaciones están bloqueadas.\n\nPor favor, ve a la configuración de tu navegador (candadito junto a la URL) y permite las notificaciones para RUGAL.');
            return;
        }

        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                new Notification("¡RUGAL Conectado!", {
                    body: "Ahora recibirás recordatorios importantes de tu mascota.",
                    icon: "assets/images/logo.png"
                });
                if (notifBanner) notifBanner.style.display = 'none';
                if (sidebarNotifBtn) {
                    sidebarNotifBtn.innerHTML = '<i class="fas fa-bell-slash" style="color: #10b981;"></i><span>Notificaciones Activas</span>';
                }
                alert("✅ ¡Notificaciones activadas correctamente!");
            } else if (permission === "denied") {
                alert("⚠️ Has bloqueado las notificaciones.\n\nPara activarlas, toca el candado 🔒 junto a la URL en la barra de direcciones y permite las notificaciones para este sitio.");
            }
        });
    };

    if (notifBtn) notifBtn.addEventListener('click', requestPermission);
    if (sidebarNotifBtn) sidebarNotifBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (Notification.permission !== 'granted') {
            requestPermission();
        }
    });
});