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

    const refresh = () => {
        const checked = items.filter((item) => item.checked).length;

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
