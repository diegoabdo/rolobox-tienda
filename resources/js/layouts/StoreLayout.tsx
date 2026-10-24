import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FlashMessage } from '@/components/FlashMessage';
import { BagIcon } from '@/components/Icons';
import { LogoMark } from '@/components/Logo';
import { whatsappLink } from '@/lib/format';

export default function StoreLayout({ children }: { children: ReactNode }) {
    const { cartCount, contact } = usePage().props;

    return (
        <div className="flex min-h-screen flex-col">
            <a href="#contenido" className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-ink focus:px-4 focus:py-2 focus:text-cream">
                Saltar al contenido
            </a>
            <div className="bg-ink px-4 py-2.5 text-center text-xs tracking-[0.08em] text-cream">
                Roles por encargo en Xela · Pago seguro con tarjeta
            </div>

            <header className="border-b border-line">
                <div className="wrap flex flex-wrap items-center justify-between gap-x-6 gap-y-3 py-4">
                    <Link href="/" className="flex items-center gap-2.5 text-[28px] font-bold tracking-[-1.4px]" aria-label="RoloBox, inicio">
                        <LogoMark size={42} />
                        <span>
                            RoloBox<span className="text-clay">.</span>
                        </span>
                    </Link>

                    <nav aria-label="Principal" className="order-3 flex w-full gap-6 overflow-x-auto text-sm font-medium sm:order-none sm:w-auto">
                        <Link href="/#menu" className="py-2 whitespace-nowrap hover:text-clay">
                            Menú
                        </Link>
                        <Link href="/armar-caja" className="py-2 whitespace-nowrap hover:text-clay">
                            Arma tu caja
                        </Link>
                        <Link href="/seguir" className="py-2 whitespace-nowrap hover:text-clay">
                            Seguir mi pedido
                        </Link>
                    </nav>

                    <Link
                        href="/carrito"
                        className="flex min-h-11 items-center gap-2.5 rounded-full border border-ink px-4 text-sm font-semibold hover:bg-ink hover:text-cream"
                    >
                        <BagIcon size={18} />
                        Carrito
                        <span className="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-clay px-1.5 text-xs text-[#fff9ee]">
                            {cartCount}
                            <span className="sr-only"> productos</span>
                        </span>
                    </Link>
                </div>
            </header>

            <main id="contenido" className="flex-1">
                {children}
            </main>

            <footer className="border-t border-line">
                <div className="wrap flex flex-wrap items-center justify-between gap-4 py-8 text-xs text-muted">
                    <span className="text-2xl font-bold tracking-tight text-ink">RoloBox.</span>
                    <span>Hecho en Guatemala</span>
                    <nav aria-label="Información" className="flex flex-wrap gap-5">
                        <Link href="/terminos" className="hover:text-ink">
                            Términos
                        </Link>
                        <Link href="/privacidad" className="hover:text-ink">
                            Privacidad y cookies
                        </Link>
                        {contact.whatsapp && (
                            <a href={whatsappLink(contact.whatsapp)} className="hover:text-ink" rel="noopener" target="_blank">
                                WhatsApp
                            </a>
                        )}
                    </nav>
                </div>
            </footer>

            <FlashMessage />
        </div>
    );
}
