<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Models\Flavor;
use App\Models\Product;
use Illuminate\Contracts\Session\Session;
use Illuminate\Validation\ValidationException;

/**
 * Carrito guardado en la sesión. Solo guarda IDs y cantidades: los precios
 * y nombres se leen siempre de la base de datos al mostrarlo o cobrarlo.
 */
class Cart
{
    private const KEY = 'cart.lines';

    public function __construct(private readonly Session $session) {}

    /**
     * @param  array<int|string, int>  $flavors  flavor_id => cantidad
     */
    public function add(Product $product, array $flavors, int $quantity = 1, ?string $giftMessage = null): void
    {
        if (! $product->published || ! $product->available) {
            throw ValidationException::withMessages(['product' => 'Ese producto ya no está disponible.']);
        }

        $flavors = $this->normalizeFlavors($flavors);
        $this->assertFlavorsFit($product, $flavors);

        $giftMessage = $giftMessage !== null ? trim($giftMessage) : null;
        $giftMessage = $giftMessage === '' ? null : $giftMessage;

        $lines = $this->raw();
        $key = $this->lineKey($product->id, $flavors, $giftMessage);

        if (! isset($lines[$key]) && count($lines) >= config('rolobox.cart.max_lines')) {
            throw ValidationException::withMessages(['product' => 'Tu carrito ya tiene demasiados productos distintos.']);
        }

        $current = $lines[$key]['quantity'] ?? 0;
        $lines[$key] = [
            'product_id' => $product->id,
            'flavors' => $flavors,
            'quantity' => min($current + $quantity, (int) config('rolobox.cart.max_quantity_per_line')),
            'gift_message' => $giftMessage,
        ];

        $this->session->put(self::KEY, $lines);
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        $lines = $this->raw();
        if (! isset($lines[$key])) {
            return;
        }

        if ($quantity < 1) {
            unset($lines[$key]);
        } else {
            $lines[$key]['quantity'] = min($quantity, (int) config('rolobox.cart.max_quantity_per_line'));
        }

        $this->session->put(self::KEY, $lines);
    }

    public function remove(string $key): void
    {
        $lines = $this->raw();
        unset($lines[$key]);
        $this->session->put(self::KEY, $lines);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    public function count(): int
    {
        return (int) array_sum(array_column($this->raw(), 'quantity'));
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    /** @return array<string, array{product_id: int, flavors: array<int, int>, quantity: int, gift_message: ?string}> */
    public function raw(): array
    {
        $lines = $this->session->get(self::KEY, []);

        return is_array($lines) ? $lines : [];
    }

    /**
     * Arma las líneas con datos actuales de la base. Quita del carrito lo que ya no se vende.
     * Los montos sin prefijo son con tarjeta (precio publicado); los "transfer*" son pagando por transferencia.
     *
     * @return array{lines: list<array<string, mixed>>, subtotalCents: int, boxSubtotalCents: int, transferSubtotalCents: int, transferBoxSubtotalCents: int, count: int, removed: int, nationalReady: bool}
     */
    public function resolve(): array
    {
        $raw = $this->raw();
        $products = Product::whereIn('id', array_column($raw, 'product_id'))->get()->keyBy('id');
        $flavorIds = [];
        foreach ($raw as $line) {
            $flavorIds = array_merge($flavorIds, array_keys($line['flavors']));
        }
        $flavors = Flavor::whereIn('id', array_unique($flavorIds))->where('active', true)->get()->keyBy('id');

        $lines = [];
        $subtotal = 0;
        $boxSubtotal = 0;
        $transferSubtotal = 0;
        $transferBoxSubtotal = 0;
        $removed = 0;
        $nationalReady = true;

        foreach ($raw as $key => $line) {
            $product = $products->get($line['product_id']);
            $lineFlavors = [];
            $firstFlavorImage = null;
            $valid = $product && $product->published && $product->available;

            foreach ($line['flavors'] as $flavorId => $count) {
                $flavor = $flavors->get($flavorId);
                if (! $flavor) {
                    $valid = false;
                    break;
                }
                $nationalReady = $nationalReady && $flavor->ships_nationally;
                $lineFlavors[] = ['id' => $flavor->id, 'name' => $flavor->name, 'count' => (int) $count];
                $firstFlavorImage ??= $flavor->imageUrl();
            }

            if (! $valid) {
                $removed++;
                $this->remove((string) $key);

                continue;
            }

            $transferUnit = $product->priceFor(PaymentMethod::BankTransfer);
            $lineTotal = $product->price_cents * $line['quantity'];
            $transferLineTotal = $transferUnit * $line['quantity'];
            $subtotal += $lineTotal;
            $transferSubtotal += $transferLineTotal;
            if ($product->type === ProductType::Box) {
                $boxSubtotal += $lineTotal;
                $transferBoxSubtotal += $transferLineTotal;
            }

            $lines[] = [
                'key' => (string) $key,
                'productId' => $product->id,
                'name' => $product->name,
                'type' => $product->type->value,
                // Un rol suelto muestra la foto de su sabor; una caja, la foto de la caja.
                'image' => $product->image_path ? asset($product->image_path) : $firstFlavorImage,
                'flavors' => $lineFlavors,
                'quantity' => (int) $line['quantity'],
                'unitPriceCents' => $product->price_cents,
                'lineTotalCents' => $lineTotal,
                'transferUnitPriceCents' => $transferUnit,
                'transferLineTotalCents' => $transferLineTotal,
                'giftMessage' => $line['gift_message'],
            ];
        }

        return [
            'lines' => $lines,
            'subtotalCents' => $subtotal,
            'boxSubtotalCents' => $boxSubtotal,
            'transferSubtotalCents' => $transferSubtotal,
            'transferBoxSubtotalCents' => $transferBoxSubtotal,
            'count' => (int) array_sum(array_column($lines, 'quantity')),
            'removed' => $removed,
            'nationalReady' => $lines !== [] && $nationalReady,
        ];
    }

    /** @return array<int, int> */
    private function normalizeFlavors(array $flavors): array
    {
        $clean = [];
        foreach ($flavors as $id => $count) {
            $id = (int) $id;
            $count = (int) $count;
            if ($id > 0 && $count > 0) {
                $clean[$id] = ($clean[$id] ?? 0) + $count;
            }
        }
        ksort($clean);

        return $clean;
    }

    /** @param  array<int, int>  $flavors */
    private function assertFlavorsFit(Product $product, array $flavors): void
    {
        $total = array_sum($flavors);

        if ($product->type === ProductType::Box && $total !== (int) $product->box_size) {
            throw ValidationException::withMessages([
                'flavors' => "Elige exactamente {$product->box_size} roles para la caja.",
            ]);
        }

        if ($product->type === ProductType::Single && ($total !== 1 || count($flavors) !== 1)) {
            throw ValidationException::withMessages(['flavors' => 'Elige un sabor.']);
        }

        $active = Flavor::whereIn('id', array_keys($flavors))->where('active', true)->count();
        if ($active !== count($flavors)) {
            throw ValidationException::withMessages(['flavors' => 'Uno de los sabores ya no está disponible.']);
        }
    }

    /** @param  array<int, int>  $flavors */
    private function lineKey(int $productId, array $flavors, ?string $giftMessage): string
    {
        return substr(hash('sha256', json_encode([$productId, $flavors, $giftMessage])), 0, 16);
    }
}
