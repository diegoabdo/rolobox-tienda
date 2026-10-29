import { Head, Link, router, useForm } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { BackIcon, LockIcon } from '@/components/Icons';
import { money, shortMoney } from '@/lib/format';
import type { CartLine, PaymentMethod, Quote, ResolvedCart, Zone } from '@/types';

type Props = {
    cart: ResolvedCart;
    zones: { local: Zone[]; national: Zone[] };
    nationalBlocked: boolean;
    dates: { date: string; label: string }[];
    slots: { value: string; label: string }[];
    quotes: Record<PaymentMethod, Quote>;
    flavorImages: Record<number, string | null>;
    idempotencyKey: string;
    legalVersion: string;
};

type CheckoutForm = {
    idempotency_key: string;
    name: string;
    phone: string;
    email: string;
    nit: string;
    zone_id: number | '';
    address: string;
    address_reference: string;
    delivery_date: string;
    delivery_slot: string;
    notes: string;
    promotion_code: string;
    payment_method: PaymentMethod;
    terms: boolean;
};

const METHODS: { value: PaymentMethod; title: string; detail: string }[] = [
    { value: 'bank_transfer', title: 'Transferencia', detail: 'Desde tu banca en línea, con descuento.' },
    { value: 'card', title: 'Tarjeta', detail: 'Visa o Mastercard, crédito o débito.' },
];

/** Opción elegible: fondo de pozo; al elegirla se rellena de caramelo. El foco del teclado se ve en el contorno. */
const CHOICE =
    'cursor-pointer transition-colors has-[:focus-visible]:outline-3 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-accent';
const choiceTone = (selected: boolean) => (selected ? 'bg-accent text-ground' : 'bg-well hover:bg-edge');

function FieldError({ message }: { message?: string }) {
    return message ? <p className="field-error">{message}</p> : null;
}

function lineTitle(line: CartLine): string {
    return line.label;
}

/** Las piezas de una línea, una por círculo, como van acomodadas en la caja. */
function LineRolls({ line, images, size }: { line: CartLine; images: Props['flavorImages']; size: 'sm' | 'lg' }) {
    const rolls = line.flavors.flatMap((f) => Array.from({ length: f.count }, () => f));
    const circle = size === 'lg' ? 'aspect-square w-full' : 'size-9';

    return (
        <ul className={size === 'lg' ? 'grid grid-cols-3 gap-2.5' : 'flex flex-wrap gap-1.5'} aria-label={`Piezas de ${lineTitle(line)}`}>
            {rolls.map((flavor, i) => (
                <li key={`${flavor.id}-${i}`} className={circle}>
                    {images[flavor.id] ? (
                        <img src={images[flavor.id] ?? ''} alt={flavor.name} className="size-full object-cover rounded-2xl" />
                    ) : (
                        <span className="flex size-full items-center justify-center rounded-xl bg-ground/15 p-1 text-center text-small font-medium text-ground" title={flavor.name}>
                            {size === 'lg' ? flavor.name : ''}
                        </span>
                    )}
                </li>
            ))}
        </ul>
    );
}

function Step({ id, title, intro, children }: { id: string; title: string; intro?: string; children: ReactNode }) {
    return (
        <section aria-labelledby={id} className="min-w-0 rounded-3xl bg-surface p-6 sm:p-10">
            <h2 id={id} className="display text-[clamp(34px,3.2vw,48px)]">
                {title}
            </h2>
            {intro && <p className="mt-4 max-w-[48ch] text-body">{intro}</p>}
            <div className="mt-8">{children}</div>
        </section>
    );
}

