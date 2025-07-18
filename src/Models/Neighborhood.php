<?php

namespace Siberfx\TurkiyePackage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Neighborhood extends Model
{
    protected $table;
    public $timestamps = false;
    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('turkiye-adresler.neighborhoods_table', 'neighborhoods');
    }

    public function district(): BelongsTo
    {
        $relationName = Str::singular(config('turkiye-adresler.districts_table')) . '_id';

        return $this->belongsTo(District::class, $relationName);
    }
}
