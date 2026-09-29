// 顔認識エンジン（Human）の共通処理
import { Human } from '@vladmandic/human';

// config/face.php の model_version と一致させる
export const MODEL_VERSION = 'human-3.3.6-faceres';

const MIN_FACE_PX = 120; // 顔の幅の最小ピクセル数
const MIN_SCORE = 0.6;   // 検出の信頼度の下限
const MAX_FACES = 3;     // 同時に検出する顔の数（一番大きい顔を照合対象にする）
const REQUIRED_MODELS = ['blazeface', 'facemesh', 'faceres'];
const GPU_BACKENDS = ['webgl', 'humangl', 'webgpu'];
const COLORS = { ok: '#16a34a', warn: '#f59e0b', ng: '#dc2626', other: '#9ca3af' };

const human = new Human({
    modelBasePath: '/models/human/',
    backend: 'webgl',
    cacheSensitivity: 0, // 前フレームの結果を使い回さない
    face: {
        enabled: true,
        detector: { rotation: true, maxDetected: MAX_FACES },
        mesh: { enabled: true },
        iris: { enabled: false },
        description: { enabled: true },
        emotion: { enabled: false },
        antispoof: { enabled: false },
        liveness: { enabled: false },
    },
    body: { enabled: false },
    hand: { enabled: false },
    object: { enabled: false },
    segmentation: { enabled: false },
    gesture: { enabled: true },
});

let detectQueue = Promise.resolve(); // 検出を1つずつ順番に実行するための待ち行列
let overlay = null;                  // { video, canvas }
let pinned = null;                   // 照合結果の表示 { text, color, until }

const backendName = () => {
    try { return human.tf.getBackend() ?? ''; } catch { return ''; }
};

// 一定時間で終わらなければエラーにする
function withTimeout(promise, ms, label) {
    let timer;
    const timeout = new Promise((_, reject) => {
        timer = setTimeout(() => reject(new Error(`${label}が${ms / 1000}秒以内に終わりませんでした。`)), ms);
    });
    return Promise.race([promise, timeout]).finally(() => clearTimeout(timer));
}

// human.detect を同時に走らせず、順番に実行する
function detect(input) {
    const run = detectQueue.then(() => human.detect(input));
    detectQueue = run.catch(() => { });
    return run;
}

// 初期化：進み具合を onStatus で画面に知らせる
export async function init(onStatus = () => { }) {
    onStatus('計算エンジン（WebGL）を準備しています…');
    try {
        await withTimeout(human.init(), 30000, '計算エンジンの準備');
    } catch (e) {
        console.warn(e);
    }

    if (!GPU_BACKENDS.includes(backendName())) {
        onStatus('WebGLが使えないため、WASMに切り替えています…');
        human.config.backend = 'wasm';
        await withTimeout(human.init(), 30000, 'WASMへの切り替え');
    }

    const timer = setInterval(() => {
        const pct = Math.round(human.models.stats().percentageLoaded * 100);
        onStatus(`AIモデルを読み込んでいます… ${pct}%（${backendName()}）`);
    }, 500);
    try {
        await withTimeout(human.load(), 180000, 'AIモデルの読み込み');
    } finally {
        clearInterval(timer);
    }

    const loaded = human.models.loaded();
    const missing = REQUIRED_MODELS.filter((m) => !loaded.includes(m));
    if (missing.length > 0) {
        throw new Error(`モデルを読み込めませんでした：${missing.join(', ')}`);
    }
    onStatus(`AIモデルの準備ができました（${backendName()}）。`);
}

export async function startCamera(video, onStatus = () => { }) {
    if (!navigator.mediaDevices?.getUserMedia) {
        throw new Error('このアドレスではカメラを使えません。http://127.0.0.1:8000 か https:// で開いてください。');
    }
    onStatus('カメラを起動しています…（許可を求められたら「許可」を押してください）');
    video.srcObject = await navigator.mediaDevices.getUserMedia({
        video: { width: 640, height: 480, facingMode: 'user' },
        audio: false,
    });
    await video.play();
}

// 最初の1回の検出はGPUの準備で時間がかかるため、経過時間を表示しながら先に済ませる
export async function warmupDetection(input, onStatus = () => { }) {
    const t0 = performance.now();
    const elapsed = () => Math.round((performance.now() - t0) / 1000);
    onStatus('初回の検出を準備しています…');
    const timer = setInterval(() => {
        onStatus(`初回の検出を準備しています…（${elapsed()}秒経過／初回は数十秒かかることがあります）`);
    }, 1000);
    try {
        await withTimeout(detect(input), 90000, '初回の検出');
    } finally {
        clearInterval(timer);
    }
    return Math.round(performance.now() - t0);
}

// 枠線を描くキャンバスをカメラ映像に重ねる（startCamera の後に呼ぶ）
export function attachOverlay(video, canvas) {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    overlay = { video, canvas };
}

