// 出席受付（教師の画面）
// 流れ：入校証をリーダーにかざす → カメラの上に持ち主の名前を出す → 顔認証で本人か確かめる → 出席を記録する
// 「入校証なしで受付」に切り替えると、これまでどおり顔認証だけで受け付ける（カメラに顔が写るたびに自動で照合する）
import {
    init, startCamera, warmupDetection, attachOverlay, showLabel,
    analyze, averageEmbedding, sleep, esc, MODEL_VERSION,
} from './face-engine.js';
import { CardWatcher } from './felica-reader.js';

window.faceAppStarted = true;

const root = document.getElementById('face-checkin');
const statusEl = document.getElementById('status');
const readerStatusEl = document.getElementById('readerStatus');
const video = document.getElementById('video');
const overlayCanvas = document.getElementById('overlay');
const toggleBtn = document.getElementById('toggleBtn');
const readerBtn = document.getElementById('readerBtn');
const modeBtn = document.getElementById('modeBtn');
const logEl = document.getElementById('log');
const recordedCountEl = document.getElementById('recordedCount');

const FRAMES_PER_MATCH = 3;  // 平均に使うフレーム数
const RESULT_HOLD = 2500;    // 結果を表示しておく時間（ミリ秒）。この間は次の照合をしない
const SAME_PERSON = 10000;   // 同じ人の「記録済み」を記録欄に何度も出さないための時間（ミリ秒）
const FACE_WAIT = 15000;     // 入校証をかざしてから、顔認証が終わるのを待つ時間（ミリ秒）
const FACE_TRIES = 3;        // 顔が一致しなかったときに、やり直せる回数

// 顔認証だけのとき：サーバーの結果ごとの、顔の枠に出す文字と色（r はサーバーの返事。status_label は「出席」か「遅刻」）
const RESULT_VIEW = {
    recorded: { ok: true, label: (s, r) => `${s.name} さん ${r.status_label}` },
    already: { ok: true, label: (s) => `${s.name} さん 記録済み` },
    not_enrolled: { ok: false, label: (s) => `${s.name} さん 受講者外` },
    closed: { ok: false, label: () => '受付時間外' },
    unknown: { ok: false, label: () => '該当者なし' },
};

// 入校証を使うとき：サーバーの結果ごとの、カメラの上（すりガラスの案内）に出す文字
const san = (r) => `${r.student.name} さん`;
const RESULT_NOTICE = {
    recorded: (r) => ({ title: san(r), sub: `${r.status_label}を記録しました`, tone: 'ok' }),
    already: (r) => ({ title: san(r), sub: `記録済みです（${r.status_label}）`, tone: 'ok' }),
    not_enrolled: (r) => ({ title: san(r), sub: 'この時限の受講者ではありません', tone: 'ng' }),
    mismatch: (r) => ({ title: san(r), sub: '顔が一致しませんでした', tone: 'ng' }),
    unknown_card: () => ({ title: '登録されていない入校証です', sub: '管理者に登録を依頼してください', tone: 'ng' }),
    card_inactive: () => ({ title: 'この入校証は使えません', sub: '先生に確認してください', tone: 'ng' }),
    closed: () => ({ title: '受付時間外です' }),
};

const setStatus = (text) => { statusEl.textContent = text; };

// カメラの上に重ねる、すりガラスの案内（見た目は checkin.blade.php の .camera-notice）
const noticeEl = document.getElementById('notice');
const noticeTitleEl = document.getElementById('noticeTitle');
const noticeSubEl = document.getElementById('noticeSub');

// 案内を出す（null を渡すと消す）
//   title … 大きい文字（例：入校証をかざしてください／山田 太郎 さん）
//   sub   … その下の小さい文字（例：カメラの正面に立ってください）。なくてもよい
//   place … 'top' にすると、上に細く出す（顔が見えるように、顔認証の間に使う）。指定しなければカメラ全体にかぶせる
//   tone  … 結果が 'ok'（うまくいった）か 'ng'（だめだった）か。data-tone に入れるだけ（CSS で色を変えたいときに使える）
function showNotice(notice) {
    noticeEl.hidden = notice === null;
    if (notice === null) return;
    noticeTitleEl.textContent = notice.title; // textContent なので、名前をそのまま入れてよい
    noticeSubEl.textContent = notice.sub ?? '';
    noticeEl.classList.toggle('is-top', notice.place === 'top');
    noticeEl.dataset.tone = notice.tone ?? '';
}

// このブラウザ・このアドレスで入校証リーダーを使えないときは、その理由（使えるなら null）
const readerUnsupported = CardWatcher.unsupportedReason();

let running = true;                   // 受付中か（一時停止中は照合しない）
let useCard = true;                   // 入校証を使うか（false なら顔認証だけ）
let touchedIdm = null;                // かざされた入校証の IDm（まだ処理していないもの）
let currentIdm = null;                // いま処理している入校証の IDm
let lastLogged = { id: null, at: 0 }; // 最後に記録欄に出した生徒

