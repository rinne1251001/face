// 入校証の照合・登録（管理者の画面）
// 入校証がタッチされたら持ち主の生徒を表示し、まだ誰のものでもない入校証なら、生徒を選んで登録する
import { CardWatcher } from './felica-reader.js';

window.faceAppStarted = true; // この画面の JavaScript が動き出した合図（partials.script-check が見る）

const SEARCH_DELAY_MS = 250; // 入力が止まってから検索するまでの時間

const $ = (id) => document.getElementById(id);
const panel = $('card-match');
const urls = {
    touch: panel.dataset.touchUrl,
    search: panel.dataset.searchUrl,
    link: panel.dataset.linkUrl,
};
const ui = {
    start: $('btn-start'),
    status: $('status'),
    progress: $('connect-progress'),
    readyNote: $('ready-note'),
    linkIdm: $('link-idm'),
    form: $('link-form'),
    search: $('student-search'),
    results: $('student-results'),
    selected: $('selected-student'),
    link: $('btn-link'),
    cancelLink: $('btn-cancel-link'),
    linkNote: $('link-note'),
    resultName: $('result-name'),
    resultNumber: $('result-number'),
    resultClass: $('result-class'),
    resultIdm: $('result-idm'),
    resultNote: $('result-note'),
    log: $('log'),
};

// 画面の切り替え（見た目は CSS が担当）
const showScreen = (name) => { panel.dataset.screen = name; };
const currentScreen = () => panel.dataset.screen;

// 例：山田 太郎（1250001・1T1）
const studentLabel = (s) => `${s.name}（${[s.student_number, s.class_name].filter(Boolean).join('・')}）`;

function addLog(text, type = 'info') {
    const li = document.createElement('li');
    if (type === 'error') li.className = 'error';
    const time = document.createElement('time');
    time.textContent = new Date().toLocaleTimeString();
    li.append(time, text); // 文字列は textContent として入るので HTML として解釈されない
    ui.log.prepend(li);
    while (ui.log.children.length > 100) ui.log.lastElementChild.remove();
}

// =====================================================================
// サーバーとのやり取り（Fetch API）
// =====================================================================
async function postJson(url, body, signal) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json', // バリデーションエラーを 422 の JSON で受け取る
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(body),
        signal,
    });
    const json = await res.json().catch(() => ({}));
    if (res.status === 401 || res.status === 419) {
        throw new Error('ログインの有効期限が切れました。ページを再読み込みして、もう一度ログインしてください。');
    }
    if (!res.ok) throw new Error(json.message ?? `通信エラー（${res.status}）`);
    return json;
}

// =====================================================================
// 照合（タッチされたとき）
// =====================================================================
let pendingIdm = null; // 生徒を登録しようとしている入校証の IDm
let selected = null;   // その入校証に登録しようとして選んだ生徒

async function handleTouch(idm) {
    if (currentScreen() === 'link') {
        // 生徒を登録している途中は、別の入校証がタッチされても画面を切り替えない
        addLog(`登録の途中のため、タッチを無視しました（IDm ${idm}）。`);
        return;
    }

    ui.readyNote.textContent = '';
    addLog(`タッチ：IDm ${idm}`);
    try {
        const res = await postJson(urls.touch, { idm });
        if (res.status === 'linked') {
            showStudent(res.student, res.idm);
            addLog(`${studentLabel(res.student)}さんの入校証です。`);
        } else if (res.status === 'unlinked') {
            addLog(res.message);
            startLink(res.idm);
        } else {
            ui.readyNote.textContent = res.message;
            addLog(`${res.message}（IDm ${res.idm}）`, 'error');
            showScreen('ready');
        }
    } catch (e) {
        ui.readyNote.textContent = '照合できませんでした。もう一度タッチしてください。';
        addLog(`照合に失敗しました：${e.message}`, 'error');
        showScreen('ready');
    }
}

// 入校証の持ち主を表示する（次の入校証がタッチされるまで出したままにする）
function showStudent(student, idm, note = '') {
    ui.resultName.textContent = student.name;
    ui.resultNumber.textContent = student.student_number;
    ui.resultClass.textContent = student.class_name ?? '（クラス未定）';
    ui.resultIdm.textContent = idm;
    ui.resultNote.textContent = [
        note,
        student.is_active ? '' : '※ 停止中の生徒です（この入校証は出席受付では使えません）。',
        '続けて、別の入校証をタッチできます。',
    ].filter(Boolean).join(' ');
    showScreen('result');
}

// =====================================================================
// 生徒の検索と登録（まだ誰のものでもない入校証のとき）
// =====================================================================
let searchTimer = null;
let searchAbort = null;
let candidates = []; // 検索で見つかった生徒（候補の一覧と同じ順）
let linking = false;

function startLink(idm) {
    pendingIdm = idm;
    ui.linkIdm.textContent = idm;
    ui.linkNote.textContent = '';
    ui.search.value = '';
    ui.results.replaceChildren();
    selectStudent(null);
    showScreen('link');
    ui.search.focus();
}

