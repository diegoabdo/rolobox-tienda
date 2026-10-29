import { Head, Link, router, usePage, usePoll } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ChatIcon, CheckIcon } from '@/components/Icons';
import { flavorSummary, longDate, money, shortMoney, whatsappLink } from '@/lib/format';
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

    // El encabezado cuenta en qué punto está el pedido: miel cuando ya está pagado, café mientras se espera.
    const header = order.isPaid
        ? {
              title: order.status === 'paid' ? '¡Pago recibido!' : order.statusLabel,
              text: (
                  <>
                      Gracias, {order.customerName.split(' ')[0]}. Guarda tu código <strong>{order.reference}</strong>: con él puedes consultar este pedido cuando quieras.
                  </>
              ),
              tone: 'bg-honey text-ground',
          }
        : transferPending
          ? {
                title: 'Tu transferencia va en camino',
                text: (
                    <>
                        Cuando el banco la acredite, tu pedido <strong>{order.reference}</strong> queda confirmado y lo preparamos para la fecha que elegiste. Puedes cerrar esta página y consultarlo después con tu código.
                    </>
                ),
                tone: 'bg-surface',
            }
          : waiting
            ? {
                  title: polls < MAX_POLLS ? 'Confirmando tu pago…' : 'Todavía no vemos el pago',
                  text:
                      polls < MAX_POLLS
                          ? 'Esto tarda unos segundos. No cierres esta página.'
                          : 'Si ya pagaste, la confirmación puede tardar unos minutos más; recarga esta página más tarde. Si no completaste el pago, puedes intentarlo de nuevo.',
                  tone: 'bg-surface',
              }
            : { title: order.statusLabel, text: null, tone: 'bg-surface' };

    return (
        <>
            <Head title={`Pedido ${order.reference}`} />
            <div className="wrap pt-6 pb-10">
                <div className={`rounded-3xl p-6 sm:p-10 lg:p-14 ${header.tone}`} aria-live={order.isPaid ? undefined : 'polite'}>
                    <h1 className="display text-[clamp(36px,4.4vw,68px)]">{header.title}</h1>
                    {header.text && <p className="mt-5 max-w-[56ch] text-lead leading-snug">{header.text}</p>}
                </div>

                <div className="mt-3 grid items-start gap-3 lg:grid-cols-12">
                    <section className="rounded-3xl bg-surface p-6 sm:p-10 lg:col-span-7" aria-label="Detalle del pedido">
                        <dl className="grid gap-6 text-body sm:grid-cols-2">
                            <div>
                                <dt className="text-small text-muted">Pedido</dt>
                                <dd className="text-lead font-bold tracking-wide">{order.reference}</dd>
                            </div>
                            <div>
                                <dt className="text-small text-muted">{order.deliveryLabel}</dt>
                                <dd className="font-medium">
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
                                <dt className="text-small text-muted">Forma de pago</dt>
                                <dd className="font-medium">{order.card ?? order.paymentMethodLabel}</dd>
                            </div>
                        </dl>

                        <ul className="mt-8 space-y-5">
                            {order.items.map((item, i) => (
                                <li key={i} className="flex justify-between gap-4 rounded-2xl bg-well p-4 text-body">
                                    <span>
                                        <span className="font-bold">
                                            {item.quantity} × {item.name}
                                        </span>
                                        <span className="block text-muted">{flavorSummary(item.flavors)}</span>
                                        {item.giftMessage && <span className="block text-muted">Tarjeta: “{item.giftMessage}”</span>}
                                    </span>
                                    <span className="font-bold tabular-nums">{money(item.lineTotalCents)}</span>
                                </li>
                            ))}
                        </ul>

                        <dl className="mt-8 space-y-2 text-body">
                            <div className="flex justify-between gap-4">
                                <dt>Subtotal</dt>
                                <dd className="tabular-nums">{money(order.subtotalCents)}</dd>
                            </div>
                            {order.discountCents > 0 && (
                                <div className="flex justify-between gap-4 text-leaf">
                                    <dt>Promoción {order.promotionCode}</dt>
                                    <dd className="tabular-nums">− {money(order.discountCents)}</dd>
                                </div>
                            )}
                            <div className="flex justify-between gap-4">
                                <dt>Envío</dt>
                                <dd className="tabular-nums">{order.shippingCents === 0 ? 'Gratis' : money(order.shippingCents)}</dd>
                            </div>
                            <div className="flex items-end justify-between gap-4 pt-4">
                                <dt className="text-lead font-bold">Total</dt>
                                <dd className="display text-[clamp(44px,4vw,64px)] tabular-nums">{shortMoney(order.totalCents)}</dd>
                            </div>
                        </dl>
                    </section>

                    <div className="space-y-3 lg:col-span-5">
                        {order.isPaid && (
                            <ol className="space-y-2 rounded-3xl bg-surface p-6 sm:p-8" aria-label="Estado del pedido">
                                {order.timeline.map((step, i) => (
                                    <li
                                        key={step.key}
                                        className={`flex items-center gap-4 rounded-2xl p-3 ${step.current ? 'bg-honey text-ground' : step.done ? 'bg-well' : ''}`}
                                    >
                                        <span
                                            className={`flex size-10 shrink-0 items-center justify-center rounded-xl text-body font-bold ${
                                                step.done ? 'bg-leaf text-ground' : step.current ? 'bg-ground text-honey' : 'bg-well text-muted'
                                            }`}
                                        >
                                            {step.done ? <CheckIcon size={18} /> : i + 1}
                                        </span>
                                        <span className={`text-body ${step.done || step.current ? 'font-bold' : 'text-muted'}`}>
                                            {step.label}
                                            {step.current && <span className="sr-only"> (estado actual)</span>}
                                        </span>
                                    </li>
                                ))}
                            </ol>
                        )}

                        <div className="flex flex-col gap-3">
                            {canRetryPayment && !waiting && (
                                <button type="button" className="btn-primary min-h-14" onClick={retryPayment} disabled={retrying}>
                                    {retrying ? 'Abriendo el pago…' : 'Pagar este pedido'}
                                </button>
                            )}
                            {canRetryPayment && waiting && polls >= MAX_POLLS && (
                                <button type="button" className="btn-primary min-h-14" onClick={retryPayment} disabled={retrying}>
                                    Intentar el pago de nuevo
                                </button>
                            )}
                            {contact.whatsapp && (
                                <a
                                    href={whatsappLink(contact.whatsapp, `Hola, tengo una consulta sobre mi pedido ${order.reference}`)}
                                    target="_blank"
                                    rel="noopener"
                                    className="btn-outline min-h-14"
                                >
                                    <ChatIcon size={18} /> Escríbenos por WhatsApp
                                </a>
                            )}
                            <Link href="/" className="btn-outline min-h-14">
                                Volver a la tienda
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