// =====================================================================
// 入校証リーダー
// =====================================================================
const watcher = new CardWatcher({
    // 入校証がかざされた（置きっぱなしの間は1回だけ呼ばれる）。ここでは覚えるだけで、処理は loop() がする
    onTouch: (idm) => {
        if (running && useCard && idm !== currentIdm) touchedIdm = idm;
    },
    onState: (state, message) => {
        const label = { idle: '未接続', connecting: '接続中', ready: '接続済み' }[state];
        readerStatusEl.textContent = `入校証リーダー：${label}${state !== 'ready' && message ? `（${message}）` : ''}`;
        readerBtn.disabled = state !== 'idle';
        readerBtn.hidden = state === 'ready'; // つながっている間は、接続のボタンを出さない
    },
});

// =====================================================================
// 顔の検出
// =====================================================================

// 検出して枠線を更新する（エラーでも止まらないようにする）
async function safeAnalyze() {
    try {
        return await analyze(video);
    } catch (e) {
        console.warn(e);
        return { ok: false, reason: '検出でエラーが起きました。' };
    }
}

// 指定した時間、枠線の更新だけを続ける（照合結果の表示中に使う）
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

// =====================================================================
// サーバーとのやり取り
// =====================================================================

// サーバーに送る（ログイン切れを見分けられるように、HTTPステータスもエラーに付ける）
async function post(url, body) {
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
    if (!res.ok) {
        const error = new Error(data.message ?? `通信エラー（${res.status}）`);
        error.status = res.status;
        throw error;
    }
    return data;
}

// 入校証の持ち主を調べる
const postCard = (idm) => post(root.dataset.cardUrl, { idm });

// 顔の特徴量を送って、出席を記録する（入校証をかざしたあとなら、その IDm も一緒に送る）
const postCheckin = (embedding, idm = null) => post(root.dataset.checkinUrl, {
    model_version: MODEL_VERSION,
    embedding,
    ...(idm ? { idm } : {}),
});

// 受付記録の表に1行追加する（新しいものを上に）
function addLog(r) {
    const s = r.student;
    if (!s) return;
    // 同じ人が立ち続けているときに「記録済み」が何行も並ばないようにする
    if (r.result === 'already' && lastLogged.id === s.id && Date.now() - lastLogged.at < SAME_PERSON) return;
    lastLogged = { id: s.id, at: Date.now() };

    const text = {
        recorded: `${r.status_label}を記録しました`,
        already: `記録済み（${r.status_label}）`,
        not_enrolled: 'この授業の受講者ではありません',
        mismatch: '入校証の持ち主の顔と一致しませんでした',
    }[r.result] ?? r.message;
    const cls = ['recorded', 'already'].includes(r.result) ? 'ok' : 'error';

    logEl.insertAdjacentHTML('afterbegin', `
        <tr><td>${new Date().toLocaleTimeString()}</td><td>${esc(s.name)}</td>
        <td>${esc(s.class_name)}</td><td class="${cls}">${esc(text)}</td></tr>`);
}

// サーバーの返事を、状態の行・受付記録・記録済みの人数に反映する
function report(r) {
    setStatus(r.message);
    addLog(r);
    if (r.recorded_count !== undefined) recordedCountEl.textContent = r.recorded_count;
}

// =====================================================================
// 入校証 → 顔認証 の受付（1人分）
// =====================================================================

// 入校証がかざされるのを待つ（一時停止・顔認証のみへの切り替え・リーダーの切断で待つのをやめたときは null）
async function waitTouch() {
    while (running && useCard && watcher.connected) {
        if (touchedIdm) {
            const idm = touchedIdm;
            touchedIdm = null;
            return idm;
        }
        await sleep(100);
    }
    return null;
}

// 結果をしばらく表示しておく（次の入校証がかざされたら、待たずに次へ進む）
async function hold(ms) {
    const until = Date.now() + ms;
    while (Date.now() < until && !touchedIdm) await sleep(100);
}

// 入校証の持ち主（student）の顔かを確かめて、出席を記録する
// 戻り値はサーバーの返事。時間内に顔が写らなかったときは null
async function verifyFace(student, idm) {
    const name = `${student.name} さん`;
    const until = Date.now() + FACE_WAIT;
    let last = null;

    for (let tries = 0; tries < FACE_TRIES && Date.now() < until && running && useCard;) {
        // 案内を上に細く出す（顔が見えるように）
        showNotice({ title: name, sub: 'カメラの正面に立ってください', place: 'top' });
        const collected = await collectFrames();
        if (collected.length < FRAMES_PER_MATCH) continue; // まだ顔がうまく写っていない

        tries++;
        setStatus('照合しています…');
        last = await postCheckin(averageEmbedding(collected), idm);
        if (last.result !== 'mismatch') return last;

        // 一致しなかったら、少し待ってからやり直す
        setStatus(last.message);
        showNotice({ title: name, sub: '顔が一致しません。正面を向いてください', tone: 'ng', place: 'top' });
        await watch(1500);
    }
    return last;
}

