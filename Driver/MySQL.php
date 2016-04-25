<?php

/**
 * Driver is optionaly.
 * I have the right to comment on your favorite Russian
 */

class AuthAcl_Driver_MySQL implements AuthAcl_IDriver
{
    private $_connection = null;
    
    private $_tables;
    
    public function __construct($options)
    {
        $this->_connection = new PDO($options['auth']['dsn'], $options['auth']['db_user'], $options['auth']['db_pass']);
        
        $this->_connection->query("SET character_set_client='{$options['auth']['charset']}'");
        $this->_connection->query("SET character_set_connection='{$options['auth']['charset']}'");
        $this->_connection->query("SET character_set_results='{$options['auth']['charset']}'");
        
        $this->tables['user'] = array();
        $this->tables['user']['name']           = $options['auth']['table'];
        $this->tables['user']['idcol']          = $options['auth']['useridcol'];
        $this->tables['user']['namecol']        = $options['auth']['usernamecol'];
        $this->tables['group']                  = $options['acl']['tblgroup'];
        $this->tables['usergroup']              = $options['acl']['tblusergroup'];
        $this->tables['resource']               = $options['acl']['tblresource'];
        $this->tables['resourceuser']           = $options['acl']['tblresourceuser'];
        $this->tables['resourcegroup']          = $options['acl']['tblresourcegroup'];
    }
    
    public function getIdPath($path)
    {
        $query = "SELECT {$this->tables['resource']['idcol']} FROM {$this->tables['resource']['name']} WHERE `path` = '{$path}'";
        $res = $this->_connection->query($query)->fetch();
        return isset($res['id']) ? $res['id'] : false;
    }
    
    public function getRights($resourceId)
    {
        $rights = array();
        $query = "SELECT {$this->tables['resourceuser']['usercol']} FROM {$this->tables['resourceuser']['name']} WHERE {$this->tables['resourceuser']['resourcecol']} = '{$resourceId}'";
        $rights['users'] = $this->_connection->query($query)->fetchAll(PDO::FETCH_COLUMN);
        $query = "SELECT {$this->tables['resourcegroup']['groupcol']} FROM {$this->tables['resourcegroup']['name']} WHERE {$this->tables['resourcegroup']['resourcecol']} = '{$resourceId}'";
        $rights['groups'] = $this->_connection->query($query)->fetchAll(PDO::FETCH_COLUMN);
        
        return $rights;
    }
    
    public function getUserInfo($userName, $getGroups=true, $groupNames=false)
    {
        $userInfo = array();
        
        $query = "SELECT * FROM {$this->tables['user']['name']} WHERE {$this->tables['user']['namecol']} = '{$userName}'";
        $res = $this->_connection->query($query);
        if ( !$res ) {
            return false;
        }
        
        $res = $res->fetch(PDO::FETCH_ASSOC);
        
        foreach ($res as $key=>$value) {
            $userInfo[$key] = $value;
        }
        
        if ( $getGroups ) {
            $query = "SELECT {$this->tables['usergroup']['groupcol']} FROM {$this->tables['usergroup']['name']} WHERE {$this->tables['usergroup']['usercol']} = '{$userInfo['id']}'";
            $res = $this->_connection->query($query);
            if ( $res ) {
                $userInfo['groups'] = $res->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $userInfo['groups'] = array();
            }
        }
        
        if ( $groupNames ) {
            $query = "
                    SELECT self.{$this->tables['group']['namecol']}
                    FROM {$this->tables['group']['name']} AS self
                    LEFT JOIN {$this->tables['usergroup']['name']} AS ug ON ug.{$this->tables['usergroup']['groupcol']} = self.{$this->tables['group']['idcol']}
                    WHERE ug.{$this->tables['usergroup']['usercol']} = '{$userInfo['id']}'";
            $res = $this->_connection->query($query);
            if ( $res ) {
                $userInfo['groupNames'] = $res->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $userInfo['groupNames'] = array();
            }
        }
        
        return $userInfo;
    }
    
