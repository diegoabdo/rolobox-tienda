import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo } from 'react';
import { BackIcon } from '@/components/Icons';
import { QuantityStepper } from '@/components/QuantityStepper';
import { money } from '@/lib/format';
import type { Flavor, Product } from '@/types';

type Props = {
    box: Product;
    flavors: Flavor[];
    preselect: string | null;
};

type BoxForm = {
    product_id: number;
    flavors: Record<string, number>;
    quantity: number;
    gift_message: string;
    go_to_cart: boolean;
};

export default function ArmarCaja({ box, flavors, preselect }: Props) {
    const size = box.boxSize ?? 6;
    const initial = useMemo(() => {
        const start: Record<string, number> = {};
        const chosen = flavors.find((f) => f.slug === preselect);
        if (chosen) start[chosen.id] = 1;
        return start;
    }, [flavors, preselect]);

    const form = useForm<BoxForm>({
        product_id: box.id,
        flavors: initial,
        quantity: 1,
        gift_message: '',
        go_to_cart: true,
    });

    const total = Object.values(form.data.flavors).reduce((sum, n) => sum + n, 0);
    const remaining = size - total;
    const slots = flavors.flatMap((f) => Array.from({ length: form.data.flavors[f.id] ?? 0 }, () => f));

    function setCount(id: number, value: number) {
        const next = { ...form.data.flavors };
        if (value <= 0) delete next[id];
        else next[id] = value;
        form.setData('flavors', next);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/carrito', { preserveScroll: true });
    }

    const errors = form.errors as Partial<Record<keyof BoxForm | 'product', string>>;
    const flavorError = errors.flavors ?? errors.product;

    return (
        <>
            <Head title="Arma tu caja" />
            <div className="wrap py-10">
                <Link href="/#menu" className="mb-6 inline-flex min-h-11 items-center gap-2 text-sm font-semibold hover:text-clay">
                    <BackIcon size={18} /> Volver al menú
                </Link>

                <form onSubmit={submit} className="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_380px]">
                    <div>
                        <h1 className="mb-2 font-display text-4xl font-bold tracking-tight sm:text-5xl">Arma tu caja</h1>
                        <p className="mb-8 text-muted">
                            {box.name} · elige {size} roles · {money(box.priceCents)}
                            {box.transferPriceCents < box.priceCents && (
                                <span className="font-semibold text-leaf"> · {money(box.transferPriceCents)} con transferencia</span>
                            )}
                        </p>

                        <fieldset>
                            <legend className="sr-only">Sabores de la caja</legend>
                            <ul className="space-y-3">
                                {flavors.map((flavor) => {
                                    const count = form.data.flavors[flavor.id] ?? 0;
                                    return (
                                        <li key={flavor.id} className="card flex items-center gap-4 p-3 pr-4">
                                            {flavor.image ? (
                                                <img src={flavor.image} alt="" className="size-16 shrink-0 rounded-xl object-cover" />
                                            ) : (
                                                <div className="size-16 shrink-0 rounded-xl bg-sand" aria-hidden="true" />
                                            )}
                                            <div className="min-w-0 flex-1">
                                                <p className="font-bold">{flavor.name}</p>
                                                <p className="text-sm text-muted">{flavor.description}</p>
                                            </div>
                                            <QuantityStepper
                                                value={count}
                                                onChange={(v) => setCount(flavor.id, v)}
                                                max={size}
                                                disableIncrease={remaining <= 0}
                                                label={flavor.name}
                                            />
                                        </li>
                                    );
                                })}
                            </ul>
                        </fieldset>
                    </div>

                    <aside className="lg:sticky lg:top-6">
                        <div className="rounded-lg border-[7px] border-[#b8804b] bg-[#d29b65] p-3">
                            <ul className="grid grid-cols-3 gap-2" aria-label="Tu caja">
                                {Array.from({ length: size }, (_, i) => {
                                    const flavor = slots[i];
                                    return (
                                        <li key={i} className="aspect-square">
                                            {flavor?.image ? (
                                                <img src={flavor.image} alt={flavor.name} className="size-full rounded-full object-cover shadow-[2px_3px_0_#9d5d31]" />
                                            ) : flavor ? (
                                                <span className="flex size-full items-center justify-center rounded-full bg-[#f4d2a0] p-1 text-center text-[11px] font-bold">
                                                    {flavor.name}
                                                </span>
                                            ) : (
                                                <span className="flex size-full items-center justify-center rounded-full border-2 border-dashed border-[#f4d2a0] text-2xl text-[#fff4e0]" aria-label="Espacio libre">
                                                    +
                                                </span>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                        <p className="mt-3 text-center text-sm font-bold" aria-live="polite">
                            {remaining === 0 ? `Caja completa · ${size} de ${size}` : `${total} de ${size} · elige ${remaining} más`}
                        </p>

                        <label className="field-label mt-6">
                            ¿Es un regalo? Mensaje para la tarjeta (opcional)
                            <textarea
                                className="field-input min-h-20 resize-none"
                                maxLength={200}
                                value={form.data.gift_message}
                                onChange={(e) => form.setData('gift_message', e.target.value)}
                                placeholder="Feliz cumple, Ana"
                            />
                        </label>
                        {form.errors.gift_message && <p className="field-error">{form.errors.gift_message}</p>}
                        {flavorError && (
                            <p className="field-error" role="alert">
                                {flavorError}
                            </p>
                        )}

                        <button type="submit" className="btn-primary mt-5 w-full justify-between" disabled={remaining !== 0 || form.processing}>
                            <span>{form.processing ? 'Agregando…' : 'Agregar caja al carrito'}</span>
                            <span>{money(box.priceCents)}</span>
                        </button>
                    </aside>
                </form>
            </div>
        </>
    );
}
