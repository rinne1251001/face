@extends('layouts.marumo')
@section('title', '自己紹介 | marumo')

@section('content')
<main class="profile-container">
    <div class="profile-card">
        <!-- プロフィール画像 -->
        <div class="profile-image-wrapper">
            <img src="{{ asset('storage/marumo/image.jpg') }}" alt="プロフィール画像" class="profile-image">
        </div>

        <!-- ユーザー情報 -->
        <h1 class="profile-name">しゅんちゃん</h1>
        <p class="profile-bio">
            はじめまして！<br>
            最近筋トレをサボってしまい、体が情けないです！<br>
            好きな種目はデッドリフトです！
        </p>

        <!-- SNSリンクや関連情報 -->
        <div class="profile-links">
            <a href="https://www.amazon.co.jp/REYS-%E3%83%93%E3%82%BF%E3%83%9F%E3%83%B37%E7%A8%AE%E9%85%8D%E5%90%88-WPC%E3%83%97%E3%83%AD%E3%83%86%E3%82%A4%E3%83%B3-%E3%83%9B%E3%82%A8%E3%82%A4%E3%83%97%E3%83%AD%E3%83%86%E3%82%A4%E3%83%B3%E2%80%A6-%E3%82%AB%E3%83%95%E3%82%A7%E3%82%AA%E3%83%AC%E9%A2%A8%E5%91%B3/dp/B0C6X64277?th=1" target="_blank" rel="noopener noreferrer" class="profile-link">欲しいものリスト</a>
            <a href="#" class="profile-link">GitHub</a>
        </div>
    </div>
</main>
@endsection