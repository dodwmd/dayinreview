<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Role;

use Orchid\Platform\Models\Role;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class RoleListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'roles';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('name', __('Name'))
                ->sort()
                ->cantHide()
                ->filter((string) Input::make())
                ->render(function (Role $role) {
                    // Access attributes array directly to avoid property not found error
                    $attributes = $role->getAttributes();
                    $roleName = $attributes['name'] ?? 'Unknown Role';

                    return Link::make($roleName)
                        ->route('platform.systems.roles.edit', ['role' => $role->getKey()]);
                }),

            TD::make('slug', __('Slug'))
                ->sort()
                ->cantHide()
                ->filter((string) Input::make()),

            TD::make('created_at', __('Created'))
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden()
                ->sort(),

            TD::make('updated_at', __('Last edit'))
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->sort(),
        ];
    }
}
