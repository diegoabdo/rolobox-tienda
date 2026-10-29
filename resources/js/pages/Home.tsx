import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { CategoryArt } from '@/components/ProductArt';
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

/**
 * Los campos de color de la página: miel, café y marfil (el color del texto, usado como fondo).
 * Cada uno trae sus propias clases para el texto, las fichas y los botones, así cada capítulo se lee bien.
 */
const FIELDS = [
    { field: 'bg-honey text-ground', art: 'dark', chip: 'bg-ground/15 hover:bg-ground/25', chipOn: 'bg-ground text-ink', row: 'bg-ground/10', button: 'bg-ground text-ink hover:bg-surface', soft: 'text-ground/75' },
    { field: 'bg-surface text-ink', art: 'light', chip: 'bg-well hover:bg-edge', chipOn: 'bg-honey text-ground', row: 'bg-well', button: 'bg-honey text-ground hover:bg-accent-strong', soft: 'text-muted' },
    { field: 'bg-ink text-ground', art: 'dark', chip: 'bg-ground/10 hover:bg-ground/20', chipOn: 'bg-ground text-ink', row: 'bg-ground/5', button: 'bg-ground text-ink hover:bg-surface', soft: 'text-ground/75' },
] as const;

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
            <Questions flavors={flavors} coverage={coverage} />
        </>
    );
}

/** Cómo se acomodan las categorías de la portada según cuántas sean. */
const TIERS = {
    tall: {
        list: 'grid-cols-2 lg:flex lg:h-[min(46svh,440px)]',
        item: 'lg:min-w-0 lg:flex-1',
        link: 'min-h-[300px] gap-6 p-5 sm:p-7',
        name: 'text-[clamp(24px,2.3vw,36px)]',
        art: 'max-w-[300px]',
    },
    mid: {
        list: 'grid-cols-2 lg:grid-cols-4',
        item: '',
        link: 'min-h-[240px] gap-4 p-5',
        name: 'text-[clamp(22px,2vw,30px)]',
        art: 'max-w-[190px]',
    },
    dense: {
        list: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-8',
        item: '',
        link: 'min-h-[150px] gap-2 p-3 sm:p-4',
        name: 'text-[clamp(16px,1.4vw,22px)]',
        art: 'max-w-[84px]',
    },
} as const;

/** Portada: el título arriba y una vitrina con una columna de color por categoría. */
function Hero({ categories, hasBoxes }: { categories: Category[]; hasBoxes: boolean }) {
    // El cuarto color de las vitrinas es una miel más oscura, para que ninguna columna repita a su vecina.
    const cases = ['bg-honey text-ground', 'bg-ink text-ground', 'bg-well text-ink', 'bg-honey-deep text-ground'];
    const arts = ['dark', 'dark', 'light', 'dark'] as const;
    // Con pocas categorías son vitrinas altas; con más, mosaicos más pequeños. Siempre se ven todas, sin «ver más».
    const tier = categories.length <= 4 ? 'tall' : categories.length <= 8 ? 'mid' : 'dense';

    return (
        <section aria-labelledby="titulo">
            <div className="wrap grid items-end gap-x-10 gap-y-8 pt-6 pb-10 lg:grid-cols-12 lg:pt-10 lg:pb-12">
                <h1 id="titulo" className="display text-[clamp(36px,9vw,52px)] lg:col-span-8 lg:text-[clamp(48px,5.4vw,84px)]">
                    Panadería
                    <br />
                    por encargo
                </h1>
                <div className="lg:col-span-4" data-reveal-group>
                    <p className="max-w-[34ch] text-lead leading-snug" data-reveal>
                        Rolls de canela, croissants, focaccias y trenzas, horneados en Xela para el día que elijas.
                    </p>
                    <div className="mt-6 flex flex-wrap items-center gap-x-8 gap-y-3" data-reveal>
                        <a href="#menu" className="btn-primary min-h-14 px-8 text-body">
                            Ver el menú
                        </a>
                        {hasBoxes && (
                            <Link href="/armar-caja" className="inline-flex min-h-11 items-center text-body font-bold underline underline-offset-4">
                                Armar una caja
                            </Link>
                        )}
                    </div>
                </div>
            </div>

            <ul className={`wrap grid gap-3 ${TIERS[tier].list}`} aria-label="Categorías">
                {categories.map((category, i) => (
                    <li key={category.id} className={`${cases[i % cases.length]} overflow-clip rounded-3xl ${TIERS[tier].item}`}>
                        <a href={`#${category.slug}`} className={`group flex h-full flex-col justify-between ${TIERS[tier].link}`}>
                            <span className={`display ${TIERS[tier].name}`}>{category.name}</span>
                            <CategoryArt
                                category={category}
                                tone={arts[i % arts.length]}
                                className={`mx-auto w-full transition-transform duration-500 ease-out group-hover:rotate-[-6deg] group-hover:scale-105 ${TIERS[tier].art}`}
                                imageClassName={`aspect-square w-full rounded-2xl object-cover transition-transform duration-500 ease-out group-hover:scale-105 ${TIERS[tier].art}`}
                            />
                        </a>
                    </li>
                ))}
            </ul>
        </section>
    );
}

