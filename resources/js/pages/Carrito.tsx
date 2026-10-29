import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { ProductArt } from '@/components/ProductArt';
import { QuantityStepper } from '@/components/QuantityStepper';
import { flavorSummary, money, shortMoney } from '@/lib/format';
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
            <div className="wrap pt-6 pb-10">
                <h1 className="display text-[clamp(36px,4.4vw,68px)]">Tu carrito</h1>

                {cart.removed > 0 && (
                    <p className="mt-6 rounded-2xl bg-well p-4 text-body" role="alert">
                        Quitamos {cart.removed === 1 ? 'un producto que ya no está disponible' : `${cart.removed} productos que ya no están disponibles`}.
                    </p>
                )}

                {cart.lines.length === 0 ? (
                    <div className="mt-10 grid items-center gap-8 rounded-3xl bg-honey p-6 text-ground sm:p-10 lg:grid-cols-2 lg:p-14">
                        <div>
                            <h2 className="display text-[clamp(30px,3.6vw,56px)]">Todavía no agregaste nada</h2>
                            <p className="mt-4 max-w-[36ch] text-lead leading-snug">Elige del menú o arma una caja para regalar.</p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <Link href="/armar-caja" className="btn min-h-14 bg-ground px-8 text-ink hover:bg-surface">
                                    Armar una caja
                                </Link>
                                <Link href="/#menu" className="btn min-h-14 bg-ground/15 px-8 hover:bg-ground/25">
                                    Ver el menú
                                </Link>
                            </div>
                        </div>
                        <ProductArt kind="croissants" tone="dark" className="mx-auto w-full max-w-[340px]" />
                    </div>
                ) : (
                    <div className="mt-10 grid items-start gap-3 lg:grid-cols-12">
                        <ul className="space-y-3 lg:col-span-8">
                            {cart.lines.map((line) => (
                                <li key={line.key} className="flex flex-wrap items-center gap-x-5 gap-y-4 rounded-3xl bg-surface p-5 sm:p-6">
                                    {line.image ? (
                                        <img src={line.image} alt="" className="size-20 shrink-0 rounded-2xl object-cover sm:size-24" />
                                    ) : (
                                        <span className="display flex size-20 shrink-0 items-center justify-center rounded-2xl bg-honey text-[44px] text-ground sm:size-24" aria-hidden="true">
                                            {line.label.charAt(0)}
                                        </span>
                                    )}
                                    <div className="min-w-0 flex-[1_1_220px]">
                                        <p className="text-lead leading-tight font-bold">{line.label}</p>
                                        {line.type === 'box' && <p className="mt-1 text-body text-muted">{flavorSummary(line.flavors)}</p>}
                                        {line.giftMessage && <p className="mt-1 text-body text-muted">Tarjeta: “{line.giftMessage}”</p>}
                                        <button
                                            type="button"
                                            onClick={() => remove(line)}
                                            disabled={busy === line.key}
                                            className="mt-2 min-h-9 cursor-pointer text-body font-medium text-honey underline underline-offset-4"
                                        >
                                            Quitar
                                        </button>
                                    </div>
                                    <QuantityStepper value={line.quantity} min={1} onChange={(v) => update(line, v)} disabled={busy === line.key} label={line.label} />
                                    <span className="w-24 text-right text-lead font-bold tabular-nums">{money(line.lineTotalCents)}</span>
                                </li>
                            ))}
                        </ul>

                        <aside className="rounded-3xl bg-honey p-6 text-ground sm:p-8 lg:sticky lg:top-6 lg:col-span-4">
                            <h2 className="display text-[clamp(40px,3.6vw,56px)]">Resumen</h2>
                            <div className="mt-6 flex items-end justify-between gap-4">
                                <span className="text-lead font-bold">Subtotal</span>
                                <span className="display text-[clamp(44px,4vw,64px)] tabular-nums">{shortMoney(cart.subtotalCents)}</span>
                            </div>
                            {cart.transferSubtotalCents < cart.subtotalCents && (
                                <p className="mt-2 flex justify-between gap-4 text-body font-bold">
                                    <span>Pagando con transferencia</span>
                                    <span className="tabular-nums">{money(cart.transferSubtotalCents)}</span>
                                </p>
                            )}
                            <p className="mt-4 text-body">El envío, las promociones y la forma de pago se eligen en el siguiente paso.</p>
                            <Link href="/checkout" className="btn mt-6 min-h-14 w-full justify-between bg-ground px-6 text-ink hover:bg-surface">
                                Continuar al pago <span aria-hidden="true">→</span>
                            </Link>
                            <Link href="/#menu" className="mt-3 inline-flex min-h-11 items-center text-body font-bold underline underline-offset-4">
                                Seguir comprando
                            </Link>
                        </aside>
                    </div>
                )}
            </div>
        </>
    );
}
