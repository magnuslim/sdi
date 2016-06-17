<?php
/**
 * Be gentle, but powerful.
 * User: Magnus
 * Date: 2016/6/3
 * Time: 10:21
 */

/*
 * Sdi is short of Simple Dependence Injection
 */
Class Sdi
{
    private $_mapper;

    private static $_sdiList = [];

    private $_reflectionClassCache = [];

    private $_singletonInstanceList = [];

    public static function getInstance(DependenceMapper $mapper)
    {
        $mapperName = $mapper->getMapperName();
        if(!isset(self::$_sdiList[$mapperName])){
            self::$_sdiList[$mapperName] = new Sdi($mapper);
        }
        return self::$_sdiList[$mapperName];
    }

    public function run($className, $isSingleton = false)
    {
        return $this->_getInstance($className, $isSingleton);
    }

    private function __construct(DependenceMapper $mapper)
    {
        if($mapper != null){
            $this->_mapper = $mapper;
        }
    }

    private function _getInstance($className)
    {
        $diConfig = $this->_mapper->get($className);
        $isSingleton = false;
        // 查找有无对应的实例化类
        if($diConfig != null){
            if(isset($diConfig['name'])){
                $className = $diConfig['name'];
            }
            if(isset($diConfig['isSingleton'])){
                $isSingleton = $diConfig['isSingleton'];
            }
            
        }

        if($isSingleton && isset($this->_singletonInstanceList[$className])){
            return $this->_singletonInstanceList[$className];
        }

        $aimInstance = null;

        $methodList = get_class_methods($className);
        if(empty($methodList)){
            $aimInstance =  new $className;
        }

        // 声明了构造函数
        if(in_array('__construct', $methodList)){
            $constructor = new ReflectionMethod($className, '__construct');
            $parameters = $constructor->getParameters();

            //构造函数需要参数，生成所需的参数
            if(count($parameters) != 0){
                $paramList = [];
                foreach ($parameters as $key => $parameter) {
                    $paramClass = $parameter->getClass();
                    if ($paramClass){
                        $paramList[] = $this->_getInstance($paramClass->name);
                    }else{
                        throw new SdiException("Unknown type for parameter {$parameter} of {$className}::__construct()", 1);
                    }
                }
                $aimInstance = $this->_generateInstance($className, $paramList);
            }else{
                //声明了构造函数，不需要参数，直接new
                $aimInstance =  new $className;
            }
        }else{
            //没有声明构造函数，直接new
            $aimInstance =  new $className;
        }

        // 如果是单例，加入到单例实例列表
        if($isSingleton){
            $this->_singletonInstanceList[$className] = $aimInstance;
        }
        
        return $aimInstance;
    }

    private function _generateInstance($className, array $arguments)
    {
        switch (count($arguments)) {
            case 0: return new $className();
            case 1: return new $className($arguments[0]);
            case 2: return new $className($arguments[0], $arguments[1]);
            case 3: return new $className($arguments[0], $arguments[1], $arguments[2]);
        }
        if (!isset($this->_reflectionClassCache[$className])) {
            $this->_reflectionClassCache[$className] = new ReflectionClass($className);
        }
        $instance = $this->_reflectionClassCache[$className]->newInstanceArgs($arguments);

        return $instance;

    }
}

Class DependenceMapper
{
    private $_config;
    private $_mapperName;

    private static $_mapperList = [];

    public static function getInstance(array $config)
    {
        $mapperName = md5(json_encode($config));
        if(!isset(self::$_mapperList[$mapperName])){
            self::$_mapperList[$mapperName] = new DependenceMapper($config);
        }
        return self::$_mapperList[$mapperName];
    }

    public function getMapperName()
    {
        if(empty($this->_mapperName)){
            $this->_mapperName = md5(json_encode($this->_config));
        }
        return $this->_mapperName;
    }

    private function __construct(array $config)
    {
        if(!empty($config)){
            $this->_config = $config;
            $this->_mapperName = md5(json_encode($config));
        }
    }

    public function get($interfaceName)
    {
        if(isset($this->_config[$interfaceName])){
            return $this->_config[$interfaceName];
        }
        return null;
    }
}

class SdiException extends Exception{

}