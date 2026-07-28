<?php

use App\Models\User;

$u = User::where('email', 'kaprog@test.com')->first();
echo $u->name.': '.$u->getRoleNames().PHP_EOL;
$u2 = User::where('email', 'siswa@test.com')->first();
echo $u2->name.': '.$u2->getRoleNames().PHP_EOL;
$u3 = User::where('email', 'hubin@test.com')->first();
echo $u3->name.': '.$u3->getRoleNames().PHP_EOL;
