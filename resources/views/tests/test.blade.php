<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="og:site_name" content="Rollin">
    <title>テスト</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/github-dark.min.css">
    <style>
        :root {
            --color-bg: #FFF;
            --color-font: #13355F;
            --color-main: #87C8DE;
            --color-accent: #EEC1BB;
        }
        [data-theme="pink"] {
            --color-bg: #FFF;
            --color-font: #8E6D63;
            --color-main: #F7C3BF;
            --color-accent: #F9D69E;
        }
        [data-theme="yellow"] {
            --color-bg: #FFF;
            --color-font: #6F5850;
            --color-main: #FDBA2E;
            --color-accent: #F77251;
        }

        body {
            background-color: var(--color-main);
            color: var(--color-font);
        }
        main {
            display: grid;
            gap: 10px;
        }
        section {
            background-color: var(--color-bg);
            padding: 20px;
            min-width: 0;
        }
        table {
            border-collapse: collapse;
            overflow-x: auto;
        }
        thead {
            background-color: var(--color-accent);
            color: var(--color-bg);
        }
        table th,
        table td {
            padding: 10px;
        }
        pre {
            overflow-x: auto;
            line-height: 1.5;
        }
        a {
            color: var(--color-accent);
            font-weight: bold;
        }
    </style>
</head>

@php
    // テーマカラーを変更
    $themeColor = 'yellow';
