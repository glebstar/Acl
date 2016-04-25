<?php

/**
 * This is class AuthAcl_Admin
 * Object this class used in admin side system
 * 
 * @author Gleb Starkov (glebstarkov@gmail.com)
 *
 */
class AuthAcl_Admin extends AuthAcl_Abstract
{
    /**
     * Get all groups
     *
     * @param string $orderBy Default - name of group
     * @param bool $getUsers
     * @param bool $getResources
     * @return array $groups May include lists resources an users
     *
     */
    public function getGroups($orderBy=null, $getUsers=false, $getResources=false)
    {
        return $this->_driver->getGroups($orderBy, $getUsers, $getResources);
    }
    
    
    /**
     * Get one group
     *
     * @param int $id Id group
     * @param bool $getUsers
     * @param bool $getResources
     * @return mixed this group info
     *                 [users]
     *                 [resources]
     * or false
     *
     */
    public function getGroup($id, $getUsers=true, $getResources=true)
    {
        return $this->_driver->getGroup($id, $getUsers, $getResources);
    }
    
    /**
     * Get users of group
     *
     * @param int $groupId
     * @return array list users
     *
     */
    public function getUsersForGroup($groupId)
    {
        return $this->_driver->getUsersForGroup($groupId);
    }
    
    /**
     * Get resources of group
     *
     * @param int $groupId
     * @return array
     *
     */
    public function getResourcesForGroup($groupId)
    {
        return $this->_driver->getResourcesForGroup($groupId);
    }
    
    /**
     * Add resource
     *
     * @param array $fields Association array
     * @return bool
     *              error embedded from getLastError()
     *
     */
    public function addResource($fields)
    {
        $error = false;
        
        $res = $this->_driver->addResource($fields, $error);
        
        if ( !empty($error) ) {
            $this->setErrorMsg($error);
        }
        
        return $res;
    }
    
    
    /**
     * Delete resource
     *
     * @param integer $id
     * @return bool
     *
     */
    public function deleteResource($id)
    {
        $error = false;
        
        $res = $this->_driver->deleteResource($id, $error);
        
        if ( !empty($error) ) {
            $this->setErrorMsg($error);
        }
        
        return $res;
    }
    
    
    /**
     * Get all resources
     *
     * @param string $orderBy
     *        default: path to resource
     * @param bool $getUsers
     * @param bool $getGroups
     * 
     * @param bool $where
     * 
     * @return array
     *         [users of resource]
     *         [groups of resource]
     *
     */
    public function getResources($orderBy=null, $getUsers=true, $getGroups=true, $where=false)
    {
        return $this->_driver->getResources($orderBy, $getUsers, $getGroups, $where);
    }
    
    /**
     * Get one resource with path or id
     *
     * @param string $path
     * @param integer id
     * @param bool $getUsers
     * @param bool $getGroups
     * @return false or array
     *
     */
    public function getResource($path=false, $id=false, $getUsers=true, $getGroups=true)
    {
        return $this->_driver->getResource($path, $id, $getUsers, $getGroups);
    }
    
    
    /**
     * Add user to resource
     *
     * @param int $resourceId
     * @param string $userLogin
     * @return bool
     *              error available from getLastError()
     *
     */
    public function addUserToResource($resourceId, $userLogin)
    {
        $error = false;
        
        $res = $this->_driver->addUserToResource($resourceId, $userLogin, $error);
        
        if ( !empty($error) ) {
            $this->setErrorMsg($error);
        }
        
        return $res;
    }
    
    
    public function deleteUserFromResource($resourceId, $userId)
    {
        $this->_driver->deleteUserFromResource($resourceId, $userId);
    }
    
    
    public function addGroupToResource($resourceId, $groupId)
    {
        if ( !is_numeric($resourceId) ) {
            $this->setErrorMsg("Id ресурса должен быть числом");
            return false;
        }
        
        if ( !is_numeric($groupId) ) {
            $this->setErrorMsg("Id группы должен быть числом");
            return false;
        }
        
        $error = false;
        
        $res = $this->_driver->addGroupToResource($resourceId, $groupId, $error);
        
        if ( !empty($error) ) {
            $this->setErrorMsg($error);
        }
        
        return $res;
    }
    
    
    public function deleteGroupFromResource($resourceId, $groupId)
    {
        $this->_driver->deleteGroupFromResource($resourceId, $groupId);
    }
    
    
    public function getResourceUsers($resourceId)
    {
        return $this->_driver->getResourceUsers($resourceId);
    }
    
    
    public function getResourceGroups($resourceId)
    {
        return $this->_driver->getResourceGroups($resourceId);
    }
    
    
    public function addGroup($fields)
    {
        if ( !is_array($fields) ) {
            $this->setErrorMsg("Значения для новой группы должны быть переданы в массиве");
            return false;
        }
        
        $error = false;
        
        $res = $this->_driver->addGroup($fields, $error);
        
        if ( !empty($error) ) {
            $this->setErrorMsg($error);
        }
        
        return $res;
    }
    
    
    public function deleteGroup($id)
    {
        $error = false;
        
        $res = $this->_driver->deleteGroup($id, $this->getSystemGroups(), $error);
        
        if ( !empty($error) ) {
            $this->setErrorMsg($error);
        }
        
        return $res;
    }
    
    
    public function addUserToGroup($groupId, $userLogin)
    {
        $error = false;
        
        $res = $this->_driver->addUserToGroup($groupId, $userLogin, $error);
        
        if ( !empty($error) ) {
            $this->setErrorMsg($error);
        }
        
        return $res;
    }
    
    
    public function deleteUserFromGroup($groupId, $userId)
    {
        $this->_driver->deleteUserFromGroup($groupId, $userId);
    }
    
    
    public function getSystemGroups()
    {
        return array(
                'superuser'     => AUTHACL_GROUP_SUPERUSER,
                'notregistered' => AUTHACL_GROUP_NOTREGISTERED,
                'registered'    => AUTHACL_GROUP_REGISTERED
            );
    }
}
?>