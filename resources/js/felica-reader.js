/*
 * 入校証（FeliCa）を、カードリーダー RC-S300 から WebUSB で読むための共通処理
 * （入校証には IDm しか入っていないので、IDm を読む Polling だけを使う）
 *
 *  - RCS300Reader … リーダーとの通信（接続・IDm の読み取り）
 *  - CardWatcher  … 画面から使う部分（接続して、入校証がかざされるのを待つ）
 *
 *  ※ RC-S300 との通信: CCID PC_to_RDR_Escape(0x6B) / RDR_to_PC_Escape(0x83)
 *  ※ RC-S300 のコマンド体系は公開仕様ではなく、コミュニティの解析結果に基づく
 *  ※ WebUSB は Chrome か Edge の、https:// か http://127.0.0.1（localhost）でだけ使える
 */

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const toHex = (bytes, sep = '') =>
    Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join(sep).toUpperCase();
const hex2 = (n) => n.toString(16).toUpperCase().padStart(2, '0');
const hex4 = (n) => n.toString(16).toUpperCase().padStart(4, '0');

// WebUSB の transferIn / transferOut にはタイムアウトが無く、リーダーが応答しないと永遠に待ち続ける。
// 一定時間で打ち切ってエラーにするためのヘルパー。
const TRANSFER_TIMEOUT_MS = 2000; // 1 コマンドの送受信を待つ上限
const SETUP_TIMEOUT_MS = 5000;    // open / claimInterface などを待つ上限

class ReaderTimeoutError extends Error {
    constructor(step) {
        super(`リーダーが応答しません（${step}）`);
        this.name = 'ReaderTimeoutError';
        this.step = step;
    }
}

function withTimeout(promise, ms, step) {
    let timer;
    return Promise.race([
        promise,
        new Promise((_, reject) => { timer = setTimeout(() => reject(new ReaderTimeoutError(step)), ms); }),
    ]).finally(() => clearTimeout(timer));
}

// =====================================================================
// RC-S300 ドライバ
// =====================================================================
export class RCS300Reader {
    static FILTERS = [
        { vendorId: 0x054c, productId: 0x0dc8 }, // RC-S300/S（業務用）
        { vendorId: 0x054c, productId: 0x0dc9 }, // RC-S300/P（個人用）
    ];

    static CMD = {
        START_TRANSPARENT: [0xff, 0x50, 0x00, 0x00, 0x02, 0x81, 0x00, 0x00],
        END_TRANSPARENT: [0xff, 0x50, 0x00, 0x00, 0x02, 0x82, 0x00, 0x00],
        RF_OFF: [0xff, 0x50, 0x00, 0x00, 0x02, 0x83, 0x00, 0x00],
        RF_ON: [0xff, 0x50, 0x00, 0x00, 0x02, 0x84, 0x00, 0x00],
    };

    // Chrome が WebUSB からの claim を禁止しているクラス
    static PROTECTED_CLASSES = new Set([0x01, 0x03, 0x08, 0x0b, 0x0e, 0x10, 0xe0]);

    constructor(device) {
        this.device = device;
        this.seq = 0;
        this.interfaceNumber = null;
        this.epIn = null;
        this.epOut = null;
        this.queue = Promise.resolve();
        this.closed = false;
        this.broken = false; // 応答が途絶えた（タイムアウトした）ら true。以後の通信はしない
    }

    // ---------------------------------------------------------------
    // 接続・切断
    // ---------------------------------------------------------------

