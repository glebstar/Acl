<?php


interface AuthAcl_IDriver
{
    /**
     * See tests for MySQL, for example
     */
    
    
    public function getIdPath($path);
    
    
    public function getRights($resourceId);
    
    
    public function getUserInfo($userName, $getGroups=true, $groupNames=false);
    
    
    public function getResource($path=false, $id=false, $getUsers=true, $getGroups=true);
    

    public function getResources($orderBy=null, $getUsers=true, $getGroups=true, $where=false);
    

    public function getGroups($orderBy=null, $getUsers=false, $getResources=false);
    

    public function getUsersForGroup($groupId);
    

    public function getResourcesForGroup($groupId);
    

    public function getGroup($id, $getUsers=true, $getResources=true);
    

    public function addResource($fields, & $errorMsg);
    

    public function deleteResource($id, & $errorMsg);
    

    public function addGroupToResource($resourceId, $groupId, & $errorMsg);
    

    public function getResourceGroups($resourceId);
    

    public function deleteGroupFromResource($resourceId, $groupId);
    

    public function addUserToResource($resourceId, $userLogin, & $errorMsg);
    

    public function getResourceUsers($resourceId);
    

    public function deleteUserFromResource($resourceId, $userId);
    

    public function addGroup($fields, & $errorMsg);
    

    public function deleteGroup($id, $sysGroups, & $errorMsg);
    

    public function addUserToGroup($groupId, $userLogin, & $errorMsg);
    

    public function deleteUserFromGroup($groupId, $userId);
}

?>
