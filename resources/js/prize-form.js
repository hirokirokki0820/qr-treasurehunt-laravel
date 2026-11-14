/**
 * 景品の登録・編集。景品名に「ハズレ」とあるのに種類が「当たり」のときは、保存の前に確かめる
 * （名前から種類を勝手に変えることはしない）。
 * 「登録して続けて追加」の Enter 送信（enter-submit.js）より先に動くよう、capture で受け取る。
 */
export function setUpPrizeForm() {
    document.querySelectorAll('form[data-prize-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const kind = form.querySelector('input[name="is_miss"]:checked')?.value;
            const name = form.querySelector('input[name="name"]')?.value ?? '';

            if (kind !== '0' || !/ハズレ|はずれ|外れ/.test(name)) {
                return;
            }

            const ok = window.confirm(`景品名に「ハズレ」とありますが、種類は「当たり」になっています。\n当たりとして保存しますか？\n（ハズレにするときは「キャンセル」を押して、種類の「ハズレ」を選んでください）`);

            if (!ok) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, { capture: true });
    });
}
