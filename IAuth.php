<?php

interface AuthAcl_IAuth

{
    
    /**
     *
     * @param string $driver Name of driver (adapter), eg, MySQL
     * @param array $options Options for authenticate object
     * @param string $loginFunction
     *
     */
    public function __construct(string $driver, array $options, string $loginFunction);
    
    
    /**
     * Main entry point to authenticate
     *
     * @return void
     *
     */
    public function start();
    
    
    /**
     * Get current user login
     *
     * @return string
     *
     */
    public function getUsername();
    
    
    public function logout();
    
    
    public function checkAuth();
}

?>