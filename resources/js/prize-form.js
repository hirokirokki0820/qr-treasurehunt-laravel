/**
 * 景品の登録・編集。
 * ・種類で「ハズレ」を選んだら、景品名は入れられないようにする（保存すると「ハズレ」になる）
 * ・景品名に「ハズレ」とあるのに種類が「当たり」のときは、保存の前に確かめる（名前から種類を勝手に変えない）
 * 「登録して続けて追加」の Enter 送信（enter-submit.js）より先に動くよう、submit は capture で受け取る。
 */
export function setUpPrizeForm() {
    document.querySelectorAll('form[data-prize-form]').forEach((form) => {
        const nameInput = form.querySelector('input[name="name"]');
        const note = form.querySelector('[data-miss-note]');
        let savedName = nameInput?.value ?? '';

        const applyKind = () => {
            if (!nameInput) {
                return;
            }

            const isMiss = form.querySelector('input[name="is_miss"]:checked')?.value === '1';

            if (isMiss && !nameInput.disabled) {
                savedName = nameInput.value;
                nameInput.value = '';
            } else if (!isMiss && nameInput.disabled) {
                nameInput.value = savedName === 'ハズレ' ? '' : savedName;
            }

            nameInput.disabled = isMiss;
            nameInput.placeholder = isMiss ? nameInput.dataset.placeholderMiss : nameInput.dataset.placeholderWin;
            if (note) {
                note.hidden = !isMiss;
            }
        };

        form.querySelectorAll('input[name="is_miss"]').forEach((radio) => radio.addEventListener('change', applyKind));

        form.addEventListener('submit', (event) => {
            const kind = form.querySelector('input[name="is_miss"]:checked')?.value;
            const name = nameInput?.value ?? '';

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
