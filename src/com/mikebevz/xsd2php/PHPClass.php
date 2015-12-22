<?php

namespace com\mikebevz\xsd2php;
/**
 * Copyright 2010 Mike Bevz <myb@mikebevz.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


require_once dirname(__FILE__) . '/Common.php';
require_once dirname(__FILE__) . '/Variable.php';
require_once dirname(__FILE__) . '/PhpFunction.php';
require_once dirname(__FILE__) . '/PhpDocComment.php';
require_once dirname(__FILE__) . '/PhpDocElement.php';
require_once dirname(__FILE__) . '/PhpDocElementFactory.php';

/**
 * PHP Class representation
 *
 * @author  Mike Bevz <myb@mikebevz.com>
 * @version 0.0.1
 *
 */
class PHPClass extends Common
{
    /**
     * Class name
     *
     * @var string name
     */
    public $name;

    /**
     * Array of class level documentation
     *
     * @var array
     */
    public $classDocBlock;

    /**
     * Class type
     *
     * @var string
     */
    public $type;

    /**
     * Class namespace
     *
     * @var string
     */
    public $namespace;

    /**
     * Class type namespace
     *
     * @var string
     */
    public $typeNamespace;

    /**
     * Class properties
     *
     * @var array
     */
    public $classProperties;

    /**
     * Class to extend
     *
     * @var string
     */
    public $extends;

    /**
     * Namespace of parent class
     *
     * @var string
     */
    public $extendsNamespace;

    /**
     * Array of class properties  array(array('name'=>'propertyName', 'docs' => array('property'=>'value')))
     *
     * @var array
     */
    public $properties;

    /**
     * Show debug info
     *
     * @var boolean
     */
    public $debug = false;

    /**
     * Returns array of PHP classes
     *
     * @return array
     */
    public function getPhpCode($dir)
    {
        $code = "\n";
        $code .= "namespace TF\\$dir" . ";\n";

        if ($this->extendsNamespace != '') {
            $code .= "use " . $this->extendsNamespace . ";\n";
        }

        if (!empty($this->classDocBlock)) {
            $code .= $this->getDocBlock($this->classDocBlock);
        }

        $code .= 'class ' . $this->name . "\n";
        if ($this->extends != '') {
            if ($this->extendsNamespace != '') {
                $nsLastName = array_reverse(explode('\\', $this->extendsNamespace));
                $code .= "\t" . 'extends ' . $nsLastName[0] . '\\' . $this->extends . "\n";
            } else {
                $code .= "\t" . 'extends ' . $this->extends . "\n";
            }
        }
        $code .= '{' . "\n";
        $code .= '' . "\n";
        if (in_array($this->type, $this->basicTypes)) {
            $code .= "\t\t" . $this->getDocBlock(['xmlType' => 'value', 'var' => $this->normalizeType($this->type)], "\t\t");
            $code .= "\t\tpublic " . '$value;';
        }

        if (!empty($this->classProperties)) {
            $code .= $this->getClassProperties($this->classProperties);
        }

        $code .= '' . "\n";
        $code .= '' . "\n";
        $code .= '} // end class ' . $this->name . "\n";

        return $code;
    }

    /**
     * Return class properties from array with indent specified
     *
     * @param array $props  Properties array
     * @param array $indent Indentation in tabs
     *
     * @return string
     */
    public function getClassProperties($props, $indent = "\t")
    {
        $code = $indent . "\n";

        foreach ($props as $prop) {

            if (!empty($prop['docs'])) {
                $code .= $indent . $this->getDocBlock($prop['docs'], $indent);
            }
            $code .= $indent . 'public $' . $prop['name'] . ";\n";


            // Add getter and setters
            if (!isset($prop['docs']['var']))
                $typeHint = null;
            else
                $typeHint = self::validateTypeHint($prop['docs']['var']);;

            $newMember = $this->getVariable($typeHint, $prop['name'], true);

            $setterCommentObj = new PhpDocComment();
            $setterCommentObj->addParam(PhpDocElementFactory::getParam($typeHint, $prop['name'], ''));
            $setterCommentObj->setReturn(PhpDocElementFactory::getReturn('$this', ''));

            $setterCode = $this->getSetterBody($prop['name'], $typeHint, $newMember);

            $setter = new PhpFunction(
                'public',
                'set' . ucfirst($prop['name']),
                $this->buildParametersString(
                    [$prop['name'] => $typeHint],
                    true,
                    // If the type of a member is nullable we should allow passing null to the setter. If the type
                    // of the member is a class and not a primitive this is only possible if setter parameter has
                    // a default null value. We can detect whether the type is a class by checking the type hint.
                    $newMember->getNullable() && !empty($typeHint)
                ),
                $setterCode,
                $setterCommentObj
            );

            $getterComment = new PhpDocComment();
            $getterComment->setReturn(PhpDocElementFactory::getReturn($typeHint, ''));
            $getterCode = $this->getGetterBody($prop['name'], $typeHint);

            $getter = new PhpFunction('public', 'get' . $prop['name'], '', $getterCode, $getterComment);


            $code = $code . $getter->getSource() . $setter->getSource() . PHP_EOL;

        }

        return $code;
    }

