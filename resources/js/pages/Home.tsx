import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { money, shortMoney } from '@/lib/format';
import type { Flavor, Product, PromotionBanner, Zone } from '@/types';

type Props = {
    products: Product[];
    flavors: Flavor[];
    singleProductId: number | null;
    promotion: PromotionBanner | null;
    coverage: { local: Zone[]; national: Zone[] };
};

export default function Home({ products, flavors, singleProductId, promotion, coverage }: Props) {
    const box = products.find((p) => p.type === 'box');
    const single = products.find((p) => p.id === singleProductId);

    return (
        <>
            <Head title="Roles de canela por encargo en Xela" />
            <Hero flavors={flavors} box={box} />
            {promotion && <PromotionBand promotion={promotion} />}
            <Menu flavors={flavors} box={box} single={single} />
            {box && <Plans box={box} single={single} />}
            <HowItWorks />
            <Coverage coverage={coverage} />
            <TrackOrder />
            <Questions flavors={flavors} box={box} single={single} coverage={coverage} />
        </>
    );
}

function Hero({ flavors, box }: { flavors: Flavor[]; box?: Product }) {
    const rolls = flavors.slice(0, 6);

    return (
        <section className="relative overflow-clip" aria-labelledby="titulo">
            <div className="wrap grid items-center gap-y-14 pt-8 pb-10 lg:min-h-[calc(100svh-92px)] lg:grid-cols-12 lg:gap-x-8 lg:py-0">
                <div className="relative z-10 lg:col-span-6" data-reveal-group>
                    <h1 id="titulo" className="display text-[clamp(60px,7.4vw,124px)]" data-reveal>
                        Seis roles, una caja.
                    </h1>
                    <p className="mt-10 max-w-[30ch] text-lead leading-snug" data-reveal>
                        Roles de canela horneados por encargo en Xela. Tú eliges los seis sabores y te la llevamos a casa.
                    </p>
                    {box && (
                        <p className="mt-6 text-lead font-semibold" data-reveal>
                            {shortMoney(box.priceCents)} la caja
                            {box.transferPriceCents < box.priceCents && (
                                <span className="text-muted"> · {shortMoney(box.transferPriceCents)} con transferencia</span>
                            )}
                        </p>
                    )}
                    <div className="mt-10" data-reveal>
                        <Link href="/armar-caja" className="btn-primary min-h-14 px-8 text-body">
                            Armar mi caja
                        </Link>
                    </div>
                </div>

                <div className="relative lg:col-span-6 lg:py-16">
                    <div
                        aria-hidden="true"
                        className="absolute top-1/2 left-[-8%] aspect-square w-[140%] -translate-y-1/2 rounded-full bg-kraft lg:left-[-14%] lg:w-[min(118vh,1080px)]"
                    />
                    <ul className="relative mx-auto grid max-w-[560px] grid-cols-3 gap-3 py-10 sm:gap-5 lg:py-0" aria-label="Los sabores de la caja">
                        {rolls.map((flavor, i) => (
                            <li key={flavor.id} className="roll-in aspect-square" style={{ '--roll-index': i } as React.CSSProperties}>
                                {flavor.image ? (
                                    <img
                                        src={flavor.image}
                                        alt={`Rol de ${flavor.name.toLowerCase()}`}
                                        className="size-full rounded-full object-cover"
                                        fetchPriority={i < 3 ? 'high' : undefined}
                                    />
                                ) : (
                                    <span className="flex size-full items-center justify-center rounded-full bg-ground p-2 text-center text-small font-semibold">
                                        {flavor.name}
                                    </span>
                                )}
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

function Menu({ flavors, box, single }: { flavors: Flavor[]; box?: Product; single?: Product }) {
    const [preview, setPreview] = useState<Flavor | null>(null);
    const [sending, setSending] = useState<number | null>(null);
    const shown = preview ?? flavors[0] ?? null;

    function addSingle(flavor: Flavor) {
        if (!single) return;
        setSending(flavor.id);
        router.post(
            '/carrito',
            { product_id: single.id, flavors: { [flavor.id]: 1 }, quantity: 1 },
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
                    {flavors.length} sabores{single ? ` · ${shortMoney(single.priceCents)} el rol` : ''}
                </p>
            </div>
            <p className="mt-6 max-w-[52ch] text-body">
                Pide los roles sueltos que quieras. Para seis de distintos sabores en caja, ármala a tu gusto.
            </p>

            {flavors.length === 0 ? (
                <p className="mt-10 bg-surface p-8 text-body">Estamos preparando el menú. Vuelve pronto.</p>
            ) : (
                <div className="mt-14 grid items-start gap-x-16 gap-y-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,440px)]">
                    <ul className="space-y-10" data-reveal-group onMouseLeave={() => setPreview(null)}>
                        {flavors.map((flavor) => (
                            <li key={flavor.id} onMouseEnter={() => setPreview(flavor)} onFocus={() => setPreview(flavor)} data-reveal>
                                <div className={`flex gap-5 transition-opacity duration-200 ${preview && preview.id !== flavor.id ? 'lg:opacity-55' : ''}`}>
                                    {flavor.image ? (
                                        <img src={flavor.image} alt="" loading="lazy" className="size-16 shrink-0 rounded-full object-cover sm:size-20" />
                                    ) : (
                                        <div className="size-16 shrink-0 rounded-full bg-kraft sm:size-20" aria-hidden="true" />
                                    )}
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-baseline justify-between gap-4">
                                            <h3 className="text-lead font-extrabold tracking-[-0.03em]">{flavor.name}</h3>
                                            {single && <span className="text-body font-semibold tabular-nums">{shortMoney(single.priceCents)}</span>}
                                        </div>
                                        <p className="mt-1 max-w-[42ch] text-body">{flavor.description}</p>
                                        {flavor.allergens && <p className="mt-1 text-small text-accent">Contiene {flavor.allergens.toLowerCase()}</p>}
                                        <div className="mt-4 flex flex-wrap items-center gap-x-6 gap-y-3">
                                            {single && (
                                                <button
                                                    type="button"
                                                    className="btn-primary min-h-11 px-5"
                                                    onClick={() => addSingle(flavor)}
                                                    disabled={sending !== null}
                                                    aria-label={`Agregar un rol de ${flavor.name} al carrito`}
                                                >
                                                    {sending === flavor.id ? 'Agregando…' : 'Agregar al carrito'}
                                                </button>
                                            )}
                                            {box && (
                                                <Link
                                                    href={`/armar-caja?sabor=${flavor.slug}`}
                                                    className="inline-flex min-h-11 items-center text-body font-semibold underline underline-offset-4 transition-colors hover:text-accent"
                                                >
                                                    Ponlo en una caja
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>

                    <aside className="lg:sticky lg:top-8" aria-label="Arma tu caja">
                        <div className="relative hidden aspect-square overflow-hidden rounded-full bg-kraft lg:block">
                            {flavors.map((flavor) =>
                                flavor.image ? (
                                    <img
                                        key={flavor.id}
                                        src={flavor.image}
                                        alt=""
                                        loading="lazy"
                                        className={`absolute inset-0 size-full object-cover transition-[opacity,scale] duration-[240ms] ease-out ${
                                            shown?.id === flavor.id ? 'scale-100 opacity-100' : 'scale-105 opacity-0'
                                        }`}
                                    />
                                ) : null,
                            )}
                        </div>
                        <p className="mt-5 hidden text-lead font-extrabold tracking-[-0.03em] lg:block" aria-hidden="true">
                            {shown?.name}
                        </p>

                        {box && (
                            <div className="bg-kraft p-6 text-ground sm:p-8 lg:mt-8">
                                <p className="text-lead font-extrabold tracking-[-0.03em]">¿Seis de distintos sabores?</p>
                                <p className="mt-2 text-body">Arma una caja a tu gusto por {shortMoney(box.priceCents)}, con tarjeta de regalo si quieres.</p>
                                <Link href="/armar-caja" className="btn mt-6 w-full bg-ground text-ink hover:bg-surface">
                                    Armar mi caja
                                </Link>
                            </div>
                        )}
                    </aside>
                </div>
            )}
        </section>
    );
}

function Plans({ box, single }: { box: Product; single?: Product }) {
    return (
        <section className="mt-32 grid lg:mt-48 lg:grid-cols-12" aria-label="Precios">
            <div className="flex min-h-[56svh] flex-col lg:min-h-[72svh] bg-kraft px-4 py-10 text-ground sm:px-8 lg:col-span-7 lg:py-12 lg:pl-[max(2rem,calc((100vw-1360px)/2+2rem))]" data-reveal-group>
                <h2 className="display text-head" data-reveal>
                    La caja de seis
                </h2>
                <p className="mt-6 max-w-[28ch] text-lead leading-snug" data-reveal>
                    Para compartir, regalar o acompañar el café.
                </p>
                <p className="mt-6 max-w-[46ch] text-body" data-reveal>
                    Seis roles a tu elección en caja kraft con papel encerado. Si es regalo, va con una tarjeta y tu mensaje.
                    {box.transferPriceCents < box.priceCents && ` Pagando por transferencia, ${money(box.transferPriceCents)}.`}
                </p>
                <div className="mt-auto flex flex-wrap items-end justify-between gap-6 pt-12">
                    <p className="display text-[clamp(80px,10vw,170px)]" data-reveal>
                        {shortMoney(box.priceCents)}
                    </p>
                    <Link href="/armar-caja" className="btn mb-3 bg-ground text-ink hover:bg-surface">
                        Armar mi caja
                    </Link>
                </div>
            </div>

            {single && (
                <div className="flex min-h-[56svh] flex-col lg:min-h-[72svh] bg-well px-4 py-10 sm:px-8 lg:col-span-5 lg:py-12 lg:pr-[max(2rem,calc((100vw-1360px)/2+2rem))]" data-reveal-group>
                    <h2 className="display text-head" data-reveal>
                        Un rol suelto
                    </h2>
                    <p className="mt-6 max-w-[24ch] text-lead leading-snug" data-reveal>
                        Para el antojo de hoy, del sabor que quieras.
                    </p>
                    <p className="mt-6 max-w-[40ch] text-body" data-reveal>
                        Se pide desde el menú y puedes sumar varios.
                        {single.transferPriceCents < single.priceCents && ` Pagando por transferencia, ${money(single.transferPriceCents)}.`}
                    </p>
                    <div className="mt-auto flex flex-wrap items-end justify-between gap-6 pt-12">
                        <p className="display text-[clamp(80px,10vw,170px)]" data-reveal>
                            {shortMoney(single.priceCents)}
                        </p>
                        <a href="#menu" className="btn-primary mb-3">
                            Elegir sabor
                        </a>
                    </div>
                </div>
            )}
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
    ['Eliges', 'Seis sabores o roles sueltos, el día y el horario. Pagas con tarjeta o transferencia.'],
    ['Horneamos', 'Amasamos, enrollamos y horneamos para la fecha de tu pedido, no antes.'],
    ['Llega', 'A tu puerta en Xela, en caja. Sigues el estado con tu código RB.'],
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
                        ¿Dónde va mi caja?
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

function Questions({
    flavors,
    box,
    single,
    coverage,
}: {
    flavors: Flavor[];
    box?: Product;
    single?: Product;
    coverage: { local: Zone[]; national: Zone[] };
}) {
    const travellers = flavors.filter((f) => f.shipsNationally).map((f) => f.name.toLowerCase());
    const prices = [box && `${money(box.transferPriceCents)} la caja`, single && `${money(single.transferPriceCents)} el rol`].filter(Boolean).join(' y ');

    const items: { topic: string; question: string; answer: string }[] = [
        {
            topic: 'Pago',
            question: '¿Cómo pago?',
            answer: `Con Visa o Mastercard, de crédito o débito, en la página segura del procesador: Twist & Rolls nunca ve tu tarjeta. Por transferencia bancaria pagas menos${prices ? `: ${prices}` : ''}.`,
        },
        {
            topic: 'Entrega',
            question: '¿Cuándo llega mi pedido?',
            answer: 'Al pagar eliges el día y el horario. Horneamos para esa fecha y te avisamos por WhatsApp cuando sale el reparto.',
        },
        {
            topic: 'Alergias',
            question: '¿Qué alérgenos tienen?',
            answer: flavors.filter((f) => f.allergens).map((f) => `${f.name}: ${f.allergens?.toLowerCase()}`).join('. ') + '.',
        },
        {
            topic: 'Regalo',
            question: '¿Puedo mandarla de regalo?',
            answer: 'Sí. Al armar tu caja escribe el mensaje para la tarjeta, hasta 200 caracteres, y la entregamos con ella a la dirección que indiques.',
        },
        {
            topic: 'Resto del país',
            question: '¿Envían fuera de Xela?',
            answer: coverage.national.length
                ? `Sí, por paquetería con número de guía, solo con los sabores que aguantan el viaje${travellers.length ? `: ${travellers.join(', ')}` : ''}.`
                : `Todavía no. Estamos probando qué sabores llegan perfectos por paquetería${travellers.length ? `; por ahora, ${travellers.join(', ')}` : ''}.`,
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
