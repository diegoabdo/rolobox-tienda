import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FlashMessage } from '@/components/FlashMessage';
import { BagIcon } from '@/components/Icons';
import { whatsappLink } from '@/lib/format';
import { useReveal } from '@/lib/reveal';

export default function StoreLayout({ children }: { children: ReactNode }) {
    const { cartCount, contact } = usePage().props;
    const { url } = usePage();
    useReveal(url);

    return (
        <div className="flex min-h-svh flex-col">
            <a href="#contenido" className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:bg-ink focus:px-4 focus:py-2 focus:text-ground">
                Saltar al contenido
            </a>

            <header className="relative z-20">
                <div className="wrap flex flex-wrap items-center justify-between gap-x-2 gap-y-2 py-5 sm:gap-x-8">
                    <Link href="/" className="flex items-center gap-3" aria-label="Twist & Rolls, inicio">
                        <img src="/images/twist-and-rolls-mark.png" alt="" width={44} height={36} className="h-8 w-auto sm:h-9" />
                        <span className="display text-[24px] leading-none sm:text-[36px]">Twist & Rolls</span>
                    </Link>

                    <nav aria-label="Principal" className="order-3 flex w-full flex-wrap gap-x-5 text-[16px] font-medium sm:order-none sm:mr-auto sm:w-auto sm:gap-x-6 sm:text-[17px]">
                        <Link href="/#menu" className="py-2 whitespace-nowrap transition-colors hover:text-honey">
                            Menú
                        </Link>
                        <Link href="/armar-caja" className="py-2 whitespace-nowrap transition-colors hover:text-honey">
                            Arma tu caja
                        </Link>
                        <Link href="/nosotros" className="py-2 whitespace-nowrap transition-colors hover:text-honey">
                            Nosotros
                        </Link>
                        <Link href="/seguir" className="py-2 whitespace-nowrap transition-colors hover:text-honey">
                            Seguir mi pedido
                        </Link>
                    </nav>

                    <Link href="/carrito" className="btn-primary min-h-11 gap-2 px-4 sm:gap-2.5 sm:px-5">
                        <BagIcon size={18} />
                        Carrito
                        <span className="inline-flex h-6 min-w-6 items-center justify-center bg-ground px-1.5 text-[14px] text-honey tabular-nums">
                            {cartCount}
                            <span className="sr-only"> productos</span>
                        </span>
                    </Link>
                </div>
            </header>

            <main id="contenido" className="flex-1">
                {children}
            </main>

            {/* El reverso de la hoja de pedido: los datos arriba y el nombre a todo el ancho. */}
            <footer className="mt-32 rounded-t-[2.5rem] bg-honey text-ground sm:mt-48">
                <div className="wrap pt-14 pb-10">
                    <div className="grid gap-10 text-body sm:grid-cols-2 lg:grid-cols-12">
                        <nav aria-label="Tienda" className="lg:col-span-3">
                            <p className="mb-3 text-lead font-bold">Tienda</p>
                            <ul className="space-y-2">
                                <li>
                                    <Link href="/#menu" className="underline-offset-4 hover:underline">
                                        Menú
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/armar-caja" className="underline-offset-4 hover:underline">
                                        Arma tu caja
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/carrito" className="underline-offset-4 hover:underline">
                                        Carrito
                                    </Link>
                                </li>
                            </ul>
                        </nav>
                        <nav aria-label="Pedidos" className="lg:col-span-3">
                            <p className="mb-3 text-lead font-bold">Pedidos y consultas</p>
                            <ul className="space-y-2">
                                <li>
                                    <Link href="/seguir" className="underline-offset-4 hover:underline">
                                        Seguir mi pedido
                                    </Link>
                                </li>
                                {contact.whatsapp && (
                                    <li>
                                        <a href={whatsappLink(contact.whatsapp)} className="underline-offset-4 hover:underline" rel="noopener" target="_blank">
                                            Escríbenos por WhatsApp
                                        </a>
                                    </li>
                                )}
                                {contact.email && (
                                    <li>
                                        <a href={`mailto:${contact.email}`} className="break-all underline-offset-4 hover:underline">
                                            {contact.email}
                                        </a>
                                    </li>
                                )}
                            </ul>
                        </nav>
                        <nav aria-label="Información" className="lg:col-span-2">
                            <p className="mb-3 text-lead font-bold">Información</p>
                            <ul className="space-y-2">
                                <li>
                                    <Link href="/nosotros" className="underline-offset-4 hover:underline">
                                        Misión y visión
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/terminos" className="underline-offset-4 hover:underline">
                                        Términos
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/privacidad" className="underline-offset-4 hover:underline">
                                        Privacidad y cookies
                                    </Link>
                                </li>
                            </ul>
                        </nav>
                        <div className="lg:col-span-4">
                            <p className="mb-3 text-lead font-bold">Horneamos en Quetzaltenango</p>
                            <p>
                                Panadería artesanal por encargo, con reparto en Xela. Pago seguro con Visa, Mastercard o transferencia.
                                {contact.company && ` Twist & Rolls es una marca de ${contact.company}.`}
                            </p>
                        </div>
                    </div>

                    <div className="mt-14 text-small">
                        <p>
                            © {new Date().getFullYear()} {contact.company ? `${contact.company} · ` : ''}Twist & Rolls · Hecho en Xela, Guatemala. Foto de Quetzaltenango:{' '}
                            <a href="https://commons.wikimedia.org/w/index.php?curid=91397697" className="underline underline-offset-3">
                                “Panoramica de Quetzaltenango”
                            </a>{' '}
                            de Jvito3312,{' '}
                            <a href="https://creativecommons.org/licenses/by-sa/4.0/" className="underline underline-offset-3">
                                CC BY-SA 4.0
                            </a>
                            , vía Wikimedia Commons. Parque Central:{' '}
                            <a href="https://commons.wikimedia.org/wiki/File:Parque_Central_de_Quetzaltenango,_Guatemala.jpg" className="underline underline-offset-3">
                                “Parque Central de Quetzaltenango, Guatemala”
                            </a>{' '}
                            de Harry Diaz 93,{' '}
                            <a href="https://creativecommons.org/licenses/by-sa/4.0" className="underline underline-offset-3">
                                CC BY-SA 4.0
                            </a>
                            , vía Wikimedia Commons.
                        </p>
                    </div>
                </div>
            </footer>

            <FlashMessage />
        </div>
    );
}
