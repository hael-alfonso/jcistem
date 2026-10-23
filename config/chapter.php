<?php

return [
    'name' => 'JCI Carmona',
    'location' => 'Carmona City, Cavite',
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