// リーダーがつながっていないときに、カメラの上に出す案内
function readerNotice() {
    if (readerUnsupported) {
        return { title: 'リーダーを使えません', sub: '「入校証なしで受付」に切り替えてください', tone: 'ng' };
    }
    if (watcher.state === 'connecting') return { title: 'リーダーに接続しています…' };
    return { title: 'リーダーを接続してください', sub: '下の「リーダーに接続」を押してください' };
}

async function cardRound() {
    // 1. 入校証を待つ（待っている間は顔の検出をしない）
    if (!watcher.connected) {
        showNotice(readerNotice());
        setStatus('入校証リーダーがつながっていません。');
        await sleep(300);
        return;
    }
    showNotice({ title: '入校証をかざしてください' });
    setStatus('受付中です。入校証をリーダーにかざしてください。');
    const idm = await waitTouch();
    if (!idm) return;

    currentIdm = idm;
    try {
        // 2. 入校証の持ち主を調べる
        setStatus('入校証を確認しています…');
        let r = await postCard(idm);

        // 3. 持ち主が分かったら、名前を出して顔認証をする
        if (r.result === 'ready') {
            const student = r.student;
            setStatus(r.message);
            r = await verifyFace(student, idm);
            if (r === null) {
                showNotice({ title: `${student.name} さん`, sub: '顔を確認できませんでした。もう一度かざしてください', tone: 'ng' });
                setStatus(`${student.name} さんの顔を確認できませんでした。もう一度、入校証をかざしてください。`);
                await hold(RESULT_HOLD);
                return;
            }
        }

        // 4. 結果を表示する
        const notice = RESULT_NOTICE[r.result];
        showNotice(notice ? notice(r) : { title: r.message, tone: 'ng' });
        report(r);
        await hold(RESULT_HOLD);
    } finally {
        currentIdm = null;
    }
}

// =====================================================================
// 顔認証だけの受付（1回分）
// =====================================================================
async function faceRound() {
    showNotice(null); // 案内は出さない（顔の枠だけ）
    const collected = await collectFrames();
    if (collected.length < FRAMES_PER_MATCH) return;

    setStatus('照合しています…');
    const r = await postCheckin(averageEmbedding(collected));
    const view = RESULT_VIEW[r.result] ?? RESULT_VIEW.unknown;
    // 照合した顔の枠に結果を表示する（fillText は HTML ではないのでエスケープ不要）
    showLabel(view.label(r.student ?? {}, r), view.ok, RESULT_HOLD);
    report(r);
    await watch(RESULT_HOLD);
}

async function loop() {
    for (; ;) {
        if (!running) {
            showNotice({ title: '受付を一時停止しています' });
            await sleep(300);
            continue;
        }

        try {
            await (useCard ? cardRound() : faceRound());
        } catch (e) {
            if (e.status === 401 || e.status === 419) {
                // 401：ログインしていない／419：ページを開いてから時間がたちすぎた
                setStatus('ログインの有効期限が切れました。ページを再読み込みして、もう一度ログインしてください。');
                showNotice({ title: 'ページを再読み込みしてください', sub: 'ログインの有効期限が切れました', tone: 'ng' });
                running = false;
                toggleBtn.disabled = true;
                modeBtn.disabled = true;
                return;
            }
            setStatus('エラー：' + e.message);
            if (useCard) showNotice({ title: 'エラーが起きました', sub: 'もう一度かざしてください', tone: 'ng' });
            await watch(RESULT_HOLD);
        }
    }
}

// =====================================================================
// ボタン
// =====================================================================

// 受付の一時停止・再開
function toggle() {
    running = !running;
    touchedIdm = null;
    toggleBtn.textContent = running ? '受付を一時停止' : '受付を再開';
    setStatus(running ? '受付を再開しました。' : '受付を一時停止しています。');
}

// 入校証を使う受付 ⇔ 顔認証だけの受付
function switchMode() {
    useCard = !useCard;
    touchedIdm = null;
    modeBtn.textContent = useCard ? '入校証なしで受付（顔認証のみ）' : '入校証を使って受付';
    if (running && !useCard) setStatus('顔認証のみで受付中です。1人ずつカメラの正面に立ってください。');
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

    // 入校証リーダー：前に許可したリーダーが挿さっていれば、すぐにつなぐ
    if (readerUnsupported) {
        readerStatusEl.textContent = `入校証リーダー：使えません（${readerUnsupported}）`;
    } else {
        readerBtn.disabled = false;
        readerBtn.addEventListener('click', () => watcher.connect()); // 機器を選ぶ画面は、ボタン操作からしか出せない
        watcher.connect({ ask: false });
    }

    toggleBtn.disabled = false;
    modeBtn.disabled = false;
    loop();
}

toggleBtn.addEventListener('click', toggle);
modeBtn.addEventListener('click', switchMode);
main();