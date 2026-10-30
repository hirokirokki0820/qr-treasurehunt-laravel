/**
 * 「選択する」ボタンで選択モードに切り替える。
 * 選択モードではチェックボックスが現れ、カードを押すと移動せずに選択・解除できる。
 */
export function setUpSelectionMode() {
    const root = document.querySelector('[data-selection-root]');

    if (!root) {
        return;
    }

    const items = Array.from(root.querySelectorAll('[data-bulk-select-item]'));
    const selectAll = root.querySelector('[data-bulk-select-all]');
    const isSelecting = () => root.hasAttribute('data-selecting');

    const notifyChange = () => items[0]?.dispatchEvent(new Event('change'));

    const setSelecting = (selecting) => {
        root.toggleAttribute('data-selecting', selecting);

        if (!selecting) {
            items.forEach((item) => {
                item.checked = false;
            });

            if (selectAll) {
                selectAll.checked = false;
            }

            notifyChange();
        }
    };

    root.querySelector('[data-selection-enter]')?.addEventListener('click', () => {
        setSelecting(true);
        items[0]?.focus();
    });

    root.querySelector('[data-selection-cancel]')?.addEventListener('click', () => setSelecting(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isSelecting()) {
            setSelecting(false);
        }
    });

    root.querySelectorAll('[data-selection-card]').forEach((card) => {
        card.addEventListener('click', (event) => {
            const checkbox = card.querySelector('[data-bulk-select-item]');

            if (!isSelecting() || event.target === checkbox || event.target.closest('label')) {
                return;
            }

            event.preventDefault();
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change'));
        });
    });

    // ブラウザの「戻る」でチェックが復元されたときは、選択モードのまま表示する
    window.addEventListener('pageshow', () => {
        if (items.some((item) => item.checked)) {
            root.setAttribute('data-selecting', '');
        }
    });
}
