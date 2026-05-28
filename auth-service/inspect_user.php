<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::with('profile.role')->where('email', 'admin@example.com')->first();
if (!$user) {
    echo "User 'admin' not found.\n";
    exit;
}

echo "User Details:\n";
print_r($user->toArray());
echo "\n";
$rolePermissions = \Illuminate\Support\Facades\DB::table('role_permission')->get();
echo "\nRole_Permission Links in DB:\n";
print_r($rolePermissions->toArray());
echo "\n";
