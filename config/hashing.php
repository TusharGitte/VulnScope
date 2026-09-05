<?php

return [
    'driver' => 'bcrypt',
    'bcrypt' => ['rounds' => (int) env('BCRYPT_ROUNDS', 12), 'verify' => true],
    'argon' => ['memory' => 65536, 'threads' => 1, 'time' => 4],
    'rehash_on_login' => true,
];
