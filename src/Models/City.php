<?php

namespace Siberfx\TurkiyePackage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class City extends Model
{
    protected $table;
    public $timestamps = false;
    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('turkiye-adresler.cities_table', 'cities');
    }

    public function districts(): HasMany
    {
        $relationName = Str::singular(config('turkiye-adresler.districts_table')) . '_id';

        return $this->hasMany(District::class, $relationName);
    }
}
