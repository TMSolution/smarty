<?php
/**
* Smarty PHPunit tests of constants
* 
* @package PHPunit
* @author Uwe Tews 
*/

require_once SMARTY_DIR . 'Smarty.class.php';

/**
* class for constants tests
*/
class ConstantsTests extends PHPUnit_Framework_TestCase {
    public function setUp()
    {
        $this->smarty = new Smarty();
        $this->smarty->error_reporting = E_ALL;
        $this->smarty->enableSecurity();
        $this->smarty->force_compile = true;
        $this->old_error_level = error_reporting();
    } 

    public function tearDown()
    {
        error_reporting($this->old_error_level);
        unset($this->smarty);
        Smarty::$template_objects = null;
    } 

    /**
    * test constants
    */
    public function testConstants()
    {
        define('MYCONSTANTS','hello world');
        $tpl = $this->smarty->createTemplate('string:{$smarty.const.MYCONSTANTS}');
        $this->assertEquals("hello world", $this->smarty->fetch($tpl));
    } 
} 

?>
