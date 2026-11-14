// Suoni generati nel browser (niente file audio).
// I telefoni li permettono solo dopo il primo tocco sulla pagina: sblocca() va chiamato allora.

let audio = null;

export function sblocca() {
    try {
        audio ??= new (window.AudioContext || window.webkitAudioContext)();
        if (audio.state === 'suspended') audio.resume();
    } catch { /* niente audio */ }
}

function tono(freq, inizio, durata, volume, forma = 'sine') {
    if (!audio || audio.state !== 'running') return false;
    const osc = audio.createOscillator();
    const gain = audio.createGain();
    osc.type = forma;
    osc.frequency.value = freq;
    const t = audio.currentTime + inizio;
    gain.gain.setValueAtTime(0.0001, t);
    gain.gain.exponentialRampToValueAtTime(volume, t + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, t + durata);
    osc.connect(gain).connect(audio.destination);
    osc.start(t);
    osc.stop(t + durata + 0.05);
    return true;
}

/** Camera urgente: tre bip forti. */
export function suonoUrgente() {
    for (let i = 0; i < 3; i++) {
        tono(880, i * 0.3, 0.16, 0.6, 'square');
        tono(660, i * 0.3 + 0.15, 0.13, 0.6, 'square');
    }
}

/** Altre novità dalla reception: due note brevi. */
export function suonoAvviso() {
    tono(660, 0, 0.15, 0.35);
    tono(990, 0.14, 0.25, 0.35);
}
