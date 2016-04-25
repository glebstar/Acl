<?php

/**
 * This is class AuthAcl_Abstract
 * Common class for ACL System
 * 
 * @author Gleb Starkov (glebstarkov@gmail.com)
 */
class AuthAcl_Abstract
{
    /**
     * Object provides an autenticate
     *
     * @var IAuth
     *
     */
    protected $_authObj;
    
    /**
     * 
     * Name of class provides an autenticate
     * This class should by realized an IAuth interface
     *
     * @var string 
     *
     */
    private $_authClass = 'SimpleAuth_SA';
    
    
    /**
     * Driver for access to data
     *
     * @var AuthAcl_IDriver
     *
     */
    protected $_driver;
    
    
    /**
     * Default configuration
     *
     * @var array 
     * 
     * [auth] - 
     *            useridcol             - id key in table of users
     *            isRememberUser        - check for remember user between sessions
     *            rememberUserTime      - storage time of cookies
     *                                    (default - 1 month)
     *            postRememberMe        - name of checkbox "Remember me"
     * 
     *            options['charset']    - charset database
     * 
     * [acl]:
     *   tblgroup          - table of groups
     *   +-+
     *     |   name          - name of table
     *     |   fields        - fields
     *   tblusergroup      - table of users to groups
     *   +-+
     *     |   name          - name of table
     *     |   idcol         - id key in table
     *     |   groupcol      - id user
     *     |   usercol       - id group
     *   tblresource       - table of resources
     *   +-+
     *     |   name          - name of table
     *     |   idcol         - id key field
     *     |   namecol       - name resource field
     *     |   pathcol       - name path to field
     *   tblresourceuser   - access users to resources
     *   +-+
     *     |   name          - name of table
     *     |   idcol         - id key
     *     |   resourcecol   - id resource
     *     |   usercol       - id user
     *   tblresourcegroup  - access groups to resources
     *   +-+
     *     |   name          - name of table
     *     |   idcol         - id key
     *     |   resourcecol   - id resource
     *     |   groupcol      - id group
     * 
     *   superuser          - id number of group "Superusers"
     *   notregisteredusers - id number of group "Not autenticate"
     *   registeredusers    - id number of group "Autenticate"
     *   rootpath           - id number root of path to project
     *
     */
    protected $_cfg = array (
        'auth' => array(
            'dsn' => "mysql:host=host;dbname=dbname",
            'db_user' => 'user',
            'db_pass' => 'password',
            'table' => 'acl_user',
            'useridcol' => 'id',
            'usernamecol' => 'login',
            'postUsername' => 'username',
            'postPassword' => 'password',
            'isRememberUser' => true,
            'rememberUserTime' => 2592000,
            'postRememberMe' => 'remember',
            'charset' => 'cp1251',
            'driver' => 'Test'
            ),
        'acl' => array(
            'tblgroup'  => array(
                'name'=>'acl_group', 
                'idcol'=> 'id',
                'namecol' => 'name'
                ),
            'tblusergroup' => array(
                'name' => 'acl_user_group_rel',
                'idcol' => 'id',
                'groupcol' => 'group_id',
                'usercol'  => 'user_id'
                ), 
            'tblresource' => array(
                'name'=>'acl_resource', 
                'idcol' => 'id',
                'namecol' => 'name',
                'pathcol'=>'path',
                'parentcol' => 'parent'
                ),
            'tblresourceuser' => array(
                'name' => 'acl_resource_user_rel',
                'idcol' => 'id',
                'resourcecol' => 'resource_id',
                'usercol' => 'user_id'
                ),
            'tblresourcegroup' => array(
                'name' => 'acl_resource_group_rel',
                'idcol' => 'id',
                'resourcecol' => 'resource_id',
                'groupcol' => 'group_id'
                ),
            'superuser' => 1,
            'notregisteredusers' => 2,
            'registeredusers' => 3,
            'rootpath' => 1,
            'driver' => 'MySQL'
            )
        );
    
    
    
