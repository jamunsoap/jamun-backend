<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = config('services.shiprocket.email');
$password = config('services.shiprocket.password');
$url = config('services.shiprocket.api_url');

echo "Email: " . $email . "\n";
echo "Password: " . $password . "\n";
echo "URL: " . $url . "\n";

$response = Illuminate\Support\Facades\Http::post("{$url}/auth/login", [
    'email' => $email,
    'password' => $password,
]);

echo "Login Status: " . $response->status() . "\n";
echo "Login Body: " . $response->body() . "\n";
