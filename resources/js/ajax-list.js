// リロードせずに一覧を入れ替える（並び替え・ページ送り）
//
// 使い方（ほかの一覧ページでも同じように使える）
//   1. 入れ替えたい部分を <div data-ajax-list> で囲む（1ページに1つ）
//   2. その中のリンクに data-ajax を付ける
//   3. コントローラーは $request->ajax() のとき、囲んだ部分のビューだけを返す
//   4. URL の値と合わせておきたい hidden の input には data-sync-query を付ける（検索フォームの並び順など）
//
// JavaScript が動かないときは、ふつうのリンクとしてページごと開く（どちらでも同じ結果になる）

const container = document.querySelector('[data-ajax-list]');
let current = null; // いま読み込み中の通信（新しく押されたら古いほうを止める）

async function load(url, { push = true } = {}) {
    current?.abort(); // 素早く何回も押したとき、遅れて届いた古い結果で上書きされないようにする
    const request = new AbortController();
    current = request;
    container.setAttribute('aria-busy', 'true');

    try {
        const res = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }, // これで Laravel の $request->ajax() が true になる
            signal: request.signal,
        });
        if (!res.ok) throw new Error(`通信エラー（${res.status}）`);
        const html = await res.text();

        const focusedId = document.activeElement?.id; // 押したリンクに、入れ替えたあともフォーカスを戻す
        container.innerHTML = html;
        if (focusedId) document.getElementById(focusedId)?.focus();

        // URL も変えておく（再読み込み・ブラウザの「戻る」・URLの共有でも同じ並び順になる）
        if (push) history.pushState(null, '', url);
        syncInputs(url);
    } catch (e) {
        if (e.name === 'AbortError') return; // 新しく押されたので止めただけ
        console.error(e);
        location.href = url; // うまくいかなかったら、ふつうにページを開く
    } finally {
        if (current === request) container.removeAttribute('aria-busy');
    }
}

// data-sync-query の input を、URL の値に合わせる
function syncInputs(url) {
    const params = new URL(url, location.href).searchParams;
    document.querySelectorAll('input[data-sync-query]').forEach((input) => {
        input.value = params.get(input.name) ?? '';
    });
}

if (container) {
    container.addEventListener('click', (e) => {
        const link = e.target.closest('a[data-ajax]');
        if (!link) return;
        // Ctrl・⌘ を押しながらのクリック（新しいタブで開く）などは、ブラウザに任せる
        if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        load(link.href);
    });

    // ブラウザの「戻る」「進む」：その URL の一覧を読み込み直す（履歴は増やさない）
    window.addEventListener('popstate', () => load(location.href, { push: false }));
}