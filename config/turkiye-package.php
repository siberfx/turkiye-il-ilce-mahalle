<?php

return [
    // Table names can be customized by the user
    'cities_table' => env('TURKIYE_CITIES_TABLE', 'cities'),
    'districts_table' => env('TURKIYE_DISTRICTS_TABLE', 'districts'),
    'neighborhoods_table' => env('TURKIYE_NEIGHBORHOODS_TABLE', 'neighborhoods'),

    // Foreign key column names
    'cities_relation_id' => env('TURKIYE_CITIES_RELATION_ID', 'city_id'),
    'districts_relation_id' => env('TURKIYE_DISTRICTS_RELATION_ID', 'district_id'),

    // Model classes used by the relationships; extend the package models to customise
    'city_model' => Siberfx\TurkiyePackage\Models\City::class,
    'district_model' => Siberfx\TurkiyePackage\Models\District::class,
    'neighborhood_model' => Siberfx\TurkiyePackage\Models\Neighborhood::class,
];
