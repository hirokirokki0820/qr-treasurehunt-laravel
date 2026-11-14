/**
 * 宝探しタイマー（[data-timer]）。
 * - 宝探し中は残り時間を数え、残り5分（data-urgent-at）で data-urgent="true" にする。0になったら再読み込みする。
 * - data-status-url があれば5秒ごとにサーバーへ状態を問い合わせ、スタート・締め切りがあれば再読み込みする（延長は再読み込みせず差し替える）。
 *
 * 端末どうしで表示をそろえるしくみ：
 * - サーバーからは終了時刻（data-ends-ms / ends_ms）とサーバーの今の時刻（now_ms）をミリ秒で受け取る。
 * - 問い合わせの行き帰りにかかった時間の半分を足して、端末の時計とサーバーの時計のずれを見積もる。
 *   直近の問い合わせのうち、行き帰りがいちばん速かったものを使う（遅い回線のぶれを避ける）。
 * - 表示の書き換えは、残り秒数がちょうど切り替わる瞬間に合わせる。
 */
const POLL_INTERVAL = 5000;
const SAMPLE_KEEP = 6;

function formatClock(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    const pad = (n) => String(n).padStart(2, '0');

    return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
}

/**
 * 端末の時計とサーバーの時計のずれ（サーバー − 端末、ミリ秒）を見積もる。
 */
function createClockSync(initialServerNow) {
    const samples = [];
    // ページが届くまでの時間は分からないので、最初は表示時点の値をそのまま使い、すぐ問い合わせて直す
    let offset = Number.isFinite(initialServerNow) && initialServerNow > 0 ? initialServerNow - Date.now() : 0;

    return {
        now: () => Date.now() + offset,
        add(serverNow, sentAt, receivedAt) {
            const roundTrip = receivedAt - sentAt;

            if (!Number.isFinite(serverNow) || roundTrip < 0) {
                return;
            }

            samples.push({ offset: serverNow + roundTrip / 2 - receivedAt, roundTrip });
            if (samples.length > SAMPLE_KEEP) {
                samples.shift();
            }
            offset = samples.reduce((best, sample) => (sample.roundTrip < best.roundTrip ? sample : best)).offset;
        },
    };
}

export function setUpTimers() {
    document.querySelectorAll('[data-timer]').forEach((timer) => {
        const running = timer.dataset.state === 'running';
        let total = Number(timer.dataset.total || 1);
        const urgentAt = Number(timer.dataset.urgentAt || 0);
        const clock = timer.querySelector('[data-timer-clock]');
        const bar = timer.querySelector('[data-timer-bar]');
        const sync = createClockSync(Number(timer.dataset.nowMs));
        // 終了時刻（サーバーの時計で）。古い画面で ends-ms がないときは、残り秒数から作る
        let endsAt = Number(timer.dataset.endsMs) || sync.now() + Number(timer.dataset.remaining || 0) * 1000;
        let reloading = false;
        let tickTimer = null;

        const reload = () => {
            if (!reloading) {
                reloading = true;
                window.location.reload();
            }
        };

        const tick = () => {
            clearTimeout(tickTimer);

            const leftMs = Math.max(0, endsAt - sync.now());
            const left = Math.ceil(leftMs / 1000);

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

                return;
            }

            // 次に数字が変わる瞬間（残りがちょうど整数秒になるとき）に合わせて書き換える
            const untilNextSecond = leftMs % 1000 || 1000;
            tickTimer = setTimeout(tick, untilNextSecond + 15);
        };

        if (running) {
            tick();
            // 画面を裏に回していたあいだは止まりやすいので、戻ったらすぐ合わせ直す
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    tick();
                }
            });
        }

        const statusUrl = timer.dataset.statusUrl;

        if (statusUrl) {
            const poll = async () => {
                try {
                    const sentAt = Date.now();
                    const response = await fetch(statusUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });

                    if (!response.ok) {
                        return;
                    }

                    const status = await response.json();
                    sync.add(Number(status.now_ms), sentAt, Date.now());

                    if (running && status.state === 'running') {
                        // 宝探し中のまま（延長を含む）なら、再読み込みせずに終了時刻を差し替える
                        endsAt = Number(status.ends_ms) || sync.now() + status.remaining * 1000;
                        total = status.total || total;
                        timer.dataset.version = status.version;
                        timer.querySelectorAll('[data-timer-ends]').forEach((ends) => {
                            ends.textContent = status.ends;
                        });
                        tick();
                    } else if (status.version !== timer.dataset.version) {
                        reload();
                    }
                } catch {
                    // 通信できないときは、次の問い合わせを待つ
                }
            };

            // 宝探し中は、ページが届くまでの時間を差し引くため、すぐに1回問い合わせる
            if (running) {
                poll();
            }
            setInterval(poll, POLL_INTERVAL);
        }
    });
}
