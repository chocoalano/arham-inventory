<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Payments\Pages;
use App\AppPanel\Clusters\Inventory\Resources\Payments\PaymentResource;

use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListPaymentActivities extends ListActivities
{
    protected static string $resource = PaymentResource::class;
}