    public function getResource($path=false, $id=false, $getUsers=true, $getGroups=true)
    {
        if ( $path ) {
            $where = "WHERE {$this->tables['resource']['pathcol']} LIKE '{$path}'";
            $query = "SELECT * FROM {$this->tables['resource']['name']} {$where}";
        } elseif ( $id ) {
            $query = "SELECT * FROM {$this->tables['resource']['name']} WHERE {$this->tables['resource']['idcol']} = '{$id}'";
        }
        $res = $this->_connection->query($query);
        if ( !$res ) {
            return false;
        }
        
        $resource = $res->fetch(PDO::FETCH_ASSOC);
        
        if ( !$resource ) {
            return false;
        }
        
        if ( $getUsers ) {
            /**
                 * пользователи ресурса
                 */
            $query = "SELECT
                    self.*
                    FROM
                    {$this->tables['user']['name']} self
                    LEFT JOIN {$this->tables['resourceuser']['name']} rur ON rur.{$this->tables['resourceuser']['usercol']} = self.{$this->tables['user']['idcol']}
                    WHERE
                    rur.{$this->tables['resourceuser']['resourcecol']} = {$resource[ $this->tables['resource']['idcol'] ]}";
            $res = $this->_connection->query($query);
            if ( $res ) {
                $resource['users'] = $res->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $resource['users'] = array();
            }
        }
        
        if ( $getGroups ) {
            /**
            * группы ресурса
            */
            $query = "SELECT
                    self.*
                    FROM
                    {$this->tables['group']['name']} self
                    LEFT JOIN {$this->tables['resourcegroup']['name']} rgr ON rgr.{$this->tables['resourcegroup']['groupcol']} = self.id
                    WHERE
                    rgr.{$this->tables['resourcegroup']['resourcecol']} = {$resource[ $this->tables['resource']['idcol'] ]}";
            $res = $this->_connection->query($query);
            if ( $res ) {
                $resource['groups'] = $res->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $resource['groups'] = array();
            }
        }
        
        return $resource;
    }
    
