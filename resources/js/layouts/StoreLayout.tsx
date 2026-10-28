import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FlashMessage } from '@/components/FlashMessage';
import { BagIcon } from '@/components/Icons';
import { BoxMark } from '@/components/Logo';
import { whatsappLink } from '@/lib/format';
import { useReveal } from '@/lib/reveal';

export default function StoreLayout({ children }: { children: ReactNode }) {
    const { cartCount, contact } = usePage().props;
    const { url } = usePage();
    useReveal(url);

    return (
        <div className="flex min-h-svh flex-col">
            <a href="#contenido" className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-ink focus:px-4 focus:py-2 focus:text-ground">
                Saltar al contenido
            </a>

            <header className="relative z-20">
                <div className="wrap flex flex-wrap items-center justify-between gap-x-3 gap-y-2 py-5 sm:gap-x-8">
                    <Link href="/" className="flex items-center gap-2.5 text-[22px] font-extrabold tracking-[-0.05em] sm:text-[26px]" aria-label="Twist & Rolls, inicio">
                        <img src="/images/twist-and-rolls-mark.png" alt="" width={44} height={36} className="h-9 w-auto rounded-md" />
                        Twist & Rolls
                    </Link>

                    <nav aria-label="Principal" className="order-3 -mx-1 flex w-full gap-6 overflow-x-auto px-1 text-body font-semibold sm:order-none sm:mr-auto sm:w-auto">
                        <Link href="/#menu" className="py-2 whitespace-nowrap transition-colors hover:text-accent">
                            Menú
                        </Link>
                        <Link href="/armar-caja" className="py-2 whitespace-nowrap transition-colors hover:text-accent">
                            Arma tu caja
                        </Link>
                        <Link href="/seguir" className="py-2 whitespace-nowrap transition-colors hover:text-accent">
                            Seguir mi pedido
                        </Link>
                    </nav>

                    <Link href="/carrito" className="btn-primary min-h-11 gap-2 px-4 sm:gap-2.5 sm:px-5">
                        <BagIcon size={18} />
                        Carrito
                        <span className="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-ground px-1.5 text-small text-accent tabular-nums">
                            {cartCount}
                            <span className="sr-only"> productos</span>
                        </span>
                    </Link>
                </div>
            </header>

            <main id="contenido" className="flex-1">
                {children}
            </main>

            <footer className="mt-32 bg-surface pt-16 pb-10 sm:mt-48">
                <div className="wrap">
                    <BoxMark className="h-auto w-full text-accent" />

                    <div className="mt-14 grid gap-10 text-small sm:grid-cols-2 lg:grid-cols-12">
                        <nav aria-label="Tienda" className="lg:col-span-3">
                            <p className="mb-3 text-body font-semibold">Tienda</p>
                            <ul className="space-y-2">
                                <li>
                                    <Link href="/#menu" className="hover:text-accent">
                                        Menú
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/armar-caja" className="hover:text-accent">
                                        Arma tu caja
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/carrito" className="hover:text-accent">
                                        Carrito
                                    </Link>
                                </li>
                            </ul>
                        </nav>
                        <nav aria-label="Pedidos" className="lg:col-span-3">
                            <p className="mb-3 text-body font-semibold">Pedidos y consultas</p>
                            <ul className="space-y-2">
                                <li>
                                    <Link href="/seguir" className="hover:text-accent">
                                        Seguir mi pedido
                                    </Link>
                                </li>
                                {contact.whatsapp && (
                                    <li>
                                        <a href={whatsappLink(contact.whatsapp)} className="hover:text-accent" rel="noopener" target="_blank">
                                            Escríbenos por WhatsApp
                                        </a>
                                    </li>
                                )}
                                {contact.email && (
                                    <li>
                                        <a href={`mailto:${contact.email}`} className="break-all hover:text-accent">
                                            {contact.email}
                                        </a>
                                    </li>
                                )}
                            </ul>
                        </nav>
                        <nav aria-label="Información" className="lg:col-span-2">
                            <p className="mb-3 text-body font-semibold">Información</p>
                            <ul className="space-y-2">
                                <li>
                                    <Link href="/terminos" className="hover:text-accent">
                                        Términos
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/privacidad" className="hover:text-accent">
                                        Privacidad y cookies
                                    </Link>
                                </li>
                            </ul>
                        </nav>
                        <div className="lg:col-span-4">
                            <p className="mb-3 text-body font-semibold">Horneamos en Quetzaltenango</p>
                            <p>
                                Panadería artesanal por encargo, con reparto en Xela. Pago seguro con Visa, Mastercard o transferencia.
                                {contact.company && ` Twist & Rolls es una marca de ${contact.company}.`}
                            </p>
                        </div>
                    </div>

                    <div className="mt-14 flex flex-wrap items-center justify-between gap-4 text-small text-muted">
                        <p>
                            © {new Date().getFullYear()} {contact.company ? `${contact.company} · ` : ''}Twist & Rolls · Hecho en Xela, Guatemala. Foto de Quetzaltenango:{' '}
                            <a href="https://commons.wikimedia.org/w/index.php?curid=91397697" className="underline underline-offset-3 hover:text-ink">
                                “Panoramica de Quetzaltenango”
                            </a>{' '}
                            de Jvito3312,{' '}
                            <a href="https://creativecommons.org/licenses/by-sa/4.0/" className="underline underline-offset-3 hover:text-ink">
                                CC BY-SA 4.0
                            </a>
                            , vía Wikimedia Commons.
                        </p>
                        <Link href="/armar-caja" className="btn-primary min-h-11 px-5 text-small">
                            Armar mi caja
                        </Link>
                    </div>
                </div>
            </footer>

            <FlashMessage />
        </div>
    );
}
