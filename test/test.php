<?php
require __DIR__ . '/../sdi.php';

interface InterfaceA{
    public function printSomething();
}

class ClassA implements InterfaceA{

    private $_var = 0;

    public function printSomething(){
        echo "This is in ClassA. _var = " . $this->_var . "\n";
    }

    public function incrVar(){
        $this->_var += 1;
    }
}

class ClassB{

    private $_a;

    public function __construct(InterfaceA $a){
        $this->_a = $a;
    }

    public function printFromA(){
        $this->_a->printSomething();
    }
}

class ClassC{

    private $_a;

    public function __construct(InterfaceA $a){
        $this->_a = $a;
    }

    public function incrVarOfA(){
        $this->_a->incrVar();
    }
}


$config = include 'config.php';
$dm = DependenceMapper::getInstance($config);
$sdi = Sdi::getInstance($dm);

$b = $sdi->run('ClassB');
//The instance of ClassA needed in ClassC::__construct() is provided by sdi in singleton.
$c = $sdi->run('ClassC');

$b->printFromA();
$c->incrVarOfA();
$b->printFromA();
