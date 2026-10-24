import { Head } from '@inertiajs/react';

type Props = { title: string; version: string; draft: boolean; html: string };

export default function Legal({ title, version, draft, html }: Props) {
    return (
        <>
            <Head title={title} />
            <article className="wrap max-w-3xl py-14">
                <p className="eyebrow mb-4">Claro, desde el principio</p>
                <h1 className="mb-3 font-display text-[clamp(38px,5vw,56px)] leading-tight font-bold tracking-tight">{title}</h1>
                <p className="text-muted">Versión {version}</p>
                {draft && (
                    <p className="my-6 rounded-xl border border-[#cfab72] bg-[#f4e1bd] p-4 text-sm leading-relaxed" role="note">
                        Borrador en revisión. Este texto todavía no fue revisado por un asesor legal.
                    </p>
                )}
                {/* Contenido propio del repositorio (resources/legal), no texto ingresado por usuarios. */}
                <div className="legal-content" dangerouslySetInnerHTML={{ __html: html }} />
            </article>
        </>
    );
}
