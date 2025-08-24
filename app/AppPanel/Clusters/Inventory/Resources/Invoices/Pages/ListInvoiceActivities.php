<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Invoices\Pages;
use App\AppPanel\Clusters\Inventory\Resources\Invoices\InvoiceResource;

use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListInvoiceActivities extends ListActivities
{
    protected static string $resource = InvoiceResource::class;
}
