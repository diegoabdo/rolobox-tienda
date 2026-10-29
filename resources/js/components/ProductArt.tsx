/**
 * Dibujos planos de lo que se hornea, para las categorías que todavía no tienen foto.
 * Cada uno es una forma sencilla en tres tonos: la masa, su sombra y el brillo.
 * `tone="light"` se dibuja sobre un fondo oscuro; `tone="dark"`, sobre uno claro.
 */
import type { Category } from '@/types';

export type ArtKind = 'rolls' | 'croissants' | 'focaccias' | 'trenzas' | 'generic';

const PALETTES = {
    light: { dough: '#d9a441', shade: '#a8731f', shine: '#efedea' },
    dark: { dough: '#6b3a1e', shade: '#3f200f', shine: '#efedea' },
} as const;

/** Qué dibujo corresponde a una categoría, por su identificador en la URL. */
export function artFor(slug: string): ArtKind {
    if (slug.includes('roll') || slug.includes('canela')) return 'rolls';
    if (slug.includes('croissant')) return 'croissants';
    if (slug.includes('focaccia')) return 'focaccias';
    if (slug.includes('trenza')) return 'trenzas';
    return 'generic';
}

type Props = { kind: ArtKind; tone: 'light' | 'dark'; className?: string };

export function ProductArt({ kind, tone, className }: Props) {
    const { dough, shade, shine } = PALETTES[tone];

    return (
        <svg viewBox="0 0 400 400" className={className} aria-hidden="true">
            {kind === 'rolls' && <Roll dough={dough} shade={shade} shine={shine} />}
            {kind === 'croissants' && <Croissant dough={dough} shade={shade} shine={shine} />}
            {kind === 'focaccias' && <Focaccia dough={dough} shade={shade} shine={shine} />}
            {kind === 'trenzas' && <Braid dough={dough} shade={shade} shine={shine} />}
            {kind === 'generic' && <Bun dough={dough} shade={shade} shine={shine} />}
        </svg>
    );
}

type Colors = { dough: string; shade: string; shine: string };

/** Un rol visto desde arriba: la masa enrollada en espiral, con gotas de glaseado. */
function Roll({ dough, shade, shine }: Colors) {
    return (
        <>
            <circle cx="200" cy="200" r="176" fill={shade} />
            <circle cx="200" cy="200" r="164" fill={dough} />
            <path
                d="M200 200 a14 14 0 0 1 28 0 a28 28 0 0 1 -56 0 a42 42 0 0 1 84 0 a56 56 0 0 1 -112 0 a70 70 0 0 1 140 0 a84 84 0 0 1 -168 0 a98 98 0 0 1 196 0 a112 112 0 0 1 -224 0 a126 126 0 0 1 252 0"
                fill="none"
                stroke={shade}
                strokeWidth="13"
                strokeLinecap="round"
            />
            {[
                [120, 92, 11],
                [286, 86, 9],
                [328, 214, 12],
                [96, 268, 9],
                [240, 322, 11],
                [170, 150, 7],
            ].map(([x, y, r]) => (
                <circle key={`${x}-${y}`} cx={x} cy={y} r={r} fill={shine} />
            ))}
        </>
    );
}

/** Un croissant de lado: siete tramos de masa a lo largo de una media luna. */
function Croissant({ dough, shade, shine }: Colors) {
    const segments = Array.from({ length: 7 }, (_, i) => {
        const t = i / 6;
        const angle = (200 + t * 140) * (Math.PI / 180);
        const size = 1 - Math.abs(t - 0.5) * 1.15;
        return {
            x: 200 + 128 * Math.cos(angle),
            y: 268 + 128 * Math.sin(angle),
            rx: 24 + 36 * size,
            ry: 50 + 30 * size,
            rotate: (angle * 180) / Math.PI + 90,
        };
    });

    return (
        <>
            {segments.map((s, i) => (
                <ellipse key={i} cx={s.x} cy={s.y} rx={s.rx} ry={s.ry} transform={`rotate(${s.rotate} ${s.x} ${s.y})`} fill={dough} stroke={shade} strokeWidth="7" />
            ))}
            {segments.slice(1, 6).map((s, i) => (
                <ellipse key={`b${i}`} cx={s.x} cy={s.y - 10} rx={s.rx * 0.32} ry={s.ry * 0.34} transform={`rotate(${s.rotate} ${s.x} ${s.y})`} fill={shine} opacity="0.55" />
            ))}
        </>
    );
}

