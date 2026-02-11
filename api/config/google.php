<?php

return [
    
    'api_key' => getenv('GOOGLE_API_KEY') ?: '',

    
    'place_id' => getenv('GOOGLE_PLACE_ID') ?: '',

    
    'cache_duration' => 86400,

    
    'cache_dir' => __DIR__ . '/../../cache/google',

    
    'max_reviews' => 10,

    
    'fields' => 'name,rating,user_ratings_total,reviews,url'
];
