<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cuenta las páginas vistas de la tienda para las estadísticas del panel.
 * No usa cookies ni guarda la IP: el visitante se identifica con una huella que cambia cada día.
 */
class RecordPageView
{
    private const BOTS = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|curl|wget|python|headless|lighthouse|monitor/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldRecord($request, $response)) {
            PageView::create([
                'page' => $request->route()->getName(),
                'visitor' => $this->visitor($request),
                'referrer' => $this->referrer($request),
                'is_mobile' => (bool) preg_match('/Mobi|Android|iPhone/i', (string) $request->userAgent()),
                'viewed_at' => now(),
            ]);
        }

        return $response;
    }

    private function shouldRecord(Request $request, Response $response): bool
    {
        $page = $request->route()?->getName();

        return $request->isMethod('GET')
            && $response->getStatusCode() === 200
            && $page !== null
            && array_key_exists($page, PageView::PAGES)
            // Recargas parciales (la cotización del checkout) y precargas no son visitas nuevas.
            && ! $request->hasHeader('X-Inertia-Partial-Component')
            && ! in_array(strtolower((string) ($request->header('Purpose') ?? $request->header('Sec-Purpose'))), ['prefetch', 'prerender'], true)
            // Las visitas de quien administra la tienda no cuentan.
            && ! $request->user()
            && ! preg_match(self::BOTS, (string) $request->userAgent());
    }

    private function visitor(Request $request): string
    {
        $day = now(config('rolobox.timezone'))->toDateString();

        return hash_hmac('sha256', $day.'|'.$request->ip().'|'.$request->userAgent(), (string) config('app.key'));
    }

    /** Solo el dominio desde el que llegó, y solo si es otro sitio. */
    private function referrer(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);
        if (! is_string($host) || $host === '' || $host === $request->getHost()) {
            return null;
        }

        return Str::limit(Str::lower(preg_replace('/^(www|m|l|lm)\./', '', $host)), 120, '');
    }
}