/** Una trenza horizontal: siete cruces de masa con semillas. */
function Braid({ dough, shade, shine }: Colors) {
    return (
        <g transform="translate(200 200) scale(1.12) translate(-200 -200)">
            <rect x="28" y="150" width="344" height="100" rx="50" fill={shade} />
            {Array.from({ length: 7 }, (_, i) => (
                <rect
                    key={i}
                    x={56 + i * 46 - 62}
                    y={200 - 27}
                    width="124"
                    height="54"
                    rx="27"
                    transform={`rotate(${i % 2 ? 46 : -46} ${56 + i * 46} 200)`}
                    fill={dough}
                    stroke={shade}
                    strokeWidth="6"
                />
            ))}
            {[84, 150, 226, 296, 340].map((x, i) => (
                <ellipse key={x} cx={x} cy={i % 2 ? 176 : 224} rx="5" ry="3.5" fill={shine} />
            ))}
        </g>
    );
}

/** Una focaccia entera: borde dorado, hoyuelos de aceite, romero y sal. */
function Focaccia({ dough, shade, shine }: Colors) {
    const dimples = Array.from({ length: 12 }, (_, i) => ({ x: 92 + (i % 4) * 72 + (Math.floor(i / 4) % 2) * 20, y: 150 + Math.floor(i / 4) * 52 }));

    return (
        <>
            <rect x="34" y="96" width="332" height="208" rx="34" fill={shade} />
            <rect x="46" y="108" width="308" height="184" rx="26" fill={dough} />
            {dimples.map((d, i) => (
                <circle key={i} cx={d.x} cy={d.y} r="10" fill={shade} />
            ))}
            {[
                [120, 130, -30],
                [244, 124, 24],
                [310, 196, -50],
                [148, 232, 40],
                [264, 262, -20],
            ].map(([x, y, r]) => (
                <ellipse key={`${x}-${y}`} cx={x} cy={y} rx="22" ry="5" transform={`rotate(${r} ${x} ${y})`} fill={shine} />
            ))}
            {[96, 188, 214, 290, 334, 168, 250].map((x, i) => (
                <circle key={x} cx={x} cy={[170, 150, 214, 246, 150, 270, 200][i]} r="3" fill={shine} />
            ))}
        </>
    );
}

/** Un pan redondo con tres cortes: el dibujo neutro de las categorías que todavía no tienen el suyo. */
function Bun({ dough, shade, shine }: Colors) {
    return (
        <>
            <ellipse cx="200" cy="214" rx="170" ry="128" fill={shade} />
            <ellipse cx="200" cy="200" rx="162" ry="122" fill={dough} />
            {[-70, 0, 70].map((x) => (
                <path key={x} d={`M${200 + x - 22} 150 q22 44 0 100`} fill="none" stroke={shade} strokeWidth="12" strokeLinecap="round" />
            ))}
            {[
                [112, 176],
                [296, 168],
                [150, 262],
                [262, 258],
            ].map(([x, y]) => (
                <ellipse key={`${x}-${y}`} cx={x} cy={y} rx="7" ry="4.5" fill={shine} />
            ))}
        </>
    );
}

/** La imagen de una categoría: su foto si la subieron desde el panel, y si no, su dibujo. */
export function CategoryArt({ category, tone, className, imageClassName }: { category: Category; tone: 'light' | 'dark'; className?: string; imageClassName?: string }) {
    if (category.image) {
        return <img src={category.image} alt="" loading="lazy" className={imageClassName ?? className} />;
    }

    return <ProductArt kind={artFor(category.slug)} tone={tone} className={className} />;
}