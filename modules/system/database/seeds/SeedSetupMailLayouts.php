<?php namespace System\Database\Seeds;

use Seeder;
use System\Models\MailLayout;

/**
 * SeedSetupMailLayouts
 */
class SeedSetupMailLayouts extends Seeder
{
    public function run()
    {
        MailLayout::createLayouts();
    }
}
