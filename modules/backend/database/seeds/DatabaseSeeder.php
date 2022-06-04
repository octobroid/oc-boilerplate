<?php namespace Backend\Database\Seeds;

use Seeder;
use Model;

/**
 * DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    /**
     * run the database seeds.
     */
    public function run()
    {
        Model::unguard();

        $this->call(\Backend\Database\Seeds\SeedSetupAdmin::class, true);

        Model::reguard();
    }
}
