<?php

namespace Siberfx\TurkiyePackage\Models;

use Illuminate\Database\Eloquent\Model;

class Neighborhood extends Model
{
    protected $table;
    public $timestamps = false;
    protected $fillable = ['id', 'district_id', 'name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('turkiye-adresler.neighborhoods_table', 'neighborhoods');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
}
