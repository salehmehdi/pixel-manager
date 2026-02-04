<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

/**
 * Supported pixel event types across all platforms.
 *
 * Each event type represents a specific user action that can be tracked
 * and sent to one or more marketing platforms.
 */
enum EventType: string
{
    case SEARCH = 'search';
    case SUBSCRIPTION = 'subscription';
    case ADD_TO_CART = 'add_to_cart';
    case PURCHASE = 'purchase';
    case VIEW_ITEM = 'view_item';
    case COMPLETED_REGISTRATION = 'completed_registration';
    case BEGIN_CHECKOUT = 'begin_checkout';
    case VIEW_CART = 'view_cart';
    case ADD_PAYMENT_INFO = 'add_payment_info';
    case ADD_TO_WISHLIST = 'add_to_wishlist';
    case PAGE_VIEW = 'page_view';
    case CUSTOMIZE_PRODUCT = 'customize_product';

    /**
     * Get all event types as an array of strings.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $type) => $type->value, self::cases());
    }

    /**
     * Try to create from string value, returns null if invalid.
     *
     * @param string $value
     * @return self|null
     */
    public static function tryFromString(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Check if the given string is a valid event type.
     *
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }
}
