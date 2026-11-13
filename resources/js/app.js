import { scrollBreadcrumbsToCurrent } from './breadcrumbs';
import { setUpBulkSelect } from './bulk-select';
import { setUpEnterSubmit } from './enter-submit';
import { autoDismissFlashMessages } from './flash-message';
import { renderQrCodes } from './qr-render';
import { setUpScanner } from './qr-scanner';
import { setUpSelectionMode } from './selection-mode';
import { limitTableRows } from './table-scroll';
import { setUpTimers } from './timer';

document.addEventListener('DOMContentLoaded', () => {
    renderQrCodes();
    setUpScanner();
    setUpBulkSelect();
    setUpSelectionMode();
    limitTableRows();
    scrollBreadcrumbsToCurrent();
    setUpTimers();
    setUpEnterSubmit();
    autoDismissFlashMessages();

    // data-menu のメニュー（details）は、外側を押したら閉じる
    document.addEventListener('click', (event) => {
        document.querySelectorAll('details[data-menu][open]').forEach((menu) => {
            if (!menu.contains(event.target)) {
                menu.removeAttribute('open');
            }
        });
    });

    // data-confirm で確認メッセージを出す。data-confirm-again があれば、OK の後にもう一度確認する
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const confirmed = window.confirm(form.dataset.confirm)
                && (!form.dataset.confirmAgain || window.confirm(form.dataset.confirmAgain));

            if (!confirmed) {
                event.preventDefault();
            }
        });
    });
});
