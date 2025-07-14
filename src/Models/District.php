<?php

namespace Siberfx\TurkiyePackage\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table;
    public $timestamps = false;
    protected $fillable = ['id', 'city_id', 'name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('turkiye-adresler.districts_table', 'districts');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function neighborhoods()
    {
        return $this->hasMany(Neighborhood::class, 'district_id');
    }
}