export default function Checkout({ cart, zones, nationalBlocked, dates, slots, quotes, flavorImages, idempotencyKey }: Props) {
    const hasNational = zones.national.length > 0;
    const firstQuote = quotes.card;
    const initialZone = [...zones.local, ...zones.national].find((z) => z.id === firstQuote.zoneId);
    const [mode, setMode] = useState<'local' | 'national'>(initialZone?.deliveryType ?? 'local');
    const [coupon, setCoupon] = useState(firstQuote.promotionCode ?? '');
    const [quoting, setQuoting] = useState(false);

    const form = useForm<CheckoutForm>({
        idempotency_key: idempotencyKey,
        name: '',
        phone: '',
        email: '',
        nit: '',
        zone_id: firstQuote.zoneId ?? '',
        address: '',
        address_reference: '',
        delivery_date: '',
        delivery_slot: '',
        notes: '',
        promotion_code: firstQuote.promotionCode ?? '',
        payment_method: 'card',
        terms: false,
    });
    const quote = quotes[form.data.payment_method];
    const byTransfer = form.data.payment_method === 'bank_transfer';
    const transferSavings = quotes.card.totalCents - quotes.bank_transfer.totalCents;
    const errors = form.errors as Partial<Record<keyof CheckoutForm | 'cart' | 'payment', string>>;

    function refreshQuote(zoneId: number | '', code: string) {
        setQuoting(true);
        router.reload({
            data: { zona: zoneId || undefined, cupon: code || undefined },
            only: ['quotes'],
            onFinish: () => setQuoting(false),
        });
    }

    function chooseMode(next: 'local' | 'national') {
        setMode(next);
        form.setData((data) => ({ ...data, zone_id: '', delivery_date: '', delivery_slot: '' }));
        refreshQuote('', coupon);
    }

    function chooseZone(zoneId: number) {
        form.setData('zone_id', zoneId);
        refreshQuote(zoneId, coupon);
    }

    function applyCoupon() {
        form.setData('promotion_code', coupon.trim().toUpperCase());
        refreshQuote(form.data.zone_id, coupon.trim());
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/checkout', { preserveScroll: true });
    }

    const zoneList = mode === 'local' ? zones.local : zones.national;
    const shipping = quote.zoneId ? (quote.freeShipping ? 'Gratis' : money(quote.shippingCents)) : 'Elige tu zona';

    return (
        <>
            <Head title="Tu pedido" />
            <div className="wrap pt-6 pb-10">
                <Link href="/carrito" className="inline-flex min-h-11 items-center gap-2 text-body font-semibold transition-colors hover:text-accent">
                    <BackIcon size={18} /> Volver al carrito
                </Link>

                <form onSubmit={submit} noValidate className="mt-6 grid grid-cols-1 items-start gap-x-16 gap-y-16 lg:grid-cols-[minmax(0,1fr)_minmax(0,460px)]">
                    <div className="min-w-0">
                        <h1 className="display text-[clamp(36px,4.4vw,68px)]">Tu pedido</h1>
                        <p className="mt-6 max-w-[40ch] text-lead leading-snug">Tres preguntas y lo horneamos para el día que elijas.</p>

                        {/* En el teléfono, la caja va arriba en pequeño; el ticket completo con el pago va al final. */}
                        <a href="#ticket" className="mt-8 flex items-center gap-4 bg-honey p-4 text-ground lg:hidden rounded-2xl">
                            <span className="min-w-0 flex-1 space-y-2">
                                {cart.lines.map((line) => (
                                    <LineRolls key={line.key} line={line} images={flavorImages} size="sm" />
                                ))}
                            </span>
                            <span className="text-right">
                                <span className="block text-small">Total</span>
                                <span className="display text-[40px] tabular-nums">{shortMoney(quote.totalCents)}</span>
                            </span>
                        </a>

                        <div className="mt-10 space-y-3">
                            <Step id="entrega" title="¿Dónde lo entregamos?" intro="El envío se suma según tu zona. Horneamos para el día y el horario que elijas.">
                                {hasNational && (
                                    <div role="radiogroup" aria-label="Tipo de entrega" className="mb-8 grid grid-cols-2 gap-3">
                                        {(['local', 'national'] as const).map((value) => (
                                            <button
                                                key={value}
                                                type="button"
                                                role="radio"
                                                aria-checked={mode === value}
                                                onClick={() => chooseMode(value)}
                                                className={`p-5 text-left ${CHOICE} ${choiceTone(mode === value)}`}
                                            >
                                                <span className="block text-lead font-extrabold tracking-[-0.03em]">
                                                    {value === 'local' ? 'Reparto en Xela' : 'Envío nacional'}
                                                </span>
                                                <span className="mt-1 block text-small">{value === 'local' ? 'Eliges día y horario' : 'Paquetería con número de guía'}</span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                                {nationalBlocked && (
                                    <p className="mb-8 max-w-[56ch] bg-surface p-5 text-body rounded-2xl">
                                        Al resto del país enviamos solo sabores que aguantan el viaje, y tu caja incluye algunos que no.
                                    </p>
                                )}

                                <fieldset className="min-w-0">
                                    <legend className="field-label mb-3">{mode === 'local' ? 'Tu zona de Xela' : 'Destino'}</legend>
                                    <div className="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                                        {zoneList.map((zone) => {
                                            const selected = form.data.zone_id === zone.id;
                                            return (
                                                <label key={zone.id} className={`flex min-h-16 flex-col justify-center px-5 ${CHOICE} ${choiceTone(selected)}`}>
                                                    <input
                                                        type="radio"
                                                        name="zone_id"
                                                        value={zone.id}
                                                        className="sr-only"
                                                        checked={selected}
                                                        onChange={() => chooseZone(zone.id)}
                                                    />
                                                    <span className="truncate text-body font-semibold">
                                                        {zone.name}
                                                        {mode === 'national' ? `, ${zone.department}` : ''}
                                                    </span>
                                                    <span className="text-small tabular-nums">envío {shortMoney(zone.feeCents)}</span>
                                                </label>
                                            );
                                        })}
                                    </div>
                                    <FieldError message={errors.zone_id} />
                                </fieldset>

                                <div className="mt-10 grid gap-5 sm:grid-cols-2">
                                    <label className="field-label sm:col-span-2">
                                        Dirección
                                        <input
                                            className="field-input"
                                            value={form.data.address}
                                            onChange={(e) => form.setData('address', e.target.value)}
                                            autoComplete="street-address"
                                            maxLength={500}
                                            aria-invalid={!!errors.address}
                                            placeholder="Calle, avenida, número de casa"
                                        />
                                        <FieldError message={errors.address} />
                                    </label>
                                    <label className="field-label sm:col-span-2">
                                        Referencias (opcional)
                                        <input
                                            className="field-input"
                                            value={form.data.address_reference}
                                            onChange={(e) => form.setData('address_reference', e.target.value)}
                                            maxLength={255}
                                            placeholder="Portón negro, frente a la tienda"
                                        />
                                    </label>
                                </div>

                                {mode === 'local' && (
                                    <>
                                        <fieldset className="mt-12 min-w-0">
                                            <legend className="field-label mb-3">Día de entrega</legend>
                                            <div className="grid grid-cols-4 gap-2.5 sm:grid-cols-6">
                                                {dates.map((d) => {
                                                    const selected = form.data.delivery_date === d.date;
                                                    const parts = d.label.split(' ');
                                                    return (
                                                        <label
                                                            key={d.date}
                                                            className={`flex aspect-square flex-col items-center justify-center text-center ${CHOICE} ${choiceTone(selected)}`}
                                                        >
                                                            <input
                                                                type="radio"
                                                                name="delivery_date"
                                                                value={d.date}
                                                                className="sr-only"
                                                                checked={selected}
                                                                onChange={() => form.setData('delivery_date', d.date)}
                                                            />
                                                            <span className="sr-only">{d.label}</span>
                                                            <span className="text-small" aria-hidden="true">
                                                                {parts[0]}
                                                            </span>
                                                            <span className="text-lead leading-none font-extrabold tabular-nums" aria-hidden="true">
                                                                {Number(d.date.slice(8))}
                                                            </span>
                                                            <span className="text-small" aria-hidden="true">
                                                                {parts[parts.length - 1]}
                                                            </span>
                                                        </label>
                                                    );
                                                })}
                                            </div>
                                            <FieldError message={errors.delivery_date} />
                                        </fieldset>

                                        <fieldset className="mt-10 min-w-0">
                                            <legend className="field-label mb-3">Horario</legend>
                                            <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                                                {slots.map((s) => {
                                                    const selected = form.data.delivery_slot === s.value;
                                                    return (
                                                        <label
                                                            key={s.value}
                                                            className={`flex min-h-14 items-center justify-center px-4 text-body font-semibold ${CHOICE} ${choiceTone(selected)}`}
                                                        >
                                                            <input
                                                                type="radio"
                                                                name="delivery_slot"
                                                                value={s.value}
                                                                className="sr-only"
                                                                checked={selected}
                                                                onChange={() => form.setData('delivery_slot', s.value)}
                                                            />
                                                            {s.label}
                                                        </label>
                                                    );
                                                })}
                                            </div>
                                            <FieldError message={errors.delivery_slot} />
                                        </fieldset>
                                    </>
                                )}
                            </Step>

                            <Step id="datos" title="¿A quién avisamos?" intro="Te escribimos por WhatsApp cuando tu pedido cambie de estado.">
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <label className="field-label">
                                        Nombre
                                        <input
                                            className="field-input"
                                            value={form.data.name}
                                            onChange={(e) => form.setData('name', e.target.value)}
                                            autoComplete="name"
                                            maxLength={120}
                                            aria-invalid={!!errors.name}
                                        />
                                        <FieldError message={errors.name} />
                                    </label>
                                    <label className="field-label">
                                        WhatsApp
                                        <input
                                            className="field-input"
                                            type="tel"
                                            inputMode="tel"
                                            value={form.data.phone}
                                            onChange={(e) => form.setData('phone', e.target.value)}
                                            autoComplete="tel"
                                            placeholder="5555 5555"
                                            aria-invalid={!!errors.phone}
                                        />
                                        <FieldError message={errors.phone} />
                                    </label>
                                    <label className="field-label">
                                        Correo (opcional)
                                        <input
                                            className="field-input"
                                            type="email"
                                            value={form.data.email}
                                            onChange={(e) => form.setData('email', e.target.value)}
                                            autoComplete="email"
                                            aria-invalid={!!errors.email}
                                        />
                                        <FieldError message={errors.email} />
                                    </label>
                                    <label className="field-label">
                                        NIT para la factura
                                        <input
                                            className="field-input uppercase"
                                            value={form.data.nit}
                                            onChange={(e) => form.setData('nit', e.target.value)}
                                            placeholder="CF"
                                            maxLength={20}
                                            aria-invalid={!!errors.nit}
                                        />
                                        <FieldError message={errors.nit} />
                                    </label>
                                    <label className="field-label sm:col-span-2">
                                        Notas para Twist & Rolls (opcional)
                                        <textarea
                                            className="field-input min-h-24 resize-y"
                                            value={form.data.notes}
                                            onChange={(e) => form.setData('notes', e.target.value)}
                                            maxLength={1000}
                                            placeholder="No escribas datos de tarjeta ni contraseñas."
                                        />
                                    </label>
                                </div>
                            </Step>

                            <Step id="pago" title="¿Cómo pagas?" intro="Los precios publicados son con tarjeta. Por transferencia bancaria pagas menos.">
                                <div role="radiogroup" aria-label="Forma de pago" className="grid gap-3 sm:grid-cols-2">
                                    {METHODS.map((method) => {
                                        const selected = form.data.payment_method === method.value;
                                        return (
                                            <button
                                                key={method.value}
                                                type="button"
                                                role="radio"
                                                aria-checked={selected}
                                                onClick={() => form.setData('payment_method', method.value)}
                                                className={`flex min-h-56 flex-col p-6 text-left focus-visible:outline-offset-2 ${CHOICE} ${choiceTone(selected)}`}
                                            >
                                                <span className="text-lead font-extrabold tracking-[-0.03em]">{method.title}</span>
                                                <span className="mt-1 text-body">{method.detail}</span>
                                                <span className="mt-auto flex flex-wrap items-end justify-between gap-x-4 gap-y-1 pt-8">
                                                    <span className="display text-[clamp(32px,3vw,44px)] tabular-nums">{shortMoney(quotes[method.value].totalCents)}</span>
                                                    {method.value === 'bank_transfer' && transferSavings > 0 && (
                                                        <span className="mb-2 text-body font-semibold">Ahorras {shortMoney(transferSavings)}</span>
                                                    )}
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                                <FieldError message={errors.payment_method} />
                            </Step>
                        </div>
                    </div>

                    <aside id="ticket" className="min-w-0 scroll-mt-6 lg:sticky lg:top-6" aria-labelledby="ticket-titulo">
                        <div className="overflow-clip rounded-3xl bg-honey text-ground">
                            <div className="p-6 sm:p-8">
                                <h2 id="ticket-titulo" className="display text-[clamp(34px,3.2vw,48px)]">
                                    {cart.lines.length === 1 && cart.lines[0].type === 'box' ? 'Tu caja' : 'Lo que llevas'}
                                </h2>
                                <ul className="mt-6 space-y-6">
                                    {cart.lines.map((line) => (
                                        <li key={line.key}>
                                            {line.type === 'box' && <LineRolls line={line} images={flavorImages} size="lg" />}
                                            <div className={`flex items-baseline justify-between gap-4 text-body ${line.type === 'box' ? 'mt-4' : ''}`}>
                                                <span className="flex min-w-0 items-center gap-3 font-semibold">
                                                    {line.type === 'single' && <LineRolls line={line} images={flavorImages} size="sm" />}
                                                    <span>
                                                        {line.quantity > 1 ? `${line.quantity} × ` : ''}
                                                        {lineTitle(line)}
                                                    </span>
                                                </span>
                                                <span className="font-semibold tabular-nums">
                                                    {shortMoney(byTransfer ? line.transferLineTotalCents : line.lineTotalCents)}
                                                </span>
                                            </div>
                                            {line.giftMessage && <p className="mt-1 text-small">Tarjeta: “{line.giftMessage}”</p>}
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div className="relative flex items-center" aria-hidden="true">
                                <span className="-ml-4 size-8 shrink-0 rounded-full bg-ground" />
                                <span className="flex flex-1 justify-evenly">
                                    {Array.from({ length: 12 }, (_, i) => (
                                        <span key={i} className="size-2 rounded-full bg-ground" />
                                    ))}
                                </span>
                                <span className="-mr-4 size-8 shrink-0 rounded-full bg-ground" />
                            </div>

                            <div className="p-6 sm:p-8">
                                <label className="field-label" htmlFor="coupon">
                                    Código de promoción
                                </label>
                                <div className="mt-2 flex gap-2">
                                    <input
                                        id="coupon"
                                        className="min-h-12 min-w-0 flex-1 bg-[#d6b48a] px-5 text-body font-bold tracking-[0.06em] text-ground uppercase focus:outline-3 focus:outline-ground"
                                        value={coupon}
                                        onChange={(e) => setCoupon(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                applyCoupon();
                                            }
                                        }}
                                        maxLength={40}
                                    />
                                    <button type="button" onClick={applyCoupon} className="btn bg-ground px-5 text-ink hover:bg-surface rounded-2xl" disabled={quoting}>
                                        Aplicar
                                    </button>
                                </div>
                                {quote.promotionError ? (
                                    <p className="mt-1.5 text-body font-semibold text-[#6a1608]">{quote.promotionError}</p>
                                ) : quote.promotionCode ? (
                                    <p className="mt-1.5 text-body font-semibold">
                                        {quote.promotionCode} aplicado: {quote.promotionDescription}
                                    </p>
                                ) : null}
                                {errors.promotion_code && <p className="mt-1.5 text-body font-semibold text-[#6a1608]">{errors.promotion_code}</p>}

                                <dl className={`mt-8 space-y-2 text-body transition-opacity ${quoting ? 'opacity-50' : ''}`} aria-live="polite">
                                    <div className="flex justify-between gap-4">
                                        <dt>Subtotal{byTransfer ? ' con transferencia' : ''}</dt>
                                        <dd className="tabular-nums">{money(quote.subtotalCents)}</dd>
                                    </div>
                                    {quote.discountCents > 0 && (
                                        <div className="flex justify-between gap-4 font-semibold">
                                            <dt>Descuento</dt>
                                            <dd className="tabular-nums">− {money(quote.discountCents)}</dd>
                                        </div>
                                    )}
                                    <div className="flex justify-between gap-4">
                                        <dt>Envío</dt>
                                        <dd className="tabular-nums">{shipping}</dd>
                                    </div>
                                    <div className="flex items-end justify-between gap-4 pt-6">
                                        <dt className="text-lead font-extrabold tracking-[-0.03em]">Total</dt>
                                        <dd className="display text-[clamp(44px,4vw,64px)] tabular-nums">{shortMoney(quote.totalCents)}</dd>
                                    </div>
                                </dl>

                                <label className="mt-8 flex cursor-pointer items-start gap-3 text-body">
                                    <input
                                        type="checkbox"
                                        className="mt-0.5 size-6 shrink-0 cursor-pointer accent-ground"
                                        checked={form.data.terms}
                                        onChange={(e) => form.setData('terms', e.target.checked)}
                                        aria-invalid={!!errors.terms}
                                    />
                                    <span>
                                        Acepto los{' '}
                                        <a href="/terminos" target="_blank" rel="noopener" className="font-semibold underline underline-offset-3">
                                            términos de compra
                                        </a>{' '}
                                        y el{' '}
                                        <a href="/privacidad" target="_blank" rel="noopener" className="font-semibold underline underline-offset-3">
                                            aviso de privacidad
                                        </a>
                                        <span className="sr-only"> (se abren en otra pestaña)</span>.
                                    </span>
                                </label>
                                {errors.terms && <p className="mt-1.5 text-body font-semibold text-[#6a1608]">{errors.terms}</p>}

                                {(errors.cart || errors.payment) && (
                                    <p className="mt-5 bg-ground p-4 text-body font-semibold text-alert" role="alert">
                                        {errors.cart ?? errors.payment}
                                    </p>
                                )}
                                {form.hasErrors && !errors.cart && !errors.payment && (
                                    <p className="mt-5 text-body font-semibold text-[#6a1608]" role="alert">
                                        Revisa los campos marcados.
                                    </p>
                                )}

                                <button
                                    type="submit"
                                    className="btn mt-6 min-h-16 w-full justify-between bg-ground px-7 text-lead text-ink hover:bg-surface rounded-2xl"
                                    disabled={form.processing || quoting}
                                >
                                    <span className="flex items-center gap-3">
                                        <LockIcon size={20} />
                                        {form.processing ? 'Abriendo el pago…' : byTransfer ? 'Pagar con transferencia' : 'Pagar con tarjeta'}
                                    </span>
                                    <span className="tabular-nums">{shortMoney(quote.totalCents)}</span>
                                </button>
                                <p className="mt-4 text-small">
                                    {byTransfer
                                        ? 'En la página segura del procesador ves los datos para transferir. Tu pedido se confirma cuando el banco acredita el pago.'
                                        : 'Pagas en la página segura del procesador. Twist & Rolls nunca recibe los datos de tu tarjeta.'}
                                </p>
                            </div>
                        </div>
                        <p className="mt-5 text-small text-muted">
                            Horneamos cada pedido por encargo: <strong className="text-ink">no hay reembolsos por cancelación</strong>. Si algo llega mal, lo
                            resolvemos.
                        </p>
                    </aside>
                </form>
            </div>
        </>
    );
}