    /**
     * This will generate getter function body
     *
     * @param $name
     * @param $type
     *
     * @return string
     *
     * @author Irfan Baig <irfan.baig@tajawal.com>
     *
     */
    public function getGetterBody($name, $type)
    {
        $getterCode = '';
        if ($type == '\DateTime') {
            $getterCode = '  if ($this->' . $name . ' == null) {' . PHP_EOL
                          . '    return null;' . PHP_EOL
                          . '  } else {' . PHP_EOL
                          . '    try {' . PHP_EOL
                          . '      return new \DateTime($this->' . $name . ');' . PHP_EOL
                          . '    } catch (\Exception $e) {' . PHP_EOL
                          . '      return false;' . PHP_EOL
                          . '    }' . PHP_EOL
                          . '  }' . PHP_EOL;
        } else {
            $getterCode = '  return $this->' . $name . ';' . PHP_EOL;
        }

        return $getterCode;
    }

    /**
     * This will generate setter function body
     *
     * @param $name
     * @param $type
     * @param $member
     *
     * @return string
     *
     * @author Irfan Baig <irfan.baig@tajawal.com>
     *
     */
    public function getSetterBody($name, $type, $member)
    {

        $setterCode = '';

        if ($type == '\DateTime') {
            if ($member->getNullable()) {
                $setterCode = '  if ($' . $name . ' == null) {' . PHP_EOL
                              . '   $this->' . $name . ' = null;' . PHP_EOL
                              . '  } else {' . PHP_EOL
                              . '    $this->' . $name . ' = $' . $name . '->format(\DateTime::Carbon);' . PHP_EOL
                              . '  }' . PHP_EOL;
            } else {
                $setterCode = '  $this->' . $name . ' = $' . $name . '->format(\DateTime::Carbon);' . PHP_EOL;
            }
        } else {
            $setterCode .= '  $this->' . $name . ' = $' . $name . ';' . PHP_EOL;
        }
        $setterCode .= '  return $this;' . PHP_EOL;

        return $setterCode;
    }

    /**
     * Validates a type to be used as a method parameter type hint.
     *
     * @param string $typeName The name of the type to test.
     *
     * @return null|string Returns a valid type hint for the type or null if there is no valid type hint.
     */
    public static function validateTypeHint($typeName)
    {
        $typeHint = $typeName;
        // We currently only support type hints for arrays and DateTimes.
        // Going forward we could support it for generated types. The challenge here are enums as they are actually
        // strings and not class instances and we have no way of determining whether the type is an enum at this point.
        if (substr($typeName, -2) == "[]") {
            $typeHint = 'array';
        } elseif ($typeName == '\DateTime') {
            $typeHint = $typeName;
        } elseif ($typeName == 'integer') {
            $typeHint = 'int';
        }

        return $typeHint;
    }

    /**
     * Adds the member. Owerwrites members with same name
     *
     * @param string $type
     * @param string $name
     * @param bool   $nullable
     */
    public function getVariable($type, $name, $nullable)
    {
        return new Variable($type, $name, $nullable);
    }

    /**
     * Generate a string representing the parameters for a function e.g. "type1 $param1, type2 $param2, $param3"
     *
     * @param array $parameters  A map of parameters. Keys are parameter names and values are parameter types.
     *                           Parameter types may be empty. In that case they are not used.
     * @param bool  $includeType Whether to include the parameters types in the string
     * @param bool  $defaultNull Whether to set the default value of parameters to null.
     *
     * @return string The parameter string.
     */
    protected function buildParametersString(array $parameters, $includeType = true, $defaultNull = false)
    {
        $parameterStrings = [];
        foreach ($parameters as $name => $type) {
            $parameterString = '$' . $name;
            if (!empty($type) && $includeType) {
                $parameterString = $type . ' ' . $parameterString;
            }
            if ($defaultNull) {
                $parameterString .= ' = null';
            }
            $parameterStrings[] = $parameterString;
        }

        return implode(', ', $parameterStrings);
    }

    /**
     * Return docBlock
     *
     * @param array  $docs   Array of docs
     * @param string $indent Indentation
     *
     * return string
     */
    public function getDocBlock($docs, $indent = "")
    {
        $code = '/**' . "\n";
        foreach ($docs as $key => $value) {
            $code .= $indent . ' * @' . $key . ' ' . $value . "\n";
        }
        $code .= $indent . ' */' . "\n";

        return $code;
    }


}
