<script>
    // 10秒たっても画面用のJavaScriptが動き出さないときに案内を出す
    setTimeout(() => {
        if (!window.faceAppStarted) {
            document.getElementById('status').textContent =
                '画面用のJavaScriptがまだ動いていません。npm run dev を起動しているか、F12のコンソールにエラーがないか確認してください。';
        }
    }, 10000);
</script>
