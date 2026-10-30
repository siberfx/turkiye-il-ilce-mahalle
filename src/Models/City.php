<?php

declare(strict_types=1);

namespace Siberfx\TurkiyePackage\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Siberfx\TurkiyePackage\Enums\NeighborhoodType;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_active
 */
class City extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('turkiye-package.cities_table', 'cities');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function districts(): HasMany
    {
        return $this->hasMany(
            config('turkiye-package.district_model', District::class),
            config('turkiye-package.cities_relation_id', 'city_id')
        );
    }

    public function neighborhoods(): HasMany
    {
        return $this->hasMany(
            config('turkiye-package.neighborhood_model', Neighborhood::class),
            config('turkiye-package.cities_relation_id', 'city_id')
        );
    }

    /**
     * Köy (villages) of the city.
     */
    public function villages(): HasMany
    {
        return $this->neighborhoods()->where('type', NeighborhoodType::Koy->value);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
