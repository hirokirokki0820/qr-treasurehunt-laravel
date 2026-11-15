/**
 * 「控えを保存」：景品の獲得状況を1枚の画像（PNG）にして保存する。
 * つながらなくなったときや、データが消えたときに、誰が何を見つけたかを確かめるための控え。
 * 画像はスマホの中で作り、サーバーには送らない。スマホでは共有の画面（「画像を保存」で写真へ）、PCではダウンロード。
 * 最後に保存した時刻をこのブラウザに覚えておき、15分たったらボタンを目立たせる。
 */
const REMIND_AFTER_MS = 15 * 60 * 1000;
const ROWS_PER_IMAGE = 250;

export function setUpSnapshot() {
    const root = document.querySelector('[data-snapshot]');
    const dataTag = document.querySelector('[data-snapshot-data]');

    if (!root || !dataTag) {
        return;
    }

    const button = root.querySelector('[data-snapshot-button]');
    const last = root.querySelector('[data-snapshot-last]');
    const key = root.dataset.snapshotKey;

    const readLast = () => {
        try {
            return Number(window.localStorage.getItem(key)) || 0;
        } catch {
            return 0;
        }
    };

    const showLast = () => {
        const at = readLast();

        if (!at) {
            last.textContent = 'まだ保存していません';
            button.toggleAttribute('data-due', true);

            return;
        }

        const time = new Date(at).toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Tokyo' });
        const due = Date.now() - at > REMIND_AFTER_MS;
        last.textContent = due ? `最後の控え ${time}・そろそろ保存を` : `最後の控え ${time}`;
        button.toggleAttribute('data-due', due);
    };

    const label = button.querySelector('[data-snapshot-label]');
    const defaultLabel = label?.textContent ?? '';
    let latest = JSON.parse(dataTag.textContent);
    let pendingFiles = null;

    const markSaved = (savedAt) => {
        try {
            window.localStorage.setItem(key, String(savedAt.getTime()));
        } catch {
            // 覚えておけなくても、保存はできている
        }
        showLast();
    };

    // 共有の画面を開く。開けなければダウンロードにする。保存できたら true
    const deliver = async (files, title) => {
        if (navigator.canShare?.({ files })) {
            try {
                await navigator.share({ files, title });

                return true;
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return false;
                }
                // 画像を作っているあいだに「押した直後」の扱いが切れると、iPhone では共有の画面を開けない。
                // そのときは、もう一度押してもらえば、作った画像をすぐ共有する
                if (error?.name === 'NotAllowedError') {
                    pendingFiles = { files, title };
                    if (label) {
                        label.textContent = '画像ができました・押して保存';
                    }

                    return null;
                }
            }
        }
        files.forEach(download);

        return true;
    };

    button.addEventListener('click', async () => {
        if (pendingFiles) {
            const { files, title } = pendingFiles;
            pendingFiles = null;
            if (label) {
                label.textContent = defaultLabel;
            }
            if (await deliver(files, title)) {
                markSaved(new Date());
            }

            return;
        }

        button.disabled = true;

        try {
            // 押した時点の最新の獲得状況を取りに行く。つながらないときは、ページを開いたときの状態で作り、そう書いておく
            let stale = false;
            try {
                const controller = new AbortController();
                const timer = window.setTimeout(() => controller.abort(), 6000);
                const response = await fetch(root.dataset.snapshotUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store', signal: controller.signal });
                window.clearTimeout(timer);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                latest = await response.json();
            } catch {
                stale = true;
            }

            const dataAt = new Date(latest.at);
            // 時刻は、アプリのほかの表示と同じ日本時間にそろえる
            const stamp = new Intl.DateTimeFormat('sv-SE', { timeZone: 'Asia/Tokyo', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }).format(dataAt).replace(/[-: ]/g, '').replace(/^(\d{8})(\d{4})$/, '$1-$2');
            // スマホは大きすぎる画像を作れないので、景品が多いときは何枚かに分ける
            const pages = chunk(latest.prizes, ROWS_PER_IMAGE);
            const counts = latest.prizes.reduce((sum, row) => {
                sum[row.status] = (sum[row.status] ?? 0) + 1;

                return sum;
            }, {});
            const files = [];
            for (const [index, rows] of pages.entries()) {
                const blob = await drawSnapshot({ ...latest, prizes: rows }, dataAt, pages.length > 1 ? `（${index + 1}/${pages.length}）` : '', counts, stale);
                const suffix = pages.length > 1 ? `-${index + 1}` : '';
                files.push(new File([blob], `宝探しの控え-${stamp}${suffix}.png`, { type: 'image/png' }));
            }

            if (await deliver(files, `${latest.event}の控え`)) {
                markSaved(new Date());
            }
        } finally {
            button.disabled = false;
        }
    });

    showLast();
    window.setInterval(showLast, 30 * 1000);
}

