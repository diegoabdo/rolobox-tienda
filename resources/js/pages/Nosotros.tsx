import { Head, Link } from '@inertiajs/react';

/** Texto de borrador: la panadería debe confirmar o reemplazar su misión y su visión. */
const MISSION = 'Hornear pan y repostería artesanal por encargo, con ingredientes de calidad y mucho cuidado, para que cada mesa de Quetzaltenango tenga algo recién hecho cuando lo necesita.';
const VISION = 'Ser la panadería de Xela a la que todos recurren para celebrar, regalar y compartir, creciendo con cada pedido sin perder lo hecho a mano.';

export default function Nosotros() {
    return (
        <>
            <Head title="Nosotros" />
            <div className="wrap pt-6 pb-10">
                <h1 className="display text-[clamp(40px,5vw,80px)]" data-reveal>
                    Nosotros
                </h1>
                <p className="mt-4 max-w-[46ch] text-lead leading-snug" data-reveal>
                    Una panadería artesanal de Quetzaltenango que hornea solo lo que se pide.
                </p>

                <div className="mt-10 grid gap-3 md:grid-cols-2">
                    <article className="flex flex-col justify-between gap-12 rounded-3xl bg-honey p-6 text-ground sm:p-10 lg:p-14" data-reveal>
                        <h2 className="display text-[clamp(36px,4vw,64px)]">Misión</h2>
                        <p className="max-w-[36ch] text-lead leading-snug">{MISSION}</p>
                    </article>
                    <article className="flex flex-col justify-between gap-12 rounded-3xl bg-surface p-6 sm:p-10 lg:p-14" data-reveal>
                        <h2 className="display text-[clamp(36px,4vw,64px)]">Visión</h2>
                        <p className="max-w-[36ch] text-lead leading-snug">{VISION}</p>
                    </article>
                </div>

                <figure className="mt-3" data-reveal>
                    <img
                        src="/images/xela-panoramica.jpg"
                        alt="Quetzaltenango al amanecer, con el volcán Santa María al fondo"
                        width={1920}
                        height={1079}
                        loading="lazy"
                        className="h-[46svh] min-h-[280px] w-full rounded-3xl object-cover object-[50%_46%]"
                    />
                    <figcaption className="mt-3 text-small text-muted">Quetzaltenango y el volcán Santa María al amanecer</figcaption>
                </figure>

                <div className="mt-12 flex flex-wrap items-center gap-x-8 gap-y-4">
                    <Link href="/#menu" className="btn-primary min-h-14 px-8 text-body">
                        Ver el menú
                    </Link>
                    <Link href="/armar-caja" className="inline-flex min-h-11 items-center text-body font-bold underline underline-offset-4">
                        Armar una caja
                    </Link>
                </div>
            </div>
        </>
    );
}
