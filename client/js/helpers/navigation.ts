export const Direction = {
    PREV: 'prev',
    NEXT: 'next',
};

/**
 * autoscroll
 */
export function autoScroll(target) {
    const viewportHeight = document.body.clientHeight;
    const viewportScrollTop = window.scrollY;
    const targetBb = target.getBoundingClientRect();
    const targetTop = window.scrollY + targetBb.top;
    const targetHeight = targetBb.height;

    // scroll down
    if (viewportScrollTop + viewportHeight < targetTop + targetHeight + 80) {
        if (targetHeight > viewportHeight) {
            window.scrollTo({ top: targetTop });
        } else {
            const marginTop = (viewportHeight - targetHeight) / 2;
            const scrollTop = targetTop - marginTop;
            window.scrollTo({ top: scrollTop });
        }
    }

    // scroll up
    if (targetTop <= viewportScrollTop) {
        window.scrollTo({ top: targetTop });
    }
}
