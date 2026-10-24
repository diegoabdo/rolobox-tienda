export function LogoMark({ size = 44 }: { size?: number }) {
    return (
        <svg width={size} height={size} viewBox="0 0 80 80" fill="none" aria-hidden="true">
            <path
                d="M65 44c10 18-8 29-27 27C17 69 7 54 10 37 13 18 28 8 44 11c16 3 25 16 20 29-4 12-18 17-29 11-9-5-12-15-6-23 5-7 15-8 21-2 5 5 3 12-3 14-4 2-8-1-7-5"
                stroke="#BE531E"
                strokeWidth="6"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path d="M15 56c13 12 36 11 49-2" stroke="#BE531E" strokeWidth="4" strokeLinecap="round" />
        </svg>
    );
}
