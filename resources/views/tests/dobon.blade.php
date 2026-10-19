@extends('layouts.marumo')
@section('title', 'ドボンゲーム | marumo')

@section('content')
<main class="game-container">
    <div class="game-card">
        <h1>🃏 トランプゲーム「ドボン」</h1>
        <p class="game-desc">自分の手札の「合計値」が、場札の数字とぴったり一致したら「ドボン」で逆転勝利！</p>

        <!-- メッセージ表示エリア -->
        <div id="status-message" class="status-box">ゲーム開始ボタンを押してください！</div>

        <!-- CPUの手札（非表示） -->
        <div class="area">
            <h3>🤖 CPUの手札: <span id="cpu-count">0</span> 枚</h3>
        </div>

        <!-- 場札 & 山札 -->
        <div class="field-area">
            <div class="deck-box">
                <span>山札</span>
                <strong id="deck-count">0</strong>
            </div>
            <div class="field-box">
                <span>場札</span>
                <div id="field-card" class="card-display">-</div>
            </div>
        </div>

        <!-- プレイヤーの手札 -->
        <div class="area">
            <h3>👤 あなたの手札 (合計: <span id="player-sum">0</span>)</h3>
            <div id="player-hand" class="hand-container"></div>
        </div>

        <!-- アクションボタン -->
        <div class="controls">
            <button id="btn-start" onclick="initGame()">ゲームスタート</button>
            <button id="btn-draw" onclick="playerDraw()" disabled>山札から引く</button>
            <button id="btn-dobon" onclick="playerDobon()" disabled class="btn-danger">🔥 ドボン！</button>
        </div>

        <div style="margin-top: 1.5rem;">
            <a href="/" class="profile-link">プロフィールに戻る</a>
        </div>
    </div>
</main>

<style>
    .game-container {
        display: flex;
        justify-content: center;
        padding: 1rem;
    }
    .game-card {
        background: #fff;
        padding: 1.5rem;
        border-radius: 12px;
        max-width: 500px;
        width: 100%;
        text-align: center;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .status-box {
        background: #f0edff;
        padding: 0.8rem;
        border-radius: 8px;
        font-weight: bold;
        margin: 1rem 0;
        min-height: 24px;
    }
    .field-area {
        display: flex;
        justify-content: center;
        gap: 2rem;
        margin: 1.5rem 0;
    }
    .deck-box, .field-box {
        background: #fafafa;
        border: 2px dashed var(--color-font);
        border-radius: 8px;
        padding: 0.5rem 1rem;
        width: 80px;
    }
    .card-display {
        font-size: 1.8rem;
        font-weight: bold;
        margin-top: 0.3rem;
    }
    .hand-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: center;
        margin-top: 0.5rem;
        min-height: 60px;
    }
    .card-btn {
        background: #ffffff;
        border: 2px solid var(--color-font);
        border-radius: 6px;
        padding: 0.5rem 0.8rem;
        font-size: 1.1rem;
        font-weight: bold;
        cursor: pointer;
        color: var(--color-font);
        transition: transform 0.1s;
    }
    .card-btn:hover {
        transform: translateY(-3px);
        background: #f0edff;
    }
    .controls {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
        margin-top: 1.5rem;
    }
    .controls button {
        padding: 0.6rem 1rem;
        border-radius: 20px;
        border: none;
        background: var(--color-font);
        color: #fff;
        font-weight: bold;
        cursor: pointer;
    }
    .controls button:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    .controls button.btn-danger {
        background: #e74c3c;
    }
</style>

