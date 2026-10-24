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
            <div className="wrap max-w-lg py-14">
                <h1 className="mb-3 font-display text-4xl font-bold tracking-tight">Seguir mi pedido</h1>
                <p className="mb-8 text-muted">Escribe el código que recibiste al pagar y el WhatsApp que usaste en el pedido.</p>
                <form onSubmit={submit} className="card space-y-5 p-6" noValidate>
                    <label className="field-label">
                        Código de pedido
                        <input
                            className="field-input font-bold tracking-wider uppercase"
                            value={form.data.reference}
                            onChange={(e) => form.setData('reference', e.target.value)}
                            placeholder="RB-XXXXXX"
                            autoComplete="off"
                            aria-invalid={!!form.errors.reference}
                        />
                    </label>
                    <label className="field-label">
                        WhatsApp
                        <input
                            className="field-input"
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
                        <p className="field-error" role="alert">
                            {form.errors.reference ?? form.errors.phone}
                        </p>
                    )}
                    <button type="submit" className="btn-primary w-full" disabled={form.processing}>
                        {form.processing ? 'Buscando…' : 'Ver mi pedido'}
                    </button>
                </form>
            </div>
        </>
    );
}
