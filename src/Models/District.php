<?php

namespace Siberfx\TurkiyePackage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class District extends Model
{
    protected $table;
    public $timestamps = false;
    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('turkiye-adresler.districts_table', 'districts');
    }

    public function city(): BelongsTo
    {
        $relationName = Str::singular(config('turkiye-adresler.cities_table')) . '_id';

        return $this->belongsTo(City::class, $relationName);
    }

    public function neighborhoods(): HasMany
    {
        $relationName = Str::singular(config('turkiye-adresler.districts_table')) . '_id';

        return $this->hasMany(Neighborhood::class, $relationName);
    }
}
