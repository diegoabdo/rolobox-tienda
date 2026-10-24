import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { BoxIcon, CardIcon, PinIcon } from '@/components/Icons';
import { money } from '@/lib/format';
import type { Flavor, Product, PromotionBanner, Zone } from '@/types';

type Props = {
    products: Product[];
    flavors: Flavor[];
    singleProductId: number | null;
    promotion: PromotionBanner | null;
    coverage: { local: Zone[]; national: Zone[] };
};

const steps = [
    ['Elige tus sabores', 'Arma una caja de seis o pide roles sueltos.'],
    ['Paga seguro', 'Visa o Mastercard en la página segura del procesador. Nunca vemos tu tarjeta.'],
    ['Horneamos para ti', 'Preparamos tu pedido para el día y la hora que elegiste.'],
    ['Lo recibes en tu casa', 'Sigue el estado de tu pedido con tu código RB.'],
] as const;

export default function Home({ products, flavors, singleProductId, promotion, coverage }: Props) {
    const box = products.find((p) => p.type === 'box');
    const single = products.find((p) => p.id === singleProductId);
    const [adding, setAdding] = useState<number | null>(null);

    function addSingle(flavor: Flavor) {
        if (!single) return;
        setAdding(flavor.id);
        router.post(
            '/carrito',
            { product_id: single.id, flavors: { [flavor.id]: 1 }, quantity: 1 },
            { preserveScroll: true, onFinish: () => setAdding(null) },
        );
    }

    return (
        <>
            <Head title="Roles de canela por encargo" />

            <section className="wrap flex flex-wrap items-center gap-12 py-14 sm:py-16">
                <div className="min-w-0 flex-[1_1_420px]">
                    <p className="eyebrow mb-5 flex items-center gap-2.5">
                        <span className="inline-block size-[7px] rounded-full bg-clay" />
                        Roles artesanales por encargo
                    </p>
                    <h1 className="mb-5 font-display text-[clamp(52px,7vw,84px)] leading-[1.02] font-bold tracking-[-0.04em]">
                        Tu antojo,
                        <br />
                        en una <span className="text-clay">caja.</span>
                    </h1>
                    <p className="mb-8 max-w-md text-lg leading-relaxed">Elige tus sabores, paga con tarjeta y recibe tu caja en casa.</p>
                    <div className="flex flex-wrap gap-3.5">
                        <Link href="/armar-caja" className="btn-primary">
                            Armar mi caja <span aria-hidden="true">→</span>
                        </Link>
                        <Link href="/#menu" className="btn-outline">
                            Ver el menú
                        </Link>
                    </div>
                    <ul className="mt-9 flex flex-wrap gap-x-6 gap-y-3 text-[13px] text-muted">
                        <li className="flex items-center gap-2">
                            <CardIcon size={18} className="text-clay" /> Visa y Mastercard
                        </li>
                        <li className="flex items-center gap-2">
                            <PinIcon size={18} className="text-clay" /> Reparto en Xela
                        </li>
                        <li className="flex items-center gap-2">
                            <BoxIcon size={18} className="text-clay" /> Cajas para regalar
                        </li>
                    </ul>
                </div>
                <div className="relative min-w-0 flex-[1_1_420px]">
                    <div className="aspect-[1.15] overflow-hidden rounded-[48%_48%_10px_10px/38%_38%_10px_10px] bg-[#e9cfae]">
                        <img
                            src="/images/caja.jpg"
                            alt="Caja abierta con seis roles de distintos sabores"
                            className="size-full object-cover"
                            fetchPriority="high"
                        />
                    </div>
                    <div className="absolute -top-3 right-0 flex size-28 rotate-12 flex-col items-center justify-center rounded-full border-[5px] border-cream bg-honey text-center text-[10px] tracking-[0.07em]">
                        UNA CAJA
                        <strong className="mt-1 font-display text-[15px] tracking-tight">seis sabores</strong>
                    </div>
                    <p className="mt-3 text-center text-[11px] text-muted">Imagen de referencia</p>
                </div>
            </section>

            {promotion && (
                <section className="wrap" aria-label="Promoción">
                    <div className="flex flex-wrap items-center justify-between gap-6 rounded-[22px] bg-ink px-8 py-7 text-cream">
                        <div className="flex-[1_1_320px]">
                            <p className="eyebrow mb-2.5 text-honey">Promoción activa</p>
                            <p className="font-display text-3xl leading-tight">{promotion.title}</p>
                            <p className="mt-2.5 text-sm opacity-85">
                                {promotion.firstOrderOnly ? 'Solo en tu primera compra · ' : ''}
                                Se aplica al pagar
                                {promotion.endsAt ? ` · válido hasta el ${promotion.endsAt}` : ''}
                            </p>
                        </div>
                        <span className="rounded-xl border-2 border-dashed border-honey px-5 py-3.5 text-xl font-bold tracking-[0.12em]">
                            {promotion.code}
                        </span>
                    </div>
                </section>
            )}

            <section id="menu" className="wrap scroll-mt-6 py-16">
                <div className="mb-8 flex flex-wrap items-end justify-between gap-5">
                    <div>
                        <p className="eyebrow mb-3.5">El menú</p>
                        <h2 className="font-display text-[44px] leading-[1.1] font-semibold tracking-[-0.03em]">
                            La felicidad tiene
                            <br />
                            forma de espiral.
                        </h2>
                    </div>
                    {single && (
                        <p className="text-sm text-muted">
                            Roles sueltos a {money(single.priceCents)} c/u
                            {single.transferPriceCents < single.priceCents && (
                                <span className="block font-semibold text-leaf">{money(single.transferPriceCents)} pagando con transferencia</span>
                            )}
                        </p>
                    )}
                </div>

                {flavors.length === 0 ? (
                    <p className="card p-6 text-muted">Estamos preparando el menú. Vuelve pronto.</p>
                ) : (
                    <ul className="grid grid-cols-[repeat(auto-fill,minmax(260px,1fr))] gap-6">
                        {flavors.map((flavor) => (
                            <li key={flavor.id} className="card flex flex-col overflow-hidden">
                                {flavor.image ? (
                                    <img src={flavor.image} alt={flavor.name} loading="lazy" className="aspect-[4/3] w-full object-cover" />
                                ) : (
                                    <div className="aspect-[4/3] bg-sand" aria-hidden="true" />
                                )}
                                <div className="flex flex-1 flex-col gap-2.5 p-5">
                                    <h3 className="font-display text-2xl tracking-tight">{flavor.name}</h3>
                                    <p className="flex-1 text-sm leading-relaxed text-muted">{flavor.description}</p>
                                    {flavor.allergens && <p className="text-xs text-muted">Contiene: {flavor.allergens}</p>}
                                    <div className="mt-1 flex flex-wrap items-center justify-between gap-2 border-t border-line pt-3.5">
                                        <Link href={`/armar-caja?sabor=${flavor.slug}`} className="text-sm font-semibold underline underline-offset-4 hover:text-clay">
                                            Ponlo en una caja
                                        </Link>
                                        {single && (
                                            <button
                                                type="button"
                                                className="btn-primary min-h-11 px-4 text-[13px]"
                                                onClick={() => addSingle(flavor)}
                                                disabled={adding !== null}
                                                aria-label={`Agregar un rol de ${flavor.name} al carrito`}
                                            >
                                                {adding === flavor.id ? 'Agregando…' : 'Agregar +'}
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            {box && (
                <section className="wrap pb-16">
                    <div className="card grid items-center overflow-hidden md:grid-cols-2">
                        <img src={box.image ?? '/images/caja.jpg'} alt="" className="aspect-[4/3] size-full object-cover" loading="lazy" />
                        <div className="p-7 sm:p-10">
                            <p className="eyebrow mb-3">Para compartir</p>
                            <h2 className="mb-3 font-display text-4xl font-semibold tracking-tight">{box.name}</h2>
                            <p className="mb-6 leading-relaxed text-muted">{box.description}</p>
                            <div className="flex flex-wrap items-center gap-5">
                                <span className="text-xl font-bold">
                                    {money(box.priceCents)}
                                    {box.transferPriceCents < box.priceCents && (
                                        <span className="block text-sm font-semibold text-leaf">
                                            {money(box.transferPriceCents)} pagando con transferencia
                                        </span>
                                    )}
                                </span>
                                <Link href="/armar-caja" className="btn-primary">
                                    Armar mi caja
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>
            )}

            <section className="wrap pb-16">
                <p className="eyebrow mb-4">Cómo funciona</p>
                <ol className="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-4">
                    {steps.map(([title, text], i) => (
                        <li key={title} className="rounded-2xl border border-line p-6">
                            <p className="mb-2.5 font-display text-3xl text-clay">0{i + 1}</p>
                            <p className="mb-1.5 font-bold">{title}</p>
                            <p className="text-sm leading-relaxed text-muted">{text}</p>
                        </li>
                    ))}
                </ol>
            </section>

            <section className="bg-sand py-16">
                <div className="wrap flex flex-wrap gap-10">
                    <div className="flex-[1_1_300px]">
                        <p className="eyebrow mb-3.5">Cobertura</p>
                        <h2 className="mb-4 font-display text-4xl font-semibold tracking-tight">¿Llegamos a tu casa?</h2>
                        <p className="leading-relaxed text-muted">El costo de envío se muestra antes de pagar, según tu zona.</p>
                    </div>
                    <div className="grid flex-[2_1_480px] grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4">
                        <div className="card p-6">
                            <p className="mb-3 text-lg font-bold">Reparto RoloBox · Xela</p>
                            {coverage.local.length ? (
                                <ul className="space-y-1.5 text-sm">
                                    {coverage.local.map((zone) => (
                                        <li key={zone.id} className="flex justify-between gap-3">
                                            <span>{zone.name}</span>
                                            <span className="text-muted">{money(zone.feeCents)}</span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-muted">Pronto anunciamos las zonas.</p>
                            )}
                        </div>
                        <div className="card p-6">
                            <p className="mb-3 text-lg font-bold">Resto del país</p>
                            {coverage.national.length ? (
                                <p className="text-sm leading-relaxed text-muted">
                                    Enviamos por paquetería a {coverage.national.length} destinos, solo con sabores que aguantan el viaje.
                                </p>
                            ) : (
                                <p className="text-sm leading-relaxed text-muted">
                                    Muy pronto. Estamos probando qué sabores llegan perfectos por paquetería.
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}
