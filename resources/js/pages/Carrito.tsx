import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { QuantityStepper } from '@/components/QuantityStepper';
import { flavorSummary, money } from '@/lib/format';
import type { CartLine, ResolvedCart } from '@/types';

export default function Carrito({ cart }: { cart: ResolvedCart }) {
    const [busy, setBusy] = useState<string | null>(null);

    function update(line: CartLine, quantity: number) {
        setBusy(line.key);
        router.patch(`/carrito/${line.key}`, { quantity }, { preserveScroll: true, onFinish: () => setBusy(null) });
    }

    function remove(line: CartLine) {
        setBusy(line.key);
        router.delete(`/carrito/${line.key}`, { preserveScroll: true, onFinish: () => setBusy(null) });
    }

    return (
        <>
            <Head title="Carrito" />
            <div className="wrap max-w-3xl py-10">
                <h1 className="mb-6 font-display text-4xl font-bold tracking-tight">Tu carrito</h1>

                {cart.removed > 0 && (
                    <p className="mb-5 rounded-xl border border-[#cfab72] bg-[#f4e1bd] p-4 text-sm" role="alert">
                        Quitamos {cart.removed === 1 ? 'un producto que ya no está disponible' : `${cart.removed} productos que ya no están disponibles`}.
                    </p>
                )}

                {cart.lines.length === 0 ? (
                    <div className="card p-8 text-center">
                        <p className="mb-5 text-lg">Todavía no agregaste nada.</p>
                        <div className="flex flex-wrap justify-center gap-3">
                            <Link href="/armar-caja" className="btn-primary">
                                Armar una caja
                            </Link>
                            <Link href="/#menu" className="btn-outline">
                                Ver el menú
                            </Link>
                        </div>
                    </div>
                ) : (
                    <>
                        <ul className="space-y-3">
                            {cart.lines.map((line) => (
                                <li key={line.key} className="card flex flex-wrap items-center gap-4 p-4">
                                    {line.image ? (
                                        <img src={line.image} alt="" className="size-16 rounded-xl object-cover" />
                                    ) : (
                                        <div className="size-16 rounded-xl bg-sand" aria-hidden="true" />
                                    )}
                                    <div className="min-w-0 flex-[1_1_200px]">
                                        <p className="font-bold">{line.type === 'single' ? `Rol de ${line.flavors[0]?.name.toLowerCase()}` : line.name}</p>
                                        {line.type === 'box' && <p className="text-sm text-muted">{flavorSummary(line.flavors)}</p>}
                                        {line.giftMessage && <p className="mt-1 text-sm text-muted">Tarjeta: “{line.giftMessage}”</p>}
                                        <button
                                            type="button"
                                            onClick={() => remove(line)}
                                            disabled={busy === line.key}
                                            className="mt-1 min-h-9 cursor-pointer text-sm font-semibold text-clay underline underline-offset-4"
                                        >
                                            Quitar
                                        </button>
                                    </div>
                                    <QuantityStepper
                                        value={line.quantity}
                                        min={1}
                                        onChange={(v) => update(line, v)}
                                        disabled={busy === line.key}
                                        label={line.name}
                                    />
                                    <span className="w-24 text-right font-bold">{money(line.lineTotalCents)}</span>
                                </li>
                            ))}
                        </ul>

                        <div className="card mt-6 p-5">
                            <div className="flex justify-between text-lg font-bold">
                                <span>Subtotal</span>
                                <span>{money(cart.subtotalCents)}</span>
                            </div>
                            {cart.transferSubtotalCents < cart.subtotalCents && (
                                <div className="mt-1 flex justify-between font-bold text-leaf">
                                    <span>Pagando con transferencia</span>
                                    <span>{money(cart.transferSubtotalCents)}</span>
                                </div>
                            )}
                            <p className="mt-1.5 text-sm text-muted">El envío, las promociones y la forma de pago se eligen en el siguiente paso.</p>
                        </div>

                        <div className="mt-6 flex flex-wrap justify-between gap-3">
                            <Link href="/#menu" className="btn-outline">
                                Seguir comprando
                            </Link>
                            <Link href="/checkout" className="btn-primary">
                                Continuar al pago <span aria-hidden="true">→</span>
                            </Link>
                        </div>
                    </>
                )}
            </div>
        </>
    );
}
