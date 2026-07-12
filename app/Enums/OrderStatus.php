<?php

namespace App\Enums;

class OrderStatus
{
    const PLACED = 'placed';
    const PICKUP_SCHEDULED = 'pickup_scheduled';
    const AT_FACILITY = 'at_facility';
    const SORTING = 'sorting';
    const WASHING = 'washing';
    const READY_FOR_DELIVERY = 'ready_for_delivery';
    const OUT_FOR_DELIVERY = 'out_for_delivery';
    const DELIVERED = 'delivered';
    const CANCELLED = 'cancelled';

    private const TRANSITIONS = [
        self::PLACED => [self::PICKUP_SCHEDULED, self::CANCELLED],
        self::PICKUP_SCHEDULED => [self::AT_FACILITY, self::CANCELLED],
        self::AT_FACILITY => [self::SORTING, self::CANCELLED],
        self::SORTING => [self::WASHING, self::CANCELLED],
        self::WASHING => [self::READY_FOR_DELIVERY, self::CANCELLED],
        self::READY_FOR_DELIVERY => [self::OUT_FOR_DELIVERY, self::CANCELLED],
        self::OUT_FOR_DELIVERY => [self::DELIVERED, self::CANCELLED],
        self::DELIVERED => [],
        self::CANCELLED => [],
    ];

    public static function all(): array
    {
        return array_keys(self::TRANSITIONS);
    }

    public static function transitionsFrom(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::transitionsFrom($from), true);
    }

    public static function label(string $status): string
    {
        return __('messages.order_status_' . $status);
    }
}
