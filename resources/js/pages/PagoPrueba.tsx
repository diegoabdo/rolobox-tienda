import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { money } from '@/lib/format';
import type { PaymentMethod } from '@/types';

type Props = { paymentId: number; reference: string; totalCents: number; paid: boolean; method: PaymentMethod };

type FakeAction = 'pagar' | 'transferencia-iniciada' | 'cancelar';

/** Simula la página del procesador de pagos. Solo existe en desarrollo. */
export default function PagoPrueba({ paymentId, reference, totalCents, paid, method }: Props) {
    const [busy, setBusy] = useState(false);
    const byTransfer = method === 'bank_transfer';

    function send(action: FakeAction) {
        setBusy(true);
        router.post(`/pago-de-prueba/${paymentId}/${action}`, {}, { onFinish: () => setBusy(false) });
    }

    return (
        <div className="flex min-h-screen items-center justify-center bg-[#eef1f5] p-5 font-sans text-[#1c2430]">
            <Head title="Pago de prueba" />
            <div className="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <p className="mb-4 rounded-lg bg-[#fff4d6] px-3 py-2 text-xs font-bold text-[#6b5000]">PROCESADOR SIMULADO · solo desarrollo</p>
                <p className="text-sm text-[#5a6575]">Pagar a RoloBox</p>
                <p className="mb-1 text-3xl font-bold">{money(totalCents)}</p>
                <p className="mb-6 text-sm text-[#5a6575]">Pedido {reference}</p>
                <div className="mb-6 rounded-xl border border-[#d5dbe3] p-4 text-sm">
                    {byTransfer ? 'Transferencia bancaria (ACH de prueba)' : 'Visa •••• 4242 (tarjeta de prueba)'}
                </div>
                {paid ? (
                    <p className="font-semibold text-[#1d6b39]">Este pago ya se confirmó.</p>
                ) : (
                    <div className="flex flex-col gap-2">
                        <button type="button" disabled={busy} onClick={() => send('pagar')} className="min-h-12 cursor-pointer rounded-xl bg-[#1c2430] font-bold text-white">
                            {byTransfer ? 'Transferencia acreditada' : 'Pagar'}
                        </button>
                        {byTransfer && (
                            <button
                                type="button"
                                disabled={busy}
                                onClick={() => send('transferencia-iniciada')}
                                className="min-h-12 cursor-pointer rounded-xl border border-[#1c2430] font-bold"
                            >
                                Transferencia iniciada (aún no llega)
                            </button>
                        )}
                        <button type="button" disabled={busy} onClick={() => send('cancelar')} className="min-h-12 cursor-pointer rounded-xl font-semibold text-[#5a6575]">
                            Cancelar y volver a la tienda
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}
