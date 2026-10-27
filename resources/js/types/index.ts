export type Flavor = {
    id: number;
    name: string;
    slug: string;
    description: string;
    allergens: string | null;
    image: string | null;
    shipsNationally: boolean;
};

export type Product = {
    id: number;
    name: string;
    slug: string;
    type: 'box' | 'single';
    description: string;
    boxSize: number | null;
    /** Precio publicado: con tarjeta. */
    priceCents: number;
    /** Precio pagando por transferencia (igual o menor). */
    transferPriceCents: number;
    image: string | null;
    available: boolean;
};

export type PaymentMethod = 'card' | 'bank_transfer';

export type Zone = {
    id: number;
    name: string;
    department: string;
    municipality: string;
    deliveryType: 'local' | 'national';
    feeCents: number;
    notes: string | null;
};

export type PromotionBanner = {
    code: string;
    title: string;
    description: string;
    endsAt: string | null;
    firstOrderOnly: boolean;
};

export type CartFlavor = { id: number; name: string; count: number };

export type CartLine = {
    key: string;
    productId: number;
    name: string;
    type: 'box' | 'single';
    image: string | null;
    flavors: CartFlavor[];
    quantity: number;
    unitPriceCents: number;
    lineTotalCents: number;
    transferUnitPriceCents: number;
    transferLineTotalCents: number;
    giftMessage: string | null;
};

export type ResolvedCart = {
    lines: CartLine[];
    subtotalCents: number;
    boxSubtotalCents: number;
    transferSubtotalCents: number;
    transferBoxSubtotalCents: number;
    count: number;
    removed: number;
    nationalReady: boolean;
};

export type Quote = {
    zoneId: number | null;
    subtotalCents: number;
    discountCents: number;
    shippingCents: number;
    totalCents: number;
    freeShipping: boolean;
    promotionCode: string | null;
    promotionDescription: string | null;
    promotionError: string | null;
};

export type TimelineStep = { key: string; label: string; done: boolean; current: boolean };

export type CustomerOrder = {
    reference: string;
    status: string;
    statusLabel: string;
    isPaid: boolean;
    deliveryType: 'local' | 'national';
    deliveryLabel: string;
    zone: string;
    deliveryDate: string | null;
    deliverySlot: string | null;
    customerName: string;
    items: { name: string; quantity: number; flavors: CartFlavor[]; lineTotalCents: number; giftMessage: string | null }[];
    subtotalCents: number;
    discountCents: number;
    shippingCents: number;
    totalCents: number;
    promotionCode: string | null;
    paymentMethod: PaymentMethod;
    paymentMethodLabel: string;
    transferInProgress: boolean;
    paidAt: string | null;
    card: string | null;
    timeline: TimelineStep[];
};

export type SharedProps = {
    cartCount: number;
    flash: { status: string | null };
    contact: { whatsapp: string; email: string; company: string };
};
