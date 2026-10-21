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
const fileInput = document.getElementById('fileInput');
const resultEl = document.getElementById('result');
const logsBody = document.getElementById('logs');

const FRAMES_PER_MATCH = 3; // 平均に使うフレーム数
const LABEL_TIME = 3000;    // 顔の枠に結果を表示しておく時間（ミリ秒）

const setStatus = (text) => { statusEl.textContent = text; };
const fmt = (v) => (v === null || v === undefined) ? '-' : Number(v).toFixed(3);

let busy = false; // 照合の二重実行を防ぐ

// 照合ログの表を読み込み直す（表の中身の HTML をサーバーからもらう）
async function loadLogs() {
    try {
        const res = await fetch(root.dataset.logsUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) throw new Error();
        logsBody.innerHTML = await res.text();
    } catch {
        logsBody.innerHTML = '<tr><td colspan="5">ログを読み込めませんでした。ページを再読み込みしてください。</td></tr>';
    }
}

function renderResult(r) {
    const rows = r.candidates.map((c) => `
        <tr><td>${esc(c.student?.name)}</td><td>${esc(c.student?.student_number)}</td>
        <td>${esc(c.student?.class_name)}</td><td>${fmt(c.similarity)}</td></tr>`).join('');
    resultEl.innerHTML = `
        <h2 class="${r.accepted ? 'ok' : 'error'}">
            ${r.accepted ? `${esc(r.student.name)} さん（${esc(r.student.class_name)}）` : '該当者なし（または判定保留）'}
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
    loadLogs(); // 照合したのでログの表を新しくする
    if (fromCamera) {
        // 照合した顔の枠に結果を表示する（fillText は HTML ではないのでエスケープ不要）
        showLabel(r.accepted ? `${r.student.name} さん` : '該当者なし', r.accepted, LABEL_TIME);
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
        cameraBtn.disabled = false;
    }
}

// 画像ファイルで照合する
async function matchFromFile() {
    const file = fileInput.files[0];
    if (!file) return;
    if (busy) {
        setStatus('照合中です。終わってから画像を選んでください。');
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
    loadLogs(); // ログの表はカメラの準備を待たずに出す

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
        setStatus('準備ができました。枠が緑色のときに「カメラで照合」を押してください。');
    }
}

cameraBtn.addEventListener('click', matchOnce);
fileInput.addEventListener('change', matchFromFile);
main();