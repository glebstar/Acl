<?php

/**
 * This is class AuthAcl_Client
 * Object of this class uses on client-side system
 * 
 * @author Gleb Starkov (glebstarkov@gmail.com)
 *
 */
class AuthAcl_Client extends AuthAcl_Abstract
{
    /**
     * Name function for not access
     *
     * @var string 
     *
     */
    private $_noAccessFunction;
    
    
    /**
     * This is method __construct
     *
     * @param array $options See parent::__construct
     * 
     * @param string $loginFunction
     * @param string $noAccessFunction
     *
     */
    public function __construct($options, $loginFunction, $noAccessFunction, $authClass = false) {
        $this->loginFunction = $loginFunction;
        $this->_noAccessFunction = $noAccessFunction;
        parent::__construct($options, $authClass);
    }
    
    
    /**
     * System entry point
     *
     */
    public function start()
    {
        $this->_logout();
        
        $url = parse_url(preg_replace('/\?.+$/', '', $_SERVER['REQUEST_URI']));
        
        $rights = $this->_getAccess($url['path']);
        $resourceStatus = $this->_getStatusResource($rights);
        
        switch ( $resourceStatus ) {
            case 0: case 1;
                return 0;
            default:
                break;
        }
        
        /**
         * need autenticate
         */
        $this->_rememberUser();
        $this->_authObj->start();
        
        
        if ( $this->checkAuth() ) {
            
            if (  $resourceStatus == 2 ) {
                return 0;
            }
            
            $userInfo = $this->_getUserInfo();
            
            foreach ($userInfo['groups'] as $item) {
                if ( $item == AUTHACL_GROUP_SUPERUSER ) {
                    /** 
                     * cуперюзер
                     */
                    return 0;
                }
            }
            
            foreach ($rights['users'] as $item) {
                if ($item == $userInfo['id']) {
                    // найдены права для пользователя
                    return 0;
                }
            }
            
            foreach ($rights['groups'] as $item) {
                foreach ($userInfo['groups'] as $item2) {
                    if ( $item == $item2 ) {
                        // найдены права для группы
                        return 0;
                    }
                }
            }
        }
        
        /** 
         * Not access ...
         */
        $this->_noAccess($this->_noAccessFunction);
    }
    
    
    public function getUserInfo($getGroups=false, $groupNames=false)
    {
        $u_name = $this->_authObj->getUsername();
        
        if ( $u_name ) {
            return $this->_driver->getUserInfo($u_name, $getGroups, $groupNames);
        }
        
        return false;
    }
    
    
    public function isAccessToResource($path)
    {
        $rights = $this->_getAccess($path);
        
        $status = $this->_getStatusResource($rights);
        
        if ( $status == 0 || $status == 1 ) {
            return true;
        }
        
        if ( $this->auth_checkAuth() ) {
            if ( $status == 2 ) {
                return true;
            }
            
            $userInfo = $this->_getUserInfo();
            
            foreach ($rights['users'] as $item) {
                if ($item == $userInfo['id']) {
                    return true;
                }
            }
            
            foreach ($rights['groups'] as $item) {
                foreach ($userInfo['groups'] as $item2) {
                    if ( $item == $item2 ) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    private function _noAccess($noAccess)
    {
        return $noAccess();
    }
    
    
    private function _getAccess($path) 
    {
        $pathItems = explode('/', preg_replace('/^\//', '', $path));
        for ($i=count($pathItems)-1; $i>=0; $i--) {
            $currentPath = '/' . implode('/', $pathItems);
            
            $result = $this->_driver->getIdPath($currentPath);
            
            if ( $result ) {
                return  $this->_getRights($result);
            }
            
            unset($pathItems[$i]);
        }
        
        return $this->_getRights(AUTHACL_ROOTPATH);
    }
    
    private function _getRights($resourceId)
    {
        return $this->_driver->getRights($resourceId);
    }
    
    private function _getUserInfo()
    {
        $userInfo = $this->_driver->getUserInfo($this->_authObj->getUsername());
        $userInfo['groups'][] = AUTHACL_GROUP_REGISTERED;
        return $userInfo;
    }
    
    
    private function _getStatusResource($rights)
    {
        if ( empty($rights['groups']) && empty($rights['users'])) {
            return 0;
        }
        
        if ( in_array(AUTHACL_GROUP_NOTREGISTERED, $rights['groups']) ) {
            return 1;
        }
        
        if ( in_array(AUTHACL_GROUP_REGISTERED, $rights['groups']) ) {
            return 2;
        }
        
        return 100;
    }
    
    
    private function _logout()
    {
        if ( isset($_REQUEST['logout']) ) {
            if ( $_REQUEST['logout'] ) {
                /**
                 * delete cookies
                 */
                $this->_unsetcookie('authacl_rememberu');
                $this->_unsetcookie('authacl_rememberp');
                return $this->_authObj->logout();
            }
        }
    }
    
    private function _rememberUser()
    {
        if ( isset($_REQUEST['logout']) ) {
            if ( $_REQUEST['logout'] ) {
                return 0;
            }
        }
        
        if ( isset($_POST[ $this->_cfg['auth']['postUsername'] ]) && !isset($_POST[ $this->_cfg['auth']['postRememberMe'] ]) ) {
            $this->_unsetcookie('authacl_rememberu');
            $this->_unsetcookie('authacl_rememberp');
            return 0;
        }
        
        if ( $this->_cfg['auth']['isRememberUser'] ) {
            if ( !$this->_authObj->checkAuth() ) {
                if ( !isset($_POST[ $this->_cfg['auth']['postUsername'] ]) ) {
                    if ( isset($_COOKIE['authacl_rememberu']) && isset($_COOKIE['authacl_rememberp']) ) {
                        $_POST[ $this->_cfg['auth']['postUsername'] ] = $_COOKIE['authacl_rememberu'];
                        $_POST[ $this->_cfg['auth']['postPassword'] ] = $_COOKIE['authacl_rememberp'];
                    }
                } else {
                    if ( isset($_POST[ $this->_cfg['auth']['postRememberMe'] ]) ) {
                        setcookie('authacl_rememberu', $_POST[ $this->_cfg['auth']['postUsername'] ], time() + $this->_cfg['auth']['rememberUserTime'], '/', $_SERVER['HTTP_HOST']);
                        setcookie('authacl_rememberp', $_POST[ $this->_cfg['auth']['postPassword'] ], time() + $this->_cfg['auth']['rememberUserTime'], '/', $_SERVER['HTTP_HOST']);
                    }
                }
            }
        } else {
            $this->_unsetcookie('authacl_rememberu');
            $this->_unsetcookie('authacl_rememberp');
        }
    }
    
    
    private function _unsetcookie($name) {
         /**
          * zaibalsya virezat' russ comments
          * nu ti ponyal chuvak
          */
    
        setcookie($name, false, (time() - 2592000), '/', $_SERVER['HTTP_HOST']);
        unset($_COOKIE[$name]);
        unset($_REQUEST[$name]);
    }
    
    public function checkAuth()
    {
        return $this->_authObj->checkAuth();
    }
}

?>