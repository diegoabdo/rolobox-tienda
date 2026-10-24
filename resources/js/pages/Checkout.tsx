import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { BackIcon, LockIcon } from '@/components/Icons';
import { flavorSummary, money } from '@/lib/format';
import type { PaymentMethod, Quote, ResolvedCart, Zone } from '@/types';

type Props = {
    cart: ResolvedCart;
    zones: { local: Zone[]; national: Zone[] };
    nationalBlocked: boolean;
    dates: { date: string; label: string }[];
    slots: { value: string; label: string }[];
    quotes: Record<PaymentMethod, Quote>;
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
    { value: 'bank_transfer', title: 'Transferencia bancaria', detail: 'Desde tu banca en línea. Precio con descuento.' },
    { value: 'card', title: 'Tarjeta', detail: 'Visa o Mastercard, crédito o débito.' },
];

function FieldError({ message }: { message?: string }) {
    return message ? <p className="field-error">{message}</p> : null;
}

export default function Checkout({ cart, zones, nationalBlocked, dates, slots, quotes, idempotencyKey }: Props) {
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

    function chooseZone(value: string) {
        const zoneId = value ? Number(value) : '';
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

    return (
        <>
            <Head title="Pagar pedido" />
            <div className="wrap py-10">
                <Link href="/carrito" className="mb-6 inline-flex min-h-11 items-center gap-2 text-sm font-semibold hover:text-clay">
                    <BackIcon size={18} /> Volver al carrito
                </Link>
                <h1 className="mb-8 font-display text-4xl font-bold tracking-tight">Tu pedido</h1>

                <form onSubmit={submit} noValidate className="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_380px]">
                    <div className="space-y-10">
                        <section aria-labelledby="entrega">
                            <h2 id="entrega" className="mb-4 text-xl font-bold">
                                ¿Cómo te lo entregamos?
                            </h2>
                            {hasNational && (
                                <div role="radiogroup" aria-label="Tipo de entrega" className="mb-5 grid grid-cols-2 gap-3">
                                    {(['local', 'national'] as const).map((value) => (
                                        <button
                                            key={value}
                                            type="button"
                                            role="radio"
                                            aria-checked={mode === value}
                                            onClick={() => chooseMode(value)}
                                            className={`cursor-pointer rounded-2xl border-2 bg-paper p-4 text-left ${mode === value ? 'border-clay' : 'border-line'}`}
                                        >
                                            <span className="block font-bold">{value === 'local' ? 'Reparto en Xela' : 'Envío nacional'}</span>
                                            <span className="mt-1 block text-sm text-muted">
                                                {value === 'local' ? 'Eliges día y horario' : 'Paquetería con número de guía'}
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            )}
                            {nationalBlocked && (
                                <p className="mb-5 text-sm text-muted">
                                    El envío al resto del país está disponible solo para sabores que aguantan el viaje. Tu carrito incluye
                                    algunos que no.
                                </p>
                            )}

                            <div className="grid gap-5 sm:grid-cols-2">
                                <label className="field-label sm:col-span-2">
                                    {mode === 'local' ? 'Zona de Xela' : 'Destino'}
                                    <select
                                        className="field-input"
                                        value={form.data.zone_id}
                                        onChange={(e) => chooseZone(e.target.value)}
                                        aria-invalid={!!errors.zone_id}
                                    >
                                        <option value="">Elige una opción</option>
                                        {zoneList.map((zone) => (
                                            <option key={zone.id} value={zone.id}>
                                                {zone.name}
                                                {mode === 'national' ? `, ${zone.department}` : ''} · envío {money(zone.feeCents)}
                                            </option>
                                        ))}
                                    </select>
                                    <FieldError message={errors.zone_id} />
                                </label>

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
                                <div className="mt-6 space-y-5">
                                    <fieldset className="min-w-0">
                                        <legend className="field-label mb-2">Día de entrega</legend>
                                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4">
                                            {dates.map((d) => (
                                                <label
                                                    key={d.date}
                                                    className={`flex min-h-12 cursor-pointer items-center justify-center rounded-xl border px-3 text-center text-sm font-semibold ${
                                                        form.data.delivery_date === d.date ? 'border-ink bg-ink text-cream' : 'border-line'
                                                    }`}
                                                >
                                                    <input
                                                        type="radio"
                                                        name="delivery_date"
                                                        value={d.date}
                                                        className="sr-only"
                                                        checked={form.data.delivery_date === d.date}
                                                        onChange={() => form.setData('delivery_date', d.date)}
                                                    />
                                                    {d.label}
                                                </label>
                                            ))}
                                        </div>
                                        <FieldError message={errors.delivery_date} />
                                    </fieldset>

                                    <fieldset className="min-w-0">
                                        <legend className="field-label mb-2">Horario</legend>
                                        <div className="grid grid-cols-3 gap-2">
                                            {slots.map((s) => (
                                                <label
                                                    key={s.value}
                                                    className={`flex min-h-12 cursor-pointer items-center justify-center rounded-xl border px-2 text-center text-sm font-semibold ${
                                                        form.data.delivery_slot === s.value ? 'border-ink bg-ink text-cream' : 'border-line'
                                                    }`}
                                                >
                                                    <input
                                                        type="radio"
                                                        name="delivery_slot"
                                                        value={s.value}
                                                        className="sr-only"
                                                        checked={form.data.delivery_slot === s.value}
                                                        onChange={() => form.setData('delivery_slot', s.value)}
                                                    />
                                                    {s.label}
                                                </label>
                                            ))}
                                        </div>
                                        <FieldError message={errors.delivery_slot} />
                                    </fieldset>
                                </div>
                            )}
                        </section>

                        <section aria-labelledby="datos">
                            <h2 id="datos" className="mb-4 text-xl font-bold">
                                Tus datos
                            </h2>
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
                                    WhatsApp para avisos del pedido
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
                                    Notas para RoloBox (opcional)
                                    <textarea
                                        className="field-input min-h-20 resize-y"
                                        value={form.data.notes}
                                        onChange={(e) => form.setData('notes', e.target.value)}
                                        maxLength={1000}
                                        placeholder="No escribas datos de tarjeta ni contraseñas."
                                    />
                                </label>
                            </div>
                        </section>

                        <section aria-labelledby="pago">
                            <h2 id="pago" className="mb-1 text-xl font-bold">
                                ¿Cómo vas a pagar?
                            </h2>
                            <p className="mb-4 text-sm text-muted">
                                Los precios publicados son con tarjeta. Pagando con transferencia bancaria tienes descuento.
                            </p>
                            <div role="radiogroup" aria-label="Forma de pago" className="grid gap-3 sm:grid-cols-2">
                                {METHODS.map((method) => {
                                    const selected = form.data.payment_method === method.value;
                                    const total = quotes[method.value].totalCents;
                                    return (
                                        <button
                                            key={method.value}
                                            type="button"
                                            role="radio"
                                            aria-checked={selected}
                                            onClick={() => form.setData('payment_method', method.value)}
                                            className={`cursor-pointer rounded-2xl border-2 bg-paper p-4 text-left ${selected ? 'border-clay' : 'border-line'}`}
                                        >
                                            <span className="flex items-baseline justify-between gap-3">
                                                <span className="font-bold">{method.title}</span>
                                                <span className="font-bold">{money(total)}</span>
                                            </span>
                                            <span className="mt-1 block text-sm text-muted">{method.detail}</span>
                                            {method.value === 'bank_transfer' && transferSavings > 0 && (
                                                <span className="mt-2 inline-block rounded-full bg-leaf px-2.5 py-0.5 text-xs font-bold text-cream">
                                                    Ahorras {money(transferSavings)}
                                                </span>
                                            )}
                                        </button>
                                    );
                                })}
                            </div>
                            <FieldError message={errors.payment_method} />
                        </section>
                    </div>

                    <aside className="space-y-5 lg:sticky lg:top-6">
                        <section className="card p-5" aria-labelledby="resumen">
                            <h2 id="resumen" className="mb-4 font-bold">
                                Resumen
                            </h2>
                            <ul className="mb-4 space-y-3 border-b border-line pb-4 text-sm">
                                {cart.lines.map((line) => (
                                    <li key={line.key} className="flex justify-between gap-3">
                                        <span>
                                            {line.quantity} × {line.type === 'single' ? `Rol de ${line.flavors[0]?.name.toLowerCase()}` : line.name}
                                            {line.type === 'box' && <span className="block text-xs text-muted">{flavorSummary(line.flavors)}</span>}
                                        </span>
                                        <span className="font-semibold">{money(byTransfer ? line.transferLineTotalCents : line.lineTotalCents)}</span>
                                    </li>
                                ))}
                            </ul>

                            <label className="field-label" htmlFor="coupon">
                                Código de promoción
                            </label>
                            <div className="mt-2 flex gap-2">
                                <input
                                    id="coupon"
                                    className="min-w-0 flex-1 rounded-xl border border-edge bg-field px-3 py-3 font-bold tracking-[0.08em] uppercase"
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
                                <button type="button" onClick={applyCoupon} className="btn-outline min-h-12 rounded-xl px-4" disabled={quoting}>
                                    Aplicar
                                </button>
                            </div>
                            {quote.promotionError ? (
                                <p className="field-error">{quote.promotionError}</p>
                            ) : quote.promotionCode ? (
                                <p className="mt-1.5 text-sm font-bold text-leaf">Código {quote.promotionCode} aplicado: {quote.promotionDescription}</p>
                            ) : null}
                            <FieldError message={errors.promotion_code} />

                            <dl className={`mt-5 space-y-2 text-sm transition-opacity ${quoting ? 'opacity-50' : ''}`} aria-live="polite">
                                <div className="flex justify-between">
                                    <dt>Subtotal{byTransfer ? ' con transferencia' : ''}</dt>
                                    <dd>{money(quote.subtotalCents)}</dd>
                                </div>
                                {quote.discountCents > 0 && (
                                    <div className="flex justify-between text-leaf">
                                        <dt>Descuento</dt>
                                        <dd>− {money(quote.discountCents)}</dd>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <dt>Envío</dt>
                                    <dd>{quote.zoneId ? (quote.freeShipping ? 'Gratis' : money(quote.shippingCents)) : 'Elige la zona'}</dd>
                                </div>
                                <div className="flex justify-between border-t border-line pt-3 text-lg font-bold">
                                    <dt>Total</dt>
                                    <dd>{money(quote.totalCents)}</dd>
                                </div>
                            </dl>
                        </section>

                        <label className="flex items-start gap-3 text-sm leading-relaxed">
                            <input
                                type="checkbox"
                                className="mt-1 size-5 shrink-0 accent-clay"
                                checked={form.data.terms}
                                onChange={(e) => form.setData('terms', e.target.checked)}
                                aria-invalid={!!errors.terms}
                            />
                            <span>
                                Acepto los{' '}
                                <a href="/terminos" target="_blank" rel="noopener" className="underline underline-offset-3">
                                    términos de compra
                                </a>{' '}
                                y el{' '}
                                <a href="/privacidad" target="_blank" rel="noopener" className="underline underline-offset-3">
                                    aviso de privacidad
                                </a>
                                <span className="sr-only"> (se abren en otra pestaña)</span>.
                            </span>
                        </label>
                        <FieldError message={errors.terms} />

                        {(errors.cart || errors.payment) && (
                            <p className="rounded-xl border border-alert/40 bg-[#f8e3dc] p-4 text-sm font-semibold text-alert" role="alert">
                                {errors.cart ?? errors.payment}
                            </p>
                        )}
                        {form.hasErrors && !errors.cart && !errors.payment && (
                            <p className="text-sm font-semibold text-alert" role="alert">
                                Revisa los campos marcados.
                            </p>
                        )}

                        <button type="submit" className="btn-primary w-full justify-between" disabled={form.processing || quoting}>
                            <span className="flex items-center gap-2.5">
                                <LockIcon size={18} />
                                {form.processing ? 'Abriendo el pago…' : byTransfer ? 'Pagar con transferencia' : 'Pagar con tarjeta'}
                            </span>
                            <span>{money(quote.totalCents)}</span>
                        </button>
                        <p className="text-center text-xs leading-relaxed text-muted">
                            {byTransfer ? (
                                <>
                                    En la página segura del procesador ves los datos para transferir.
                                    <br />
                                    Tu pedido se confirma cuando el banco acredita el pago.
                                </>
                            ) : (
                                <>
                                    Visa · Mastercard. Pagas en la página segura del procesador.
                                    <br />
                                    RoloBox nunca recibe los datos de tu tarjeta.
                                </>
                            )}
                        </p>
                        <p className="rounded-xl border border-line px-4 py-3 text-center text-xs leading-relaxed text-muted">
                            Horneamos cada pedido por encargo: <strong className="text-ink">no hay reembolsos por cancelación</strong>. Si algo llega mal, lo resolvemos.
                        </p>
                    </aside>
                </form>
            </div>
        </>
    );
}
