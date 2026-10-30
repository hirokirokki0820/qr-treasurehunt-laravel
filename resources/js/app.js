import { scrollBreadcrumbsToCurrent } from './breadcrumbs';
import { setUpBulkSelect } from './bulk-select';
import { setUpEnterSubmit } from './enter-submit';
import { autoDismissFlashMessages } from './flash-message';
import { renderQrCodes } from './qr-render';
import { setUpScanner } from './qr-scanner';
import { setUpSelectionMode } from './selection-mode';
import { limitTableRows } from './table-scroll';

document.addEventListener('DOMContentLoaded', () => {
    renderQrCodes();
    setUpScanner();
    setUpBulkSelect();
    setUpSelectionMode();
    limitTableRows();
    scrollBreadcrumbsToCurrent();
    setUpEnterSubmit();
    autoDismissFlashMessages();

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
