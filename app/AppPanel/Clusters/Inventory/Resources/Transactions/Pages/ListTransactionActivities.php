<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Transactions\Pages;
use App\AppPanel\Clusters\Inventory\Resources\Transactions\TransactionResource;

use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListTransactionActivities extends ListActivities
{
    protected static string $resource = TransactionResource::class;
}
