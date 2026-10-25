<?php

return [
    'name' => 'JCI Carmona',
    'location' => 'Carmona City, Cavite',
    // Chapter contact details supplied by the site owner on 25 October 2026.
    'public_address' => 'Carmona, Philippines, 4116',
    'phone' => '0967 899 4639',
    'phone_uri' => '+639678994639',
    'email' => 'jcicarmona2024@gmail.com',
    'map_url' => 'https://www.bing.com/maps?where1=Carmona%2C%20Philippines%2C%204116',
    'system_name' => 'JCISTEM',
    'facebook_url' => 'https://www.facebook.com/jcicarmonaofficial',
    'instagram_url' => 'https://www.instagram.com/jcicarmona/',
    'national_url' => 'https://jciphilippines.com/',
    'membership_url' => 'https://jciphilippines.com/get-involved/join-us/',
    'international_url' => 'https://jci.cc/',
    'backup_path' => env('CHAPTER_BACKUP_PATH'),
    'backup_private_path' => storage_path('app/private'),
    'mysqldump_path' => env('CHAPTER_MYSQLDUMP_PATH', PHP_OS_FAMILY === 'Windows' && is_file(dirname(base_path(), 2).'/mysql/bin/mysqldump.exe') ? dirname(base_path(), 2).'/mysql/bin/mysqldump.exe' : 'mysqldump'),
];
