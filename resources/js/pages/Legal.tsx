import { Head } from '@inertiajs/react';

type Props = { title: string; version: string; draft: boolean; html: string };

export default function Legal({ title, version, draft, html }: Props) {
    return (
        <>
            <Head title={title} />
            <article className="wrap max-w-3xl py-14">
                <h1 className="mb-3 display text-head">{title}</h1>
                <p className="text-muted">Versión {version}</p>
                {draft && (
                    <p className="my-6 bg-well p-4 text-small leading-relaxed" role="note">
                        Borrador en revisión. Este texto todavía no fue revisado por un asesor legal.
                    </p>
                )}
                {/* Contenido propio del repositorio (resources/legal), no texto ingresado por usuarios. */}
                <div className="legal-content" dangerouslySetInnerHTML={{ __html: html }} />
            </article>
        </>
    );
}
