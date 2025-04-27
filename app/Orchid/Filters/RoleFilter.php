<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Platform\Models\Role;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Select;

class RoleFilter extends Filter
{
    /**
     * @var array
     */
    public $parameters = [
        'role',
    ];

    public function name(): string
    {
        return __('Role');
    }

    public function run(Builder $builder): Builder
    {
        return $builder->whereHas('roles', function (Builder $query) {
            $query->where('slug', $this->request->get('role'));
        });
    }

    /**
     * @return Field[]
     */
    public function display(): array
    {
        return [
            Select::make('role')
                ->fromModel(Role::class, 'name', 'slug')
                ->empty()
                ->value($this->request->get('role'))
                ->title(__('Role')),
        ];
    }

    public function value(): string
    {
        $role = Role::query()->where('slug', $this->request->get('role'))->first();

        if ($role === null) {
            return $this->name();
        }

        // Access attributes array directly to avoid property not found error
        $attributes = $role->getAttributes();
        $roleName = $attributes['name'] ?? 'Unknown Role';

        return $this->name().': '.$roleName;
    }
}
