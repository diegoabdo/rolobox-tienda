import { Head, Link, router, usePage, usePoll } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ChatIcon, CheckIcon } from '@/components/Icons';
import { flavorSummary, longDate, money, whatsappLink } from '@/lib/format';
import type { CustomerOrder } from '@/types';

type Props = {
    order: CustomerOrder;
    token: string;
    canRetryPayment: boolean;
};

const MAX_POLLS = 30; // ~2 minutos esperando la confirmación del pago

export default function Pedido({ order, token, canRetryPayment }: Props) {
    const { contact } = usePage().props;
    const transferPending = !order.isPaid && order.transferInProgress;
    const waiting = !order.isPaid && order.status === 'pending_payment' && !transferPending;
    const [polls, setPolls] = useState(0);
    const [retrying, setRetrying] = useState(false);

    const { stop } = usePoll(4000, { only: ['order', 'canRetryPayment'], onFinish: () => setPolls((n) => n + 1) }, { autoStart: waiting });

    useEffect(() => {
        if (!waiting || polls >= MAX_POLLS) stop();
    }, [waiting, polls, stop]);

    function retryPayment() {
        setRetrying(true);
        router.post(`/pedido/${order.reference}/pagar`, { token }, { onFinish: () => setRetrying(false) });
    }

    return (
        <>
            <Head title={`Pedido ${order.reference}`} />
            <div className="wrap max-w-3xl py-10">
                {order.isPaid ? (
                    <div className="mb-8 flex flex-col items-center gap-3 text-center">
                        <span className="flex size-20 items-center justify-center rounded-full bg-leaf text-ground">
                            <CheckIcon size={38} strokeWidth={2.5} />
                        </span>
                        <h1 className="display text-head">
                            {order.status === 'paid' ? '¡Pago recibido!' : order.statusLabel}
                        </h1>
                        <p className="max-w-md text-muted">
                            Gracias, {order.customerName.split(' ')[0]}. Guarda tu código <strong className="text-ink">{order.reference}</strong>: con él puedes
                            consultar este pedido cuando quieras.
                        </p>
                    </div>
                ) : transferPending ? (
                    <div className="mb-8 text-center" aria-live="polite">
                        <h1 className="mb-3 display text-head">Tu transferencia va en camino</h1>
                        <p className="mx-auto max-w-md text-muted">
                            Cuando el banco la acredite, tu pedido <strong className="text-ink">{order.reference}</strong> queda confirmado y lo
                            preparamos para la fecha que elegiste. Puedes cerrar esta página y consultarlo después con tu código.
                        </p>
                    </div>
                ) : waiting ? (
                    <div className="mb-8 text-center" aria-live="polite">
                        <h1 className="mb-3 display text-head">
                            {polls < MAX_POLLS ? 'Confirmando tu pago…' : 'Todavía no vemos el pago'}
                        </h1>
                        <p className="mx-auto max-w-md text-muted">
                            {polls < MAX_POLLS
                                ? 'Esto tarda unos segundos. No cierres esta página.'
                                : 'Si ya pagaste, la confirmación puede tardar unos minutos más; recarga esta página más tarde. Si no completaste el pago, puedes intentarlo de nuevo.'}
                        </p>
                    </div>
                ) : (
                    <h1 className="mb-8 text-center display text-head">{order.statusLabel}</h1>
                )}

                {order.isPaid && (
                    <ol className="card mb-6 grid grid-cols-2 gap-4 p-5 sm:grid-cols-4" aria-label="Estado del pedido">
                        {order.timeline.map((step, i) => (
                            <li key={step.key} className="flex items-center gap-3 sm:flex-col sm:text-center">
                                <span
                                    className={`flex size-8 shrink-0 items-center justify-center rounded-full text-small font-bold ${
                                        step.done ? 'bg-leaf text-ground' : step.current ? 'bg-accent text-ground' : 'bg-well text-muted'
                                    }`}
                                >
                                    {step.done ? <CheckIcon size={16} /> : i + 1}
                                </span>
                                <span className={`text-small ${step.done || step.current ? 'font-bold' : 'text-muted'}`}>
                                    {step.label}
                                    {step.current && <span className="sr-only"> (estado actual)</span>}
                                </span>
                            </li>
                        ))}
                    </ol>
                )}

                <section className="card mb-6 p-5" aria-label="Detalle del pedido">
                    <dl className="grid gap-3 text-small sm:grid-cols-2">
                        <div>
                            <dt className="text-muted">Pedido</dt>
                            <dd className="font-bold tracking-wide">{order.reference}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">{order.deliveryLabel}</dt>
                            <dd>
                                {order.zone}
                                {order.deliveryDate && (
                                    <span className="block">
                                        {longDate(order.deliveryDate)}
                                        {order.deliverySlot ? `, ${order.deliverySlot}` : ''}
                                    </span>
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted">Forma de pago</dt>
                            <dd>{order.card ?? order.paymentMethodLabel}</dd>
                        </div>
                    </dl>

                    <ul className="my-5 space-y-3 py-4 text-small">
                        {order.items.map((item, i) => (
                            <li key={i} className="flex justify-between gap-3">
                                <span>
                                    {item.quantity} × {item.name}
                                    <span className="block text-small text-muted">{flavorSummary(item.flavors)}</span>
                                    {item.giftMessage && <span className="block text-small text-muted">Tarjeta: “{item.giftMessage}”</span>}
                                </span>
                                <span className="font-semibold">{money(item.lineTotalCents)}</span>
                            </li>
                        ))}
                    </ul>

                    <dl className="space-y-2 text-small">
                        <div className="flex justify-between">
                            <dt>Subtotal</dt>
                            <dd>{money(order.subtotalCents)}</dd>
                        </div>
                        {order.discountCents > 0 && (
                            <div className="flex justify-between text-leaf">
                                <dt>Promoción {order.promotionCode}</dt>
                                <dd>− {money(order.discountCents)}</dd>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <dt>Envío</dt>
                            <dd>{order.shippingCents === 0 ? 'Gratis' : money(order.shippingCents)}</dd>
                        </div>
                        <div className="flex justify-between pt-3 text-body font-bold">
                            <dt>Total</dt>
                            <dd>{money(order.totalCents)}</dd>
                        </div>
                    </dl>
                </section>

                <div className="flex flex-wrap justify-center gap-3">
                    {canRetryPayment && !waiting && (
                        <button type="button" className="btn-primary" onClick={retryPayment} disabled={retrying}>
                            {retrying ? 'Abriendo el pago…' : 'Pagar este pedido'}
                        </button>
                    )}
                    {canRetryPayment && waiting && polls >= MAX_POLLS && (
                        <button type="button" className="btn-primary" onClick={retryPayment} disabled={retrying}>
                            Intentar el pago de nuevo
                        </button>
                    )}
                    {contact.whatsapp && (
                        <a
                            href={whatsappLink(contact.whatsapp, `Hola, tengo una consulta sobre mi pedido ${order.reference}`)}
                            target="_blank"
                            rel="noopener"
                            className="btn-outline"
                        >
                            <ChatIcon size={18} /> Escríbenos por WhatsApp
                        </a>
                    )}
                    <Link href="/" className="btn-outline">
                        Volver a la tienda
                    </Link>
                </div>
            </div>
        </>
    );
}
