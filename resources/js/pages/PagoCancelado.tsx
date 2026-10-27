import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
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
            <div className="wrap max-w-lg py-16 text-center">
                <h1 className="mb-3 display text-head">No se completó el pago</h1>
                <p className="mb-8 text-muted">
                    Tu pedido <strong className="text-ink">{reference}</strong> por {money(totalCents)} quedó guardado, pero no se hizo ningún cobro. Puedes
                    intentarlo de nuevo cuando quieras.
                </p>
                <div className="flex flex-col gap-3">
                    <button type="button" className="btn-primary" onClick={retry} disabled={busy}>
                        {busy ? 'Abriendo el pago…' : 'Intentar de nuevo'}
                    </button>
                    {contact.whatsapp && (
                        <a href={whatsappLink(contact.whatsapp, `Hola, tuve un problema al pagar el pedido ${reference}`)} className="btn-outline" target="_blank" rel="noopener">
                            Pedir ayuda por WhatsApp
                        </a>
                    )}
                    <Link href="/" className="py-2 text-small font-semibold underline underline-offset-4">
                        Volver a la tienda
                    </Link>
                </div>
            </div>
        </>
    );
}
