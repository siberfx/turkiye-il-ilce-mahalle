<?php

namespace Siberfx\TurkiyePackage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Neighborhood extends Model
{
    protected $table;
    public $timestamps = false;
    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('turkiye-package.neighborhoods_table', 'neighborhoods');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(
            District::class, 
            config('turkiye-package.districts_relation_id', 'district_id')
        );
    }
}
