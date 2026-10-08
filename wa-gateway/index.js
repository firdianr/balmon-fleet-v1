const { default: makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const qrcode = require('qrcode-terminal');
const express = require('express');
const cors = require('cors');
const pino = require('pino');

const app = express();
app.use(express.json());
app.use(cors());

let sock;

async function connectToWhatsApp() {
    const { state, saveCreds } = await useMultiFileAuthState('auth_info_baileys');

    sock = makeWASocket({
        logger: pino({ level: 'silent' }),
        printQRInTerminal: true,
        auth: state
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            console.log('Scan QR Code di bawah ini menggunakan WhatsApp HP Anda:');
            qrcode.generate(qr, { small: true });
        }

        if (connection === 'close') {
            const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
            console.log('Koneksi terputus karena:', lastDisconnect?.error, ', mencoba menghubungkan ulang:', shouldReconnect);
            if (shouldReconnect) {
                connectToWhatsApp();
            }
        } else if (connection === 'open') {
            console.log('✅ WhatsApp Gateway Berhasil Terhubung!');
        }
    });
}

// 🚀 API Endpoint Async: Terima data -> Langsung kirim response -> Olah antrean di background
app.post('/send-message', async (req, res) => {
    const { phone, message } = req.body;

    if (!phone || !message) {
        return res.status(400).json({ status: false, message: 'Parameter phone dan message wajib diisi' });
    }

    // 1. Beri respons cepat ke Laravel
    res.json({ status: true, message: 'Pesan telah diterima dan sedang diproses di antrean background.' });

    // 2. Normalisasi & Ratakan (Flatten) input array/string
    // .flat(Infinity) menangani kasus array bersarang dari Laravel
    const rawList = Array.isArray(phone) ? phone.flat(Infinity) : [phone];

    // 3. Eksekusi pengiriman di background
    (async () => {
        for (let i = 0; i < rawList.length; i++) {
            let item = rawList[i];

            // Jika item masih berupa array/object, lewati atau ambil string-nya
            if (Array.isArray(item)) {
                item = item[0];
            }

            if (!item) continue;

            try {
                // Pastikan dibaca sebagai String sebelum dipanggil .replace()
                let formattedPhone = String(item).replace(/[^0-9]/g, '');

                if (formattedPhone.startsWith('0')) {
                    formattedPhone = '62' + formattedPhone.slice(1);
                }

                if (!formattedPhone) continue;

                const jid = `${formattedPhone}@s.whatsapp.net`;

                // Kirim pesan via Baileys
                await sock.sendMessage(jid, { text: message });
                console.log(`[WA Gateway] ✅ Pesan terkirim ke ${jid} (${i + 1}/${rawList.length})`);

            } catch (err) {
                console.error(`[WA Gateway] ❌ Gagal kirim pesan ke ${item}:`, err.message);
            }

            // Jeda 2 detik antar nomor
            if (i < rawList.length - 1) {
                await new Promise(resolve => setTimeout(resolve, 2000));
            }
        }
    })();
});

// Jalankan Server Express di Port 3000
const PORT = 3000;
app.listen(PORT, () => {
    console.log(`🚀 WA Gateway Server berjalan di http://localhost:${PORT}`);
    connectToWhatsApp();
});