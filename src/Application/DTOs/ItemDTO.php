<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Application\DTOs;

/**
 * Item (Product) Data Transfer Object.
 */
final readonly class ItemDTO
{
    public function __construct(
        public string $itemId,
        public string $itemName,
        public float $price,
        public int $quantity,
        public ?string $category,
        public ?string $brand,
        public ?string $variant,
        public ?float $discount
    ) {
    }

    /**
     * Create from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            itemId: $data['item_id'] ?? $data['id'] ?? '',
            itemName: $data['item_name'] ?? $data['name'] ?? '',
            price: (float) ($data['price'] ?? $data['item_price'] ?? 0),
            quantity: (int) ($data['quantity'] ?? 1),
            category: $data['category'] ?? $data['item_category'] ?? null,
            brand: $data['brand'] ?? $data['item_brand'] ?? null,
            variant: $data['variant'] ?? $data['item_variant'] ?? null,
            discount: isset($data['discount']) ? (float) $data['discount'] : null
        );
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'item_id' => $this->itemId,
            'item_name' => $this->itemName,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'category' => $this->category,
            'brand' => $this->brand,
            'variant' => $this->variant,
            'discount' => $this->discount,
        ];
    }
}
