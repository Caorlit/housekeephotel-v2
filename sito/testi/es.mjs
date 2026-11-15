// Textos del sitio en español (mismas claves que it.mjs).
export default {
    lang: 'es',
    percorso: '/es/',
    meta: {
        titolo: 'Housekeep Hotel – La comunicación sencilla entre recepción y camareras de piso',
        descrizione: 'Web app para hoteles: la camarera toca la habitación y recepción sabe al instante cuándo está lista. Sin contraseñas, funciona sin Wi-Fi, en 6 idiomas. 3 meses gratis.',
    },
    nav: { come: 'Cómo funciona', addette: 'Camareras', reception: 'Recepción', prezzi: 'Precios', faq: 'Preguntas', cta: 'Pruébalo gratis', accedi: 'Acceder' },

    hero: {
        etichetta: 'Promoción de lanzamiento: 3 meses gratis',
        titolo: 'Comunícate fácilmente con quien limpia las habitaciones',
        sottotitolo: 'Basta una web app: nada que instalar y sin contraseñas. La camarera toca la habitación y recepción sabe al instante cuándo está lista.',
        cta1: 'Pruébalo gratis 3 meses',
        cta2: 'Solicita una demo',
        nota: 'Sin tarjeta de crédito · Listo en un día',
    },

    problemi: {
        titolo: 'Adiós a los mensajes perdidos y a las carreras por el pasillo',
        sottotitolo: 'Hoy las habitaciones listas pasan por chats, llamadas y walkie-talkies. Con Housekeep todo está en un solo lugar.',
        voci: [
            ['messaggi', 'Ningún mensaje perdido', 'Cada habitación y cada incidencia queda en el panel: nada se pierde entre los chats de grupo.'],
            ['pronta', 'Sabes al instante cuándo está lista', 'La camarera toca, la ficha se vuelve verde y en recepción suena un aviso. En tiempo real.'],
            ['radio', 'Sin walkie-talkies ni llamadas', 'No hace falta buscar a nadie: el estado de todas las habitaciones siempre a la vista.'],
            ['whatsapp', 'Conectado a WhatsApp', 'Reenvía una incidencia a mantenimiento con un toque y avisa a la camarera de las urgencias.'],
        ],
    },

    come: {
        titolo: 'Cómo funciona',
        sottotitolo: 'Tres gestos, cero formación.',
        passi: [
            ['La camarera toca la habitación', 'Un toque al empezar (amarillo) y otro al terminar (verde). Funciona incluso sin cobertura.', 'telefono'],
            ['Recepción lo ve todo en tiempo real', 'El panel se actualiza solo y suena cuando una habitación está lista o llega una incidencia.', 'tabellone'],
            ['¿Un problema? Basta una foto', 'El botón rojo abre la cámara: la incidencia llega al instante a recepción, lista para reenviar por WhatsApp.', 'segnalazione'],
        ],
    },

    addette: {
        titolo: 'Fácil para todas, desde el primer día',
        sottotitolo: 'Pensada para quien no se lleva bien con las apps: botones grandes, colores claros, muy pocas palabras.',
        voci: [
            ['Sin contraseñas', 'Cada camarera tiene su código QR personal: lo escanea y se abre su página.'],
            ['Funciona sin Wi-Fi', 'En los pasillos sin cobertura los toques se guardan en el móvil y se envían solos al volver la conexión.'],
            ['Botones grandes, incluso con guantes', 'Números enormes y colores: gris por hacer, amarillo limpiando, verde lista.'],
            ['En su idioma', 'Recepción elige el idioma de cada camarera.'],
            ['Se instala como una app', 'Un icono en la pantalla de inicio, sin pasar por las tiendas.'],
            ['Los próximos días de un vistazo', 'Con el calendario ve las habitaciones previstas para la semana.'],
        ],
        lingue: 'Italiano · English · Română · Українська · Español · বাংলা',
    },

    reception: {
        titolo: 'Todo el día bajo control, en una sola pantalla',
        sottotitolo: 'Para recepción y gobernanta: en PC o tablet, sin instalar nada.',
        voci: [
            ['Panel en tiempo real', 'Todas las habitaciones con su color, la camarera y la hora. Filtros por planta y por camarera.'],
            ['Sonidos y notificaciones', 'Un aviso cuando una habitación está lista y otro más fuerte cuando llega una incidencia.'],
            ['Importa desde tu PMS', 'Subes el archivo de reservas: salidas y repasos se crean solos, para la misma camarera.'],
            ['El día listo en un minuto', 'Asigna habitaciones con un toque, reparte automáticamente y gestiona los días libres.'],
            ['Reenvío por WhatsApp', 'La incidencia con foto va a mantenimiento y la urgencia a la camarera: en su idioma.'],
            ['Estadísticas', 'Tiempos medios de limpieza, habitaciones por camarera, incidencias resueltas.'],
        ],
    },

    prezzi: {
        titolo: 'Precios sencillos, sin permanencia',
        sottotitolo: 'Cuesta menos que un café al día y te devuelve horas de llamadas.',
        promo: 'Promoción de lanzamiento: <b>3 meses gratis</b>. Solo regístrate, sin tarjeta de crédito.',
        mese: '/mes',
        piani: [
            ['Small', '30 €', 'hasta 35 habitaciones', 'hasta 3 camareras'],
            ['Medium', '60 €', 'hasta 70 habitaciones', 'hasta 5 camareras'],
            ['Large', 'A consultar', 'más de 70 habitaciones', 'equipo a medida'],
        ],
        inclusi: ['Panel y app para camareras', 'Importación desde tu PMS', 'Reenvío por WhatsApp', 'Asistencia de nuestro equipo'],
        consigliato: 'El más elegido',
        note: 'Alta del hotel 99 € pago único · Recepcionista adicional 30 €/año · Precios sin IVA',
        cta: 'Empieza la prueba gratuita',
    },

    faq: {
        titolo: 'Preguntas frecuentes',
        voci: [
            ['¿Hay que instalar una app?', 'No: es una web app. Se abre desde el código QR y, si se quiere, se añade a la pantalla de inicio como una app normal.'],
            ['¿Las camareras necesitan una cuenta?', 'No, ni email ni contraseña: cada una tiene su código QR personal. Si se pierde un móvil, recepción genera un código nuevo.'],
            ['¿Y si en el pasillo no hay cobertura?', 'La app sigue funcionando: los toques se guardan en el móvil y se envían en cuanto vuelve la conexión. No se pierde nada.'],
            ['¿Funciona con nuestro PMS?', 'Importa las reservas desde un archivo CSV, que casi todos los programas de hotel exportan. Salidas y repasos se crean solos.'],
            ['¿Hace falta un móvil de empresa?', 'No. Sirve el móvil de la camarera, Android o iPhone, o una tablet en cada planta.'],
            ['¿Las fotos de las incidencias están seguras?', 'Solo las ve el personal del hotel y se borran solas a los 90 días. El enlace reenviado por WhatsApp caduca a los 30 días.'],
            ['¿Cómo funciona la prueba gratuita?', 'Nos escribes, activamos tu hotel y te ayudamos a empezar. Durante 3 meses no pagas nada y no hace falta tarjeta: luego decides tú.'],
        ],
    },

    prova: {
        titolo: 'Prueba Housekeep gratis durante 3 meses',
        sottotitolo: 'Rellena el formulario: te contestamos en un día laborable y activamos tu hotel.',
        campi: {
            nome: 'Nombre y apellidos', hotel: 'Nombre del hotel', citta: 'Ciudad', email: 'Email', telefono: 'Teléfono',
            camere: 'Número de habitaciones', interesse: '¿Qué prefieres?', prova: 'Activar la prueba gratuita', demo: 'Una demo guiada',
            messaggio: 'Mensaje (opcional)', privacy: 'He leído la <a href="{privacy}">política de privacidad</a> y acepto ser contactado.',
            invia: 'Enviar solicitud',
        },
        errore: 'Algo ha fallado: revisa los campos obligatorios y la casilla de privacidad, y vuelve a intentarlo.',
        oppure: '¿Prefieres hablar ahora?',
        whatsapp: 'Escríbenos por WhatsApp',
    },

    footer: { privacy: 'Privacidad', app: 'Acceder a la app', diritti: 'Todos los derechos reservados.' },

    grazie: {
        titolo: '¡Gracias, solicitud recibida!',
        testo: 'Te contestamos en un día laborable al email que nos has dejado. Si tienes prisa, escríbenos por WhatsApp.',
        torna: 'Volver al sitio',
    },

    privacy: {
        titolo: 'Política de privacidad',
        aggiornata: 'Última actualización: noviembre de 2026',
        sezioni: [
            ['Responsable del tratamiento', 'Caorle.it S.r.l., Via Giovanni Vescovo 2, 30021 Caorle (VE), Italia, email {email}.'],
            ['Qué datos recogemos', 'Con el formulario "Pruébalo gratis": nombre, nombre y ciudad del hotel, email, teléfono, número de habitaciones y el posible mensaje. El sitio no utiliza cookies de perfilado ni herramientas de estadística de terceros, y las fuentes se alojan en nuestro servidor.'],
            ['Para qué los usamos', 'Solo para contactarte, activar la prueba gratuita y responder a tus preguntas (base jurídica: medidas precontractuales solicitadas por el interesado, art. 6.1.b RGPD).'],
            ['Durante cuánto tiempo', 'El tiempo necesario para gestionar la solicitud y, si no te conviertes en cliente, no más de 24 meses.'],
            ['Quién los trata', 'El personal de Caorle.it S.r.l. y el proveedor de hosting, como encargado del tratamiento. Los datos no se venden ni se ceden a terceros con fines comerciales.'],
            ['Tus derechos', 'Puedes solicitar en cualquier momento el acceso, la rectificación, la supresión, la limitación o la portabilidad de tus datos y oponerte al tratamiento escribiendo a {email}. También puedes presentar una reclamación ante la autoridad de control italiana (www.garanteprivacy.it).'],
        ],
    },
};
