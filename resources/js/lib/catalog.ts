import type { Flavor, Product } from '@/types';

/** ¿Esta caja acepta este sabor? Las mismas reglas que `Product::accepts()` en el servidor. */
export function boxAccepts(box: Product, flavor: Flavor): boolean {
    if (box.mixed) return flavor.mixEligible;
    return flavor.categoryId !== null && flavor.categoryId === box.categoryId && flavor.boxEligible;
}

/** Productos que se piden sueltos en una categoría (las presentaciones: completa, porción, pequeña…). */
export function singlesOf(products: Product[], categoryId: number): Product[] {
    return products.filter((p) => p.type === 'single' && p.categoryId === categoryId);
}

/** Cajas en las que puede ir un sabor. */
export function boxesFor(products: Product[], flavor: Flavor): Product[] {
    return products.filter((p) => p.type === 'box' && boxAccepts(p, flavor));
}
