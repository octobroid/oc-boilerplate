<?php namespace Backend\Database\Seeds;

use Seeder;
use Backend\Models\UserRole;
use Backend\Models\UserGroup;

/**
 * SeedSetupAdmin
 */
class SeedSetupAdmin extends Seeder
{
    public function run()
    {
        UserRole::create([
            'name' => 'Developer',
            'code' => UserRole::CODE_DEVELOPER,
            'description' => 'Site administrator with access to developer tools.',
            'color_background' => '#3498db',
            'sort_order' => 1
        ]);

        UserRole::create([
            'name' => 'Publisher',
            'code' => UserRole::CODE_PUBLISHER,
            'description' => 'Site editor with access to publishing tools.',
            'color_background' => '#1abc9c',
            'sort_order' => 2
        ]);

        UserGroup::create([
            'name' => 'Owners',
            'code' => UserGroup::CODE_OWNERS,
            'description' => 'Default group for website owners.',
            'is_new_user_default' => false
        ]);
    }
}
