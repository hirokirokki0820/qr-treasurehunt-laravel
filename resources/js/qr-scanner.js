import { whenReachable } from './connection';

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
    // 読み取ってよいパス。カンマ区切りで複数（引き換えの画面は、引き換え用QRと参加者カードの両方）
    const acceptPaths = (root.dataset.acceptPath ?? '/treasure/').split(',');
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

        const acceptPath = acceptPaths.find((path) => url.pathname.startsWith(path));

        if (url.origin !== window.location.origin || !acceptPath) {
            showMessage(wrongMessage, 'error');

            return;
        }

        // 参加者カードの子の番では、お宝のQR（/treasure/合言葉）を主催者用のページ（data-rewrite-to）に置き換えて開く
        if (root.dataset.rewriteTo) {
            url = new URL(root.dataset.rewriteTo + url.pathname.slice(acceptPath.length), window.location.origin);
        }

        handled = true;
        showMessage(successMessage, 'success');
        await stop();

        // 圏外なら、読み取ったお宝を覚えておき、つながったら自動で開く
        await whenReachable(url.href, () => {
            window.location.href = url.href;
        }, () => {
            showMessage('通信が切れています。つながったら、読み取ったQRコードの先を自動で開きます。このまま待っていてください。', 'error');
        });
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

        // 許可の確認が出たまま、などで起動が進まないときは、10秒で案内を出してボタンを押せるように戻す
        const slowStart = window.setTimeout(() => {
            if (scanner?.isScanning) {
                return;
            }

            showMessage('カメラがなかなか起動しません。画面の上や下に「カメラの使用を許可」が出ていたら、許可してください。うまくいかないときは「カメラを起動」をもう一度押すか、Safari や Chrome で開き直してください。スマホ標準のカメラアプリでも読み取れます。', 'error');
            startButton.disabled = false;
        }, 10000);

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
            showMessage(cameraErrorMessage(error), 'error');
        } finally {
            window.clearTimeout(slowStart);
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

/**
 * カメラを起動できなかった理由を、分かる範囲で言い分ける。
 */
function cameraErrorMessage(error) {
    const text = `${error?.name ?? ''} ${error?.message ?? error ?? ''}`;
    const fallback = 'スマホ標準のカメラアプリで読み取ることもできます。';

    if (/NotAllowed|Permission/i.test(text)) {
        return `カメラの使用が許可されていません。ブラウザの設定でこのサイトのカメラを「許可」にしてから「カメラを起動」を押してください。${fallback}`;
    }
    if (/NotFound|Requested device not found|no camera/i.test(text)) {
        return `この端末でカメラが見つかりませんでした。カメラのあるスマホやタブレットで開いてください。${fallback}`;
    }
    if (/NotReadable|in use|Could not start/i.test(text)) {
        return `カメラをほかのアプリが使っているようです。カメラを使っているアプリを閉じてから「カメラを起動」を押してください。${fallback}`;
    }

    return `カメラを起動できませんでした。ブラウザのカメラ許可を確認して「カメラを起動」を押すか、Safari や Chrome で開き直してください。${fallback}`;
}
