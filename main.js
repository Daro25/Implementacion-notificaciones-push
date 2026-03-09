function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

async function subscribeUser() {
    if ('serviceWorker' in navigator) {
        try {
            const register = await navigator.serviceWorker.register('sw.js');
            console.log('Service Worker registrado');

            const subscription = await register.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
            });

            await fetch('save_subscription.php', {
                method: 'POST',
                body: JSON.stringify(subscription),
                headers: { 'Content-Type': 'application/json' }
            });

            alert('¡Te has suscrito con éxito!');
        } catch (e) {
            console.error('Error de suscripción:', e);
            alert('Error al suscribir: ' + e.message);
        }
    } else {
        alert('Tu navegador no soporta notificaciones push.');
    }
}