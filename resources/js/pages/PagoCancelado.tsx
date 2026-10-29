import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ProductArt } from '@/components/ProductArt';
import { money, whatsappLink } from '@/lib/format';

type Props = { reference: string; totalCents: number; token: string };

export default function PagoCancelado({ reference, totalCents, token }: Props) {
    const { contact } = usePage().props;
    const [busy, setBusy] = useState(false);

    function retry() {
        setBusy(true);
        router.post(`/pedido/${reference}/pagar`, { token }, { onFinish: () => setBusy(false) });
    }

    return (
        <>
            <Head title="Pago no completado" />
            <div className="wrap pt-6 pb-10">
                <div className="grid items-center gap-8 rounded-3xl bg-honey p-6 text-ground sm:p-10 lg:grid-cols-2 lg:p-14">
                    <div>
                        <h1 className="display text-[clamp(32px,4vw,60px)]">No se completó el pago</h1>
                        <p className="mt-5 max-w-[44ch] text-lead leading-snug">
                            Tu pedido <strong>{reference}</strong> por {money(totalCents)} quedó guardado, pero no se hizo ningún cobro. Puedes intentarlo de nuevo cuando quieras.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center gap-3">
                            <button type="button" className="btn min-h-14 bg-ground px-8 text-ink hover:bg-surface" onClick={retry} disabled={busy}>
                                {busy ? 'Abriendo el pago…' : 'Intentar de nuevo'}
                            </button>
                            {contact.whatsapp && (
                                <a
                                    href={whatsappLink(contact.whatsapp, `Hola, tuve un problema al pagar el pedido ${reference}`)}
                                    className="btn min-h-14 bg-ground/15 px-6 hover:bg-ground/25"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    Pedir ayuda por WhatsApp
                                </a>
                            )}
                            <Link href="/" className="inline-flex min-h-11 items-center text-body font-bold underline underline-offset-4">
                                Volver a la tienda
                            </Link>
                        </div>
                    </div>
                    <ProductArt kind="trenzas" tone="dark" className="mx-auto w-full max-w-[380px]" />
                </div>
            </div>
        </>
    );
}
