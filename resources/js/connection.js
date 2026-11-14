/**
 * 通信が切れたときの表示と自動再開。
 * - 端末が圏外になった（offline）、または状態の問い合わせが続けて失敗したら、画面の上に「通信が切れています」を出す。
 * - つながり直したら表示を消し、待たせていた操作（読み取ったお宝を開く、ボタンの送信）を自動で続ける。
 * - 見つけた景品はサーバーに保存されているので、画面を開き直しても消えない。
 */
const FAILURES_BEFORE_WARNING = 2;

let banner = null;
let failures = 0;
const waiting = [];

function showBanner(text, tone) {
    if (!banner) {
        banner = document.createElement('p');
        banner.setAttribute('role', 'status');
        banner.dataset.connectionBanner = '';
        banner.className = 'no-print fixed inset-x-3 top-3 z-50 rounded-xl px-4 py-3 text-center text-sm font-bold shadow-lg';
        document.body.append(banner);
    }

    banner.textContent = text;
    banner.dataset.tone = tone;
    banner.classList.toggle('bg-ruby', tone === 'offline');
    banner.classList.toggle('text-white', tone === 'offline');
    banner.classList.toggle('bg-sea', tone === 'online');
    banner.classList.toggle('text-white', true);
    banner.hidden = false;
}

function hideBanner() {
    if (banner) {
        banner.hidden = true;
    }
}

function goOffline() {
    showBanner('通信が切れています。電波のよい場所へ移動してください。つながり直すと自動で再開します。', 'offline');
}

function goOnline() {
    const wasShowing = banner && !banner.hidden && banner.dataset.tone === 'offline';
    failures = 0;

    if (wasShowing) {
        showBanner('つながりました。', 'online');
        setTimeout(hideBanner, 2000);
    }

    // 待たせていた操作を、つながった順に続ける
    while (waiting.length > 0) {
        waiting.shift()();
    }
}

/**
 * 問い合わせの成否を知らせる。続けて失敗したら「通信が切れています」を出し、成功したら再開する。
 */
export function reportConnection(ok) {
    if (ok) {
        if (failures >= FAILURES_BEFORE_WARNING || waiting.length > 0) {
            goOnline();
        }
        failures = 0;

        return;
    }

    failures += 1;
    if (failures >= FAILURES_BEFORE_WARNING) {
        goOffline();
    }
}

/**
 * サーバーに届くか確かめてから action を行う。届かなければ「通信が切れています」を出し、
 * つながり直したところで action を行う（3秒ごとにも確かめ直す）。
 */
export async function whenReachable(checkUrl, action, onWaiting = () => {}) {
    const reachable = async () => {
        if (!navigator.onLine) {
            return false;
        }
        try {
            const response = await fetch(checkUrl, { method: 'GET', cache: 'no-store', credentials: 'same-origin' });

            return response.status < 500;
        } catch {
            return false;
        }
    };

    if (await reachable()) {
        action();

        return;
    }

    goOffline();
    onWaiting();

    let done = false;
    const run = () => {
        if (!done) {
            done = true;
            clearInterval(retry);
            hideBanner();
            action();
        }
    };
    const retry = setInterval(async () => {
        if (await reachable()) {
            goOnline();
            run();
        }
    }, 3000);
    waiting.push(run);
}

export function setUpConnectionWatch() {
    window.addEventListener('offline', goOffline);
    window.addEventListener('online', () => {
        // online になっても実際に届くとは限らないので、表示だけ消して、待っている操作は whenReachable の確認に任せる
        if (waiting.length === 0) {
            goOnline();
        }
    });

    // 圏外のときにボタン（宝箱を開ける など）を押したら、送らずに待って、つながったら送る
    document.querySelectorAll('form[data-wait-for-connection]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (navigator.onLine || form.dataset.sending === 'true') {
                return;
            }

            event.preventDefault();
            form.dataset.sending = 'true';
            whenReachable(window.location.href, () => form.submit());
        });
    });
}
