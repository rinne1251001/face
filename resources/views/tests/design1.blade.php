@extends('layouts.test')
@section('title', 'デザインテスト')

@section('content')
@push('styles')
<style>

</style>
@endpush

@section('content')
こんにちは
<div style="background-color: var(--color-main); width: 120px; height: 120px;"></div>
<div style="background-color: var(--color-sub); width: 120px; height: 120px;"></div>
<div style="background-color: var(--color-accent); width: 120px; height: 120px;"></div>


@endsection

@push('scripts')
<script></script>
@endpush
</body>
</html>