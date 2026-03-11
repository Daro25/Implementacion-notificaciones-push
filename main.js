// 1. Esta es la función que faltaba: Convierte la clave VAPID de texto a un formato que el navegador entiende.
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

// 2. Función principal de suscripción
async function subscribeUser() {
    if ('serviceWorker' in navigator) {
        try {
            // Esperamos a que el Service Worker esté listo
            await navigator.serviceWorker.register('sw.js');
            const registration = await navigator.serviceWorker.ready;
            
            console.log('Service Worker activo y listo para suscribir');

            // Intentamos la suscripción usando la función de arriba
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
            });

            // Enviamos el token al servidor PHP
            const response = await fetch('save_subscription.php', {
                method: 'POST',
                body: JSON.stringify(subscription),
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();
            
            if (result.status === "success" || result.success === true) {
                alert('¡Te has suscrito con éxito! Ya puedes recibir notificaciones.');
            } else {
                console.log(result);
                console.error('Error en el servidor:', result.message?? result.error?? 'Error desconocido');
            }

        } catch (e) {
            console.error('Error de suscripción:', e);
            alert('Error al suscribir: ' + e.message);
        }
    } else {
        alert('Tu navegador no soporta notificaciones push.');
    }
}