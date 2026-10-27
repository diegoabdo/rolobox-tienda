import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
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

/** Cuántos roles de cada sabor hay en la caja, en la forma que espera el carrito. */
function countPicks(picks: number[]): Record<string, number> {
    return picks.reduce<Record<string, number>>((acc, id) => ({ ...acc, [id]: (acc[id] ?? 0) + 1 }), {});
}

export default function ArmarCaja({ box, flavors, preselect }: Props) {
    const size = box.boxSize ?? 6;
    const initialPicks = useMemo(() => {
        const chosen = flavors.find((f) => f.slug === preselect);
        return chosen ? [chosen.id] : [];
    }, [flavors, preselect]);

    /** Sabores en el orden en que se agregaron, para que cada rol nuevo ocupe el siguiente espacio sin mover los demás. */
    const [picks, setPicks] = useState<number[]>(initialPicks);

    const form = useForm<BoxForm>({
        product_id: box.id,
        flavors: countPicks(initialPicks),
        quantity: 1,
        gift_message: '',
        go_to_cart: true,
    });

    const total = picks.length;
    const remaining = size - total;
    const slots = picks.map((id) => flavors.find((f) => f.id === id)).filter((f): f is Flavor => !!f);

    function setCount(id: number, value: number) {
        const count = picks.filter((pick) => pick === id).length;
        const last = picks.lastIndexOf(id);
        const next = value > count ? [...picks, id] : last === -1 ? picks : picks.filter((_, i) => i !== last);
        setPicks(next);
        form.setData('flavors', countPicks(next));
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
                <Link href="/#menu" className="mb-6 inline-flex min-h-11 items-center gap-2 text-body font-semibold hover:text-accent">
                    <BackIcon size={18} /> Volver al menú
                </Link>

                <form onSubmit={submit} className="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_380px]">
                    <div>
                        <h1 className="mb-2 display text-head">Arma tu caja</h1>
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
                                                <img src={flavor.image} alt="" className="size-16 shrink-0 rounded-full object-cover" />
                                            ) : (
                                                <div className="size-16 shrink-0 rounded-full bg-well" aria-hidden="true" />
                                            )}
                                            <div className="min-w-0 flex-1">
                                                <p className="text-lead font-extrabold tracking-[-0.03em]">{flavor.name}</p>
                                                <p className="text-body">{flavor.description}</p>
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
                        <div className="bg-kraft p-4">
                            <ul className="grid grid-cols-3 gap-2" aria-label="Tu caja">
                                {Array.from({ length: size }, (_, i) => {
                                    const flavor = slots[i];
                                    return (
                                        <li key={i} className="aspect-square">
                                            {flavor?.image ? (
                                                <img
                                                    key={`${flavor.id}-${i}`}
                                                    src={flavor.image}
                                                    alt={flavor.name}
                                                    className="roll-in size-full rounded-full object-cover [--roll-index:0]"
                                                />
                                            ) : flavor ? (
                                                <span className="roll-in flex size-full items-center justify-center rounded-full bg-ink p-1 text-center text-small font-bold text-ground [--roll-index:0]">
                                                    {flavor.name}
                                                </span>
                                            ) : (
                                                <span className="flex size-full items-center justify-center rounded-full bg-ground/25 text-2xl text-ground" aria-label="Espacio libre">
                                                    +
                                                </span>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                        <p className="mt-3 text-center text-small font-bold" aria-live="polite">
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
