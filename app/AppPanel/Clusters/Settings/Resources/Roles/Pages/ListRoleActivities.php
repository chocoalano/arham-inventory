<?php

namespace App\AppPanel\Clusters\Settings\Resources\Roles\Pages;
use App\AppPanel\Clusters\Settings\Resources\Roles\RoleResource;

use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListRoleActivities extends ListActivities
{
    protected static string $resource = RoleResource::class;
}
