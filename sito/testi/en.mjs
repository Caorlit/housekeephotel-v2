// Site texts in English (same keys as it.mjs).
export default {
    lang: 'en',
    percorso: '/en/',
    meta: {
        titolo: 'Housekeep Hotel – Simple communication between front desk and housekeeping',
        descrizione: 'A web app for hotels: housekeepers tap the room, the front desk knows instantly when it is ready. No passwords, works without Wi-Fi, in 6 languages. 3 months free.',
    },
    nav: { come: 'How it works', addette: 'Housekeepers', reception: 'Front desk', prezzi: 'Pricing', faq: 'FAQ', cta: 'Try it free', accedi: 'Log in' },

    hero: {
        etichetta: 'Launch offer: 3 months free',
        titolo: 'Talk easily with the people who clean your rooms',
        sottotitolo: 'Just a web app: nothing to install, no passwords. The housekeeper taps the room, the front desk knows instantly when it is ready.',
        cta1: 'Try it free for 3 months',
        cta2: 'Book a demo',
        nota: 'No credit card · Up and running in a day',
    },

    problemi: {
        titolo: 'No more lost messages and running down corridors',
        sottotitolo: 'Today ready rooms travel through chats, phone calls and walkie-talkies. With Housekeep everything is in one place.',
        voci: [
            ['messaggi', 'No lost messages', 'Every room and every issue stays on the board: nothing gets buried in group chats.'],
            ['pronta', 'Know instantly when a room is ready', 'The housekeeper taps, the tile turns green and the front desk hears a chime. In real time.'],
            ['radio', 'No walkie-talkies, no phone calls', 'No need to look for anyone: the status of every room is always in front of you.'],
            ['whatsapp', 'Connected to WhatsApp', 'Forward an issue to maintenance with one tap and alert housekeepers about urgent rooms.'],
        ],
    },

    come: {
        titolo: 'How it works',
        sottotitolo: 'Three gestures, zero training.',
        passi: [
            ['The housekeeper taps the room', 'One tap when she starts (yellow), one when she is done (green). It even works where there is no signal.', 'telefono'],
            ['The front desk sees everything live', 'The board updates by itself and chimes when a room is ready or an issue comes in.', 'tabellone'],
            ['A problem? Just a photo', 'The red button opens the camera: the report reaches the front desk instantly, ready to forward on WhatsApp.', 'segnalazione'],
        ],
    },

    addette: {
        titolo: 'Easy for everyone, from day one',
        sottotitolo: 'Designed for people who don\'t like apps: big buttons, clear colours, very few words.',
        voci: [
            ['No passwords', 'Each housekeeper has her own QR code: she scans it and her page opens.'],
            ['Works without Wi-Fi', 'In corridors with no signal, taps are kept on the phone and sent automatically when the connection returns.'],
            ['Big buttons, even with gloves', 'Huge numbers and colours: grey to do, yellow cleaning, green ready.'],
            ['In her own language', 'The front desk chooses the language for each housekeeper.'],
            ['Installs like an app', 'An icon on the Home screen, no app store needed.'],
            ['The coming days at a glance', 'The calendar shows the rooms planned for the week.'],
        ],
        lingue: 'Italiano · English · Română · Українська · Español · বাংলা',
    },

    reception: {
        titolo: 'Your whole day under control, on one screen',
        sottotitolo: 'For the front desk and the head housekeeper: on a PC or tablet, nothing to install.',
        voci: [
            ['Live board', 'Every room coloured by status, with housekeeper and time. Filter by floor or by housekeeper.'],
            ['Sounds and notifications', 'A chime when a room is ready, a louder one when an issue is reported.'],
            ['Import from your PMS', 'Upload your bookings file: check-outs and stay-overs are created automatically, for the same housekeeper.'],
            ['Your day ready in a minute', 'Assign rooms with a tap, distribute them automatically, handle days off.'],
            ['Forward on WhatsApp', 'Reports with photos go to maintenance, urgent rooms to the housekeeper: in her language.'],
            ['Statistics', 'Average cleaning times, rooms per housekeeper, resolved issues.'],
        ],
    },

    prezzi: {
        titolo: 'Simple pricing, no strings attached',
        sottotitolo: 'One yearly subscription, one invoice. Unlimited housekeepers.',
        promo: 'Launch offer: <b>3 months free</b>. Just sign up, no credit card required.',
        mese: '/year',
        piani: [
            ['Small', '€199', 'up to 35 rooms', 'unlimited housekeepers'],
            ['Medium', '€379', '36 to 70 rooms', 'unlimited housekeepers'],
            ['Large', 'On request', 'more than 70 rooms', 'unlimited housekeepers'],
        ],
        inclusi: ['1 front desk user included', 'Board and housekeeper app', 'Import from your PMS', 'Forward on WhatsApp', 'Email support'],
        consigliato: 'Most popular',
        note: 'Hotel setup €99 one-off · Extra front desk user €60/year · Phone and WhatsApp support €100/year · Prices excluding VAT',
        cta: 'Start the free trial',
    },

    faq: {
        titolo: 'Frequently asked questions',
        voci: [
            ['Do we need to install an app?', 'No: it is a web app. It opens from the QR code and, if you like, can be added to the Home screen like a normal app.'],
            ['Do housekeepers need an account?', 'No email, no password: each one has her personal QR code. If a phone is lost, the front desk simply generates a new code.'],
            ['What if there is no signal in the corridor?', 'The app keeps working: taps are stored on the phone and sent as soon as the connection returns. Nothing gets lost.'],
            ['Does it work with our PMS?', 'It imports bookings from a CSV file, which almost every hotel software can export. Check-outs and stay-overs are created automatically.'],
            ['Do we need company phones?', 'No. The housekeeper\'s own phone works, Android or iPhone, or a tablet on each floor.'],
            ['Are the photos of the reports safe?', 'Only hotel staff can see them and they are deleted automatically after 90 days. Links forwarded on WhatsApp expire after 30 days.'],
            ['How does the free trial work?', 'Write to us, we activate your hotel and help you get started. For 3 months you pay nothing and need no credit card. At the end we send you the details for the yearly payment: you decide whether to continue.'],
        ],
    },

    prova: {
        titolo: 'Try Housekeep free for 3 months',
        sottotitolo: 'Fill in the form: we will get back to you within one working day and activate your hotel.',
        campi: {
            nome: 'Full name', hotel: 'Hotel name', citta: 'City', email: 'Email', telefono: 'Phone',
            camere: 'Number of rooms', interesse: 'What would you like?', prova: 'Start the free trial', demo: 'A guided demo',
            messaggio: 'Message (optional)', privacy: 'I have read the <a href="{privacy}">privacy notice</a> and agree to be contacted.',
            invia: 'Send request',
        },
        errore: 'Something went wrong: please check the required fields and the privacy box, then try again.',
        oppure: 'Would you rather talk now?',
        whatsapp: 'Message us on WhatsApp',
    },

    footer: { privacy: 'Privacy', app: 'Log in to the app', diritti: 'All rights reserved.' },

    grazie: {
        titolo: 'Thank you, request received!',
        testo: 'We will get back to you within one working day at the email you gave us. In a hurry? Message us on WhatsApp.',
        torna: 'Back to the site',
    },

    privacy: {
        titolo: 'Privacy notice',
        aggiornata: 'Last updated: November 2026',
        sezioni: [
            ['Data controller', 'Caorle.it S.r.l., Via Giovanni Vescovo 2, 30021 Caorle (VE), Italy, email {email}.'],
            ['What data we collect', 'Through the "Try it free" form: name, hotel name and city, email, phone, number of rooms and any message. This site uses no profiling cookies or third-party analytics, and fonts are hosted on our own server.'],
            ['Why we use it', 'Only to contact you, activate the free trial and answer your questions (legal basis: pre-contractual measures requested by you, Art. 6(1)(b) GDPR).'],
            ['How long we keep it', 'For as long as needed to handle your request and, if you do not become a customer, no longer than 24 months.'],
            ['Who processes it', 'Caorle.it S.r.l. staff and our hosting provider, acting as data processor. Your data is never sold or passed on to third parties for marketing.'],
            ['Your rights', 'You can request access, rectification, erasure, restriction or portability of your data and object to processing at any time by writing to {email}. You may also lodge a complaint with the Italian Data Protection Authority (www.garanteprivacy.it).'],
        ],
    },
};
