const quetzales = new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' });

export function money(cents: number): string {
    return quetzales.format(cents / 100);
}

/** "2026-11-07" → "sábado 7 de noviembre", sin depender de la zona horaria del navegador. */
export function longDate(isoDate: string): string {
    const [y, m, d] = isoDate.split('-').map(Number);
    const date = new Date(Date.UTC(y, m - 1, d, 12));
    const text = new Intl.DateTimeFormat('es-GT', { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' }).format(date);
    return text.charAt(0).toUpperCase() + text.slice(1);
}

export function flavorSummary(flavors: { name: string; count: number }[]): string {
    return flavors.map((f) => (f.count > 1 ? `${f.count} ${f.name.toLowerCase()}` : f.name.toLowerCase())).join(' · ');
}

export function whatsappLink(number: string, text?: string): string {
    const digits = number.replace(/\D/g, '');
    return `https://wa.me/${digits}${text ? `?text=${encodeURIComponent(text)}` : ''}`;
}
