/** app.css の --animate-toast-timer（残り時間バー）と同じ長さにする */
const DISPLAY_MILLISECONDS = 3000;
const FADE_MILLISECONDS = 500;

/**
 * 画面右上に重ねて表示する完了メッセージ（data-auto-dismiss）を、数秒後に消す。
 * × ボタンを押せばすぐに消せる。
 */
export function autoDismissFlashMessages() {
    document.querySelectorAll('[data-auto-dismiss]').forEach((message) => {
        const dismiss = () => {
            message.classList.add('opacity-0', '-translate-y-4');
            window.setTimeout(() => message.remove(), FADE_MILLISECONDS);
        };

        const timer = window.setTimeout(dismiss, DISPLAY_MILLISECONDS);

        message.querySelector('[data-dismiss]')?.addEventListener('click', () => {
            window.clearTimeout(timer);
            dismiss();
        });
    });
}
