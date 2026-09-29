import {
    init, startCamera, warmupDetection, attachOverlay, startPreview,
    analyze, cropFace, postJson, sleep, MODEL_VERSION,
} from './face-engine.js';

window.faceAppStarted = true;

const root = document.getElementById('face-register');
const statusEl = document.getElementById('status');
const video = document.getElementById('video');
const overlayCanvas = document.getElementById('overlay');
const captureBtn = document.getElementById('captureBtn');
const saveBtn = document.getElementById('saveBtn');
const resetBtn = document.getElementById('resetBtn');
const thumbs = document.getElementById('thumbs');

const TARGET = 5;      // 撮影枚数
const MIN_SAVE = 3;    // 登録に必要な最低枚数
const INTERVAL = 1000; // 撮影間隔（ミリ秒）
const ANALYZE_OPTIONS = { requireSingle: true }; // 登録時は1人だけ写っていることを条件にする

const setStatus = (text) => { statusEl.textContent = text; };
let samples = [];

async function capture() {
    captureBtn.disabled = true;
    saveBtn.disabled = true;
    const deadline = Date.now() + 30000;

    while (samples.length < TARGET && Date.now() < deadline) {
        const r = await analyze(video, ANALYZE_OPTIONS);
        if (!r.ok) {
            setStatus(r.reason);
            await sleep(200);
            continue;
        }
        const image = cropFace(video, r.face.box);
        samples.push({ embedding: r.embedding, image });
        thumbs.insertAdjacentHTML('beforeend', `<img src="${image}" alt="">`);
        setStatus(`${samples.length} / ${TARGET} 枚撮影しました。少しずつ顔の角度や表情を変えてください。`);
        await sleep(INTERVAL);
    }

    setStatus(samples.length >= MIN_SAVE
        ? `${samples.length}枚撮影しました。問題なければ「登録する」を押してください。`
        : '十分な枚数を撮影できませんでした。明るさや距離を調整して撮り直してください。');
    saveBtn.disabled = samples.length < MIN_SAVE;
    captureBtn.disabled = false;
}

async function save() {
    saveBtn.disabled = true;
    setStatus('送信しています…');
    try {
        const res = await postJson(root.dataset.storeUrl, { model_version: MODEL_VERSION, samples });
        setStatus(`${res.saved}枚を登録しました（合計 ${res.total} 枚）。`);
        samples = [];
        thumbs.innerHTML = '';
    } catch (e) {
        setStatus('登録に失敗しました：' + e.message);
        saveBtn.disabled = false;
    }
}

function reset() {
    samples = [];
    thumbs.innerHTML = '';
    saveBtn.disabled = true;
    setStatus('撮り直します。枠が緑色になったら「撮影開始」を押してください。');
}

async function main() {
    try {
        await init(setStatus);
        await startCamera(video, setStatus);
        attachOverlay(video, overlayCanvas);
        await warmupDetection(video, setStatus);
    } catch (e) {
        setStatus('初期化に失敗しました：' + e.message);
        console.error(e);
        return;
    }
    startPreview(video, ANALYZE_OPTIONS);
    setStatus('枠が緑色になったら「撮影開始」を押してください。');
    captureBtn.disabled = false;
}

captureBtn.addEventListener('click', capture);
saveBtn.addEventListener('click', save);
resetBtn.addEventListener('click', reset);
main();