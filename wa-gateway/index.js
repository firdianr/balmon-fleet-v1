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

// 🚀 API Endpoint untuk Menerima Pesan dari Laravel
app.post('/send-message', async (req, res) => {
    try {
        const { phone, message } = req.body;

        if (!phone || !message) {
            return res.status(400).json({ status: false, message: 'Parameter phone dan message wajib diisi' });
        }

        // Format nomor Indonesia (Ubah 08xx atau +628xx menjadi 628xx@s.whatsapp.net)
        let formattedPhone = phone.replace(/[^0-9]/g, '');
        if (formattedPhone.startsWith('0')) {
            formattedPhone = '62' + formattedPhone.slice(1);
        }
        const jid = `${formattedPhone}@s.whatsapp.net`;

        // Kirim Pesan
        await sock.sendMessage(jid, { text: message });

        return res.json({ status: true, message: `Pesan berhasil dikirim ke ${phone}` });
    } catch (error) {
        console.error('Error sending message:', error);
        return res.status(500).json({ status: false, error: error.message });
    }
});

// Jalankan Server Express di Port 3000
const PORT = 3000;
app.listen(PORT, () => {
    console.log(`🚀 WA Gateway Server berjalan di http://localhost:${PORT}`);
    connectToWhatsApp();
});