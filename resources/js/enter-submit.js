/**
 * data-enter-submits="ボタンのセレクタ" を付けたフォームでは、入力欄で Enter を押したときに
 * 指定したボタン（例：「登録して続けて追加」）で送信する。
 * 日本語入力の変換確定の Enter と、複数行の入力欄（textarea）の改行は対象外。
 * 送信中にもう一度 Enter を押しても、二重に登録されないようにする。
 */
export function setUpEnterSubmit() {
    document.querySelectorAll('form[data-enter-submits]').forEach((form) => {
        const button = form.querySelector(form.dataset.enterSubmits);

        if (!button) {
            return;
        }

        let submitting = false;

        form.addEventListener('submit', () => {
            submitting = true;
        });

        // ブラウザの「戻る」でこのページに戻ったときは、また送信できるようにする
        window.addEventListener('pageshow', () => {
            submitting = false;
        });

        form.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' || event.isComposing || event.keyCode === 229) {
                return;
            }

            if (submitting) {
                event.preventDefault();

                return;
            }

            if (event.shiftKey || event.ctrlKey || event.altKey || event.metaKey) {
                return;
            }

            const target = event.target;

            if (!(target instanceof HTMLInputElement) || ['checkbox', 'radio', 'submit', 'button'].includes(target.type)) {
                return;
            }

            event.preventDefault();
            form.requestSubmit(button);
        });
    });
}