// 照合結果などを、対象の顔の枠に一定時間表示する
export function showLabel(text, ok, ms = 3000) {
    pinned = { text, color: ok ? COLORS.ok : COLORS.ng, until: Date.now() + ms };
}

// 映像を検出し続けて枠線を更新する（戻り値の関数を呼ぶと止まる）
export function startPreview(video, options = {}) {
    let stopped = false;
    (async () => {
        while (!stopped) {
            if (!document.hidden && !video.paused) {
                try {
                    await analyze(video, options);
                } catch (e) {
                    console.warn(e);
                }
            }
            await sleep(100);
        }
    })();
    return () => { stopped = true; };
}

// 顔を検出し、一番大きい顔の品質をチェックする。カメラ映像なら枠線も描く
export async function analyze(input, { requireSingle = false } = {}) {
    const result = await detect(input);
    const faces = result.face ?? [];
    const area = (f) => f.box[2] * f.box[3];
    let target = -1;
    faces.forEach((f, i) => {
        if (target < 0 || area(f) > area(faces[target])) target = i;
    });

    const check = checkFace(result, target, requireSingle);
    if (overlay && input === overlay.video) drawOverlay(faces, target, check);
    return check;
}

function checkFace(result, t, requireSingle) {
    const face = result.face?.[t] ?? null;
    const fail = (reason, short) => ({ ok: false, reason, short, face });

    if (result.error) return fail('検出エラー：' + result.error, 'エラー');
    if (!face) return fail('顔が見つかりません。', '');
    if (requireSingle && result.face.length > 1) return fail('カメラに写るのは1人だけにしてください。', '1人だけ写してください');
    if (face.score < MIN_SCORE) return fail('顔がはっきり写っていません。明るい場所で試してください。', '不鮮明');
    if (face.box[2] < MIN_FACE_PX) return fail('もう少しカメラに近づいてください。', '近づいてください');
    const sideways = result.gesture.some((g) => g.face === t && (g.gesture === 'facing left' || g.gesture === 'facing right'));
    if (sideways) return fail('カメラの正面を向いてください。', '正面を向いてください');
    if (!face.embedding?.length) return fail('特徴量を取得できませんでした。', '読み取り失敗');

    return { ok: true, reason: '', short: '認識中', face, embedding: Array.from(face.embedding) };
}

function drawOverlay(faces, target, check) {
    const { canvas } = overlay;
    const ctx = canvas.getContext('2d');
    const W = canvas.width;
    const H = canvas.height;
    const lw = Math.max(2, Math.round(W / 200));  // 線の太さ
    const fs = Math.max(14, Math.round(W / 32));  // 文字の大きさ
    const pad = Math.round(fs * 0.3);

    ctx.clearRect(0, 0, W, H);
    ctx.lineWidth = lw;
    ctx.font = `bold ${fs}px sans-serif`;
    ctx.textBaseline = 'top';

    faces.forEach((face, i) => {
        const [rx, ry, rw, rh] = face.boxRaw; // 0〜1 に正規化された枠
        const x = rx * W;
        const y = ry * H;
        const w = rw * W;
        const h = rh * H;

        let color = COLORS.other;
        let label = '対象外';
        if (i === target) {
            if (pinned && Date.now() < pinned.until) {
                color = pinned.color;
                label = pinned.text;
            } else {
                color = check.ok ? COLORS.ok : COLORS.warn;
                label = check.short;
            }
        }

        ctx.strokeStyle = color;
        ctx.strokeRect(x, y, w, h);
        if (!label) return;

        const tw = ctx.measureText(label).width + pad * 2;
        const th = fs + pad * 2;
        const ly = y - th >= 0 ? y - th : y + h; // 枠の上に入らなければ下に出す
        ctx.fillStyle = color;
        ctx.fillRect(x - lw / 2, ly, tw, th);
        ctx.fillStyle = '#fff';
        ctx.fillText(label, x - lw / 2 + pad, ly + pad);
    });
}

// 顔の周辺を正方形に切り出して JPEG の data URL にする
export function cropFace(input, box, size = 224) {
    const [x, y, w, h] = box;
    const side = Math.max(w, h) * 1.5;
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    canvas.getContext('2d').drawImage(
        input, x + w / 2 - side / 2, y + h / 2 - side / 2, side, side, 0, 0, size, size,
    );
    return canvas.toDataURL('image/jpeg', 0.9);
}

// 複数フレームの特徴量を正規化して平均する
export function averageEmbedding(list) {
    const sum = new Array(list[0].length).fill(0);
    for (const e of list) {
        const norm = Math.sqrt(e.reduce((s, v) => s + v * v, 0)) || 1;
        e.forEach((v, i) => { sum[i] += v / norm; });
    }
    return sum.map((v) => v / list.length);
}

export async function postJson(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message ?? `通信エラー（${res.status}）`);
    return data;
}

export const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

export const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
));