    public function getResources($orderBy=null, $getUsers=true, $getGroups=true, $where=false)
    {
        if ( empty($orderBy) ) {
            $orderBy = $this->tables['resource']['pathcol'];
        }
        if ( $where ) {
            $where = " WHERE {$where}";
        } else {
            $where = '';
        }
        
        $query = "SELECT * FROM {$this->tables['resource']['name']} {$where} ORDER BY {$orderBy}";
        $res = $this->_connection->query($query);
        if ( !$res ) {
            return false;
        }
        $resources = $res->fetchAll(PDO::FETCH_ASSOC);
        
        if ( !$getUsers && !$getGroups ) {
            return $resources;
        }
        
        foreach ($resources as & $_r) {
            if ( $getUsers ) {
                /**
                 * пользователи ресурса
                 */
                $query = "SELECT
                        self.*
                        FROM
                        {$this->tables['user']} self
                        LEFT JOIN {$this->tables['resourceuser']['name']} rur ON rur.{$this->tables['resourceuser']['usercol']} = self.id
                        WHERE
                        rur.{$this->tables['resourceuser']['resourcecol']} = {$_r[ $this->tables['resource']['idcol'] ]}";
                $res = $this->_connection->query($query);
                if ( $res ) {
                    $_r['users'] = $res->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            
            if ( $getGroups ) {
                /**
                 * группы ресурса
                 */
                $query = "SELECT
                        self.*
                        FROM
                        {$this->tables['group']['name']} self
                        LEFT JOIN {$this->tables['resourcegroup']['name']} rgr ON rgr.{$this->tables['resourcegroup']['groupcol']} = self.id
                        WHERE
                        rgr.{$this->tables['resourcegroup']['resourcecol']} = {$_r[ $this->tables['resource']['idcol'] ]}";
                $res = $this->_connection->query($query);
                if ( $res ) {
                    $_r['groups'] = $res->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        }
        return $resources;
    }
    
    public function getGroups($orderBy=null, $getUsers=false, $getResources=false)
    {
        if ( empty($orderBy) ) {
            $orderBy = $this->tables['group']['namecol'];
        }
        $query = "SELECT * FROM {$this->tables['group']['name']} ORDER BY {$orderBy}";
        $res = $this->_connection->query($query);
        if ( !$res ) {
            return false;
        }
        $groups = $res->fetchAll(PDO::FETCH_ASSOC);
        
        if ( !$getUsers && !$getResources ) {
            return $groups;
        }
        
        foreach ($groups as & $_g) {
            if ( $getUsers ) {
                $_g['users'] = $this->getUsersForGroup($_g[ $this->tables['group']['idcol'] ]);
            }
            
            if ( $getResources ) {
                $_g['resources'] = $this->getResourcesForGroup($_g[ $this->tables['group']['idcol'] ]);
            }
        }
        
        return $groups;
    }
    
    public function getUsersForGroup($groupId)
    {
        $query = "SELECT
                self.*
                FROM
                {$this->tables['user']['name']} self
                LEFT JOIN {$this->tables['usergroup']['name']} ugr 
                ON ugr.{$this->tables['usergroup']['usercol']} = self.{$this->tables['user']['idcol']}
                WHERE
                ugr.{$this->tables['usergroup']['groupcol']} = {$groupId}";
        $res = $this->_connection->query($query);
        
        if ( !$res ) {
            return false;
        }
        
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getResourcesForGroup($groupId)
    {
        $query = "SELECT 
                self.* 
                FROM {$this->tables['resource']['name']} self 
                LEFT JOIN {$this->tables['resourcegroup']['name']} rgr 
                ON rgr.{$this->tables['resourcegroup']['resourcecol']} = self.{$this->tables['resource']['idcol']} 
                WHERE {$this->tables['resourcegroup']['groupcol']} = {$groupId}";
        $res = $this->_connection->query($query);
        if ( !$res ) {
            return false;
        }
        
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getGroup($id, $getUsers=true, $getResources=true)
    {
        $query = "SELECT * 
                FROM {$this->tables['group']['name']}
                WHERE {$this->tables['group']['idcol']} = '{$id}'";
        $res = $this->_connection->query($query);
        
        if ( !$res ) {
            return false;
        }
        
        $group = $res->fetch(PDO::FETCH_ASSOC);
        
        if ( $getUsers ) {
            $group['users'] = $this->getUsersForGroup($group[ $this->tables['group']['idcol'] ]);
        }
        
        if ( $getResources ) {
            $group['resources'] = $this->getResourcesForGroup($group[ $this->tables['group']['idcol'] ]);
        }
        
        return $group;
    }
    
    public function addResource($fields, & $errorMsg)
    {
        if ( !is_array($fields) ) {
            return false;
        }
        
        $pathCol = $this->tables['resource']['pathcol'];
        $tblName = $this->tables['resource']['name'];
        
        if ( !isset($fields[$pathCol]) || empty($fields[$pathCol]) ) {
            $errorMsg = 'Должен быть указан путь к ресурсу';
            return false;
        }
        
        $query = "SELECT {$pathCol} FROM {$tblName} WHERE {$pathCol} = '{$fields[$pathCol]}'";
        
        $res = $this->_connection->query($query);
        if ( $res ) {
            $_check = $res->fetch();
            if ( $_check ) {
                $errorMsg = "Ресурс с путём {$fields[$pathCol]} уже существует";
                return false;
            }
        }
        
        $queryItems = $this->_parseQueryItems($fields);
        
        $query = "INSERT INTO {$tblName} ({$queryItems['keys']}) VALUES ({$queryItems['values']})";
        $res = $this->_connection->exec($query);
        
        if ( $res === false ) {
            $_err = $this->_connection->errorInfo();
            $errorMsg = $_err[2];
            return false;
        }
        
        /**
         * Ресурс добавлен
         * Нужно прописать родительский узел
         * у его потомков, если они были в базе
         * раньше, чем родитель
         */
        $newResource = $this->getResource($fields[$pathCol], false, false);
        
        $update_child = array();
        $child = $this->getResources(null, false, false, "`path` LIKE '{$newResource['path']}/%'");
        foreach ($child as $_ch) {
            if ( $_ch['parent'] == '1' ) {
                $update_child[] = $_ch['id'];
                continue;
            }
            $_oldParent = $this->getResource(false, $_ch['parent'], false, false);
            if ( strlen($_oldParent['path']) < strlen($newResource['path']) ) {
                $update_child[] = $_ch['id'];
            }
        }
        if ( !empty($update_child) ) {
            $_ids = implode (",", $update_child);
            $sql = "UPDATE auth_resource SET `parent`='{$newResource['id']}' WHERE `id` IN({$_ids})";
            $this->_connection->exec($sql);
        }
        
        return true;
    }
    
    /**
     * Функция готовит элементы для INSERT запроса.
     * Перечень ключей и значений.
     *
     * @param array $items Ассоциативный массив ключей и значений 
     *              для INSERT запроса, например:
     *              array(
     *                  'key1' => 'value1',
     *                  'key2' => 'value2',
     *                  ...
     *                  'keyN' => 'valueN'
     *              )
     * @return array спарсенный список ключей и значений в виде:
     *         array(
     *             'keys'   => '`key1`, `key2`, ..., `keyN`',
     *             'values' => ''value1', 'value2', ..., 'valueN''
     *         )
     *
     */
    private function _parseQueryItems($items)
    {
        $keys = '`';
        $values = '\'';
        foreach ($items as $key=>$value) {
            $keys .= "{$key}`,`";
            $values .= "{$value}','";
        }
        $pattern = "/,`$/";
        $keys = preg_replace($pattern, '', $keys);
        $pattern = "/,\'$/";
        $values = preg_replace($pattern, '', $values);
        
        return array(
                'keys'   => $keys,
                'values' => $values
                );
    }
    
    public function deleteResource($id, & $errorMsg)
    {
        /**
         * Проверить, что ресурс существует
         */
        $query = "SELECT {$this->tables['resource']['idcol']}, {$this->tables['resource']['parentcol']}
                FROM {$this->tables['resource']['name']} 
                WHERE {$this->tables['resource']['idcol']} = '{$id}'";
                
        $res = $this->_connection->query($query);
        if ( $res ) {
            $_check = $res->fetch();
            if ( !$_check ) {
                $errorMsg = "Ресурс № {$id} не существует";
                return false;
            }
        } else {
            $errorMsg = "Ресурс № {$id} не существует";
            return false;
        }
        
        $resId = $_check[ $this->tables['resource']['idcol'] ];
        $parent = $_check[ $this->tables['resource']['parentcol'] ];
        
        /**
         * Удалить все связи с данным ресурсом
         */
        $query = "DELETE FROM {$this->tables['resourceuser']['name']} WHERE {$this->tables['resourceuser']['resourcecol']}='{$resId}'";
        $this->_connection->exec($query);
        
        $query = "DELETE FROM {$this->tables['resourcegroup']['name']} WHERE {$this->tables['resourcegroup']['resourcecol']}='{$resId}'";
        $this->_connection->exec($query);
        
        /**
         * Перевести дерево ресурса на родителя удаляемого ресурса
         */
        $query = "UPDATE {$this->tables['resource']['name']} SET {$this->tables['resource']['parentcol']} = '{$parent}' WHERE {$this->tables['resource']['parentcol']}='{$resId}'";
        $this->_connection->exec($query);
        
        /**
         * Удалить ресурс
         */
        $query = "DELETE FROM {$this->tables['resource']['name']} WHERE {$this->tables['resource']['idcol']} = '{$resId}'";
        $this->_connection->exec($query);
        
        return true;
    }
    
    public function addGroupToResource($resourceId, $groupId, & $errorMsg)
    {
        /**
         * проверить, что группа существует
         */
        $query = "SELECT {$this->tables['group']['idcol']} FROM {$this->tables['group']['name']} WHERE {$this->tables['group']['idcol']} = '{$groupId}'";
        
        $res = $this->_connection->query($query);
        if ( $res ) {
            $_check = $res->fetch();
            if ( !$_check ) {
                $errorMsg = "Группа № {$groupId} не найдена";
                return false;
            }
        } else {
            $errorMsg = "Группа № {$groupId} не найдена";
            return false;
        }
        
        $grId = $_check[ $this->tables['group']['idcol'] ];
        
        /**
         * проверить, что ресурс существует
         */
        $query = "SELECT {$this->tables['resource']['idcol']} FROM {$this->tables['resource']['name']} WHERE {$this->tables['resource']['idcol']} = '{$resourceId}'";
        $res = $this->_connection->query($query);
        if ( $res ) {
            $_check = $res->fetch();
            if ( !$_check ) {
                $errorMsg = "Ресурс № {$resourceId} не найден";
                return false;
            }
        } else {
            $errorMsg = "Ресурс № {$resourceId} не найден";
            return false;
        }
        
        $resId = $_check[ $this->tables['resource']['idcol'] ];
        
        /**
         * проверить, что группа ещё не владеет ресурсом
         */
        $query = "
                SELECT 
                {$this->tables['resourcegroup']['idcol']} 
                FROM {$this->tables['resourcegroup']['name']} 
                WHERE 
                {$this->tables['resourcegroup']['resourcecol']}={$resId} 
                AND {$this->tables['resourcegroup']['groupcol']}={$grId}";
        
        $res = $this->_connection->query($query);
        if ( $res ) {
            $_check = $res->fetch();
            if ( $_check ) {
                $errorMsg = "Группа № {$grId}= уже владеет ресурсом № {$resId}";
                return false;
            }
        }
        
        /**
         * добавить группу для ресурса
         */
        $query = "
                INSERT INTO 
                {$this->tables['resourcegroup']['name']} 
                ({$this->tables['resourcegroup']['resourcecol']}, {$this->tables['resourcegroup']['groupcol']})
                VALUES ({$resId}, {$grId})";
        $res = $this->_connection->exec($query);
        
        if ( $res === false ) {
            $_err = $this->_connection->errorInfo();
            $errorMsg = $_err[2];
            return false;
        }
        
        return true;
    }
    
    public function getResourceGroups($resourceId)
    {
        $query = "SELECT
                self.*
                FROM
                {$this->tables['group']['name']} self
                LEFT JOIN {$this->tables['resourcegroup']['name']} rgr ON rgr.{$this->tables['resourcegroup']['groupcol']} = self.{$this->tables['group']['idcol']}
                WHERE
                rgr.{$this->tables['resourcegroup']['resourcecol']} = '{$resourceId}'";
        
        $res = $this->_connection->query($query);
        if ( !$res ) {
            return array();
        }
        
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function deleteGroupFromResource($resourceId, $groupId)
    {
        $query = "
                DELETE FROM {$this->tables['resourcegroup']['name']} 
                WHERE 
                {$this->tables['resourcegroup']['resourcecol']} = '{$resourceId}' AND
                {$this->tables['resourcegroup']['groupcol']} = '{$groupId}'";
        $this->_connection->exec($query);
    }
    
    public function addUserToResource($resourceId, $userLogin, & $errorMsg)
    {
        /**
         * проверить, что пользователь существует
         */
        $query = "SELECT {$this->tables['user']['idcol']} FROM {$this->tables['user']['name']} WHERE {$this->tables['user']['namecol']} = '{$userLogin}'";
        $res = $this->_connection->query($query);
        if ( $res ) {
            $_check = $res->fetch();
            if ( !$_check ) {
                $errorMsg = "Пользователь ={$userLogin}= не найден";
                return false;
            }
        } else {
            $errorMsg = "Пользователь ={$userLogin}= не найден";
            return false;
        }
        
        $userId = $_check[ $this->tables['user']['idcol'] ];
        
        /**
         * проверить, что ресурс существует
         */
        $query = "SELECT {$this->tables['resource']['idcol']} FROM {$this->tables['resource']['name']} WHERE {$this->tables['resource']['idcol']} = '{$resourceId}'";
        $res = $this->_connection->query($query);
        if ( $res ) {
            $_check = $res->fetch();
            if ( !$_check ) {
                $errorMsg = "Ресурс № {$resourceId} не найден";
                return false;
            }
        } else {
            $errorMsg = "Ресурс № {$resourceId} не найден";
            return false;
        }
        
        $resId = $_check[ $this->tables['resource']['idcol'] ];
        
        /**
         * проверить, что пользователь ещё не владеет ресурсом
         */
        $query = "
                SELECT 
                {$this->tables['resourceuser']['idcol']} 
                FROM {$this->tables['resourceuser']['name']} 
                WHERE 
                {$this->tables['resourceuser']['resourcecol']}={$resId} 
                AND {$this->tables['resourceuser']['usercol']}={$userId}";
        $res = $this->_connection->query($query);
        if ( $res ) {
            $_check = $res->fetch();
            if ( $_check ) {
                $errorMsg = "Пользователь ={$userLogin}= уже владеет ресурсом № {$resId}";
                return false;
            }
        }
        
        /**
         * добавить пользователя для ресурса
         */
        $query = "
                INSERT INTO 
                {$this->tables['resourceuser']['name']} 
                ({$this->tables['resourceuser']['resourcecol']}, {$this->tables['resourceuser']['usercol']})
                VALUES ({$resId}, {$userId})";
        $res = $this->_connection->exec($query);
        
        if ( $res === false ) {
            $_err = $this->_connection->errorInfo();
            $errorMsg = $_err[2];
            return false;
        }
        
        return true;
    }
    
    public function getResourceUsers($resourceId)
    {
        $query = "SELECT
                self.*
                FROM
                {$this->tables['user']['name']} self
                LEFT JOIN {$this->tables['resourceuser']['name']} rur ON rur.{$this->tables['resourceuser']['usercol']} = self.id
                WHERE
                rur.{$this->tables['resourceuser']['resourcecol']} = '{$resourceId}'";
        
        $res = $this->_connection->query($query);
        if ( !$res ) {
            return array();
        }
        
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function deleteUserFromResource($resourceId, $userId)
    {
        $query = "
                DELETE FROM {$this->tables['resourceuser']['name']} 
                WHERE 
                {$this->tables['resourceuser']['resourcecol']} = '{$resourceId}' AND
                {$this->tables['resourceuser']['usercol']} = '{$userId}'";
        $this->_connection->exec($query);
    }
    
    public function addGroup($fields, & $errorMsg)
    {
        $nameCol = $this->tables['group']['namecol'];
        $tblName = $this->tables['group']['name'];
        
        /**
         * Нельзя добавить группу без имени
         */
        if ( !isset($fields[$nameCol]) ) {
            $errorMsg = "Не передан обязательный элемент - {$nameCol}";
            return false;
        }
        
        if ( empty($fields[$nameCol]) ) {
            $errorMsg = "Нужно указать имя группы";
            return false;
        }
        
        /**
         * Проверить, что группа с данным именем не существует
         */
        $query = "SELECT {$nameCol} FROM {$tblName} WHERE {$nameCol} = '{$fields[$nameCol]}'";
        
        $res = $this->_connection->query($query);
        if ( $res ) {
            $_check = $res->fetch();
            if ( $_check ) {
                $errorMsg = "Группа с именем {$fields[$nameCol]} уже существует";
                return false;
            }
        }
        
        /**
         * Добавляем группу в базу данных
         */
        $queryItems = $this->_parseQueryItems($fields);
        
        $query = "INSERT INTO {$tblName} ({$queryItems['keys']}) VALUES ({$queryItems['values']})";
        $res = $this->_connection->exec($query);
        
        if ( $res === false ) {
            $_err = $this->_connection->errorInfo();
            $errorMsg = $_err[2];
            return false;
        }
        
        return true;
    }
    
    public function deleteGroup($id, $sysGroups, & $errorMsg)
    {
        /**
         * Проверить, что группа существует
         */
        $query = "SELECT {$this->tables['group']['idcol']} 
                FROM {$this->tables['group']['name']} 
                WHERE {$this->tables['group']['idcol']} = '{$id}'";
        $res = $this->_connection->query($query);
        if ( $res ) {
            $_check = $res->fetch();
            if ( !$_check ) {
                $errorMsg = "Группа № {$id} не существует";
                return false;
            }
        } else {
            $errorMsg = "Группа № {$id} не существует";
            return false;
        }
        
        $resId = $_check[ $this->tables['group']['idcol'] ];
        
        /**
         * Нельзя удалить системные группы
         */
        if ( in_array($id, $sysGroups) ) {
            $errorMsg = "Группа № {$id} является системной";
            return false;
        }
        
        /**
         * Удалить все связи данной группой
         */
        $query = "DELETE FROM {$this->tables['usergroup']['name']} WHERE {$this->tables['usergroup']['groupcol']}='{$resId}'";
        $this->_connection->exec($query);
        
        $query = "DELETE FROM {$this->tables['resourcegroup']['name']} WHERE {$this->tables['resourcegroup']['groupcol']}='{$resId}'";
        $this->_connection->exec($query);
        
        /**
         * Удалить группу
         */
        $query = "DELETE FROM {$this->tables['group']['name']} WHERE {$this->tables['group']['idcol']} = '{$resId}'";
        $this->_connection->exec($query);
        
        return true;
    }
    
    public function addUserToGroup($groupId, $userLogin, & $errorMsg)
    {
        /**
         * проверить, что пользователь существует
         */
        $query = "SELECT {$this->tables['user']['idcol']} FROM {$this->tables['user']['name']} WHERE {$this->tables['user']['namecol']} = '{$userLogin}'";
        $res = $this->_connection->query($query);
        if ( $res ) {
            $_check = $res->fetch();
            if ( !$_check ) {
                $errorMsg = "Пользователь ={$userLogin}= не найден";
                return false;
            }
        } else {
            $errorMsg = "Пользователь ={$userLogin}= не найден";
            return false;
        }
        
        $userId = $_check[ $this->tables['user']['idcol'] ];
        
        /**
         * проверить, что группа существует
         */
        $query = "SELECT {$this->tables['group']['idcol']} FROM {$this->tables['group']['name']} WHERE {$this->tables['group']['idcol']} = '{$groupId}'";
        $res = $this->_connection->query($query);
        if ( $res ) {
            $_check = $res->fetch();
            if ( !$_check ) {
                $errorMsg = "Группа № {$id} не найдена";
                return false;
            }
        } else {
            $errorMsg = "Группа № {$id} не найдена";
            return false;
        }
        
        $grId = $_check[ $this->tables['group']['idcol'] ];
        
        /**
         * проверить, что пользователь ещё не состоит в группе
         */
        $query = "
                SELECT 
                {$this->tables['usergroup']['idcol']} 
                FROM {$this->tables['usergroup']['name']} 
                WHERE 
                {$this->tables['usergroup']['groupcol']}={$grId} 
                AND {$this->tables['usergroup']['usercol']}={$userId}";
                
        $res = $this->_connection->query($query);
        if ( $res ) {
            $_check = $res->fetch();
            if ( $_check ) {
                $errorMsg = "Пользователь ={$userLogin}= уже состоит в группе № {$grId}";
                return false;
            }
        }
        
        /**
         * добавить пользователя в группу
         */
        $query = "
                INSERT INTO 
                {$this->tables['usergroup']['name']} 
                ({$this->tables['usergroup']['groupcol']}, {$this->tables['usergroup']['usercol']})
                VALUES ({$grId}, {$userId})";
        $res = $this->_connection->exec($query);
        
        if ( $res === false ) {
            $_err = $this->_connection->errorInfo();
            $errorMsg = $_err[2];
            return false;
        }
        
        return true;
    }
    
    public function deleteUserFromGroup($groupId, $userId)
    {
        $query = "
                DELETE FROM {$this->tables['usergroup']['name']} 
                WHERE 
                {$this->tables['usergroup']['groupcol']} = '{$groupId}' AND
                {$this->tables['usergroup']['usercol']} = '{$userId}'";
        $this->_connection->exec($query);
    }
}
