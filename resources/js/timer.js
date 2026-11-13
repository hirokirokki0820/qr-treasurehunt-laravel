/**
 * 宝探しタイマー（[data-timer]）。
 * - 宝探し中は残り時間を1秒ごとに減らし、残り5分（data-urgent-at）で data-urgent="true" にする。0になったら再読み込みする。
 * - data-status-url があれば5秒ごとにサーバーへ状態を問い合わせ、スタート・締め切りがあれば再読み込みする（延長は再読み込みせず差し替える）。
 *   残り秒数はサーバーの値で補正するので、スマホの時計がずれていても表示はそろう。
 */
const POLL_INTERVAL = 5000;

function formatClock(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    const pad = (n) => String(n).padStart(2, '0');

    return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
}

export function setUpTimers() {
    document.querySelectorAll('[data-timer]').forEach((timer) => {
        const running = timer.dataset.state === 'running';
        let total = Number(timer.dataset.total || 1);
        const urgentAt = Number(timer.dataset.urgentAt || 0);
        const clock = timer.querySelector('[data-timer-clock]');
        const bar = timer.querySelector('[data-timer-bar]');
        let deadline = Date.now() + Number(timer.dataset.remaining || 0) * 1000;
        let reloading = false;

        const reload = () => {
            if (!reloading) {
                reloading = true;
                window.location.reload();
            }
        };

        if (running) {
            const tick = () => {
                const left = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));

                if (clock) {
                    clock.textContent = formatClock(left);
                }
                if (bar) {
                    bar.style.width = `${Math.min(100, (left / total) * 100)}%`;
                }
                if (urgentAt > 0) {
                    timer.dataset.urgent = left <= urgentAt ? 'true' : 'false';
                }
                if (left === 0) {
                    // サーバー側でも終了になってから表示を切り替える
                    setTimeout(reload, 1500);
                }
            };

            tick();
            setInterval(tick, 1000);
        }

        const statusUrl = timer.dataset.statusUrl;

        if (statusUrl) {
            setInterval(async () => {
                try {
                    const response = await fetch(statusUrl, { headers: { Accept: 'application/json' } });

                    if (!response.ok) {
                        return;
                    }

                    const status = await response.json();

                    if (running && status.state === 'running') {
                        // 宝探し中のまま（延長を含む）なら、再読み込みせずに残り時間と終了時刻を差し替える
                        deadline = Date.now() + status.remaining * 1000;
                        total = status.total || total;
                        timer.dataset.version = status.version;
                        timer.querySelectorAll('[data-timer-ends]').forEach((ends) => {
                            ends.textContent = status.ends;
                        });
                    } else if (status.version !== timer.dataset.version) {
                        reload();
                    }
                } catch {
                    // 通信できないときは、次の問い合わせを待つ
                }
            }, POLL_INTERVAL);
        }
    });
}