@endphp
<body data-theme="{{ $themeColor }}">

    <main>
        <section>
            @php
                $fruits = [
                    ['apple', 'りんご', 'red'],
                    ['banana', 'ばなな', 'yellow'],
                ];
            @endphp
            <table border="1">
                <thead>
                    <tr><th>英語</th><th>日本語</th><th>色</th></tr>
                </thead>
                <tbody>
                    @foreach($fruits as $fruit)
                        <tr><td>{{ $fruit[0] }}</td><td>{{ $fruit[1] }}</td><td>{{ $fruit[2] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section>
            @php
                // モデル（app/Model/Subject.php）を使ってデータベースから全件取得
                $subjects = \App\Models\Subject::all();
            @endphp

            <form>
                <label for="favorite">好きな授業を選んでください：</label>
                <select id="favorite" name="favorite">
                    <option value="" disabled selected>選択してください</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </form>

            <table border="1">
                <thead>
                    <tr><th>Subjects(授業)テーブルに登録されているデータ</th></tr>
                </thead>
                <tbody>
                    @forelse ($subjects as $subject)
                        <tr><td>{{ $loop->iteration }}．{{ $subject->name }}（{{ $subject->schedule_label }}）</td></tr>
                    @empty
                        <tr><td>まだデータが保存されていません</td></tr>
                    @endforelse
                </tbody>
            </table>

            <p>
                モデルファイルは、データベースのテーブルとPHPのプログラムを繋ぐ「通訳」や「窓口」のような役割を持つファイルのこと<br>
                このテーブルをPHPからどう扱うかを定義したプログラムで、モデルがあるおかげで難しいSQL文を毎回書かずにスッキリとしたPHPのコードでデータベースを操作できるようになります<br>
                「schedule_label」はSubjectsテーブルの項目にはありませんが、以下のコードがあるので曜日と時間を同時に取得できています
            </p>
            <pre><code class="php">protected function scheduleLabel(): Attribute
{
    return Attribute::get(function () {
        $day = self::DAYS[$this->day_of_week] ?? '?';
        $period = $this->period_start === $this->period_end
            ? "{$this->period_start}限"
            : "{$this->period_start}〜{$this->period_end}限";

        return "{$day}曜 {$period}";
    });
}</code></pre>
        </section>

        <section>
            @auth('admin')
                <p>現在、管理者としてログインしています（ログインID: {{ auth('admin')->user()->login_id }}）</p>
            @endauth
            @guest('admin')
                <p>管理者ログインは<a href="{{ route('admin.login') }}">こちら</a>からできます</p>
            @endguest

            @auth('teacher')
                <p>現在、教師としてログインしています（ログインID: {{ auth('teacher')->user()->login_id }}）</p>
            @endauth
            @guest('teacher')
                <p>教師ログインは<a href="{{ route('teacher.login') }}">こちら</a>からできます</p>
            @endguest

            <div>
                <svg style="color: {{ auth('admin')->check() ? '#F8A7A0' : '#FCF16E' }}; width: 192px; height: 177px;">
                    <use href="{{ auth('teacher')->check() ? '#heart' : '#star' }}" />
                </svg>
            </div>

            <p>AdminモデルとTeacherモデルはクラス宣言で</p>
            <pre><code class="php">class Admin extends Authenticatable</code></pre>
            <p>
                と認証システムである「Authenticatable」を継承しています<br>
                そのため、@@authやauth()を使うことができます
            </p>
        </section>

        <section>
            @php
                $embedding = [3, 4, ...array_fill(0, 62, 0.1)];
                $hasError = false;
                try {
                    $unitVector = \App\Services\FaceMatcher::toUnitVector($embedding);
                    $sos = 0.0;
                    foreach ($unitVector as $val) {
                        $sos += $val * $val;
                    }
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $hasError = true;
                    $errorMessages = $e->errors();
                }
            @endphp

            <p>
                @if(!$hasError)
                    {{ count($embedding) }}次元配列をFaceMatcher::toUnitVector()を使ってL2正規化しました<br>
                    結果は[{{ implode(', ', $unitVector) }}]になりました<br>
                    これをそれぞれ2乗して足すと{{ $sos }}になります<br>
                @else
                    {{ count($embedding) }}次元配列はtoUnitVector()を使えません<br>
                    エラーメッセージ：{{ $errorMessages['embedding'][0] ?? '不明なエラー' }}
                @endif
            </p>

            <p>
                toUnitVector()はconfig/face.phpにあるmin_dimension以上でないとエラーになります<br>
                min_dimensionの今の値：{{ config('face.min_dimension') }}
            </p>

            <pre><code class="php">public static function toUnitVector(mixed $raw, string $field = 'embedding'): array
{
    // 配列でない、連想配列（リスト形式でない）、次元数（要素数）が設定範囲外なら終了
    $count = is_array($raw) ? count($raw) : 0;
    if (! is_array($raw) || ! array_is_list($raw) || $count < config('face.min_dimension') || $count > config('face.max_dimension')) {
        throw ValidationException::withMessages([$field => '特徴量の形式が正しくありません。']);
    }

    // すべての要素の二乗和を求める
    $sum = 0.0;
    foreach ($raw as $x) {
        if (! is_int($x) && ! is_float($x)) {
            throw ValidationException::withMessages([$field => '特徴量に数値以外が含まれています。']);
        }
        $sum += $x * $x;
    }
    if ($sum <= 0.0 || ! is_finite($sum)) {
        throw ValidationException::withMessages([$field => '特徴量が不正です。']);
    }

    // ルートをかぶせて実際のベクトルの長さに戻す
    $norm = sqrt($sum);

    // 特徴ベクトルを1にする（例：[3, 4]→[0.6, 0.8]のように同じ比率で三平方の定理で計算すると1になる）
    return array_map(fn ($x) => $x / $norm, $raw);
}</code></pre>
        </section>
    </main>

    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" style="display: none;">
        <symbol id="heart" viewBox="0 0 192 177">
            <path fill="currentColor" d="M96 46.8c39.6-97.5 194 0 0 125.2C-98 46.8 56.4-50.7 96 46.8z" fill-rule="evenodd"/>
        </symbol>
    </svg>

    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" style="display: none;">
        <symbol id="star" viewBox="0 0 192 177">
            <path fill="currentColor" d="M1 67.6 65 56l31-56 31 56 64 11.6-44.7 46.2 8.4 63.2L96 149.6 37.3 177l8.4-63.2z" fill-rule="evenodd"/>
        </symbol>
    </svg>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>
</body>

</html>