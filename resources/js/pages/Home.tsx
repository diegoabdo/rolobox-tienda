import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { boxesFor, singlesOf } from '@/lib/catalog';
import { money, shortMoney } from '@/lib/format';
import type { Category, Flavor, Product, PromotionBanner, Zone } from '@/types';

type Props = {
    categories: Category[];
    flavors: Flavor[];
    products: Product[];
    promotion: PromotionBanner | null;
    coverage: { local: Zone[]; national: Zone[] };
};

export default function Home({ categories, flavors, products, promotion, coverage }: Props) {
    const boxes = products.filter((p) => p.type === 'box');
    // Solo se muestran las categorías que tienen algo para pedir.
    const shown = categories.filter((category) => flavors.some((f) => f.categoryId === category.id));

    return (
        <>
            <Head title="Panadería artesanal por encargo en Xela" />
            <Hero categories={shown} hasBoxes={boxes.length > 0} />
            {promotion && <PromotionBand promotion={promotion} />}
            <Menu categories={shown} flavors={flavors} products={products} />
            {boxes.length > 0 && <Boxes boxes={boxes} categories={categories} />}
            <HowItWorks />
            <Coverage coverage={coverage} />
            <TrackOrder />
            <Questions flavors={flavors} coverage={coverage} />
        </>
    );
}

/** Círculos de color con el nombre, para las categorías que todavía no tienen foto. */
const TILE_TONES = ['bg-ground text-ink', 'bg-accent text-ground', 'bg-ink text-ground', 'bg-well text-ink'];