function finishLink(message) {
    if (message) addLog(message);
    pendingIdm = null;
    selectStudent(null);
    showScreen('ready');
}

// 候補から生徒を選ぶ（null を渡すと、選んでいない状態に戻す）
function selectStudent(student) {
    selected = student;
    ui.selected.textContent = student ? `選んだ生徒：${studentLabel(student)}` : '';
    ui.link.disabled = student === null;
}

ui.search.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(searchStudents, SEARCH_DELAY_MS);
});

async function searchStudents() {
    const q = ui.search.value.trim();
    searchAbort?.abort(); // 前の検索の返事は、もう要らない
    if (q === '') {
        ui.results.replaceChildren();
        return;
    }
    searchAbort = new AbortController();
    try {
        const res = await postJson(urls.search, { q }, searchAbort.signal);
        renderResults(res.students);
    } catch (e) {
        if (e.name !== 'AbortError') addLog(`生徒の検索に失敗しました：${e.message}`, 'error');
    }
}

function renderResults(students) {
    candidates = students;
    if (students.length === 0) {
        const li = document.createElement('li');
        li.className = 'empty';
        li.textContent = '該当する生徒がいません';
        ui.results.replaceChildren(li);
        return;
    }
    ui.results.replaceChildren(...students.map((s, i) => {
        const li = document.createElement('li');
        li.tabIndex = 0; // キーボード（↓↑・Enter）でも選べるように
        li.setAttribute('role', 'option');
        li.dataset.index = i; // candidates の何番目か
        // 名前・学籍番号・クラス・（すでに入校証を持っているか）
        const texts = [s.name, s.student_number, s.class_name ?? 'クラス未定'];
        if (s.has_card) texts.push('※入校証あり');
        li.append(...texts.map((text) => {
            const span = document.createElement('span');
            span.textContent = text;
            return span;
        }));
        return li;
    }));
}

// 候補を押す（または Enter）と、その生徒を選ぶ。登録するのは「この生徒を登録する」を押してから
function pick(li) {
    const student = candidates[Number(li.dataset.index)];
    selectStudent(student);
    ui.search.value = student.name;
    ui.results.replaceChildren();
    ui.link.focus();
}

ui.results.addEventListener('click', (e) => {
    const li = e.target.closest('li[role="option"]');
    if (li) pick(li);
});
ui.results.addEventListener('keydown', (e) => {
    const li = e.target.closest('li[role="option"]');
    if (!li) return;
    if (e.key === 'Enter') { e.preventDefault(); pick(li); }
    if (e.key === 'ArrowDown') { e.preventDefault(); li.nextElementSibling?.focus(); }
    if (e.key === 'ArrowUp') { e.preventDefault(); (li.previousElementSibling ?? ui.search).focus(); }
});
ui.search.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        ui.results.querySelector('li[role="option"]')?.focus();
    }
});

ui.form.addEventListener('submit', async (e) => {
    e.preventDefault(); // ページは送信しない（Fetch で送る）
    if (linking || !pendingIdm || !selected) return;
    linking = true;
    ui.link.disabled = true;
    ui.linkNote.textContent = '';
    try {
        const res = await postJson(urls.link, { idm: pendingIdm, student_id: selected.id });
        addLog(res.message);
        pendingIdm = null;
        selectStudent(null);
        showStudent(res.student, res.idm, '登録しました。');
    } catch (e2) {
        ui.linkNote.textContent = e2.message;
        ui.link.disabled = selected === null;
        addLog(`生徒の登録に失敗しました：${e2.message}`, 'error');
    } finally {
        linking = false;
    }
});

ui.cancelLink.addEventListener('click', () => finishLink(`生徒は登録しませんでした（IDm ${pendingIdm}）。`));

// =====================================================================
// リーダーの接続
// =====================================================================
const watcher = new CardWatcher({
    onTouch: handleTouch,
    onState: (state, message) => {
        if (state === 'connecting') {
            ui.progress.textContent = message;
            showScreen('connecting');
        } else if (state === 'ready') {
            ui.readyNote.textContent = '';
            addLog(message);
            showScreen('ready');
        } else {
            // 未接続（接続に失敗した・リーダーが抜かれた・読み取りが止まった）
            pendingIdm = null;
            ui.status.textContent = message;
            if (message) addLog(message, 'error');
            showScreen('start');
        }
    },
});

const reason = CardWatcher.unsupportedReason();
if (reason) {
    // このブラウザ・このアドレスではリーダーを使えない
    ui.status.textContent = reason;
    addLog(reason, 'error');
} else {
    ui.status.textContent = '';
    ui.start.disabled = false;
    ui.start.addEventListener('click', () => watcher.connect()); // 機器を選ぶ画面は、ボタン操作からしか出せない
    watcher.connect({ ask: false });                             // 前に許可したリーダーが挿さっていれば、すぐ始める
}