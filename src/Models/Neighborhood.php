<?php

declare(strict_types=1);

namespace Siberfx\TurkiyePackage\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_active
 */
class Neighborhood extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('turkiye-package.neighborhoods_table', 'neighborhoods');
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

    public function district(): BelongsTo
    {
        return $this->belongsTo(
            config('turkiye-package.district_model', District::class),
            config('turkiye-package.districts_relation_id', 'district_id')
        );
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
