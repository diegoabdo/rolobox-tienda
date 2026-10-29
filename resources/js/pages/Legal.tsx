import { Head } from '@inertiajs/react';

type Props = { title: string; version: string; draft: boolean; html: string };

export default function Legal({ title, version, draft, html }: Props) {
    return (
        <>
            <Head title={title} />
            <div className="wrap pt-6 pb-10">
                <div className="rounded-3xl bg-honey p-6 text-ground sm:p-10 lg:p-14">
                    <h1 className="display text-[clamp(36px,4.4vw,68px)]">{title}</h1>
                    <p className="mt-4 text-lead">Versión {version}</p>
                    {draft && (
                        <p className="mt-6 max-w-[60ch] rounded-2xl bg-ground/15 p-4 text-body" role="note">
                            Borrador en revisión. Este texto todavía no fue revisado por un asesor legal.
                        </p>
                    )}
                </div>

                {/* Contenido propio del repositorio (resources/legal), no texto ingresado por usuarios. */}
                <article className="mt-3 rounded-3xl bg-surface p-6 sm:p-10 lg:p-14">
                    <div className="legal-content max-w-[72ch]" dangerouslySetInnerHTML={{ __html: html }} />
                </article>
            </div>
        </>
    );
}
