//Service Worker
self.addEventListener('push', function(event) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Nueva Notificación';
    const options = {
        body: data.body || 'Tienes un nuevo mensaje.',
        icon: '/img/icon.png', // Ruta a tu icono
        badge: '/img/badge.png' // Ruta a tu badge
    };

    event.waitUntil(self.registration.showNotification(title, options));
});