    // onStep(message) … 進行状況（どこで止まったか分かるように、各手順の前に呼ばれる）
    async open({ onStep = () => { } } = {}) {
        const d = this.device;
        const step = (label, fn) => {
            onStep(label);
            return withTimeout(fn(), SETUP_TIMEOUT_MS, label);
        };

        if (!d.opened) await step('USB デバイスを開いています', () => d.open());
        if (d.configuration === null) await step('USB 構成を選択しています', () => d.selectConfiguration(1));

        console.info('[RC-S300] USB 構成:', RCS300Reader.describeDevice(d));
        const target = this.#findBulkInterface();
        if (!target) throw new Error('RC-S300 の通信用インターフェイスが見つかりません。');

        await step(`インターフェイス ${target.interfaceNumber} を確保しています`,
            () => d.claimInterface(target.interfaceNumber));
        this.interfaceNumber = target.interfaceNumber;
        this.epIn = target.epIn.endpointNumber;
        this.epOut = target.epOut.endpointNumber;

        // 前回の接続が途中で切れた（ページ再読み込み・タブを閉じた等）とき、
        // ホストとリーダーで「データトグル」がずれて最初のコマンドが黙って捨てられることがある。
        // ClearFeature(ENDPOINT_HALT) で両方向をリセットしてそろえる。
        onStep('エンドポイントをリセットしています');
        for (const [dir, ep] of [['in', this.epIn], ['out', this.epOut]]) {
            try {
                await withTimeout(d.clearHalt(dir, ep), TRANSFER_TIMEOUT_MS, `clearHalt(${dir})`);
            } catch (e) {
                console.warn('[RC-S300] clearHalt に失敗しました（続行します）', dir, e);
            }
        }

        const init = [
            ['END_TRANSPARENT', 'EndTransparentSession'],
            ['START_TRANSPARENT', 'StartTransparentSession'],
            ['RF_OFF', 'RF OFF'],
            ['RF_ON', 'RF ON'],
        ];
        for (const [key, label] of init) {
            onStep(`初期化コマンドを送信しています: ${label}`);
            await this.transceive(RCS300Reader.CMD[key], label);
            if (key === 'RF_OFF' || key === 'RF_ON') await sleep(50);
        }
    }

    async close() {
        if (this.closed) return;
        this.closed = true;
        if (!this.broken && this.epOut !== null) {
            try {
                await this.transceive(RCS300Reader.CMD.RF_OFF, 'RF OFF');
                await this.transceive(RCS300Reader.CMD.END_TRANSPARENT, 'EndTransparentSession');
            } catch (_) { /* 抜去済みなど */ }
        }
        if (!this.broken) {
            try {
                if (this.interfaceNumber !== null) {
                    await withTimeout(this.device.releaseInterface(this.interfaceNumber), SETUP_TIMEOUT_MS, 'releaseInterface');
                }
            } catch (_) { /* noop */ }
        }
        // close() は待ち続けている転送も中断させる（応答が途絶えたときはこれで解放する）
        try {
            if (this.device.opened) await withTimeout(this.device.close(), SETUP_TIMEOUT_MS, 'close');
        } catch (_) { /* noop */ }
    }

    // 診断用: USB インターフェイスとエンドポイントの一覧
    static describeDevice(device) {
        const cfg = device.configuration;
        if (!cfg) return '（構成が選択されていません）';
        return cfg.interfaces.map((iface) => {
            const alt = iface.alternate ?? iface.alternates?.[0];
            const eps = (alt?.endpoints ?? [])
                .map((e) => `${e.direction}${e.endpointNumber}/${e.type}/${e.packetSize}`)
                .join(' ');
            return `IF${iface.interfaceNumber} class=0x${hex2(alt?.interfaceClass ?? 0)} [${eps}]`;
        }).join(' | ');
    }

    // ---------------------------------------------------------------
    // FeliCa コマンド
    // ---------------------------------------------------------------

