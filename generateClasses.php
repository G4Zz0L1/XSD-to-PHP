<?php
/**
 * Created by PhpStorm.
 * User: Irfan
 * Date: 12/22/15
 * Time: 11:56 AM
 */

set_include_path(get_include_path().PATH_SEPARATOR.
                 realpath("src"));

require_once "com/mikebevz/xsd2php/Xsd2Php.php";

function my_autoloader($class) {
    include $class . '.php';
}

spl_autoload_register('my_autoloader');

// Sample code usage
$xsdFile = dirname(__FILE__)."/../resources/ubl2.0/maindoc/UBL-Order-2.0.xsd";
$xsd2php = new xsd2php\Xsd2Php($xsdFile);

$xml = $xsd2php->getXML(); // Will out put xml

$actual = $xml->saveXml();

$xsd2php->saveClasses('your/folder/path/', true);