<?php

class AuthAcl_Test_MySQLTest extends PHPUnit_Framework_TestCase
{
    private $authOptions = array(
        'auth' => array(
            'dsn' => "mysql:localhost=host;dbname=test_acl",
            'db_user' => "user",
            'db_pass' => 'pass',
            'table' => 'acl_user',
            'useridcol' => 'id',
            'usernamecol' => 'login',
            'driver' => 'MySQL'
            ),
        'acl' => array(
            'tblgroup'  => 'acl_group',
            'tblusergroup' => 'acl_user_group_rel',
            'tblresource' => 'acl_resource',
            'tblresourceuser' => 'acl_resource_user_rel',
            'tblresourcegroup' => 'acl_resource_group_rel',
            'driver' => 'MySQL'
            )
        );
        
    private $admin = null;
    
    public function testResource()
    {
        $this->admin = new AuthAcl_Admin($this->authOptions);
        
        // add resource
        $newRes = array(
                'name' => 'test_' . md5(time()),
                'path' => md5(time()),
                'descr' => ''
            );
        
        $this->assertTrue($this->admin->addResource($newRes));
        
        // second time to add the same resource can not be
        $this->assertFalse($this->admin->addResource($newRes));
        
        // list of resources
        $resources = $this->admin->getResources();
        $this->assertTrue(is_array($resources));
        
        // one resource
        $_res = $this->admin->getResource($newRes['path']);
        $this->assertEquals((string)$_res['name'], (string)$newRes['name']);
        
        // try add not correct group
        $this->assertFalse($this->admin->addGroupToResource($_res['id'], -22));
        
        // add group in resource
        $this->assertTrue($this->admin->addGroupToResource($_res['id'], 1));
        
        // get resource groups
        $groups = $this->admin->getResourceGroups($_res['id']);
        $this->assertTrue(is_array($groups));
        
        $this->assertEquals((string)'superadmin', (string)$groups[0]['name']);
        
        // delete group from resource
        $this->admin->deleteGroupFromResource($_res['id'], 1);
        
        // add groups of the resource
        $groups = $this->admin->getResourceGroups($_res['id']);
        $this->assertTrue(is_array($groups));
        
        // void
        $this->assertEquals((int)0, count($groups));
        
        // try add incorrect user
        $this->assertFalse($this->admin->addUserToResource($_res['id'], 'unknownLogin'));
        
        // correct user in incorrect group
        $this->assertFalse($this->admin->addUserToResource(-22, 'mysql'));
        
        // correct user and group
        $this->assertTrue($this->admin->addUserToResource($_res['id'], 'mysql'));
        
        // already exists
        $this->assertFalse($this->admin->addUserToResource($_res['id'], 'mysql'));
        
        // get all users of resource
        $users = $this->admin->getResourceUsers($_res['id']);
        $this->assertTrue(is_array($users));
        
        $this->assertEquals((int)1, count($users));
        
        $this->assertEquals((string)'mysql', (string)$users[0]['login']);
        
        $this->admin->deleteUserFromResource($_res['id'], $users[0]['id']);
        
        $users = $this->admin->getResourceUsers($_res['id']);
        $this->assertTrue(is_array($users));
        
        $this->assertEquals((int)0, count($users));
        
        $this->assertTrue($this->admin->deleteResource($_res['id']));
        
        $this->assertFalse($this->admin->getResource($newRes['path']));
    }
    
    public function testGroup()
    {
        $this->admin = new AuthAcl_Admin($this->authOptions);
        
        $fields = array(
                'name' => 'test_' . md5(time()),
                'descr' => ''
            );
        $this->assertTrue($this->admin->addGroup($fields));
        
        $groups = $this->admin->getGroups();
        $newGroup = array();
        foreach ($groups as $_g) {
            if ( $_g['name'] == $fields['name'] ) {
                $newGroup = $_g;
                break;
            }
        }
        $this->assertFalse(empty($newGroup));
        $newGroup = $this->admin->getGroup($newGroup['id']);
        $this->assertFalse(empty($newGroup));
        
        $this->assertFalse($this->admin->addUserToGroup($newGroup['id'], 'unknownLogin'));
        
        $this->assertTrue($this->admin->addUserToGroup($newGroup['id'], 'mysql'));
        
        $users = $this->admin->getUsersForGroup($newGroup['id']);
        $this->assertTrue(is_array($users));
        
        $this->assertEquals((int)1, count($users));
        
        $this->assertEquals((string)'mysql', (string)$users[0]['login']);
        
        $this->admin->deleteUserFromGroup($newGroup['id'], $users[0]['id']);
        
        $users = $this->admin->getUsersForGroup($newGroup['id']);
        $this->assertTrue(is_array($users));
        
        $this->assertEquals((int)0, count($users));
        
        
        $this->admin->deleteGroup($newGroup['id']);
        
        $groups = $this->admin->getGroups();
        $newGroup = false;
        foreach ($groups as $_g) {
            if ( $_g['name'] == $fields['name'] ) {
                $newGroup = $_g;
                break;
            }
        }
        $this->assertFalse($newGroup);
    }
}

if ( !function_exists('__autoload') ) {
    function __autoload($class)
    {
        $class = str_replace('_', '/', $class);
        require_once($class . '.php');
    }
}

?>
