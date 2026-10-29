import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { BackIcon } from '@/components/Icons';
import { QuantityStepper } from '@/components/QuantityStepper';
import { boxAccepts } from '@/lib/catalog';
import { money, shortMoney } from '@/lib/format';
import type { Category, Flavor, Product } from '@/types';

type Props = {
    categories: Category[];
    flavors: Flavor[];
    boxes: Product[];
    selectedBox: string;
    preselect: string | null;
};

type BoxForm = {
    product_id: number;
    flavors: Record<string, number>;
    quantity: number;
    gift_message: string;
    go_to_cart: boolean;
};

/** Cuántas piezas de cada sabor hay en la caja, en la forma que espera el carrito. */
function countPicks(picks: number[]): Record<string, number> {
    return picks.reduce<Record<string, number>>((acc, id) => ({ ...acc, [id]: (acc[id] ?? 0) + 1 }), {});
}

export default function ArmarCaja({ categories, flavors, boxes, selectedBox, preselect }: Props) {
    const [box, setBox] = useState<Product>(boxes.find((b) => b.slug === selectedBox) ?? boxes[0]);
    const size = box.boxSize ?? 6;

    const initialPicks = useMemo(() => {
        const chosen = flavors.find((f) => f.slug === preselect);
        return chosen && boxAccepts(box, chosen) ? [chosen.id] : [];
        // Solo al abrir la página: después mandan los botones.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    /** Sabores en el orden en que se agregaron, para que cada pieza nueva ocupe el siguiente espacio sin mover las demás. */
    const [picks, setPicks] = useState<number[]>(initialPicks);

    const form = useForm<BoxForm>({
        product_id: box.id,
        flavors: countPicks(initialPicks),
        quantity: 1,
        gift_message: '',
        go_to_cart: true,
    });

    const available = flavors.filter((f) => boxAccepts(box, f));
    const total = picks.length;
    const remaining = size - total;
    const slots = picks.map((id) => flavors.find((f) => f.id === id)).filter((f): f is Flavor => !!f);
    const columns = size <= 4 ? 2 : size <= 9 ? 3 : 4;

    function update(next: number[], nextBox: Product = box) {
        setPicks(next);
        form.setData((data) => ({ ...data, product_id: nextBox.id, flavors: countPicks(next) }));
    }

    function chooseBox(next: Product) {
        if (next.id === box.id) return;
        setBox(next);
        // Se conservan los sabores que la nueva caja también acepta, hasta que quepan.
        const nextSize = next.boxSize ?? 6;
        const kept = picks.filter((id) => {
            const flavor = flavors.find((f) => f.id === id);
            return flavor && boxAccepts(next, flavor);
        });
        update(kept.slice(0, nextSize), next);
    }

    function setCount(id: number, value: number) {
        const count = picks.filter((pick) => pick === id).length;
        const last = picks.lastIndexOf(id);
        update(value > count ? [...picks, id] : last === -1 ? picks : picks.filter((_, i) => i !== last));
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/carrito', { preserveScroll: true });
    }

    const errors = form.errors as Partial<Record<keyof BoxForm | 'product', string>>;
    const flavorError = errors.flavors ?? errors.product;

    // Una caja mixta agrupa los sabores por categoría.
    const groups = box.mixed
        ? categories.map((category) => ({ category, items: available.filter((f) => f.categoryId === category.id) })).filter((g) => g.items.length > 0)
        : [{ category: null, items: available }];

    return (
        <>
            <Head title="Arma tu caja" />
            <div className="wrap py-10">
                <Link href="/#menu" className="mb-6 inline-flex min-h-11 items-center gap-2 text-body font-semibold hover:text-accent">
                    <BackIcon size={18} /> Volver al menú
                </Link>

                <form onSubmit={submit} className="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_380px]">
                    <div>
                        <h1 className="mb-6 display text-head">Arma tu caja</h1>

                        {boxes.length > 1 && (
                            <div role="radiogroup" aria-label="Tipo de caja" className="mb-8 flex flex-wrap gap-2">
                                {boxes.map((option) => {
                                    const selected = option.id === box.id;
                                    return (
                                        <button
                                            key={option.id}
                                            type="button"
                                            role="radio"
                                            aria-checked={selected}
                                            onClick={() => chooseBox(option)}
                                            className={`min-h-12 cursor-pointer px-6 text-body font-semibold transition-colors ${
                                                selected ? 'bg-accent text-ground' : 'bg-well hover:bg-edge'
                                            } rounded-xl`}
                                        >
                                            {option.name} · {shortMoney(option.priceCents)}
                                        </button>
                                    );
                                })}
                            </div>
                        )}

                        <p className="mb-8 text-body">
                            {box.name} · elige {size} piezas · {money(box.priceCents)}
                            {box.transferPriceCents < box.priceCents && (
                                <span className="font-semibold text-leaf"> · {money(box.transferPriceCents)} con transferencia</span>
                            )}
                            {box.description && <span className="mt-1 block text-muted">{box.description}</span>}
                        </p>

                        <fieldset>
                            <legend className="sr-only">Sabores de la caja</legend>
                            {available.length === 0 && <p className="bg-surface p-6 text-body rounded-2xl">Esta caja todavía no tiene sabores disponibles.</p>}
                            <div className="space-y-10">
                                {groups.map((group) => (
                                    <div key={group.category?.id ?? 'todos'}>
                                        {group.category && <h2 className="mb-4 text-lead font-extrabold tracking-[-0.03em]">{group.category.name}</h2>}
                                        <ul className="space-y-3">
                                            {group.items.map((flavor) => {
                                                const count = picks.filter((id) => id === flavor.id).length;
                                                return (
                                                    <li key={flavor.id} className="card flex items-center gap-4 p-3 pr-4">
                                                        {flavor.image ? (
                                                            <img src={flavor.image} alt="" className="size-16 shrink-0 object-cover rounded-xl" />
                                                        ) : (
                                                            <span
                                                                className="flex size-16 shrink-0 items-center justify-center bg-honey text-lead font-extrabold text-ground rounded-xl"
                                                                aria-hidden="true"
                                                            >
                                                                {flavor.name.charAt(0)}
                                                            </span>
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
                                    </div>
                                ))}
                            </div>
                        </fieldset>
                    </div>

                    <aside className="lg:sticky lg:top-6">
                        <div className="bg-honey p-4 rounded-2xl">
                            <ul className="grid gap-2" style={{ gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }} aria-label="Tu caja">
                                {Array.from({ length: size }, (_, i) => {
                                    const flavor = slots[i];
                                    return (
                                        <li key={i} className="aspect-square">
                                            {flavor?.image ? (
                                                <img
                                                    key={`${flavor.id}-${i}`}
                                                    src={flavor.image}
                                                    alt={flavor.name}
                                                    className="roll-in size-full object-cover [--roll-index:0] rounded-2xl"
                                                />
                                            ) : flavor ? (
                                                <span className="roll-in flex size-full items-center justify-center bg-ink p-1 text-center text-small font-bold text-ground [--roll-index:0] rounded-2xl">
                                                    {flavor.name}
                                                </span>
                                            ) : (
                                                <span className="flex size-full items-center justify-center bg-ground/25 text-2xl text-ground" aria-label="Espacio libre">
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
