const SPIRAL =
    'M65 44c10 18-8 29-27 27C17 69 7 54 10 37 13 18 28 8 44 11c16 3 25 16 20 29-4 12-18 17-29 11-9-5-12-15-6-23 5-7 15-8 21-2 5 5 3 12-3 14-4 2-8-1-7-5';

export function LogoMark({ size = 44, className }: { size?: number; className?: string }) {
    return (
        <svg width={size} height={size} viewBox="0 0 80 80" fill="none" aria-hidden="true" className={className}>
            <path d={SPIRAL} stroke="currentColor" strokeWidth="6" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M15 56c13 12 36 11 49-2" stroke="currentColor" strokeWidth="4" strokeLinecap="round" />
        </svg>
    );
}

/** La caja de seis vista desde arriba: seis piezas en fila, con la espiral calada en cada una. */
export function BoxMark({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 1204 200" className={className} role="img" aria-label="Twist & Rolls">
            {Array.from({ length: 6 }, (_, i) => (
                <g key={i} transform={`translate(${i * 201} 0)`}>
                    <circle cx="100" cy="100" r="100" fill="currentColor" />
                    <path
                        d={SPIRAL}
                        transform={`translate(20 20) scale(2) rotate(${i * 60} 40 40)`}
                        fill="none"
                        stroke="var(--color-surface)"
                        strokeWidth="5"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </g>
            ))}
        </svg>
    );
}
