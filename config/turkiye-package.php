<?php

return [
    // Table names can be customized by the user
    'cities_table' => env('TURKIYE_CITIES_TABLE', 'cities'),
    'districts_table' => env('TURKIYE_DISTRICTS_TABLE', 'districts'),
    'neighborhoods_table' => env('TURKIYE_NEIGHBORHOODS_TABLE', 'neighborhoods'),

    'cities_relation_id' => env('TURKIYE_CITIES_RELATION_ID', 'city_id'),
    'districts_relation_id' => env('TURKIYE_DISTRICTS_RELATION_ID', 'district_id'),
    'neighborhoods_relation_id' => env('TURKIYE_NEIGHBORHOODS_RELATION_ID', 'neighborhood_id'),
];
