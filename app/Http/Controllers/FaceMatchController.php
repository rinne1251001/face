<?php

namespace App\Http\Controllers;

use App\Models\MatchLog;
use App\Services\FaceMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FaceMatchController extends Controller
{
    // faces/match.blade.php（顔認証画面）を表示する
    public function show(): View
    {
        return view('faces.match');
    }

    // クライアントから顔照合のリクエストを受けるメソッド
    public function match(Request $request, FaceMatcher $matcher): JsonResponse
    {
        // バリデーションチェック
        $request->validate([
            'model_version' => ['required', 'string', Rule::in([config('face.model_version')])],
            'embedding' => ['required', 'array'],
        ], ['model_version.in' => 'モデルのバージョンがサーバー設定と一致しません。']);

        // 生の顔特徴量配列(embedding)をFaceMatcher::toUnitVector()に渡して長さを1に揃えた単位ベクトルを作成
        $query = FaceMatcher::toUnitVector($request->input('embedding'));
        // 長さを1にした配列をFaceMatcher::match()に渡して類似度を照合
        $result = $matcher->match($query, $request->input('model_version'));

        /**
         * 「誰が・いつ・どのような判定結果で顔認証を行ったか」という監査履歴（ログ）をmatch_logsテーブル保存
         * 
         * データベースに照合結果を保存する理由
         * ①不正アクセスやなりすましを追跡しやすい
         * ②システムの運用基準を最適化するデータとして活用できる
         * ③モデルバージョン変更時の互換性チェック
         */
        MatchLog::create([
            'person_id' => $result['best_person_id'],
            'similarity' => $result['similarity'],
            'margin' => $result['margin'],
            'accepted' => $result['accepted'],
            'model_version' => $request->input('model_version'),
            'admin_id' => $request->user('admin')?->id,
        ]);

        // Personオブジェクトから必要な情報だけ取得
        $summary = fn ($person) => $person?->only(['id', 'name', 'student_number', 'class_name']);

        // HTTPレスポンスとしてJSON形式のデータをクライアントへ返却
        return response()->json([
            // 本人確認成功可否（true/false）
            'accepted' => $result['accepted'],
            // 1位の人物データ
            'person' => $summary($result['person']),
            // 1位の類似度スコア
            'similarity' => $result['similarity'],
            // 1位と2位のスコア差
            'margin' => $result['margin'],
            // 判定閾値（コサイン類似度）
            'threshold' => config('face.threshold'),
            // 上位3名の候補者リスト
            'candidates' => array_map(fn ($c) => [
                'person' => $summary($c['person']),
                'similarity' => $c['similarity'],
            ], $result['candidates']),
        ]);
    }
}