    // FeliCa コマンドを送り、カードの応答パケット（先頭が長さバイト）を返す。無応答なら null
    async felicaCommand(cmd, timeoutMs = 100) {
        const res = await this.transceive(this.#buildCommunicateThruEx(cmd, timeoutMs), `FeliCa コマンド 0x${hex2(cmd[0])}`);
        if (res.length < 2) return null;
        const sw = (res[res.length - 2] << 8) | res[res.length - 1];
        if (sw !== 0x9000) return null;

        const pkt = RCS300Reader.parseTlv(res, 0, res.length - 2).get(0x97);
        if (!pkt || pkt.length < 2 || pkt[0] !== pkt.length) return null;
        return pkt;
    }

    // Polling：かざされているカードの IDm を返す（カードがなければ null）
    // systemCode=0xFFFF は「どのカードでも」の意味
    async pollFelica(systemCode = 0xffff) {
        const pkt = await this.felicaCommand([
            0x00, (systemCode >> 8) & 0xff, systemCode & 0xff, 0x01, 0x00,
        ]);
        if (!pkt || pkt[1] !== 0x01 || pkt.length < 18) return null;

        return { idm: toHex(pkt.slice(2, 10)), pmm: toHex(pkt.slice(10, 18)) };
    }

    // ---------------------------------------------------------------
    // 低レベル
    // ---------------------------------------------------------------

    // RC-S300 にコマンドを送り、応答のデータ部（ヘッダー除去済み）を返す
    transceive(payload, label = 'コマンド') {
        const run = async () => {
            if (this.broken) {
                throw new Error('リーダーとの通信が途絶えています。USB を抜き差ししてから再接続してください。');
            }
            this.seq = (this.seq + 1) & 0xff;
            const seq = this.seq;
            const len = payload.length;

            const packet = new Uint8Array(10 + len);
            packet[0] = 0x6b;               // PC_to_RDR_Escape
            packet[1] = len & 0xff;         // データ長（リトルエンディアン 4 バイト）
            packet[2] = (len >> 8) & 0xff;
            packet[3] = (len >> 16) & 0xff;
            packet[4] = (len >> 24) & 0xff;
            packet[5] = 0x00;               // スロット番号
            packet[6] = seq;                // シーケンス番号（応答にそのまま返る）
            packet.set(payload, 10);

            try {
                const out = await withTimeout(
                    this.device.transferOut(this.epOut, packet), TRANSFER_TIMEOUT_MS, `${label} の送信`);
                if (out.status !== 'ok') throw new Error(`transferOut 失敗: ${out.status}`);

                // 前回の接続で読まれずに残った応答が先に届くことがあるので、
                // シーケンス番号が一致する応答が来るまで読み飛ばす
                for (let attempt = 0; attempt < 4; attempt++) {
                    // Polling の応答は 64 バイトで足りる
                    const result = await withTimeout(
                        this.device.transferIn(this.epIn, 64), TRANSFER_TIMEOUT_MS, `${label} の応答待ち`);
                    if (result.status !== 'ok' || !result.data) throw new Error(`transferIn 失敗: ${result.status}`);

                    const bytes = new Uint8Array(result.data.buffer, result.data.byteOffset, result.data.byteLength);
                    if (bytes.length >= 10 && bytes[0] === 0x83 && bytes[6] === seq) {
                        const dataLen = bytes[1] | (bytes[2] << 8) | (bytes[3] << 16) | (bytes[4] << 24);
                        return bytes.slice(10, 10 + dataLen);
                    }
                    console.warn('[RC-S300] 古い応答を読み飛ばしました:', toHex(bytes, ' '));
                }
                throw new Error('RC-S300 から想定外の応答が続きました。USB を抜き差ししてから再接続してください。');
            } catch (e) {
                // 応答待ちのまま転送が残っているので、以後この接続は使わない
                if (e instanceof ReaderTimeoutError) this.broken = true;
                throw e;
            }
        };

        // コマンドは1つずつ順番に送る（前のコマンドが失敗しても、次は実行する）
        const p = this.queue.then(run, run);
        this.queue = p.catch(() => { });
        return p;
    }

    #buildCommunicateThruEx(felicaCmd, timeoutMs) {
        const timeoutUs = timeoutMs * 1000;
        const cmdLen = felicaCmd.length + 1; // FeliCa パケットは先頭に「長さ」バイトが付く
        const req = [
            0x5f, 0x46, 0x04,
            timeoutUs & 0xff, (timeoutUs >> 8) & 0xff, (timeoutUs >> 16) & 0xff, (timeoutUs >> 24) & 0xff,
            0x95, 0x82, (cmdLen >> 8) & 0xff, cmdLen & 0xff,
            cmdLen, ...felicaCmd,
        ];
        return [
            0xff, 0x50, 0x00, 0x01, 0x00,
            (req.length >> 8) & 0xff, req.length & 0xff,
            ...req,
            0x00, 0x00, 0x00,
        ];
    }

