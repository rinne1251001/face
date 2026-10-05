// 顔認証での出席受付（教師の画面）
// カメラに顔が写るたびに自動で照合し、受講者なら出席を記録する
import {
    init, startCamera, warmupDetection, attachOverlay, showLabel,
    analyze, averageEmbedding, sleep, esc, MODEL_VERSION,
} from './face-engine.js';

window.faceAppStarted = true;

const root = document.getElementById('face-checkin');
const statusEl = document.getElementById('status');
const video = document.getElementById('video');
const overlayCanvas = document.getElementById('overlay');
const toggleBtn = document.getElementById('toggleBtn');
const logEl = document.getElementById('log');
const recordedCountEl = document.getElementById('recordedCount');

const FRAMES_PER_MATCH = 3;  // 平均に使うフレーム数
const RESULT_HOLD = 2500;    // 結果を枠に表示しておく時間（ミリ秒）。この間は次の照合をしない
const SAME_PERSON = 10000;   // 同じ人の「記録済み」を記録欄に何度も出さないための時間（ミリ秒）

// サーバーの結果ごとの、枠に出す文字と色
const RESULT_VIEW = {
    recorded: { ok: true, label: (s) => `${s.name} さん 出席` },
    already: { ok: true, label: (s) => `${s.name} さん 記録済み` },
    not_enrolled: { ok: false, label: (s) => `${s.name} さん 受講者外` },
    unknown: { ok: false, label: () => '該当者なし' },
};

const setStatus = (text) => { statusEl.textContent = text; };

let running = true;                   // 受付中か（一時停止中は照合しない）
let lastLogged = { id: null, at: 0 }; // 最後に記録欄に出した生徒

// 検出して枠線を更新する（エラーでも止まらないようにする）
async function safeAnalyze() {
    try {
        return await analyze(video);
    } catch (e) {
        console.warn(e);
        return { ok: false, reason: '検出でエラーが起きました。' };
    }
}

// 指定した時間、枠線の更新だけを続ける（照合結果の表示中・一時停止中に使う）
async function watch(ms) {
    const until = Date.now() + ms;
    while (Date.now() < until) {
        await safeAnalyze();
        await sleep(100);
    }
}

// 条件の良いフレームを集める
async function collectFrames() {
    const collected = [];
    for (let i = 0; i < 10 && running && collected.length < FRAMES_PER_MATCH; i++) {
        const r = await safeAnalyze();
        if (r.ok) {
            collected.push(r.embedding);
        } else {
            setStatus(r.reason || 'カメラの正面に立ってください。');
            await sleep(150);
        }
    }
    return collected;
}

// サーバーに特徴量を送る（ログイン切れを見分けられるように、HTTPステータスもエラーに付ける）
async function postCheckin(embedding) {
    const res = await fetch(root.dataset.checkinUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ model_version: MODEL_VERSION, embedding }),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const error = new Error(data.message ?? `通信エラー（${res.status}）`);
        error.status = res.status;
        throw error;
    }
    return data;
}

// 受付記録の表に1行追加する（新しいものを上に）
function addLog(r) {
    const s = r.student;
    if (!s) return;
    // 同じ人が立ち続けているときに「記録済み」が何行も並ばないようにする
    if (r.result === 'already' && lastLogged.id === s.id && Date.now() - lastLogged.at < SAME_PERSON) return;
    lastLogged = { id: s.id, at: Date.now() };

    const text = {
        recorded: '出席を記録しました',
        already: `記録済み（${r.status_label}）`,
        not_enrolled: 'この授業の受講者ではありません',
    }[r.result] ?? r.message;
    const cls = r.result === 'not_enrolled' ? 'error' : 'ok';

    logEl.insertAdjacentHTML('afterbegin', `
        <tr><td>${new Date().toLocaleTimeString()}</td><td>${esc(s.name)}</td>
        <td>${esc(s.class_name)}</td><td class="${cls}">${esc(text)}</td></tr>`);
}

async function loop() {
    for (;;) {
        if (!running) {
            await watch(300);
            continue;
        }

        const collected = await collectFrames();
        if (collected.length < FRAMES_PER_MATCH) continue;

        try {
            setStatus('照合しています…');
            const r = await postCheckin(averageEmbedding(collected));
            const view = RESULT_VIEW[r.result] ?? RESULT_VIEW.unknown;
            // 照合した顔の枠に結果を表示する（fillText は HTML ではないのでエスケープ不要）
            showLabel(view.label(r.student ?? {}), view.ok, RESULT_HOLD);
            setStatus(r.message);
            addLog(r);
            if (r.recorded_count !== undefined) recordedCountEl.textContent = r.recorded_count;
        } catch (e) {
            if (e.status === 401 || e.status === 419) {
                // 401：ログインしていない／419：ページを開いてから時間がたちすぎた
                setStatus('ログインの有効期限が切れました。ページを再読み込みして、もう一度ログインしてください。');
                running = false;
                toggleBtn.disabled = true;
                return;
            }
            setStatus('エラー：' + e.message);
        }
        await watch(RESULT_HOLD);
    }
}

function toggle() {
    running = !running;
    toggleBtn.textContent = running ? '受付を一時停止' : '受付を再開';
    setStatus(running ? '受付を再開しました。カメラの正面に立ってください。' : '受付を一時停止しています。');
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
    toggleBtn.disabled = false;
    setStatus('受付中です。1人ずつカメラの正面に立ってください。');
    loop();
}

toggleBtn.addEventListener('click', toggle);
main();