function chunk(list, size) {
    const pages = [];
    for (let i = 0; i < list.length; i += size) {
        pages.push(list.slice(i, i + size));
    }

    return pages.length ? pages : [[]];
}

function download(file) {
    const url = URL.createObjectURL(file);
    const link = document.createElement('a');
    link.href = url;
    link.download = file.name;
    document.body.append(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 10000);
}

/**
 * 控えの画像を描く。幅 1080px、景品1件につき1行。
 */
function drawSnapshot(data, now, pageLabel, counts, stale) {
    const width = 1080;
    const pagePadding = 48;
    const rowHeight = 46;
    const headerHeight = 210;
    const rows = data.prizes;
    const height = headerHeight + rowHeight * (rows.length + 1) + 60;

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    const font = (size, weight = 400) => `${weight} ${size}px "Hiragino Sans", "Hiragino Kaku Gothic ProN", "Noto Sans JP", "Yu Gothic", sans-serif`;

    ctx.fillStyle = '#fffdf8';
    ctx.fillRect(0, 0, width, height);
    ctx.fillStyle = '#1f2a44';
    ctx.textBaseline = 'middle';


    ctx.font = font(22, 700);
    ctx.fillText(`QR宝探し　景品の獲得状況の控え${pageLabel}`, pagePadding, 50);
    ctx.font = font(36, 800);
    ctx.fillText(fit(ctx, data.event, width - pagePadding * 2), pagePadding, 100);
    ctx.font = font(22, 400);
    ctx.fillStyle = '#4a5571';
    const when = now.toLocaleString('ja-JP', { month: 'long', day: 'numeric', weekday: 'short', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Tokyo' });
    ctx.fillText(`${when} 時点・${data.phase}　未獲得 ${counts['未獲得'] ?? 0}／引き換え待ち ${counts['引き換え待ち'] ?? 0}／引き換え済み ${counts['引き換え済み'] ?? 0}`, pagePadding, 150);
    if (stale) {
        ctx.fillStyle = '#b9402b';
        ctx.font = font(20, 700);
        ctx.fillText('つながらないため、ページを開いた時点の状態です（最新ではありません）', pagePadding, 185);
    }

    const columns = [
        { label: 'No.', x: pagePadding, width: 80 },
        { label: '景品名', x: pagePadding + 80, width: 420 },
        { label: '種類', x: pagePadding + 510, width: 90 },
        { label: '状態', x: pagePadding + 600, width: 150 },
        { label: '獲得者', x: pagePadding + 760, width: width - pagePadding - (pagePadding + 760) },
    ];

    let y = headerHeight;
    ctx.fillStyle = '#1f2a44';
    ctx.fillRect(pagePadding, y + rowHeight - 3, width - pagePadding * 2, 3);
    ctx.font = font(20, 700);
    columns.forEach((column) => ctx.fillText(column.label, column.x, y + rowHeight / 2));
    y += rowHeight;

    rows.forEach((row, index) => {
        if (row.status === '引き換え待ち') {
            ctx.fillStyle = '#fbe9bf';
            ctx.fillRect(pagePadding, y, width - pagePadding * 2, rowHeight);
        } else if (index % 2 === 1) {
            ctx.fillStyle = '#f7f0df';
            ctx.fillRect(pagePadding, y, width - pagePadding * 2, rowHeight);
        }

        ctx.fillStyle = '#1f2a44';
        const middle = y + rowHeight / 2;
        ctx.font = font(22, 800);
        ctx.fillText(String(row.no), columns[0].x, middle);
        ctx.font = font(21, 700);
        ctx.fillText(fit(ctx, row.name, columns[1].width), columns[1].x, middle);
        ctx.font = font(20, 400);
        ctx.fillText(row.kind, columns[2].x, middle);
        ctx.font = font(20, row.status === '引き換え待ち' ? 800 : 400);
        ctx.fillText(row.status, columns[3].x, middle);
        ctx.font = font(20, 400);
        const winner = row.winner ? `${row.winner}（${row.winnerId}）${row.at ? ' ' + row.at : ''}` : '—';
        ctx.fillText(fit(ctx, winner, columns[4].width), columns[4].x, middle);
        y += rowHeight;
    });

    ctx.font = font(18, 400);
    ctx.fillStyle = '#4a5571';
    ctx.fillText('つながらなくなったら、この控えと景品リスト（紙）で続けます。参加者には見せないでください。', pagePadding, y + 34);

    return new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
}

/**
 * 幅に収まらない文字は、後ろを「…」にする。
 */
function fit(ctx, text, maxWidth) {
    if (ctx.measureText(text).width <= maxWidth) {
        return text;
    }

    let cut = text;
    while (cut.length > 1 && ctx.measureText(`${cut}…`).width > maxWidth) {
        cut = cut.slice(0, -1);
    }

    return `${cut}…`;
}