    /**
     * variable for example access to data in tables
     *
     * @var array 
     *
     */
    protected $tables = array();
    
    
    /**
     * Last Error
     *
     * @var string 
     *
     */
    private $_errormessage = '';
    
    
    /**
     * Name function for login
     *
     * @var string 
     *
     */
    protected $loginFunction = '';
    
    
    /**
     * This is method __construct
     * 
     * @param string $options - configuration, see up
     * @param mixed $authClass - name of Class Auth System
     */
    public function __construct($options, $authClass = false)
    {
        if ( $authClass !== false ) {
            $this->_authClass = $authClass;
        }
        
        $pattern = '/^tbl/';
        foreach ($this->_cfg['acl'] as $key=>$tbl) {
            if ( preg_match($pattern, $key) ) {
                $options = $this->_fixOptions($key, $options);
            }
        }
        
        // user configuration
        $this->_cfg = self::_arrayMerge($this->_cfg, $options);
        
        // fill names of tables
        $this->tables['user']                   = $this->_cfg['auth']['table'];
        $this->tables['group']                  = $this->_cfg['acl']['tblgroup'];
        $this->tables['usergroup']              = $this->_cfg['acl']['tblusergroup'];
        $this->tables['resource']               = $this->_cfg['acl']['tblresource'];
        $this->tables['resourceuser']           = $this->_cfg['acl']['tblresourceuser'];
        $this->tables['resourcegroup']          = $this->_cfg['acl']['tblresourcegroup'];
        
        /**
         * constants of system groups
         */
        if ( !defined('AUTHACL_GROUP_SUPERUSER') ) {
            define('AUTHACL_GROUP_SUPERUSER',       $this->_cfg['acl']['superuser']);
        }
        if ( !defined('AUTHACL_GROUP_NOTREGISTERED') ) {
            define('AUTHACL_GROUP_NOTREGISTERED',   $this->_cfg['acl']['notregisteredusers']);
        }
        if ( !defined('AUTHACL_GROUP_REGISTERED') ) {
            define('AUTHACL_GROUP_REGISTERED',      $this->_cfg['acl']['registeredusers']);
        }
        if ( !defined('AUTHACL_ROOTPATH') ) {
            define('AUTHACL_ROOTPATH',              $this->_cfg['acl']['rootpath']);
        }
        if ( !defined('AUTHACL_ROOT_DIR') ) {
            define('AUTHACL_ROOT_DIR', dirname(__FILE__));
        }
        
        $_driverPath = AUTHACL_ROOT_DIR . "/Driver/{$this->_cfg['acl']['driver']}.php";
        
        if ( !file_exists($_driverPath) ) {
            throw new Exception('Not found driver file ' . $_driverPath);
        }
        
        $_driverClass = "AuthAcl_Driver_{$this->_cfg['acl']['driver']}";
        
        $this->_driver = new $_driverClass($this->_cfg);
        $this->_authObj = new $this->_authClass($this->_cfg['auth']['driver'], $this->_cfg['auth'], $this->loginFunction);
    }
    
    
    /**
     * internaly util
     *
     */
    private static function _arrayMerge($array1, $array2)
    {
        if (is_array($array1) && is_array($array2)) {
            foreach ($array1 as $key => $value) {
                if (isset($array2[$key])) {
                    if (is_integer($key)) {
                        $array1[] = $array2[$key];
                    } elseif (is_array($value) && is_array($array2[$key])) {
                        $array1[$key] = self::_arrayMerge($value, $array2[$key]);
                    } else {
                        $array1[$key] = $array2[$key];
                    }
                }
            }
            foreach ($array2 as $key => $value) {
                if (!isset($array1[$key])) {
                    $array1[$key] = $array2[$key];
                }
            }        
        } else {
            return array_merge($array1, $array2);
        }
        return $array1;
    }
    
    
    /**
     * internaly util
     *
     */
    private function _fixOptions($key, $options)
    {
        if ( isset($options['acl'][$key]) && !is_array($options['acl'][$key]) ) {
            $options['acl'][$key] = array(
                    'name' => $options['acl'][$key]
                    );
        }
        
        return $options;
    }
    
    final protected function setErrorMsg($msg)
    {
        $this->_errormessage = $msg;
    }
    
    final public function getLastError()
    {
        return $this->_errormessage;
    }
}
