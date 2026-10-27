<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/** Una página vista en la tienda. Se guarda sin cookies ni datos personales; se borra pasado un año. */
class PageView extends Model
{
    use MassPrunable;

    public $timestamps = false;

    protected $fillable = ['page', 'visitor', 'referrer', 'is_mobile', 'viewed_at'];

    /** Nombre visible de cada página de la tienda, por nombre de ruta. */
    public const PAGES = [
        'home' => 'Inicio',
        'box' => 'Arma tu caja',
        'cart.show' => 'Carrito',
        'checkout.show' => 'Pagar pedido',
        'orders.show' => 'Estado del pedido',
        'orders.lookup' => 'Seguir mi pedido',
        'payments.return' => 'Regreso del pago',
        'payments.cancelled' => 'Pago cancelado',
        'legal.terms' => 'Términos',
        'legal.privacy' => 'Privacidad',
    ];

    protected function casts(): array
    {
        return [
            'is_mobile' => 'boolean',
            'viewed_at' => 'datetime',
        ];
    }

    public function prunable(): Builder
    {
        return static::where('viewed_at', '<', now()->subYear());
    }
}
