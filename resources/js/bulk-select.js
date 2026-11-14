/**
 * 景品一覧のチェックボックス。選択数に応じて一括削除ボタンを有効にする。
 */
export function setUpBulkSelect() {
    const button = document.querySelector('[data-bulk-delete-button]');

    if (!button) {
        return;
    }

    const selectAll = document.querySelector('[data-bulk-select-all]');
    const items = Array.from(document.querySelectorAll('[data-bulk-select-item]'));
    const count = button.querySelector('[data-bulk-delete-count]');

    // 確認のメッセージに、選んだもの（data-bulk-label）を並べる。選んだ数が多いときは先頭の10件だけ
    const form = button.form;
    const baseConfirm = form?.dataset.confirm;

    const updateConfirm = () => {
        if (!form || !baseConfirm) {
            return;
        }

        const labels = items.filter((item) => item.checked && item.dataset.bulkLabel).map((item) => `・${item.dataset.bulkLabel}`);

        if (labels.length === 0) {
            form.dataset.confirm = baseConfirm;

            return;
        }

        const shown = labels.slice(0, 10).join('\n');
        const more = labels.length > 10 ? `\n…ほか${labels.length - 10}件` : '';
        form.dataset.confirm = `次の${labels.length}件を削除します。\n${shown}${more}\n\n${baseConfirm}`;
    };

    const refresh = () => {
        const checked = items.filter((item) => item.checked).length;
        updateConfirm();

        button.disabled = checked === 0;
        count.textContent = checked > 0 ? `（${checked}件）` : '';

        if (selectAll) {
            selectAll.checked = checked > 0 && checked === items.length;
            selectAll.indeterminate = checked > 0 && checked < items.length;
        }
    };

    selectAll?.addEventListener('change', () => {
        items.forEach((item) => {
            item.checked = selectAll.checked;
        });
        refresh();
    });

    items.forEach((item) => item.addEventListener('change', refresh));
    refresh();

    // ブラウザの「戻る」でチェック状態が復元されたときも、ボタンの状態を合わせる
    window.addEventListener('pageshow', refresh);
}
