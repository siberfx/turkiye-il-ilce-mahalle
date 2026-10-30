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
 * @property NeighborhoodType $type
 * @property int|null $parent_id
 * @property bool $is_active
 */
class Neighborhood extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $attributes = [
        'type' => 'mahalle',
    ];

    public function getTable(): string
    {
        return config('turkiye-package.neighborhoods_table', 'neighborhoods');
    }

    protected function casts(): array
    {
        return [
            'type' => NeighborhoodType::class,
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

    public function district(): BelongsTo
    {
        return $this->belongsTo(
            config('turkiye-package.district_model', District::class),
            config('turkiye-package.districts_relation_id', 'district_id')
        );
    }

    /**
     * The köy a bağlı belongs to.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(config('turkiye-package.neighborhood_model', self::class), 'parent_id');
    }

    /**
     * The bağlı (mevkii, mezra, ...) of a köy.
     */
    public function children(): HasMany
    {
        return $this->hasMany(config('turkiye-package.neighborhood_model', self::class), 'parent_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, NeighborhoodType $type): void
    {
        $query->where('type', $type->value);
    }

    public function scopeMahalle(Builder $query): void
    {
        $query->where('type', NeighborhoodType::Mahalle->value);
    }

    public function scopeKoy(Builder $query): void
    {
        $query->where('type', NeighborhoodType::Koy->value);
    }

    public function scopeBagli(Builder $query): void
    {
        $query->where('type', NeighborhoodType::Bagli->value);
    }
}
