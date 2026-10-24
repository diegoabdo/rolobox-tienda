import type { SVGProps } from 'react';

type IconProps = SVGProps<SVGSVGElement> & { size?: number };

function base({ size = 20, ...props }: IconProps) {
    return {
        width: size,
        height: size,
        viewBox: '0 0 24 24',
        fill: 'none',
        stroke: 'currentColor',
        strokeWidth: 2,
        strokeLinecap: 'round' as const,
        strokeLinejoin: 'round' as const,
        'aria-hidden': true,
        ...props,
    };
}

export const BagIcon = (p: IconProps) => (
    <svg {...base(p)}>
        <path d="M6 7h12l-1 13H7L6 7z" />
        <path d="M9 7a3 3 0 0 1 6 0" />
    </svg>
);

export const CardIcon = (p: IconProps) => (
    <svg {...base(p)}>
        <rect x="3" y="6" width="18" height="12" rx="2" />
        <path d="M3 10h18" />
    </svg>
);

export const PinIcon = (p: IconProps) => (
    <svg {...base(p)}>
        <path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z" />
        <circle cx="12" cy="9" r="2.5" />
    </svg>
);

export const BoxIcon = (p: IconProps) => (
    <svg {...base(p)}>
        <path d="M3 7l9-4 9 4-9 4-9-4z" />
        <path d="M3 7v10l9 4 9-4V7" />
    </svg>
);

export const LockIcon = (p: IconProps) => (
    <svg {...base(p)}>
        <rect x="5" y="11" width="14" height="10" rx="2" />
        <path d="M8 11V8a4 4 0 0 1 8 0v3" />
    </svg>
);

export const CheckIcon = (p: IconProps) => (
    <svg {...base(p)}>
        <path d="M5 12.5l4.5 4.5L19 7.5" />
    </svg>
);

export const BackIcon = (p: IconProps) => (
    <svg {...base(p)}>
        <path d="M15 5l-7 7 7 7" />
    </svg>
);

export const ArrowIcon = (p: IconProps) => (
    <svg {...base(p)}>
        <path d="M5 12h14M13 6l6 6-6 6" />
    </svg>
);

export const ChatIcon = (p: IconProps) => (
    <svg {...base(p)}>
        <path d="M21 12a8.5 8.5 0 0 1-12.6 7.4L3 21l1.7-5.2A8.5 8.5 0 1 1 21 12z" />
    </svg>
);
