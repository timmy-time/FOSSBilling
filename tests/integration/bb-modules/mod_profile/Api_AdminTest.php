<?php
/**
 * @group Core
 */
class Api_Admin_ProfileTest extends BBDbApiTestCase
{
    protected $_initialSeedFile = 'admins.xml';

    public function testProfile()
    {
        $array = $this->api_admin->profile_get();
        $this->assertInternalType('array', $array);

        $bool = $this->api_admin->profile_logout();
        $this->assertTrue($bool);

        $data = array(
            'email'     =>  'demo@boxbilling.com',
            'name'      =>  'Demo Admin',
            'signature' =>  'New Signature',
        );
        $bool = $this->api_admin->profile_update($data);
        $this->assertTrue($bool);

        $bool = $this->api_admin->profile_generate_api_key($data);
        $this->assertTrue($bool);
    }

    public function testPassword()
    {
        $data = array(
            'password' =>  'demo12313123A',
            'password_confirm' =>  'demo12313123A',
        );
        $bool = $this->api_admin->profile_change_password($data);
        $this->assertTrue($bool);

        $array = $this->api_admin->profile_get();
        $password = $this->di['db']->getCell('Select pass from admin where id = ?', array($array['id']));

        $expected = sha1($data['password']);
        $this->assertEquals($expected, $password);
    }
}