<?php

namespace App\AppPanel\Clusters\Settings\Resources\Users\Pages;
use App\AppPanel\Clusters\Settings\Resources\Users\UserResource;

use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListOrderActivities extends ListActivities
{
    protected static string $resource = UserResource::class;
}
