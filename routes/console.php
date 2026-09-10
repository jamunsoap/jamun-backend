<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:setup', function () {
    \App\Models\User::updateOrCreate(
        ['email' => 'jamunsoap01@gmail.com'],
        [
            'name' => 'Jamun Admin',
            'password' => \Illuminate\Support\Facades\Hash::make('jamunsoap01@gmail.com'),
            'role' => 'admin',
        ]
    );
    $this->info('Admin account successfully set: jamunsoap01@gmail.com / jamunsoap01@gmail.com');
})->purpose('Set or update master admin credentials');