<script>
    const suits = ['♠', '♥', '♦', '♣'];
    let deck = [], playerHand = [], cpuHand = [], fieldCard = null;
    let isPlayerTurn = true;

    // トランプ山札作成
    function createDeck() {
        deck = [];
        for (let s of suits) {
            for (let num = 1; num <= 13; num++) {
                deck.push({ suit: s, num: num });
            }
        }
        // シャッフル
        for (let i = deck.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [deck[i], deck[j]] = [deck[j], deck[i]];
        }
    }

    // ゲーム初期化
    function initGame() {
        createDeck();
        playerHand = [deck.pop(), deck.pop(), deck.pop(), deck.pop()];
        cpuHand = [deck.pop(), deck.pop(), deck.pop(), deck.pop()];
        fieldCard = deck.pop();
        isPlayerTurn = true;

        document.getElementById('btn-start').innerText = 'やり直す';
        document.getElementById('btn-draw').disabled = false;
        
        updateUI();
        setMessage("あなたのターンです。出せるカードをタップするか、山札から引いてください。");
    }

    // UI更新
    function updateUI() {
        document.getElementById('deck-count').innerText = deck.length;
        document.getElementById('cpu-count').innerText = cpuHand.length;
        document.getElementById('field-card').innerText = `${fieldCard.suit}${getCardName(fieldCard.num)}`;

        // 手札描画
        const handEl = document.getElementById('player-hand');
        handEl.innerHTML = '';
        let sum = 0;

        playerHand.forEach((card, index) => {
            sum += card.num;
            const btn = document.createElement('button');
            btn.className = 'card-btn';
            btn.innerText = `${card.suit}${getCardName(card.num)}`;
            btn.onclick = () => playCard(index);
            handEl.appendChild(btn);
        });

        document.getElementById('player-sum').innerText = sum;

        // ドボン判定ボタン活性化
        const canDobon = (sum === fieldCard.num) || playerHand.some(c => c.num === fieldCard.num);
        document.getElementById('btn-dobon').disabled = !isPlayerTurn || !canDobon;
    }

    function getCardName(num) {
        if (num === 1) return 'A';
        if (num === 11) return 'J';
        if (num === 12) return 'Q';
        if (num === 13) return 'K';
        return num;
    }

    function setMessage(msg) {
        document.getElementById('status-message').innerText = msg;
    }

    // カードを出す
    function playCard(index) {
        if (!isPlayerTurn) return;
        const card = playerHand[index];

        // マークか数字が一致していれば出せる（UNO風ルール）
        if (card.suit === fieldCard.suit || card.num === fieldCard.num) {
            fieldCard = playerHand.splice(index, 1)[0];
            updateUI();

            if (playerHand.length === 0) {
                setMessage("🎉 あなたの勝利です！（上がりました）");
                endGame();
                return;
            }

            isPlayerTurn = false;
            setMessage("CPUの番です...");
            setTimeout(cpuTurn, 1000);
        } else {
            alert("同じマークか同じ数字のカードしか出せません！");
        }
    }

    // 山札から引く
    function playerDraw() {
        if (!isPlayerTurn) return;
        if (deck.length > 0) {
            playerHand.push(deck.pop());
            updateUI();
            isPlayerTurn = false;
            setMessage("カードを引きました。CPUの番です...");
            setTimeout(cpuTurn, 1000);
        } else {
            setMessage("山札がありません！引き分けです。");
        }
    }

    // ドボン宣言！
    function playerDobon() {
        const sum = playerHand.reduce((a, b) => a + b.num, 0);
        if (sum === fieldCard.num) {
            setMessage(`🔥 ドボン成立！！（手札合計 ${sum} = 場札 ${fieldCard.num}）あなたの劇的勝利！`);
        } else {
            setMessage(`🔥 ドボン成立！！（同じ数字のカード）あなたの勝利！`);
        }
        endGame();
    }

    // CPUの思考ロジック
    function cpuTurn() {
        // CPUドボンチェック
        const cpuSum = cpuHand.reduce((a, b) => a + b.num, 0);
        if (cpuSum === fieldCard.num) {
            setMessage(`💥 CPU「ドボン！」（手札合計 ${cpuSum} = 場札 ${fieldCard.num}）あなたの負けです...`);
            endGame();
            return;
        }

        // 出せるカードを探す
        const validIndex = cpuHand.findIndex(c => c.suit === fieldCard.suit || c.num === fieldCard.num);

        if (validIndex !== -1) {
            fieldCard = cpuHand.splice(validIndex, 1)[0];
            if (cpuHand.length === 0) {
                setMessage("😭 CPUが上がり！あなたの負けです。");
                endGame();
                return;
            }
        } else if (deck.length > 0) {
            cpuHand.push(deck.pop());
        }

        isPlayerTurn = true;
        updateUI();
        setMessage("あなたのターンです！");
    }

    function endGame() {
        isPlayerTurn = false;
        document.getElementById('btn-draw').disabled = true;
        document.getElementById('btn-dobon').disabled = true;
    }
</script>
@endsection