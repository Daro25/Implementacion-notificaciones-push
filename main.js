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
            await navigator.serviceWorker.register('https://ljusstudie.site/Implementacion-notificaciones-push/sw.js');
            const registration = await navigator.serviceWorker.ready;
            
            console.log('1. Service Worker activo y listo.');
            
            // VERIFICACIÓN CLAVE: ¿Tenemos la llave y el permiso?
            console.log('2. Mi clave VAPID es:', VAPID_PUBLIC_KEY);
            console.log('3. El estado del permiso en el navegador es:', Notification.permission);

            if (Notification.permission === 'denied') {
                alert('Las notificaciones están bloqueadas en tu navegador. Haz clic en el candado de la barra de direcciones para permitirlas.');
                return; // Detenemos la ejecución aquí
            }

            console.log('4. Revisando si hay suscripciones atascadas...');
    
            // Buscar y destruir cualquier suscripción fantasma previa
            const existingSub = await registration.pushManager.getSubscription();
            if (existingSub) {
                console.log('¡Suscripción fantasma encontrada! Borrando...');
                await existingSub.unsubscribe();
                console.log('Limpieza completada.');
            }

            console.log('5. Solicitando token fresco a Google/Mozilla...');

            // Intentamos la suscripción
            const subscription = await Promise.race([
                registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
                }),
                new Promise((_, reject) =>
                    setTimeout(() => reject(new Error("Timeout en subscribe")), 5000)
                )
            ]);
            console.log('6. ¡Suscripción exitosa! Token obtenido.');
            // Enviamos el token al servidor PHP
            const response = await fetch('./save_subscription.php', {
                method: 'POST',
                body: JSON.stringify(subscription),
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();
            
            if (result.status === "success" || result.success === true) {
                alert('¡Te has suscrito con éxito! Ya puedes recibir notificaciones.');
            } else {
                //console.log(result);
                console.error('Error en el servidor:', result.message || result.error || 'Error desconocido');
            }

        } catch (error) {
            console.error('❌ ERROR GRAVE al suscribir:', error);
            alert('Fallo al suscribir: ' + error.message);
        }
    } else {
        alert('Tu navegador no soporta notificaciones push.');
    }
}