<?php

namespace Siberfx\TurkiyePackage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;

class District extends Model
{
    protected $table;
    public $timestamps = false;
    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('turkiye-package.districts_table', 'districts');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(
            City::class, 
            config('turkiye-package.cities_relation_id', 'city_id')
        );
    }

    public function neighborhoods(): HasMany
    {
        return $this->hasMany(
            Neighborhood::class, 
            config('turkiye-package.districts_relation_id', 'district_id')
        );
    }
}
