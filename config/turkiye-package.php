<?php

return [
    // Table names can be customized by the user
    'cities_table' => env('TURKIYE_CITIES_TABLE', 'cities'),
    'districts_table' => env('TURKIYE_DISTRICTS_TABLE', 'districts'),
    'neighborhoods_table' => env('TURKIYE_NEIGHBORHOODS_TABLE', 'neighborhoods'),

    // Foreign key column names
    'cities_relation_id' => env('TURKIYE_CITIES_RELATION_ID', 'city_id'),
    'districts_relation_id' => env('TURKIYE_DISTRICTS_RELATION_ID', 'district_id'),

    // Plate codes of the cities listed first by City::ordered() (cities.sort_order); every other
    // city follows in Turkish alphabetical order. Default: the three most populous provinces —
    // İstanbul, Ankara, İzmir. E.g. TURKIYE_FEATURED_CITIES="34,6,35,16" to add Bursa.
    // After changing it run `php artisan turkiye:sort-cities` (or re-seed).
    'featured_cities' => array_map(intval(...), array_filter(explode(',', (string) env('TURKIYE_FEATURED_CITIES', '34,6,35')))),

    // Model classes used by the relationships; extend the package models to customise
    'city_model' => Siberfx\TurkiyePackage\Models\City::class,
    'district_model' => Siberfx\TurkiyePackage\Models\District::class,
    'neighborhood_model' => Siberfx\TurkiyePackage\Models\Neighborhood::class,
];
