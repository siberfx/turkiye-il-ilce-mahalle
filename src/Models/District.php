<?php

declare(strict_types=1);

namespace Siberfx\TurkiyePackage\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Siberfx\TurkiyePackage\Enums\NeighborhoodType;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_active
 */
class District extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('turkiye-package.districts_table', 'districts');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(
            config('turkiye-package.city_model', City::class),
            config('turkiye-package.cities_relation_id', 'city_id')
        );
    }

    public function neighborhoods(): HasMany
    {
        return $this->hasMany(
            config('turkiye-package.neighborhood_model', Neighborhood::class),
            config('turkiye-package.districts_relation_id', 'district_id')
        );
    }

    /**
     * Köy (villages) of the district.
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
