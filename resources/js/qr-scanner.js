/**
 * アプリ内QRスキャナー。読み取ったURLが、このサイトの data-accept-path で始まるページ
 * （宝のQRなら /treasure/、参加用QRなら /join/）なら、そのページへ移動する。
 */
export function setUpScanner() {
    const root = document.getElementById('qr-scanner');

    if (!root) {
        return;
    }

    const startButton = root.querySelector('[data-scanner-start]');
    const stopButton = root.querySelector('[data-scanner-stop]');
    const message = root.querySelector('[data-scanner-message]');
    const readerId = 'qr-reader';
    const acceptPath = root.dataset.acceptPath ?? '/treasure/';
    const wrongMessage = root.dataset.wrongMessage ?? 'このQRコードは読み取れません。';
    const successMessage = root.dataset.successMessage ?? '読み取りました。移動します…';
    let scanner = null;
    let handled = false;

    const showMessage = (text, tone = 'info') => {
        message.textContent = text;
        message.dataset.tone = tone;
        message.hidden = false;
    };

    const toggleButtons = (running) => {
        startButton.hidden = running;
        stopButton.hidden = !running;
    };

    const stop = async () => {
        if (scanner?.isScanning) {
            await scanner.stop();
        }

        toggleButtons(false);
    };

    const onScanSuccess = async (decodedText) => {
        if (handled) {
            return;
        }

        let url;

        try {
            url = new URL(decodedText, window.location.origin);
        } catch {
            showMessage(wrongMessage, 'error');

            return;
        }

        if (url.origin !== window.location.origin || !url.pathname.startsWith(acceptPath)) {
            showMessage(wrongMessage, 'error');

            return;
        }

        handled = true;
        showMessage(successMessage, 'success');
        await stop();
        window.location.href = url.href;
    };

    const start = async () => {
        if (scanner?.isScanning) {
            return;
        }

        if (!window.isSecureContext) {
            showMessage('カメラを使うには https:// でアクセスしてください。スマホ標準のカメラアプリでも読み取れます。', 'error');

            return;
        }

        startButton.disabled = true;
        showMessage('カメラを起動しています…');

        try {
            const { Html5Qrcode } = await import('html5-qrcode');

            scanner ??= new Html5Qrcode(readerId);

            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: (width, height) => {
                    const size = Math.floor(Math.min(width, height) * 0.8);

                    return { width: size, height: size };
                } },
                onScanSuccess,
                () => {},
            );

            toggleButtons(true);
            showMessage('QRコードを枠の中に写してください。');
        } catch (error) {
            console.error(error);
            showMessage('カメラを起動できませんでした。ブラウザのカメラ許可を確認して「カメラを起動」を押すか、スマホ標準のカメラアプリで読み取ってください。', 'error');
        } finally {
            startButton.disabled = false;
        }
    };

    startButton.addEventListener('click', start);

    // data-autostart="true" のページ（QRコードを読み取る画面）は、開いたらすぐカメラを起動する
    if (root.dataset.autostart === 'true') {
        start();
    }

    stopButton.addEventListener('click', async () => {
        await stop();
        showMessage('スキャンを停止しました。');
    });

    window.addEventListener('pagehide', () => {
        stop();
    });
}
