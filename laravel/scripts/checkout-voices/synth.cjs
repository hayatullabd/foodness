const dns = require('node:dns');
const fs = require('node:fs');
const path = require('node:path');

const originalLookup = dns.lookup;
dns.lookup = (hostname, options, callback) => {
    if (typeof options === 'function') {
        callback = options;
        options = {};
    }
    const opts = typeof options === 'object' && options ? { ...options, family: 4 } : { family: 4 };
    return originalLookup(hostname, opts, callback);
};

const readStdin = () => new Promise((resolve, reject) => {
    const chunks = [];
    process.stdin.on('data', (chunk) => chunks.push(chunk));
    process.stdin.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
    process.stdin.on('error', reject);
});

const safeId = (value) => {
    const id = String(value || '').trim();
    if (!/^[a-z0-9-]{1,40}$/.test(id)) {
        throw new Error('Invalid voice id');
    }
    return id;
};

(async () => {
    const job = JSON.parse(await readStdin());
    const outRoot = path.resolve(String(job.out || ''));
    const phrases = job.phrases || {};
    const voices = Array.isArray(job.voices) ? job.voices : [];
    const { EdgeTTS } = require('@andresaya/edge-tts');
    const tts = new EdgeTTS();

    for (const voice of voices) {
        const id = safeId(voice.id);
        for (const field of ['name', 'phone', 'address']) {
            const text = String(phrases[field] || '').trim();
            if (!text) {
                throw new Error('Missing line for ' + field);
            }
            const file = path.join(outRoot, id, field + '.mp3');
            fs.mkdirSync(path.dirname(file), { recursive: true });
            const result = await tts.generate(text, String(voice.voice), {
                rate: String(voice.rate || '+0%'),
                pitch: String(voice.pitch || '+0Hz'),
            });
            if (!result.audio || result.audio.length < 1000) {
                throw new Error('Empty recording for ' + id + '/' + field);
            }
            fs.writeFileSync(file, result.audio);
            console.log(id + '/' + field + '.mp3 ' + result.audio.length);
        }
    }

    fs.writeFileSync(
        path.join(outRoot, 'manifest.json'),
        JSON.stringify({ phrases }, null, 2)
    );
})().catch((error) => {
    console.error(error && error.message ? error.message : error);
    process.exit(1);
});
