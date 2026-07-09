<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use App\Services\Access\UserService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! Role::query()->where('slug', 'super-admin')->exists()) {
    (new RolesAndPermissionsSeeder)->run();
}

$email = 'admin@agricart.test';
$password = 'password';

$existing = User::query()->where('email', $email)->first();

if ($existing instanceof User) {
    $existing->update([
        'password' => $password,
        'status' => User::STATUS_ACTIVE,
        'role_id' => Role::query()->where('slug', 'super-admin')->value('id'),
    ]);
    echo "Updated existing user #{$existing->id} ({$email}) — password reset to: {$password}\n";

    exit(0);
}

$role = Role::query()->where('slug', 'super-admin')->firstOrFail();
$userService = app(UserService::class);

$user = User::query()->create([
    'user_number' => $userService->nextUserNumber(),
    'name' => 'Super Admin',
    'email' => $email,
    'password' => $password,
    'role_id' => $role->id,
    'status' => User::STATUS_ACTIVE,
]);

echo "Created Super Admin user #{$user->id}\n";
echo "Email: {$email}\n";
echo "Password: {$password}\n";
echo 'Admin login: ' . url('/admin/login') . "\n";
