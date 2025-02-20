<?php

namespace App\Enums;

class OrderStatus
{
    const PENDING = 'Pending';
    const PROCESSING = 'Processing';
    const SHIPPED = 'Shipped';
    const COMPLETED = 'Completed';
    const CANCELLED = 'Cancelled';
}
