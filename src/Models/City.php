<?php

namespace Siberfx\TurkiyePackage\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table;
    public $timestamps = false;
    protected $fillable = ['id', 'name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('turkiye-adresler.cities_table', 'cities');
    }

    public function districts()
    {
        return $this->hasMany(District::class, 'city_id');
    }
}
