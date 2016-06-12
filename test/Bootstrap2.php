<?php

require __DIR__.'/../vendor/autoload.php';

function rmdir_recursive($dir) {
        if (is_dir($dir)) { 
         $objects = scandir($dir); 
         foreach ($objects as $object) { 
           if ($object != "." && $object != "..") { 
             if (filetype($dir."/".$object) == "dir") rmdir_recursive($dir."/".$object); else unlink($dir."/".$object); 
           } 
         } 
         reset($objects); 
         rmdir($dir); 
       } 
    }

    function assertXmlEqual($mythis, $expFn,$actual) {
        //$expFn = dirname(__FILE__).'/data/expected/ubl2.0/XSDConvertertoXML.xml';
        $expected = file_get_contents($expFn);

        $temp = "";
        if($expected != $actual) {
          $temp = tempnam(sys_get_temp_dir(), 'Tux');
          file_put_contents($temp,$actual);
        }
        $mythis->assertTrue($expected == $actual, "Check vimdiff $temp $expFn");

    }
 
