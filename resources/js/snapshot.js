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
 * 状態ごとの色（控えの画像と、控えのメールでそろえる）。色だけに頼らず、状態の名前も必ず書く。
 */
const STATUS_STYLE = {
    未獲得: { fill: '#eceae4', text: '#4a5571' },
    引き換え待ち: { fill: '#fbe3a6', text: '#1f2a44', row: '#fdf3d6' },
    引き換え済み: { fill: '#d6ece9', text: '#1f5a55', row: '#f1f2f4', muted: true },
    対象外: { fill: '#f1efea', text: '#8a90a2' },
};

/**
 * 控えの画像を描く。幅 1080px。上に集計のカード、その下に No.／景品名／種類／状態／獲得者 の表。
 * 景品名がいちばん広い列で、長い名前も省略せずに折り返す。引き換え待ちの行は黄色、引き換え済みの行は薄い灰色、ハズレの行は灰色の細字。
 */
function drawSnapshot(data, now, pageLabel, counts, stale) {
    const width = 1080;
    const pad = 48;
    const lineHeight = 28;
    const font = (size, weight = 400) => `${weight} ${size}px "Hiragino Sans", "Hiragino Kaku Gothic ProN", "Noto Sans JP", "Yu Gothic", sans-serif`;

    const columns = {
        no: { x: pad, width: 64 },
        name: { x: pad + 64, width: 0 },
        kind: { x: 0, width: 76 },
        status: { x: 0, width: 140 },
        winner: { x: 0, width: 250 },
    };
    columns.winner.x = width - pad - columns.winner.width;
    columns.status.x = columns.winner.x - columns.status.width - 12;
    columns.kind.x = columns.status.x - columns.kind.width - 8;
    columns.name.width = columns.kind.x - columns.name.x - 16;

    // 行の高さを先に決める（景品名の行数で変わる）
    const measure = document.createElement('canvas').getContext('2d');
    const rows = data.prizes.map((row) => {
        const isMiss = row.kind === 'ハズレ';
        measure.font = font(21, isMiss ? 400 : 700);
        // 控えなので、景品名は省略せず全文を折り返して出す
        const lines = wrap(measure, row.name, columns.name.width, Infinity);
        measure.font = font(18, 400);
        const winnerText = row.winner ? `${row.winner} · ${row.winnerId}${row.at ? ' · ' + row.at : ''}` : '—';
        const winnerLines = wrap(measure, winnerText, columns.winner.width, Infinity);
        const lineCount = Math.max(lines.length, winnerLines.length);

        return { ...row, isMiss, lines, winnerLines, height: Math.max(52, 20 + lineCount * lineHeight) };
    });

    const headerBottom = (data.schedule ? 212 : 182) + (stale ? 34 : 0);
    const cardsTop = headerBottom;
    const cardsHeight = 92;
    const tableTop = cardsTop + cardsHeight + 28;
    const tableHeadHeight = 44;
    const height = tableTop + tableHeadHeight + rows.reduce((sum, row) => sum + row.height, 0) + 70;

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#fffdf8';
    ctx.fillRect(0, 0, width, height);

    // 見出し：アプリ名、イベント名、開催予定、集計の時点と状況
    let y = 46;
    ctx.fillStyle = '#4a5571';
    ctx.font = font(20, 700);
    ctx.fillText(`QR宝探し　景品の獲得状況の控え${pageLabel}`, pad, y);
    y += 50;
    ctx.fillStyle = '#1f2a44';
    ctx.font = font(36, 800);
    ctx.fillText(fit(ctx, data.event, width - pad * 2), pad, y);
    ctx.font = font(21, 400);
    ctx.fillStyle = '#4a5571';
    if (data.schedule) {
        y += 46;
        ctx.fillText(`開催予定：${data.schedule}`, pad, y);
    }
    y += 36;
    const when = now.toLocaleString('ja-JP', { month: 'long', day: 'numeric', weekday: 'short', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Tokyo' });
    ctx.fillText(`集計：${when} 時点${data.phase ? '・' + data.phase : ''}`, pad, y);
    if (stale) {
        y += 34;
        ctx.fillStyle = '#b9402b';
        ctx.font = font(20, 700);
        ctx.fillText('つながらないため、ページを開いた時点の状態です（最新ではありません）', pad, y);
    }

    // 集計のカード（未獲得／引き換え待ち／引き換え済み）
    const cards = [
        ['未獲得', '#f3eee2'],
        ['引き換え待ち', STATUS_STYLE.引き換え待ち.fill],
        ['引き換え済み', STATUS_STYLE.引き換え済み.fill],
    ];
    const gap = 16;
    const cardWidth = (width - pad * 2 - gap * 2) / 3;
    cards.forEach(([label, fill], index) => {
        const x = pad + index * (cardWidth + gap);
        roundRect(ctx, x, cardsTop, cardWidth, cardsHeight, 14, fill);
        ctx.fillStyle = '#4a5571';
        ctx.font = font(19, 700);
        ctx.fillText(label, x + 20, cardsTop + 28);
        ctx.fillStyle = '#1f2a44';
        ctx.font = font(38, 800);
        const count = String(counts[label] ?? 0);
        ctx.fillText(count, x + 20, cardsTop + 64);
        const countWidth = ctx.measureText(count).width;
        ctx.font = font(19, 700);
        ctx.fillText('個', x + 26 + countWidth, cardsTop + 68);
    });

    // 表の見出し
    y = tableTop;
    ctx.fillStyle = '#1f2a44';
    ctx.font = font(19, 700);
    ctx.fillText('No.', columns.no.x, y + tableHeadHeight / 2);
    ctx.fillText('景品名', columns.name.x, y + tableHeadHeight / 2);
    ctx.fillText('種類', columns.kind.x, y + tableHeadHeight / 2);
    ctx.fillText('状態', columns.status.x, y + tableHeadHeight / 2);
    ctx.fillText('獲得者', columns.winner.x, y + tableHeadHeight / 2);
    ctx.fillRect(pad, y + tableHeadHeight - 3, width - pad * 2, 3);
    y += tableHeadHeight;

    rows.forEach((row) => {
        const style = STATUS_STYLE[row.status] ?? STATUS_STYLE.未獲得;
        if (style.row) {
            ctx.fillStyle = style.row;
            ctx.fillRect(pad, y, width - pad * 2, row.height);
        }
        ctx.fillStyle = '#e6dfcc';
        ctx.fillRect(pad, y + row.height - 1, width - pad * 2, 1);

        // ハズレは灰色、引き換え済みは少し落ち着いた色にして、引き換え待ちがいちばん目立つようにする
        const textColor = row.isMiss ? '#8a90a2' : (style.muted ? '#4f5875' : '#1f2a44');
        const subColor = row.isMiss ? '#8a90a2' : (style.muted ? '#6b7389' : '#4a5571');
        const middle = y + row.height / 2;

        ctx.fillStyle = textColor;
        ctx.font = font(21, row.isMiss ? 400 : 800);
        ctx.fillText(String(row.no), columns.no.x, middle);

        ctx.font = font(21, row.isMiss ? 400 : 700);
        const firstLine = middle - ((row.lines.length - 1) * lineHeight) / 2;
        row.lines.forEach((line, index) => ctx.fillText(line, columns.name.x, firstLine + index * lineHeight));

        ctx.font = font(18, 400);
        ctx.fillStyle = subColor;
        ctx.fillText(row.kind, columns.kind.x, middle);

        // 状態は色つきの札に、名前も書く
        ctx.font = font(17, row.status === '引き換え待ち' ? 800 : 700);
        const pillWidth = Math.min(columns.status.width, ctx.measureText(row.status).width + 22);
        roundRect(ctx, columns.status.x, middle - 15, pillWidth, 30, 15, style.fill);
        ctx.fillStyle = style.text;
        ctx.fillText(row.status, columns.status.x + 11, middle + 1);

        ctx.font = font(18, 400);
        ctx.fillStyle = row.isMiss ? '#8a90a2' : (style.muted ? '#6b7389' : '#1f2a44');
        const firstWinnerLine = middle - ((row.winnerLines.length - 1) * lineHeight) / 2;
        row.winnerLines.forEach((line, index) => ctx.fillText(line, columns.winner.x, firstWinnerLine + index * lineHeight));

        y += row.height;
    });

    ctx.font = font(18, 400);
    ctx.fillStyle = '#4a5571';
    ctx.fillText('つながらなくなったら、この控えと景品リスト（紙）で続けます。参加者には見せないでください。', pad, y + 38);

    return new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
}

function roundRect(ctx, x, y, w, h, r, fill) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
    ctx.fillStyle = fill;
    ctx.fill();
}

/**
 * 幅に合わせて折り返す（maxLines 行まで。はみ出す分は最後の行を「…」にする）。日本語は1文字ずつ、英数字も1文字ずつ区切る。
 */
function wrap(ctx, text, maxWidth, maxLines) {
    const lines = [];
    let current = '';
    for (const char of Array.from(text)) {
        if (ctx.measureText(current + char).width > maxWidth && current !== '') {
            lines.push(current);
            current = char;
            if (lines.length === maxLines) {
                break;
            }
        } else {
            current += char;
        }
    }
    if (lines.length < maxLines) {
        lines.push(current);

        return lines;
    }
    // 入りきらなかった：最後の行を「…」で終える
    const consumed = Array.from(lines.join('')).length;
    if (consumed < Array.from(text).length) {
        lines[maxLines - 1] = fit(ctx, lines[maxLines - 1] + Array.from(text).slice(consumed).join(''), maxWidth);
    }

    return lines.slice(0, maxLines);
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