    #findBulkInterface() {
        const candidates = [];
        for (const iface of this.device.configuration.interfaces) {
            const alt = iface.alternate ?? iface.alternates[0];
            if (!alt || RCS300Reader.PROTECTED_CLASSES.has(alt.interfaceClass)) continue;
            const epIn = alt.endpoints.find((e) => e.direction === 'in' && e.type === 'bulk');
            const epOut = alt.endpoints.find((e) => e.direction === 'out' && e.type === 'bulk');
            if (epIn && epOut) {
                candidates.push({ interfaceNumber: iface.interfaceNumber, cls: alt.interfaceClass, epIn, epOut });
            }
        }
        candidates.sort((a, b) => (b.cls === 0xff) - (a.cls === 0xff));
        return candidates[0] ?? null;
    }

    static parseTlv(bytes, start, end) {
        const map = new Map();
        let i = start;
        while (i + 2 <= end) {
            const tag = bytes[i++];
            let len = bytes[i++];
            if (len === 0x81) {
                len = bytes[i++];
            } else if (len === 0x82) {
                len = (bytes[i] << 8) | bytes[i + 1];
                i += 2;
            }
            if (i + len > end) break;
            map.set(tag, bytes.slice(i, i + len));
            i += len;
        }
        return map;
    }
}

// =====================================================================
// 画面から使う部分：接続して、入校証がかざされるのを待つ
// =====================================================================
const POLL_INTERVAL_MS = 250; // カードがあるかを調べる間隔
const REMOVE_THRESHOLD = 2;   // 連続でカード無しがこの回数続いたら「離れた」とみなす
const MAX_ERRORS = 3;         // 連続エラーがこの回数に達したら停止
const SONY_VENDOR_ID = 0x054c;

const isRcs300 = (d) => RCS300Reader.FILTERS.some(
    (f) => f.vendorId === d.vendorId && f.productId === d.productId);

// エラーを、画面に出せる日本語の説明にする
export function explainReaderError(e) {
    const claimFailed = e?.name === 'ReaderTimeoutError' && /インターフェイス/.test(e.step ?? '');
    if (claimFailed || (e?.name === 'NetworkError' && /claim/i.test(e.message ?? ''))) {
        return `RC-S300 の通信用インターフェイスを確保できませんでした（${e.message}）。`
            + 'ソニーの「NFCポートソフトウェア」をインストールし、Chrome を再起動してからリーダーを挿し直してください。';
    }
    switch (e?.name) {
        case 'NotFoundError':
            return '機器が選択されませんでした。';
        case 'SecurityError':
            return `USB へのアクセスが拒否されました（${e.message}）。`;
        case 'NetworkError':
            return `USB 通信に失敗しました（${e.message}）。別のタブやアプリがリーダーを使っていないか確認してください。`;
        case 'ReaderTimeoutError':
            return `${e.message}。リーダーを挿し直し、ページを再読み込みしてから接続し直してください。`;
        default:
            return e?.message ?? String(e);
    }
}

/*
 * 使い方：
 *   const watcher = new CardWatcher({
 *       onTouch: (idm) => { … },            // 入校証がかざされた（置きっぱなしの間は1回だけ呼ばれる）
 *       onState: (state, message) => { … }, // 'idle'（未接続）→ 'connecting'（接続中）→ 'ready'（待ち受け中）
 *   });
 *   await watcher.connect();                // ボタンを押したときに呼ぶ（機器を選ぶ画面はボタン操作からしか出せない）
 *   await watcher.connect({ ask: false });  // 前に許可したリーダーが挿さっていれば、選ぶ画面を出さずにつなぐ
 */
export class CardWatcher {
    constructor({ onTouch, onState = () => { } }) {
        this.onTouch = onTouch;
        this.onState = onState;
        this.state = 'idle';
        this.reader = null;
        this.running = false;     // 待ち受けを続けるか（false にすると、次の確認のときに止まる）
        this.stopMessage = '';    // 止めた理由（リーダーが抜かれた、など）
        this.listening = false;
    }

