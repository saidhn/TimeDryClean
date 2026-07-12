<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;

class OrderWorkflowService
{
    public function transition(Order $order, string $toStatus, string $actorType, int $actorId, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $toStatus, $actorType, $actorId, $note) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $locked->status;

            if (!OrderStatus::canTransition($fromStatus, $toStatus)) {
                throw new InvalidOrderTransitionException($fromStatus, $toStatus);
            }

            $locked->update(['status' => $toStatus]);

            OrderStatusHistory::create([
                'order_id' => $locked->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by_type' => $actorType,
                'changed_by_id' => $actorId,
                'note' => $note,
            ]);

            return $locked;
        });
    }
}
