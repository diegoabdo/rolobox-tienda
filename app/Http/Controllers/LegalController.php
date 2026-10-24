<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function terms(): Response
    {
        return $this->page('Términos de compra', 'terminos');
    }

    public function privacy(): Response
    {
        return $this->page('Privacidad y cookies', 'privacidad');
    }

    private function page(string $title, string $file): Response
    {
        // Contenido propio del repositorio (resources/legal), no texto de usuarios.
        $html = strtr(file_get_contents(resource_path("legal/{$file}.html")), [
            '{{empresa}}' => e(config('rolobox.legal_entity') ?: '[razón social pendiente]'),
            '{{correo}}' => e(config('rolobox.contact.email') ?: 'pedidos@example.com'),
        ]);

        return Inertia::render('Legal', [
            'title' => $title,
            'version' => config('rolobox.legal_version'),
            'draft' => (bool) config('rolobox.legal_draft'),
            'html' => $html,
        ]);
    }
}
