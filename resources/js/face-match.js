import {
    init, startCamera, warmupDetection, attachOverlay, startPreview, showLabel,
    analyze, averageEmbedding, postJson, sleep, esc, MODEL_VERSION,
} from './face-engine.js';

window.faceAppStarted = true;

const root = document.getElementById('face-match');
const statusEl = document.getElementById('status');
const video = document.getElementById('video');
const overlayCanvas = document.getElementById('overlay');
const cameraBtn = document.getElementById('cameraBtn');
const autoCheck = document.getElementById('autoCheck');
const fileInput = document.getElementById('fileInput');
const resultEl = document.getElementById('result');

const FRAMES_PER_MATCH = 3; // 平均に使うフレーム数
const COOLDOWN = 3000;      // 結果を表示しておく時間／連続照合の間隔（ミリ秒）

const setStatus = (text) => { statusEl.textContent = text; };
const fmt = (v) => (v === null || v === undefined) ? '-' : Number(v).toFixed(3);

let busy = false;    // 照合の二重実行を防ぐ
let looping = false; // 連続照合が動いているか

function renderResult(r) {
    const rows = r.candidates.map((c) => `
        <tr><td>${esc(c.person?.name)}</td><td>${esc(c.person?.student_number)}</td>
        <td>${esc(c.person?.class_name)}</td><td>${fmt(c.similarity)}</td></tr>`).join('');
    resultEl.innerHTML = `
        <h2 class="${r.accepted ? 'ok' : 'error'}">
            ${r.accepted ? `${esc(r.person.name)} さん（${esc(r.person.class_name)}）` : '該当者なし（または判定保留）'}
        </h2>
        <p>類似度 ${fmt(r.similarity)} ／ 2位との差 ${fmt(r.margin)} ／ しきい値 ${fmt(r.threshold)}
           ／ 照合時刻 ${new Date().toLocaleTimeString()}</p>
        <table>
            <tr><th>候補</th><th>学籍番号</th><th>クラス</th><th>類似度</th></tr>
            ${rows || '<tr><td colspan="4">登録データがありません。</td></tr>'}
        </table>`;
}

async function sendEmbedding(embedding, { fromCamera = false } = {}) {
    setStatus('サーバーで照合しています…');
    const r = await postJson(root.dataset.matchUrl, { model_version: MODEL_VERSION, embedding });
    renderResult(r);
    if (fromCamera) {
        // 照合した顔の枠に結果を表示する（fillText は HTML ではないのでエスケープ不要）
        showLabel(r.accepted ? `${r.person.name} さん` : '該当者なし', r.accepted, COOLDOWN);
    }
}

// 条件の良いフレームを集める（途中経過を画面に出す）
async function collectFrames(maxAttempts) {
    const collected = [];
    for (let i = 1; i <= maxAttempts && collected.length < FRAMES_PER_MATCH; i++) {
        const r = await analyze(video);
        if (r.ok) {
            collected.push(r.embedding);
            setStatus(`顔を読み取っています…（${collected.length} / ${FRAMES_PER_MATCH}）`);
        } else {
            setStatus(r.reason);
            await sleep(150);
        }
    }
    return collected;
}

// ボタン：1回だけ照合する
async function matchOnce() {
    if (busy) return;
    busy = true;
    cameraBtn.disabled = true;
    try {
        setStatus('顔を探しています…');
        const collected = await collectFrames(40);
        if (collected.length === 0) {
            throw new Error('条件の良い顔画像が得られませんでした。明るさや距離を調整してください。');
        }
        await sendEmbedding(averageEmbedding(collected), { fromCamera: true });
        setStatus('照合が完了しました。');
    } catch (e) {
        setStatus('エラー：' + e.message);
    } finally {
        busy = false;
        cameraBtn.disabled = looping;
    }
}

// チェックボックス：顔が写るたびに照合し続ける
async function autoLoop() {
    if (looping) return;
    looping = true;
    cameraBtn.disabled = true;
    while (autoCheck.checked) {
        if (busy) {
            await sleep(200);
            continue;
        }
        busy = true;
        try {
            const collected = await collectFrames(10);
            if (collected.length === FRAMES_PER_MATCH) {
                await sendEmbedding(averageEmbedding(collected), { fromCamera: true });
                setStatus(`照合しました。${COOLDOWN / 1000}秒後に次の照合を始めます。`);
                await sleep(COOLDOWN);
            }
        } catch (e) {
            setStatus('エラー：' + e.message);
            await sleep(COOLDOWN);
        } finally {
            busy = false;
        }
    }
    looping = false;
    cameraBtn.disabled = false;
    setStatus('連続照合を止めました。');
}

// 画像ファイルで照合する
async function matchFromFile() {
    const file = fileInput.files[0];
    if (!file) return;
    if (busy) {
        setStatus('照合中です。連続照合を止めてから画像を選んでください。');
        fileInput.value = '';
        return;
    }
    busy = true;
    const img = new Image();
    img.src = URL.createObjectURL(file);
    try {
        setStatus('画像から顔を読み取っています…');
        await img.decode();
        const r = await analyze(img);
        if (!r.ok) throw new Error(r.reason);
        await sendEmbedding(r.embedding);
        setStatus('照合が完了しました。');
    } catch (e) {
        setStatus('エラー：' + e.message);
    } finally {
        busy = false;
        URL.revokeObjectURL(img.src);
        fileInput.value = '';
    }
}

async function main() {
    try {
        await init(setStatus);
    } catch (e) {
        setStatus('初期化に失敗しました：' + e.message);
        console.error(e);
        return;
    }

    let cameraReady = false;
    try {
        await startCamera(video, setStatus);
        attachOverlay(video, overlayCanvas);
        const ms = await warmupDetection(video, setStatus);
        console.log(`初回の検出にかかった時間: ${ms}ms`);
        cameraReady = true;
    } catch (e) {
        setStatus('カメラの準備に失敗しました（画像ファイルでの照合は使えます）：' + e.message);
        console.error(e);
    }

    fileInput.disabled = false;
    if (cameraReady) {
        startPreview(video);
        cameraBtn.disabled = false;
        autoCheck.disabled = false;
        setStatus('準備ができました。枠が緑色のときに「カメラで照合」を押すか、「連続して照合する」にチェックを入れてください。');
    }
}

cameraBtn.addEventListener('click', matchOnce);
autoCheck.addEventListener('change', () => { if (autoCheck.checked) autoLoop(); });
fileInput.addEventListener('change', matchFromFile);
main();