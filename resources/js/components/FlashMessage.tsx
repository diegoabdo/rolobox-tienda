import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export function FlashMessage() {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState<string | null>(flash?.status ?? null);

    useEffect(() => {
        setVisible(flash?.status ?? null);
        if (!flash?.status) return;
        const timer = window.setTimeout(() => setVisible(null), 4500);
        return () => window.clearTimeout(timer);
    }, [flash?.status]);

    return (
        <div role="status" aria-live="polite" className="pointer-events-none fixed inset-x-0 bottom-5 z-50 flex justify-center px-4">
            {visible && (
                <p className="pointer-events-auto rounded-full bg-ink px-5 py-3 text-sm font-semibold text-cream shadow-lg">{visible}</p>
            )}
        </div>
    );
}
