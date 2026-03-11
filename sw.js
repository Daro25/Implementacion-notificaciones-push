self.addEventListener('push', function(event) {
    const data = event.data ? event.data.json() : {};

    const title = data.title || 'Aviso de Marketplace';
    const options = {
        body: data.body || 'Tienes una nueva actualización.',
        icon: data.icon || 'https://cdn-icons-png.flaticon.com/512/1827/1827347.png', // Usa el icono enviado o uno por defecto
        badge: data.icon || 'https://cdn-icons-png.flaticon.com/512/1827/1827347.png',
        data: {
            url: data.url || '/' // Guardamos la URL aquí para usarla en el evento 'notificationclick'
        }
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Este evento es el que hace que la redirección funcione
self.addEventListener('notificationclick', function(event) {
    event.notification.close(); // Cierra la notificación al hacer clic

    // Recupera la URL que guardamos en la opción 'data' arriba
    const urlToOpen = event.notification.data.url;

    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(windowClients => {
            // Si ya hay una pestaña abierta con esa URL, ponle el foco
            for (let i = 0; i < windowClients.length; i++) {
                let client = windowClients[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            // Si no está abierta, abre una nueva
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});