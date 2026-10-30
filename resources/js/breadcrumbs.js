/**
 * スマホでパンくずリストが横に収まらないとき、今いるページ（右端）が見えるようにスクロールしておく。
 */
export function scrollBreadcrumbsToCurrent() {
    document.querySelectorAll('[data-breadcrumbs]').forEach((nav) => {
        nav.scrollLeft = nav.scrollWidth;
    });
}
