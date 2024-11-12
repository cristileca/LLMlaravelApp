<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(User $user)
    {
        $admin = $user->whereEmail('apb+admin@neurony.ro')->first();

        if (!($admin instanceof User && $admin->exists)) {
            $admin = $user->create([
                'name' => 'Admin User',
                'email' => 'apb+admin@neurony.ro',
                'email_verified_at' => Carbon::now(),
                'password' => bcrypt('admin'),
            ]);
        }
        /** @TODO add role logic here when we have roles implemented */
    }
}
