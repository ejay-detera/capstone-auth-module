<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::where('email', 'finance-admin@example.com')->first();
if(!$user) {
    echo "User not found\n";
    exit;
}
if(!$user->credentials) {
    echo "Credentials not found\n";
    exit;
}
echo "User ID: " . $user->id . "\n";
echo "Password Hash: " . $user->credentials->password_hash . "\n";
echo "Hash check for 'password': " . (\Illuminate\Support\Facades\Hash::check('password', $user->credentials->password_hash) ? 'true' : 'false') . "\n";
