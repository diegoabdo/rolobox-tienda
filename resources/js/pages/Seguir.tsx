import { Head, useForm } from '@inertiajs/react';

export default function Seguir() {
    const form = useForm({ reference: '', phone: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/seguir');
    }

    return (
        <>
            <Head title="Seguir mi pedido" />
            <div className="wrap pt-6 pb-10">
                <h1 className="display text-[clamp(36px,4.4vw,68px)]">Seguir mi pedido</h1>
                <p className="mt-4 max-w-[46ch] text-lead leading-snug">Escribe el código que recibiste al pagar y el WhatsApp que usaste en el pedido.</p>

                <form onSubmit={submit} noValidate className="mt-10 grid rounded-3xl bg-honey text-ground lg:grid-cols-[1fr_auto_1fr]">
                    <div className="flex flex-col justify-between gap-6 p-6 sm:p-10 lg:p-14">
                        <p className="display text-[clamp(40px,4vw,64px)]">Tu código empieza con RB</p>
                        <p className="max-w-[34ch] text-body">Lo encuentras en la confirmación de tu pedido. Con él y tu WhatsApp ves en qué paso va.</p>
                    </div>

                    <div className="relative flex items-center justify-between lg:flex-col" aria-hidden="true">
                        <span className="-ml-4 size-8 shrink-0 rounded-full bg-ground lg:-mt-4 lg:ml-0" />
                        <span className="flex flex-1 justify-evenly lg:flex-col lg:items-center lg:self-stretch lg:py-4">
                            {Array.from({ length: 14 }, (_, i) => (
                                <span key={i} className={`size-2 rounded-full bg-ground ${i > 9 ? 'hidden sm:block' : ''}`} />
                            ))}
                        </span>
                        <span className="-mr-4 size-8 shrink-0 rounded-full bg-ground lg:-mb-4 lg:mr-0" />
                    </div>

                    <div className="space-y-4 p-6 sm:p-10 lg:p-14">
                        <label className="block text-body font-bold">
                            Código de pedido
                            <input
                                className="mt-2 block min-h-14 w-full rounded-xl bg-ground/10 px-4 text-body font-bold tracking-wider text-ground uppercase placeholder:font-normal placeholder:tracking-normal placeholder:text-ground/70 placeholder:normal-case focus:outline-3 focus:outline-ground aria-invalid:outline-3 aria-invalid:outline-[#7a1d0c]"
                                value={form.data.reference}
                                onChange={(e) => form.setData('reference', e.target.value)}
                                placeholder="RB-XXXXXX"
                                autoComplete="off"
                                aria-invalid={!!form.errors.reference}
                            />
                        </label>
                        <label className="block text-body font-bold">
                            WhatsApp
                            <input
                                className="mt-2 block min-h-14 w-full rounded-xl bg-ground/10 px-4 text-body text-ground placeholder:text-ground/70 focus:outline-3 focus:outline-ground aria-invalid:outline-3 aria-invalid:outline-[#7a1d0c]"
                                type="tel"
                                inputMode="tel"
                                value={form.data.phone}
                                onChange={(e) => form.setData('phone', e.target.value)}
                                placeholder="5555 5555"
                                autoComplete="tel"
                                aria-invalid={!!form.errors.phone}
                            />
                        </label>
                        {(form.errors.reference || form.errors.phone) && (
                            <p className="text-body font-bold text-[#6a1608]" role="alert">
                                {form.errors.reference ?? form.errors.phone}
                            </p>
                        )}
                        <button type="submit" className="btn min-h-14 w-full bg-ground text-ink hover:bg-surface" disabled={form.processing}>
                            {form.processing ? 'Buscando…' : 'Ver mi pedido'}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}
