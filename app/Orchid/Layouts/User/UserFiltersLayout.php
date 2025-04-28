<?php

namespace App\Orchid\Layouts\User;

use App\Orchid\Filters\RoleFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class UserFiltersLayout extends Selection
{
    /**
     * @return Filter[]
     */
    #[\Override]
    public function filters(): array
    {
        return [
            new RoleFilter,
        ];
    }
}