function Hero({ categories, hasBoxes }: { categories: Category[]; hasBoxes: boolean }) {
    return (
        <section className="relative overflow-clip" aria-labelledby="titulo">
            <div className="wrap grid items-center gap-y-14 pt-8 pb-10 lg:min-h-[calc(100svh-92px)] lg:grid-cols-12 lg:gap-x-8 lg:py-0">
                <div className="relative z-10 lg:col-span-6" data-reveal-group>
                    <h1 id="titulo" className="display text-[clamp(56px,6.6vw,112px)]" data-reveal>
                        Panadería por encargo.
                    </h1>
                    <p className="mt-10 max-w-[32ch] text-lead leading-snug" data-reveal>
                        Rolls de canela, croissants, focaccias y trenzas, horneados en Xela para el día que elijas.
                    </p>
                    <div className="mt-10 flex flex-wrap gap-3" data-reveal>
                        <a href="#menu" className="btn-primary min-h-14 px-8 text-body">
                            Ver el menú
                        </a>
                        {hasBoxes && (
                            <Link href="/armar-caja" className="btn-outline min-h-14 px-8 text-body">
                                Armar una caja
                            </Link>
                        )}
                    </div>
                </div>

                <div className="relative lg:col-span-6 lg:py-16">
                    <div
                        aria-hidden="true"
                        className="absolute top-1/2 left-[-8%] aspect-square w-[140%] -translate-y-1/2 rounded-full bg-kraft lg:left-[-14%] lg:w-[min(118vh,1080px)]"
                    />
                    <ul className="relative mx-auto grid max-w-[560px] grid-cols-2 gap-4 py-10 sm:gap-6 lg:py-0" aria-label="Categorías">
                        {categories.map((category, i) => (
                            <li key={category.id} className="roll-in" style={{ '--roll-index': i } as React.CSSProperties}>
                                <a href={`#${category.slug}`} className="group block text-center">
                                    {category.image ? (
                                        <>
                                            <img
                                                src={category.image}
                                                alt=""
                                                className="aspect-square w-full rounded-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                                                fetchPriority={i < 2 ? 'high' : undefined}
                                            />
                                            <span className="mt-3 inline-block rounded-full bg-ground px-4 py-1.5 text-body font-extrabold tracking-[-0.03em] text-ink">{category.name}</span>
                                        </>
                                    ) : (
                                        <span
                                            className={`flex aspect-square w-full items-center justify-center rounded-full p-4 text-[clamp(22px,2.6vw,38px)] leading-none font-extrabold tracking-[-0.04em] transition-transform duration-300 group-hover:scale-[1.03] ${TILE_TONES[i % TILE_TONES.length]}`}
                                        >
                                            {category.name}
                                        </span>
                                    )}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </section>
    );
}

function PromotionBand({ promotion }: { promotion: PromotionBanner }) {
    return (
        <section className="bg-ink text-ground" aria-labelledby="promo">
            <div className="wrap flex flex-wrap items-center justify-between gap-x-10 gap-y-6 py-12" data-reveal-group>
                <div data-reveal>
                    <h2 id="promo" className="display text-head">
                        {promotion.title}
                    </h2>
                    <p className="mt-4 text-body">
                        {promotion.firstOrderOnly ? 'Solo en tu primera compra. ' : ''}
                        Escribe el código al pagar
                        {promotion.endsAt ? `; vale hasta el ${promotion.endsAt}` : ''}.
                    </p>
                </div>
                <p className="rounded-full bg-ground px-8 py-4 text-lead font-extrabold tracking-[0.04em] text-accent" data-reveal>
                    <span className="sr-only">Código: </span>
                    {promotion.code}
                </p>
            </div>
        </section>
    );
}

function Menu({ categories, flavors, products }: { categories: Category[]; flavors: Flavor[]; products: Product[] }) {
    /** Presentación elegida en cada categoría (completa, porción, pequeña…); por defecto, la primera. */
    const [chosen, setChosen] = useState<Record<number, number>>({});
    const [sending, setSending] = useState<number | null>(null);

    function addSingle(product: Product, flavor: Flavor) {
        setSending(flavor.id);
        router.post(
            '/carrito',
            { product_id: product.id, flavors: { [flavor.id]: 1 }, quantity: 1 },
            { preserveScroll: true, onFinish: () => setSending(null) },
        );
    }

    return (
        <section id="menu" className="wrap mt-32 scroll-mt-8 lg:mt-48" aria-labelledby="menu-titulo">
            <div className="flex flex-wrap items-baseline justify-between gap-x-8 gap-y-3" data-reveal-group>
                <h2 id="menu-titulo" className="display text-head" data-reveal>
                    El menú
                </h2>
                <p className="text-body text-muted" data-reveal>
                    Pide lo que quieras suelto, o arma una caja.
                </p>
            </div>

            {categories.length === 0 ? (
                <p className="mt-10 bg-surface p-8 text-body">Estamos preparando el menú. Vuelve pronto.</p>
            ) : (
                <>
                    {categories.length > 1 && (
                        <nav aria-label="Categorías del menú" className="z-20 mt-8 bg-ground py-3 lg:sticky lg:top-0">
                            <ul className="flex flex-wrap gap-2">
                                {categories.map((category) => (
                                    <li key={category.id}>
                                        <a
                                            href={`#${category.slug}`}
                                            className="inline-flex min-h-11 items-center rounded-full bg-well px-5 text-body font-semibold whitespace-nowrap transition-colors hover:bg-accent hover:text-ground"
                                        >
                                            {category.name}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    )}

                    <div className="space-y-28 pt-14">
                        {categories.map((category) => {
                            const items = flavors.filter((f) => f.categoryId === category.id);
                            const singles = singlesOf(products, category.id);
                            const presentation = singles.find((p) => p.id === chosen[category.id]) ?? singles[0];

                            return (
                                <section key={category.id} id={category.slug} className="scroll-mt-20" aria-labelledby={`cat-${category.slug}`}>
                                    <div data-reveal-group>
                                        <h3 id={`cat-${category.slug}`} className="display text-[clamp(32px,3.6vw,56px)]" data-reveal>
                                            {category.name}
                                        </h3>
                                        {category.description && (
                                            <p className="mt-4 max-w-[52ch] text-body" data-reveal>
                                                {category.description}
                                            </p>
                                        )}
                                    </div>

                                    {singles.length > 1 && (
                                        <div role="radiogroup" aria-label={`Presentación de ${category.name}`} className="mt-8 flex flex-wrap gap-2">
                                            {singles.map((single) => {
                                                const selected = presentation?.id === single.id;
                                                return (
                                                    <button
                                                        key={single.id}
                                                        type="button"
                                                        role="radio"
                                                        aria-checked={selected}
                                                        onClick={() => setChosen((current) => ({ ...current, [category.id]: single.id }))}
                                                        className={`min-h-12 cursor-pointer rounded-full px-6 text-body font-semibold transition-colors ${
                                                            selected ? 'bg-accent text-ground' : 'bg-well hover:bg-edge'
                                                        }`}
                                                    >
                                                        {single.name} · {shortMoney(single.priceCents)}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    )}

                                    <ul className="mt-12 grid gap-x-16 gap-y-12 md:grid-cols-2" data-reveal-group>
                                        {items.map((flavor) => {
                                            const boxes = boxesFor(products, flavor);
                                            return (
                                                <li key={flavor.id} className="flex gap-5" data-reveal>
                                                    {flavor.image ? (
                                                        <img src={flavor.image} alt="" loading="lazy" className="size-20 shrink-0 rounded-full object-cover sm:size-24" />
                                                    ) : (
                                                        <span
                                                            className="flex size-20 shrink-0 items-center justify-center rounded-full bg-kraft text-[clamp(28px,3vw,40px)] font-extrabold text-ground sm:size-24"
                                                            aria-hidden="true"
                                                        >
                                                            {flavor.name.charAt(0)}
                                                        </span>
                                                    )}
                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex items-baseline justify-between gap-4">
                                                            <h4 className="text-lead font-extrabold tracking-[-0.03em]">{flavor.name}</h4>
                                                            {presentation && (
                                                                <span className="text-body font-semibold tabular-nums">
                                                                    {shortMoney(presentation.priceCents + flavor.extraCents)}
                                                                </span>
                                                            )}
                                                        </div>
                                                        <p className="mt-1 max-w-[42ch] text-body">{flavor.description}</p>
                                                        {flavor.allergens && <p className="mt-1 text-small text-accent">Contiene {flavor.allergens.toLowerCase()}</p>}
                                                        <div className="mt-4 flex flex-wrap items-center gap-x-6 gap-y-3">
                                                            {presentation && (
                                                                <button
                                                                    type="button"
                                                                    className="btn-primary min-h-11 px-5"
                                                                    onClick={() => addSingle(presentation, flavor)}
                                                                    disabled={sending !== null || !presentation.available}
                                                                    aria-label={`Agregar ${presentation.name} de ${flavor.name} al carrito`}
                                                                >
                                                                    {sending === flavor.id ? 'Agregando…' : presentation.available ? 'Agregar al carrito' : 'No disponible'}
                                                                </button>
                                                            )}
                                                            {boxes.length > 0 && (
                                                                <Link
                                                                    href={`/armar-caja?sabor=${flavor.slug}`}
                                                                    className="inline-flex min-h-11 items-center text-body font-semibold underline underline-offset-4 transition-colors hover:text-accent"
                                                                >
                                                                    Ponlo en una caja
                                                                </Link>
                                                            )}
                                                        </div>
                                                    </div>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                </section>
                            );
                        })}
                    </div>
                </>
            )}
        </section>
    );
}

/** Una franja de color por cada caja, con su precio grande y un solo botón. */
const BOX_TONES = ['bg-kraft text-ground', 'bg-well text-ink', 'bg-surface text-ink'];

function Boxes({ boxes, categories }: { boxes: Product[]; categories: Category[] }) {
    return (
        <section className="mt-32 lg:mt-48" aria-labelledby="cajas">
            <div className="wrap mb-12" data-reveal-group>
                <h2 id="cajas" className="display text-head" data-reveal>
                    Cajas para regalar o compartir
                </h2>
                <p className="mt-4 max-w-[52ch] text-body" data-reveal>
                    Elige tú qué lleva cada una. Con tarjeta de regalo si quieres.
                </p>
            </div>
            {boxes.map((box, i) => {
                const category = categories.find((c) => c.id === box.categoryId);
                const tone = BOX_TONES[i % BOX_TONES.length];
                return (
                    <div key={box.id} className={tone}>
                        <div className="wrap grid min-h-[44svh] items-end gap-x-10 gap-y-8 py-10 lg:grid-cols-12 lg:py-14" data-reveal-group>
                            <div className="lg:col-span-7" data-reveal>
                                <h3 className="display text-[clamp(36px,4.4vw,72px)]">{box.name}</h3>
                                <p className="mt-5 max-w-[44ch] text-body">{box.description}</p>
                                <p className="mt-2 text-small">
                                    {box.boxSize} piezas{box.mixed ? ', mezclando categorías' : category ? ` de ${category.name.toLowerCase()}` : ''}
                                    {box.transferPriceCents < box.priceCents && ` · ${money(box.transferPriceCents)} pagando con transferencia`}
                                </p>
                            </div>
                            <div className="flex items-end justify-between gap-6 lg:col-span-5 lg:justify-end lg:gap-10" data-reveal>
                                <p className="display text-[clamp(44px,5vw,80px)] tabular-nums">{shortMoney(box.priceCents)}</p>
                                <Link
                                    href={`/armar-caja?caja=${box.slug}`}
                                    className={`btn mb-1 shrink-0 ${tone.includes('bg-kraft') ? 'bg-ground text-ink hover:bg-surface' : 'bg-accent text-ground hover:bg-accent-strong'}`}
                                >
                                    Armar esta caja
                                </Link>
                            </div>
                        </div>
                    </div>
                );
            })}
        </section>
    );
}

/** Generador fijo para que el dibujo sea igual en cada visita. */
function seeded(seed: number) {
    return () => {
        seed = (seed * 16807) % 2147483647;
        return (seed - 1) / 2147483646;
    };
}

type Dot = { x: number; y: number; r: number; tone: 'pale' | 'soft' | 'accent' };

function processDots(): Dot[] {
    const rand = seeded(7);
    const dots: Dot[] = [];
    for (let i = 0; i < 44; i++) {
        dots.push({ x: 20 + rand() * 340, y: 20 + rand() * 230, r: 5 + rand() * 4, tone: rand() > 0.5 ? 'pale' : 'soft' });
    }
    for (let col = 0; col < 20; col++) {
        for (let row = 0; row < 3; row++) {
            dots.push({ x: 410 + col * 19.5, y: 116 + row * 19, r: 8, tone: 'soft' });
        }
    }
    for (let b = 0; b < 6; b++) {
        const bx = 830 + (b % 3) * 124;
        const by = 50 + Math.floor(b / 3) * 100;
        for (let k = 0; k < 6; k++) {
            dots.push({ x: bx + (k % 3) * 34, y: by + Math.floor(k / 3) * 34, r: 15, tone: 'accent' });
        }
    }
    return dots;
}

const DOTS = processDots();
const TONES = { pale: 'var(--color-well)', soft: 'var(--color-edge)', accent: 'var(--color-accent)' };

const STEPS = [
    ['Eliges', 'Lo que quieras suelto o en caja, el día y el horario. Pagas con tarjeta o transferencia.'],
    ['Horneamos', 'Amasamos y horneamos para la fecha de tu pedido, no antes.'],
    ['Llega', 'A tu puerta en Xela, en su empaque. Sigues el estado con tu código RB.'],
] as const;

function HowItWorks() {
    return (
        <section className="wrap mt-32 lg:mt-48" aria-labelledby="proceso">
            <div className="grid gap-8 lg:grid-cols-12" data-reveal-group>
                <h2 id="proceso" className="display text-head lg:col-span-5" data-reveal>
                    Del horno a tu puerta
                </h2>
                <p className="max-w-[40ch] self-end text-body lg:col-span-5 lg:col-start-8" data-reveal>
                    Horneamos solo lo que se pide. Por eso al pagar eliges el día y el horario de entrega.
                </p>
            </div>

            <div className="mt-16 hidden sm:block" data-reveal>
                <svg viewBox="0 0 1240 270" className="h-auto w-full" aria-hidden="true">
                    {DOTS.map((d, i) => (
                        <circle key={i} cx={d.x} cy={d.y} r={d.r} fill={TONES[d.tone]} />
                    ))}
                </svg>
            </div>

            <div className="mt-12 grid grid-cols-[96px_minmax(0,1fr)] gap-x-6 sm:mt-10 sm:grid-cols-3 sm:gap-x-12">
                <svg viewBox="0 0 270 1240" className="row-span-3 h-auto w-full sm:hidden" aria-hidden="true">
                    {DOTS.map((d, i) => (
                        <circle key={i} cx={d.y} cy={d.x} r={d.r} fill={TONES[d.tone]} />
                    ))}
                </svg>
                <ol className="row-span-3 grid gap-y-10 sm:col-span-3 sm:grid-cols-3 sm:gap-x-12" data-reveal-group>
                    {STEPS.map(([title, text]) => (
                        <li key={title} className="self-center sm:self-start" data-reveal>
                            <h3 className="text-lead font-extrabold tracking-[-0.03em]">{title}</h3>
                            <p className="mt-2 max-w-[32ch] text-body">{text}</p>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}

function Coverage({ coverage }: { coverage: { local: Zone[]; national: Zone[] } }) {
    return (
        <section className="wrap mt-32 grid items-center gap-x-16 gap-y-12 lg:mt-48 lg:grid-cols-2" aria-labelledby="cobertura">
            <figure data-reveal>
                <div className="aspect-square overflow-hidden rounded-full">
                    <img
                        src="/images/xela-panoramica.jpg"
                        alt="Quetzaltenango al amanecer, con el volcán Santa María al fondo"
                        width={1920}
                        height={1079}
                        loading="lazy"
                        className="drift size-full object-cover object-[50%_40%]"
                    />
                </div>
                <figcaption className="mt-4 text-small text-muted">Quetzaltenango y el volcán Santa María al amanecer.</figcaption>
            </figure>

            <div data-reveal-group>
                <h2 id="cobertura" className="display text-head" data-reveal>
                    Repartimos en Xela
                </h2>
                <p className="mt-6 max-w-[40ch] text-body" data-reveal>
                    El envío se suma al pagar, según tu zona, y se ve antes de confirmar.
                </p>
                {coverage.local.length ? (
                    <dl className="mt-10 grid grid-cols-2 gap-x-10 gap-y-3 text-body" data-reveal>
                        {coverage.local.map((zone) => (
                            <div key={zone.id} className="flex justify-between gap-4">
                                <dt>{zone.name}</dt>
                                <dd className="font-semibold tabular-nums">{shortMoney(zone.feeCents)}</dd>
                            </div>
                        ))}
                    </dl>
                ) : (
                    <p className="mt-10 text-body" data-reveal>
                        Pronto anunciamos las zonas.
                    </p>
                )}
                <p className="mt-10 max-w-[44ch] bg-surface p-6 text-body" data-reveal>
                    {coverage.national.length
                        ? `También enviamos por paquetería a ${coverage.national.length} destinos del país, solo con los sabores que aguantan el viaje.`
                        : 'Al resto del país, muy pronto: estamos probando qué sabores llegan perfectos por paquetería.'}
                </p>
            </div>
        </section>
    );
}

function TrackOrder() {
    const form = useForm({ reference: '', phone: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/seguir');
    }

    return (
        <section className="wrap mt-32 lg:mt-48" aria-labelledby="seguir">
            <form
                onSubmit={submit}
                noValidate
                className="relative mx-auto grid max-w-[1120px] bg-accent text-ground lg:grid-cols-[46%_auto_minmax(0,1fr)]"
                data-reveal
            >
                <div className="flex flex-col justify-between gap-6 p-6 sm:p-10 lg:p-12">
                    <h2 id="seguir" className="display text-head">
                        ¿Dónde va mi pedido?
                    </h2>
                    <p className="max-w-[34ch] text-body">Escribe el código RB que recibiste al pagar y el WhatsApp que usaste en el pedido.</p>
                </div>

                <div className="relative flex items-center justify-between px-0 lg:flex-col lg:px-0 lg:py-0" aria-hidden="true">
                    <span className="-ml-4 size-8 shrink-0 rounded-full bg-ground lg:-mt-4 lg:ml-0" />
                    <span className="flex flex-1 justify-evenly lg:flex-col lg:items-center lg:self-stretch lg:py-4">
                        {Array.from({ length: 14 }, (_, i) => (
                            <span key={i} className={`size-2 rounded-full bg-ground ${i > 9 ? 'hidden sm:block' : ''}`} />
                        ))}
                    </span>
                    <span className="-mr-4 size-8 shrink-0 rounded-full bg-ground lg:-mb-4 lg:mr-0" />
                </div>

                <div className="space-y-3 p-6 sm:p-10 lg:p-12">
                    <label className="block">
                        <span className="sr-only">Código de pedido</span>
                        <input
                            className="block min-h-14 w-full bg-[#f6c27c] px-4 text-body font-semibold text-ground uppercase placeholder:text-ground/70 placeholder:normal-case focus:outline-3 focus:outline-ground aria-invalid:outline-3 aria-invalid:outline-[#7a1d0c]"
                            value={form.data.reference}
                            onChange={(e) => form.setData('reference', e.target.value)}
                            placeholder="Código, por ejemplo RB-7K2M9Q"
                            autoComplete="off"
                            aria-invalid={!!form.errors.reference}
                        />
                    </label>
                    <label className="block">
                        <span className="sr-only">WhatsApp del pedido</span>
                        <input
                            className="block min-h-14 w-full bg-[#f6c27c] px-4 text-body text-ground placeholder:text-ground/70 focus:outline-3 focus:outline-ground aria-invalid:outline-3 aria-invalid:outline-[#7a1d0c]"
                            type="tel"
                            inputMode="tel"
                            value={form.data.phone}
                            onChange={(e) => form.setData('phone', e.target.value)}
                            placeholder="WhatsApp, 5555 5555"
                            autoComplete="tel"
                            aria-invalid={!!form.errors.phone}
                        />
                    </label>
                    {(form.errors.reference || form.errors.phone) && (
                        <p className="text-body font-semibold text-[#6a1608]" role="alert">
                            {form.errors.reference ?? form.errors.phone}
                        </p>
                    )}
                    <button type="submit" className="btn min-h-14 w-full bg-ground text-ink hover:bg-surface" disabled={form.processing}>
                        {form.processing ? 'Buscando…' : 'Ver mi pedido'}
                    </button>
                </div>
            </form>
        </section>
    );
}

function Questions({ flavors, coverage }: { flavors: Flavor[]; coverage: { local: Zone[]; national: Zone[] } }) {
    const travellers = flavors.filter((f) => f.shipsNationally).map((f) => f.name.toLowerCase());

    const items: { topic: string; question: string; answer: string }[] = [
        {
            topic: 'Pago',
            question: '¿Cómo pago?',
            answer: 'Con Visa o Mastercard, de crédito o débito, en la página segura del procesador: Twist & Rolls nunca ve tu tarjeta. Por transferencia bancaria pagas menos; en el pago ves el total con cada forma.',
        },
        {
            topic: 'Entrega',
            question: '¿Cuándo llega mi pedido?',
            answer: 'Al pagar eliges el día y el horario. Horneamos para esa fecha y te avisamos por WhatsApp cuando sale el reparto.',
        },
        {
            topic: 'Alergias',
            question: '¿Qué alérgenos tienen?',
            answer: 'Cada sabor del menú indica lo que contiene. Si tienes una alergia, escríbenos por WhatsApp antes de pedir y te ayudamos a elegir.',
        },
        {
            topic: 'Cajas',
            question: '¿Puedo armar una caja mixta o mandarla de regalo?',
            answer: 'Sí. En «Armar una caja» eliges qué lleva cada una; las cajas mixtas mezclan categorías. Puedes escribir un mensaje para la tarjeta, de hasta 200 caracteres, y la entregamos a la dirección que indiques.',
        },
        {
            topic: 'Resto del país',
            question: '¿Envían fuera de Xela?',
            answer: coverage.national.length
                ? `Sí, por paquetería con número de guía, solo con los sabores que aguantan el viaje${travellers.length ? `: ${travellers.join(', ')}` : ''}.`
                : `Todavía no. Estamos probando qué productos llegan perfectos por paquetería${travellers.length ? `; por ahora, ${travellers.join(', ')}` : ''}.`,
        },
        {
            topic: 'Cambios',
            question: '¿Puedo cancelar un pedido?',
            answer: 'Horneamos cada pedido por encargo, así que no hay reembolsos por cancelación. Si algo llega mal, escríbenos y lo resolvemos.',
        },
    ];

    const [open, setOpen] = useState(0);

    return (
        <section className="wrap mt-32 lg:mt-48" aria-labelledby="preguntas">
            <div className="mx-auto max-w-[900px]">
                <h2 id="preguntas" className="display text-head" data-reveal>
                    Antes de pedir
                </h2>
                <ul className="mt-20 [--tab-step:44px] sm:[--tab-step:96px] lg:[--tab-step:130px]">
                    {items.map((item, i) => {
                        const isOpen = open === i;
                        const tone = i % 2 === 0 ? 'bg-surface' : 'bg-well';
                        return (
                            <li key={item.topic} className={`relative ${i > 0 ? 'mt-12' : ''} ${tone}`}>
                                <span
                                    className={`absolute -top-10 flex h-10 w-[128px] items-center justify-center rounded-t-full px-4 text-small font-semibold sm:w-[200px] ${tone}`}
                                    style={{ left: `calc(${i} * var(--tab-step))` }}
                                    aria-hidden="true"
                                >
                                    {item.topic}
                                </span>
                                <h3>
                                    <button
                                        type="button"
                                        className="flex min-h-14 w-full cursor-pointer items-center justify-between gap-6 px-5 py-5 text-left text-lead font-semibold tracking-[-0.02em] transition-colors hover:text-accent sm:px-8"
                                        aria-expanded={isOpen}
                                        aria-controls={`respuesta-${i}`}
                                        onClick={() => setOpen(isOpen ? -1 : i)}
                                    >
                                        {item.question}
                                        <span
                                            className={`flex size-9 shrink-0 items-center justify-center rounded-full bg-ground text-accent transition-transform duration-200 ${isOpen ? 'rotate-45' : ''}`}
                                            aria-hidden="true"
                                        >
                                            +
                                        </span>
                                    </button>
                                </h3>
                                <div id={`respuesta-${i}`} hidden={!isOpen} className="px-5 pb-8 sm:px-8">
                                    <p className="max-w-[56ch] text-body">{item.answer}</p>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            </div>
        </section>
    );
}