function PromotionBand({ promotion }: { promotion: PromotionBanner }) {
    return (
        <section className="wrap mt-3" aria-labelledby="promo">
            <div className="flex flex-wrap items-center justify-between gap-x-10 gap-y-6 rounded-3xl bg-ink px-6 py-10 text-ground sm:px-10 lg:px-14" data-reveal-group>
                <div data-reveal>
                    <h2 id="promo" className="display text-head">
                        {promotion.title}
                    </h2>
                    <p className="mt-3 text-body">
                        {promotion.firstOrderOnly ? 'Solo en tu primera compra. ' : ''}
                        Escribe el código al pagar
                        {promotion.endsAt ? `; vale hasta el ${promotion.endsAt}` : ''}.
                    </p>
                </div>
                <p className="display bg-ground px-8 py-4 text-[clamp(26px,2.4vw,36px)] text-honey" data-reveal>
                    <span className="sr-only">Código: </span>
                    {promotion.code}
                </p>
            </div>
        </section>
    );
}

/** El menú: un capítulo de color por categoría, con su dibujo fijo a un lado y la lista al otro. */
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
        <section id="menu" className="mt-3 scroll-mt-0" aria-label="El menú">
            {categories.length > 1 && (
                <nav aria-label="Categorías del menú" className="z-30 bg-ground py-3 lg:sticky lg:top-0">
                    <ul className="wrap flex flex-wrap gap-2">
                        {categories.map((category) => (
                            <li key={category.id}>
                                <a
                                    href={`#${category.slug}`}
                                    className="inline-flex min-h-10 items-center rounded-xl bg-well px-4 text-small font-medium whitespace-nowrap transition-colors hover:bg-honey hover:text-ground"
                                >
                                    {category.name}
                                </a>
                            </li>
                        ))}
                    </ul>
                </nav>
            )}

            {categories.length === 0 && <p className="wrap my-24 bg-surface p-8 text-body">Estamos preparando el menú. Vuelve pronto.</p>}

            {categories.map((category, index) => {
                const tone = FIELDS[index % FIELDS.length];
                const items = flavors.filter((f) => f.categoryId === category.id);
                const singles = singlesOf(products, category.id);
                const presentation = singles.find((p) => p.id === chosen[category.id]) ?? singles[0];
                const many = items.length > 8;
                const compact = categories.length > 6;

                return (
                    <section key={category.id} id={category.slug} className="wrap mt-3 scroll-mt-4 lg:scroll-mt-28" aria-labelledby={`cat-${category.slug}`}>
                        <div className={`${tone.field} grid gap-x-16 gap-y-8 rounded-3xl px-5 sm:px-10 lg:grid-cols-12 lg:px-14 ${compact ? 'py-10 lg:py-12' : 'py-14 lg:py-20'}`}>
                            <div className={many ? 'lg:col-span-4' : 'lg:col-span-5'}>
                                <div className="lg:sticky lg:top-24" data-reveal-group>
                                    <h2 id={`cat-${category.slug}`} className={`display ${compact ? 'text-[clamp(30px,3vw,48px)]' : 'text-[clamp(36px,4.2vw,64px)]'}`} data-reveal>
                                        {category.name}
                                    </h2>
                                    {category.description && (
                                        <p className="mt-5 max-w-[34ch] text-lead leading-snug" data-reveal>
                                            {category.description}
                                        </p>
                                    )}

                                    {singles.length > 1 && (
                                        <div role="radiogroup" aria-label={`Presentación de ${category.name}`} className="mt-8 flex flex-wrap gap-2" data-reveal>
                                            {singles.map((single) => {
                                                const selected = presentation?.id === single.id;
                                                return (
                                                    <button
                                                        key={single.id}
                                                        type="button"
                                                        role="radio"
                                                        aria-checked={selected}
                                                        onClick={() => setChosen((current) => ({ ...current, [category.id]: single.id }))}
                                                        className={`min-h-12 cursor-pointer px-5 text-body font-medium transition-colors ${selected ? tone.chipOn : tone.chip} rounded-xl`}
                                                    >
                                                        {single.name} · {shortMoney(single.priceCents)}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    )}

                                    <CategoryArt
                                        category={category}
                                        tone={tone.art}
                                        className={`hidden w-full lg:block ${compact ? 'mt-6 max-w-[220px]' : 'mt-10 max-w-[420px]'}`}
                                        imageClassName={`hidden aspect-square w-full rounded-3xl object-cover lg:block ${compact ? 'mt-6 max-w-[220px]' : 'mt-10 max-w-[420px]'}`}
                                    />
                                </div>
                            </div>

                            <ul className={many ? 'grid content-start gap-3 lg:col-span-8 xl:grid-cols-2' : 'space-y-3 lg:col-span-7'} data-reveal-group>
                                {items.map((flavor) => {
                                    const inBoxes = boxesFor(products, flavor);
                                    return (
                                        <li key={flavor.id} className={`flex gap-5 p-5 sm:p-6 ${tone.row} rounded-2xl`} data-reveal>
                                            {flavor.image && <img src={flavor.image} alt="" loading="lazy" className="size-20 shrink-0 object-cover sm:size-28 rounded-xl" />}
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-baseline justify-between gap-4">
                                                    <h3 className="text-lead leading-tight font-bold">{flavor.name}</h3>
                                                    {presentation && <span className="text-lead font-bold tabular-nums">{shortMoney(presentation.priceCents + flavor.extraCents)}</span>}
                                                </div>
                                                <p className="mt-2 max-w-[46ch] text-body">{flavor.description}</p>
                                                {flavor.allergens && <p className={`mt-1 text-small ${tone.soft}`}>Contiene {flavor.allergens.toLowerCase()}.</p>}
                                                <div className="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2">
                                                    {presentation && (
                                                        <button
                                                            type="button"
                                                            className={`btn min-h-11 px-5 ${tone.button}`}
                                                            onClick={() => addSingle(presentation, flavor)}
                                                            disabled={sending !== null || !presentation.available}
                                                            aria-label={`Agregar ${presentation.name} de ${flavor.name} al carrito`}
                                                        >
                                                            {sending === flavor.id ? 'Agregando…' : presentation.available ? 'Agregar' : 'No disponible'}
                                                        </button>
                                                    )}
                                                    {inBoxes.length > 0 && (
                                                        <Link
                                                            href={`/armar-caja?sabor=${flavor.slug}`}
                                                            className="inline-flex min-h-11 items-center text-body font-medium underline underline-offset-4 hover:opacity-70"
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
                        </div>
                    </section>
                );
            })}
        </section>
    );
}

/** Las cajas: una franja por caja, con el precio y el botón siempre a la derecha. */
function Boxes({ boxes, categories }: { boxes: Product[]; categories: Category[] }) {
    const bands = ['bg-honey text-ground', 'bg-well text-ink', 'bg-ink text-ground'];
    const buttons = ['bg-ground text-ink hover:bg-surface', 'bg-honey text-ground hover:bg-accent-strong', 'bg-ground text-ink hover:bg-surface'];

    return (
        <section aria-labelledby="cajas">
            <div>
                <div className="wrap py-20 lg:py-28" data-reveal-group>
                    <h2 id="cajas" className="display text-[clamp(34px,4vw,60px)]" data-reveal>
                        Cajas para regalar
                    </h2>
                    <p className="mt-5 max-w-[46ch] text-lead leading-snug" data-reveal>
                        Tú eliges qué lleva cada una, y puedes sumar una tarjeta con tu mensaje.
                    </p>
                </div>
            </div>
            {boxes.map((box, i) => {
                const category = categories.find((c) => c.id === box.categoryId);
                return (
                    <div key={box.id} className="wrap mt-3">
                        <div className={`${bands[i % bands.length]} grid items-center gap-x-10 gap-y-6 rounded-3xl px-6 py-10 sm:px-10 lg:grid-cols-12 lg:px-14 lg:py-14`} data-reveal-group>
                            <div className="lg:col-span-6" data-reveal>
                                <h3 className="display text-[clamp(26px,2.8vw,40px)]">{box.name}</h3>
                                <p className="mt-3 max-w-[44ch] text-body">{box.description}</p>
                                <p className="mt-1 text-small opacity-80">
                                    {box.boxSize} piezas{box.mixed ? ', mezclando categorías' : category ? ` de ${category.name.toLowerCase()}` : ''}
                                    {box.transferPriceCents < box.priceCents && ` · ${money(box.transferPriceCents)} con transferencia`}
                                </p>
                            </div>
                            <div className="flex items-center justify-between gap-6 lg:col-span-6 lg:justify-end lg:gap-10" data-reveal>
                                <p className="display text-[clamp(36px,3.4vw,52px)] tabular-nums">{shortMoney(box.priceCents)}</p>
                                <Link href={`/armar-caja?caja=${box.slug}`} className={`btn min-h-14 shrink-0 px-8 ${buttons[i % buttons.length]}`}>
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

const STEPS = [
    ['Eliges', 'Lo que quieras suelto o en caja, el día y el horario. Pagas con tarjeta o transferencia.'],
    ['Horneamos', 'Amasamos y horneamos para la fecha de tu pedido, no antes.'],
    ['Llega', 'A tu puerta en Xela, en su empaque. Sigues el estado con tu código RB.'],
] as const;

/** Tres pasos, en una fila compacta. */
function HowItWorks() {
    return (
        <section className="wrap mt-24 lg:mt-32" aria-labelledby="proceso">
            <h2 id="proceso" className="display text-[clamp(32px,3.4vw,52px)]" data-reveal>
                Del horno a tu puerta
            </h2>
            <ol className="mt-8 grid gap-3 md:grid-cols-3" data-reveal-group>
                {STEPS.map(([title, text], i) => (
                    <li key={title} className={`rounded-3xl p-6 sm:p-8 ${i === 0 ? 'bg-surface' : i === 1 ? 'bg-well' : 'bg-honey text-ground'}`} data-reveal>
                        <h3 className="display text-[clamp(22px,2vw,30px)]">{title}</h3>
                        <p className="mt-3 max-w-[34ch] text-body">{text}</p>
                    </li>
                ))}
            </ol>
        </section>
    );
}

/** Xela: la foto grande, con la tarjeta de zonas puesta a su lado. */
function Coverage({ coverage }: { coverage: { local: Zone[]; national: Zone[] } }) {
    return (
        <>
            <section className="wrap mt-24 grid gap-3 lg:mt-32 lg:grid-cols-12" aria-labelledby="cobertura">
                <figure className="lg:col-span-7" data-reveal>
                    <img
                        src="/images/xela-parque-central.jpg"
                        alt="El Parque Central de Quetzaltenango: el kiosco, el teatro y la catedral"
                        width={1920}
                        height={755}
                        loading="lazy"
                        className="h-full min-h-[320px] w-full object-cover rounded-3xl"
                    />
                </figure>
                <div className="rounded-3xl bg-honey p-6 text-ground sm:p-10 lg:col-span-5" data-reveal-group>
                    <h2 id="cobertura" className="display text-[clamp(32px,3.4vw,52px)]" data-reveal>
                        Repartimos en Xela
                    </h2>
                    <p className="mt-5 max-w-[36ch] text-body" data-reveal>
                        El envío se suma al pagar, según tu zona, y se ve antes de confirmar.
                    </p>
                    {coverage.local.length ? (
                        <dl className="mt-8 grid grid-cols-2 gap-x-8 gap-y-2 text-body" data-reveal>
                            {coverage.local.map((zone) => (
                                <div key={zone.id} className="flex justify-between gap-4">
                                    <dt>{zone.name}</dt>
                                    <dd className="font-bold tabular-nums">{shortMoney(zone.feeCents)}</dd>
                                </div>
                            ))}
                        </dl>
                    ) : (
                        <p className="mt-8 text-body" data-reveal>
                            Pronto anunciamos las zonas.
                        </p>
                    )}
                    <p className="mt-8 bg-ground/15 p-4 text-body rounded-2xl" data-reveal>
                        {coverage.national.length
                            ? `También enviamos por paquetería a ${coverage.national.length} destinos del país, solo con lo que aguanta el viaje.`
                            : 'Al resto del país, muy pronto: estamos probando qué productos llegan perfectos por paquetería.'}
                    </p>
                </div>
            </section>
        </>
    );
}

/** Las preguntas, como texto para leer y no como acordeón. */
function Questions({ flavors, coverage }: { flavors: Flavor[]; coverage: { local: Zone[]; national: Zone[] } }) {
    const travellers = flavors.filter((f) => f.shipsNationally).map((f) => f.name.toLowerCase());

    const items: { question: string; answer: string }[] = [
        {
            question: '¿Cómo pago?',
            answer: 'Con Visa o Mastercard, de crédito o débito, en la página segura del procesador: Twist & Rolls nunca ve tu tarjeta. Por transferencia bancaria pagas menos; en el pago ves el total con cada forma.',
        },
        {
            question: '¿Cuándo llega mi pedido?',
            answer: 'Al pagar eliges el día y el horario. Horneamos para esa fecha y te avisamos por WhatsApp cuando sale el reparto.',
        },
        {
            question: '¿Qué alérgenos tienen?',
            answer: 'Cada sabor del menú indica lo que contiene. Si tienes una alergia, escríbenos por WhatsApp antes de pedir y te ayudamos a elegir.',
        },
        {
            question: '¿Puedo mandar una caja de regalo?',
            answer: 'Sí. En «Armar una caja» eliges los sabores de cada caja. Puedes escribir un mensaje para la tarjeta, de hasta 200 caracteres, y la entregamos a la dirección que indiques.',
        },
        {
            question: '¿Envían fuera de Xela?',
            answer: coverage.national.length
                ? `Sí, por paquetería con número de guía, solo con lo que aguanta el viaje${travellers.length ? `: ${travellers.join(', ')}` : ''}.`
                : `Todavía no. Estamos probando qué productos llegan perfectos por paquetería${travellers.length ? `; por ahora, ${travellers.join(', ')}` : ''}.`,
        },
        {
            question: '¿Puedo cancelar un pedido?',
            answer: 'Horneamos cada pedido por encargo, así que no hay reembolsos por cancelación. Si algo llega mal, escríbenos y lo resolvemos.',
        },
    ];

    return (
        <section className="wrap mt-24 grid gap-x-12 gap-y-12 lg:mt-36 lg:grid-cols-12" aria-labelledby="preguntas">
            <h2 id="preguntas" className="display text-head lg:col-span-5" data-reveal>
                Antes de pedir
            </h2>
            <dl className="space-y-10 lg:col-span-7" data-reveal-group>
                {items.map((item) => (
                    <div key={item.question} data-reveal>
                        <dt className="text-lead font-bold">{item.question}</dt>
                        <dd className="mt-2 max-w-[60ch] text-body">{item.answer}</dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}
