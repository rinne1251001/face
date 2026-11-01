@extends('layouts.test')
@section('title', 'パスワード変更')
@push('styles')
    <style>
        :root {
            --color-font-light1: #51626F;
            --color-font-light2: #829AAB;
            --color-border: #ececf1;
        }

        form {
            display: grid;
            gap: 20px;
        }

        .inp-container p {
            color: var(--color-error-font);
            font-size: 0.8em;
            padding: 3px 0 0 10px;
        }

        .inp-hov {
            display: grid;
            border: var(--color-border) 1.5px solid;
            outline: transparent 0px solid;
            border-radius: 5px;
            padding: 5px 8px;
            transition: border-color 0.3s ease, outline 0.3s ease;
        }

        .inp-hov:hover {
            border: var(--color-accent) 1.5px solid;
            outline: var(--color-accent) 0.5px solid;
        }

        .inp-hov input {
            min-width: 0;
            border: none;
            font-size: 1.15em;
            padding: 2px;
            outline: none;
        }

        .inp-hov label {
            color: var(--color-font-light1);
            font-size: 0.9em;
            padding: 2px;
            transition: color 0.3s ease;
        }

        .inp-hov:hover label {
            color: var(--color-accent);
        }
    </style>
@endpush
@section('content')

    <main class="main">
        <div class="content-wrapper">
            <form method="POST" action="{{ route('teacher.settings.password.update') }}">
                @csrf
                @method('PUT')

                <div class="inp-container">
                    <div class="inp-hov">
                        <label id="current_password">現在のパスワード</label>
                        <input for="current_password" type="password" name="current_password" autocomplete="current-password" required autofocus>
                    </div>
                    @if ($errors->has('current_password'))
                        @foreach ($errors->get('current_password') as $message)
                            <p>{{ $message }}</p>
                        @endforeach
                    @endif
                </div>

                <div>
                    <div class="inp-hov">
                        <label id="password">新しいパスワード（8文字以上）</label>
                        <input for="password" type="password" name="password" autocomplete="new-password" required>
                    </div>
                    @if ($errors->has('password'))
                        @foreach ($errors->get('password') as $message)
                            <p>{{ $message }}</p>
                        @endforeach
                    @endif
                </div>

                <div>
                    <div class="inp-hov">
                        <label id="password_confirmation">新しいパスワード（確認のためもう一度）</label>
                        <input for="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                    </div>
                </div>

                <button>変更する</button>
            </form>
        </div>
    </main>

@endsection