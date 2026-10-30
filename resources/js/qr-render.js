/**
 * data-qr="URL" を持つ要素の中に、そのURLのQRコード（SVG）を描画する。
 */
export async function renderQrCodes() {
    const targets = document.querySelectorAll('[data-qr]');

    if (targets.length === 0) {
        return;
    }

    const { default: QRCode } = await import('qrcode');

    await Promise.all(
        Array.from(targets).map(async (element) => {
            element.innerHTML = await QRCode.toString(element.dataset.qr, {
                type: 'svg',
                margin: 1,
                errorCorrectionLevel: 'M',
                color: { dark: '#1f2a44', light: '#ffffff' },
            });

            element.querySelector('svg')?.setAttribute('role', 'img');
            element.querySelector('svg')?.setAttribute('aria-label', 'QRコード');
        }),
    );
}
