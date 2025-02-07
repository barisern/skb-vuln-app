<?php

return [
    'mongodb' => [
        'uri' => 'mongodb://root:root123@mongodb:27017',
        'database' => 'shop'
    ],
    
    'jwt_key' => '<random_string_at_least_32_characters_long>',
    'jwt_algorithm' => 'HS256',
    
    'template' => [
        'cache' => false,
        'debug' => true,
        'auto_reload' => true
    ],
    
    'upload_dir' => __DIR__ . '/../../assets/uploads',
    
    'gift_card_prefix' => 'GIFT-',
]; 