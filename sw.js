self.addEventListener('push', function(event) {
    const data = event.data ? event.data.json() : { title: 'Nuevo aviso', body: 'Revisa las novedades.' };

    const options = {
        body: data.body,
        icon: 'https://cdn-icons-png.flaticon.com/512/1827/1827347.png', // Puedes cambiar esto
        vibrate: [100, 50, 100],
        data: { dateOfArrival: Date.now() }
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});