/**
 * data-scroll-rows="20" を付けた表の枠を、ヘッダーと指定行数ぶんの高さにする。
 * それより多い行はスクロールで表示する（実際の行の高さで計算するので、文字が折り返しても20行ちょうどになる）。
 */
export function limitTableRows() {
    document.querySelectorAll('[data-scroll-rows]').forEach((container) => {
        const visibleRows = Number(container.dataset.scrollRows);

        const fit = () => {
            const rows = container.querySelectorAll('tbody tr');

            if (rows.length <= visibleRows) {
                container.style.maxHeight = 'none';

                return;
            }

            const top = container.getBoundingClientRect().top - container.scrollTop;
            const lastVisibleRow = rows[visibleRows - 1].getBoundingClientRect();

            container.style.maxHeight = `${Math.ceil(lastVisibleRow.bottom - top)}px`;
        };

        fit();
        window.addEventListener('resize', fit);
    });
}
