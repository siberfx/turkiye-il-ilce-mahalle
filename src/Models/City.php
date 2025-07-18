<?php

namespace Siberfx\TurkiyePackage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $table;
    public $timestamps = false;
    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('turkiye-package.cities_table', 'cities');
    }

    public function districts(): HasMany
    {
        return $this->hasMany(
            District::class, 
            config('turkiye-package.cities_relation_id', 'city_id')
        );
    }
}
