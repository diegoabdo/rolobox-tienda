import { useEffect } from 'react';

/**
 * Hace aparecer los elementos con [data-reveal] al entrar en pantalla.
 * Dentro de un mismo [data-reveal-group] se escalonan en orden.
 */
export function useReveal(key: string) {
    useEffect(() => {
        const root = document.documentElement;
        if (!('IntersectionObserver' in window)) return;
        root.classList.add('js-reveal');

        document.querySelectorAll<HTMLElement>('[data-reveal-group]').forEach((group) => {
            group.querySelectorAll<HTMLElement>('[data-reveal]').forEach((el, i) => el.style.setProperty('--reveal-index', String(i)));
        });

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting && entry.boundingClientRect.top >= 0) return;
                    entry.target.setAttribute('data-revealed', '');
                    observer.unobserve(entry.target);
                });
            },
            { rootMargin: '0px 0px -8% 0px' },
        );

        document.querySelectorAll('[data-reveal]:not([data-revealed])').forEach((el) => observer.observe(el));

        return () => observer.disconnect();
    }, [key]);
}