    // この画面でリーダーを使えないときは、その理由を返す（使えるなら null）
    static unsupportedReason() {
        if (!window.isSecureContext) return '入校証リーダーは https:// か http://127.0.0.1:8000 で開いたときだけ使えます。';
        if (!('usb' in navigator)) return 'このブラウザは入校証リーダー（WebUSB）に対応していません。Chrome か Edge を使ってください。';
        return null;
    }

    get connected() {
        return this.state === 'ready';
    }

    // リーダーにつなぐ。つながったら true（つながらなかった理由は onState の message で知らせる）
    async connect({ ask = true } = {}) {
        if (this.state !== 'idle') return this.connected;

        const reason = CardWatcher.unsupportedReason();
        if (reason) {
            this.#setState('idle', reason);
            return false;
        }
        this.#listen();
        this.#setState('connecting', 'リーダーを探しています…');

        try {
            // 一度許可した RC-S300 が1台だけ挿さっていれば、選択画面を出さずにそれを使う
            const paired = (await navigator.usb.getDevices()).filter(isRcs300);
            let device = paired.length === 1 ? paired[0] : null;
            if (!device && !ask) {
                this.#setState('idle');
                return false;
            }
            if (!device) {
                this.#setState('connecting', 'ブラウザの選択画面で RC-S300 を選んで「接続」を押してください。');
                device = await navigator.usb.requestDevice({ filters: [{ vendorId: SONY_VENDOR_ID }] });
            }
            if (!isRcs300(device)) {
                throw new Error(`選択された機器は RC-S300 ではありません（PID 0x${hex4(device.productId)}）。`);
            }

            this.reader = new RCS300Reader(device);
            await this.reader.open({
                onStep: (message) => {
                    this.#setState('connecting', message);
                    console.info('[RC-S300]', message);
                },
            });
        } catch (e) {
            console.error(e);
            await this.#teardown(explainReaderError(e));
            return false;
        }

        this.running = true;
        this.#setState('ready', 'リーダーに接続しました。');
        this.#pollLoop(); // 待ち受けは裏で続ける（止まったら中で後片付けをする）
        return true;
    }

    async #pollLoop() {
        let presentIdm = null; // いま置かれているカード
        let missCount = 0;
        let errorCount = 0;
        let stopReason = '';

        while (this.running) {
            try {
                const card = await this.reader.pollFelica();
                errorCount = 0;

                if (card) {
                    missCount = 0;
                    // 同じカードを置きっぱなしにしている間は、1回だけ知らせる
                    if (card.idm !== presentIdm) {
                        presentIdm = card.idm;
                        try {
                            await this.onTouch(card.idm);
                        } catch (e) {
                            console.error(e); // 画面側の処理のエラーでは、読み取りを止めない
                        }
                    }
                } else if (presentIdm !== null && ++missCount >= REMOVE_THRESHOLD) {
                    presentIdm = null;
                }
            } catch (e) {
                console.error(e);
                if (this.reader?.broken || ++errorCount >= MAX_ERRORS) {
                    stopReason = `読み取りが止まりました：${explainReaderError(e)}`;
                    break;
                }
            }
            await sleep(POLL_INTERVAL_MS);
        }

        await this.#teardown(stopReason || this.stopMessage);
    }

    async #teardown(message) {
        this.running = false;
        const r = this.reader;
        this.reader = null;
        this.stopMessage = '';
        if (r) await r.close();
        this.#setState('idle', message);
    }

    #setState(state, message = '') {
        this.state = state;
        this.onState(state, message);
    }

    // リーダーの抜き差しと、ページを離れるときの後片付け（最初の1回だけ登録する）
    #listen() {
        if (this.listening) return;
        this.listening = true;

        navigator.usb.addEventListener('disconnect', (event) => {
            if (this.reader && event.device === this.reader.device) {
                this.stopMessage = 'リーダーが取り外されました。挿し直してください。';
                this.running = false;
            }
        });
        // 前に許可したリーダーが挿されたら、自動でつなぎ直す
        navigator.usb.addEventListener('connect', () => {
            if (this.state === 'idle') this.connect({ ask: false });
        });
        window.addEventListener('pagehide', () => { this.running = false; });
    }
}