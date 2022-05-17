<?php

use Backend\Widgets\Form;
use Illuminate\Database\Eloquent\Model;

require_once __DIR__.'/../fixtures/models/BackendUserFixture.php';

class FormTestModel extends Model
{

}

class FormTest extends PluginTestCase
{
    public function testRestrictedFieldWithUserWithNoPermissions()
    {
        $user = new BackendUserFixture;
        $this->actingAs($user);

        $form = $this->restrictedFormFixture();

        $form->render();
        $this->assertNull($form->getField('testRestricted'));
    }

    public function testRestrictedFieldWithUserWithWrongPermissions()
    {
        $user = new BackendUserFixture;
        $this->actingAs($user->withPermission('test.wrong_permission', true));

        $form = $this->restrictedFormFixture();

        $form->render();
        $this->assertNull($form->getField('testRestricted'));
    }

    public function testRestrictedFieldWithUserWithRightPermissions()
    {
        $user = new BackendUserFixture;
        $this->actingAs($user->withPermission('test.access_field', true));

        $form = $this->restrictedFormFixture();

        $form->render();
        $this->assertNotNull($form->getField('testRestricted'));
    }

    public function testRestrictedFieldWithUserWithRightWildcardPermissions()
    {
        $user = new BackendUserFixture;
        $this->actingAs($user->withPermission('test.access_field', true));

        $form = new Form(null, [
            'model' => new FormTestModel,
            'arrayName' => 'array',
            'fields' => [
                'testField' => [
                    'type' => 'text',
                    'label' => 'Test 1'
                ],
                'testRestricted' => [
                    'type' => 'text',
                    'label' => 'Test 2',
                    'permission' => 'test.*'
                ]
            ]
        ]);

        $form->render();
        $this->assertNotNull($form->getField('testRestricted'));
    }

    public function testRestrictedFieldWithSuperuser()
    {
        $user = new BackendUserFixture;
        $this->actingAs($user->asSuperUser());

        $form = $this->restrictedFormFixture();

        $form->render();
        $this->assertNotNull($form->getField('testRestricted'));
    }

    public function testRestrictedFieldSinglePermissionWithUserWithWrongPermissions()
    {
        $user = new BackendUserFixture;
        $this->actingAs($user->withPermission('test.wrong_permission', true));

        $form = $this->restrictedFormFixture(true);

        $form->render();
        $this->assertNull($form->getField('testRestricted'));
    }

    public function testRestrictedFieldSinglePermissionWithUserWithRightPermissions()
    {
        $user = new BackendUserFixture;
        $this->actingAs($user->withPermission('test.access_field', true));

        $form = $this->restrictedFormFixture(true);

        $form->render();
        $this->assertNotNull($form->getField('testRestricted'));
    }

    public function testCheckboxlistTrigger()
    {
        $form = new Form(null, [
            'model' => new FormTestModel,
            'arrayName' => 'array',
            'fields' => [
                'trigger' => [
                    'type' => 'checkboxlist',
                    'options' => [
                        '1' => 'Value One'
                    ]
                ],
                'triggered' => [
                    'type' => 'text',
                    'trigger' => [
                        'field' => 'trigger[]',
                        'action' => 'show',
                        'condition' => 'value[1]'
                    ]
                ]
            ]
        ]);

        $form->render();

        $attributes = $form->getField('triggered')->getAttributes('container', false);
        $this->assertEquals('[name="array[trigger][]"]', array_get($attributes, 'data-trigger'));
    }

    protected function restrictedFormFixture(bool $singlePermission = false)
    {
        return new Form(null, [
            'model' => new FormTestModel,
            'arrayName' => 'array',
            'fields' => [
                'testField' => [
                    'type' => 'text',
                    'label' => 'Test 1'
                ],
                'testRestricted' => [
                    'type' => 'text',
                    'label' => 'Test 2',
                    'permissions' => ($singlePermission) ? 'test.access_field' : [
                        'test.access_field'
                    ]
                ]
            ]
        ]);
    }
}
