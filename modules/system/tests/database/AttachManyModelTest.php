<?php

use System\Models\File as FileModel;
use Database\Tester\Models\User;

class AttachManyModelTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        include_once base_path() . '/modules/system/tests/fixtures/plugins/database/tester/models/User.php';

        $this->runPluginRefreshCommand('Database.Tester');
    }

    public function testDeleteFlagDestroyRelationship()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
        Model::reguard();

        $this->assertEmpty($user->photos);
        $user->photos()->create(['data' => base_path() . '/modules/system/tests/fixtures/plugins/database/tester/assets/images/avatar.png']);
        $user->reloadRelations();
        $this->assertNotEmpty($user->photos);

        $photo = $user->photos->first();
        $photoId = $photo->id;

        $user->photos()->remove($photo);
        $this->assertNull(FileModel::find($photoId));
    }

    public function testDeleteFlagDeleteModel()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
        Model::reguard();

        $this->assertEmpty($user->photos);
        $user->photos()->create(['data' => base_path() . '/modules/system/tests/fixtures/plugins/database/tester/assets/images/avatar.png']);
        $user->reloadRelations();
        $this->assertNotEmpty($user->photos);

        $photo = $user->photos->first();
        $this->assertNotNull($photo);
        $photoId = $photo->id;

        $user->delete();
        $this->assertNull(FileModel::find($photoId));
    }

    /**
     * @deprecated Removing arbitrary models soon unsupported
     * @see isModelRemovable
     */
    // public function testRemovalProtection()
    // {
    //     Model::unguard();
    //     $user1 = User::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
    //     $user2 = User::create(['name' => 'Jerry', 'email' => 'jerry@email.tld']);
    //     Model::reguard();

    //     $user1->photos()->create(['data' => base_path() . '/modules/system/tests/fixtures/plugins/database/tester/assets/images/avatar.png']);
    //     $user2->photos()->create(['data' => base_path() . '/modules/system/tests/fixtures/plugins/database/tester/assets/images/avatar.png']);

    //     $user1Photo = $user1->photos->first();
    //     $user1PhotoId = $user1Photo->id;

    //     // Attempt to remove user 1's photo from user 2
    //     $user2->photos()->remove($user1Photo);
    //     $this->assertNotNull(FileModel::find($user1PhotoId));

    //     $user2Photo = $user2->photos->first();
    //     $this->assertNotNull($user2Photo);
    // }
}
