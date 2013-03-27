<?php
namespace Vivo\Serializer\Adapter;

use Vivo\Serializer\Exception;
use Vivo\Text\Text;

use Zend\Serializer\Adapter\AbstractAdapter;

/**
 * Serializer adapter for serializing to Vivo entity format.
 *
 */
class Entity extends AbstractAdapter
{

    const INDENT = "\t";
    const EOA = '__EOA__';
    const NONE = '__NONE__';

    /**
     * Serialize
     *
     * @param  mixed $value
     * @return string
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function serialize($entity)
    {
        return $this->serializeRun($entity);
    }

    private function serializeRun($value, $name = false, $depth = 0)
    {
        $out = ($indent = str_repeat(self::INDENT, $depth))
                . ($name ? "$name " : '');
        switch ($type = gettype($value)) {
        case 'NULL':
            return $out . 'NULL';
        case 'boolean':
            return $out . 'boolean ' . ($value ? 'true' : 'false');
        case 'integer':
        case 'long':
        case 'float':
        case 'double':
            return $out . gettype($value) . ' ' . strval($value);
        case 'string':
            $str = strval($value);
            if (substr($str, -1) == '\\')
                $str .= '\\';
            return $out . 'string "' . str_replace('"', '\\"', $str) . '"';
        case 'array':
            $out .= "array (\n";
            foreach ($value as $key => $val) {
                $out .= $indent . self::INDENT . $this->serializeRun($key)
                        . ' : '
                        . ltrim($this->serializeRun($val, false, $depth + 1))
                        . "\n";
            }
            return $out . $indent . ")";
        case 'object':
            $class_name = get_class($value);
            $out .= "object $class_name ";
            $out .= "{\n";

            //@todo: proc?
            // 				if (method_exists($value, '__vivo_sleep')) {
            // 					$vars = array();
            // 					foreach ($value->__vivo_sleep() as $name)
            // 						$vars[$name] = $value->$name;
            // 				} else {

            $props = $this->getObjectProperties($value);
            unset($props['path']);

            foreach ($props as $name => $prop) {
                $prop->setAccessible(true);
                $val = $prop->getValue($value);

                // 					if (substr($name, 0, 2) != '__') //@fixme: proc?
                $out .= $this->serializeRun($val, $name, $depth + 1) . "\n";
            }
            return $out . $indent . '}';
        default:
            throw new Exception\RuntimeException("Unsupported type $type of value $value");
        }
    }

    private function getObjectProperties($className)
    {
        if (is_object($className)) {
            $ref = new \ReflectionObject($className);
        } else {
            $ref = new \ReflectionClass($className);
        }
        $props = $ref->getProperties();
        $props_arr = array();
        foreach ($props as $prop) {
            $name = $prop->getName();
            $props_arr[$name] = $prop;
        }
        if ($parentClass = $ref->getParentClass()) {
            $parent_props_arr = $this
                    ->getObjectProperties($parentClass->getName());
            if (count($parent_props_arr) > 0)
                $props_arr = array_merge($parent_props_arr, $props_arr);
        }
        return $props_arr;
    }

    public function unserialize($serialized)
    {
        return $this->unserializeRun($serialized);
    }

    public function unserializeRun(&$str, &$pos = 0)
    {
        switch ($type = Text::readWord($str, $pos)) {
        case 'NULL':
            return null;
        case 'boolean':
            return (Text::readWord($str, $pos) == 'true');
        case 'integer':
            return intval(Text::readWord($str, $pos));
        case 'long':
            return longval(Text::readWord($str, $pos));
        case 'float':
        case 'double':
            return doubleval(Text::readWord($str, $pos));
        case 'string':
            Text::expectChar('"', $str, $pos);
            $len = strlen($str);
            $start = $pos;
            while (!($str{$pos} == '"'
                    && ($str{$pos - 1} != '\\' || $str{$pos - 2} == '\\'))
                    && ($pos < $len))
                $pos++;
            $str2 = str_replace("\\\"", "\"",
                    substr($str, $start, ($pos++) - $start));
            return (substr($str2, -2) == "\\\\") ? substr($str2, 0, -1) : $str2;
        case 'array':
            Text::expectChar('(', $str, $pos);
            $array = array();
            while (($key = $this->unserializeRun($str, $pos)) !== self::EOA) {
                Text::expectChar(':', $str, $pos);
                $value = $this->unserializeRun($str, $pos);
                $array[$key] = $value;
            }
            return $array;
        case 'object':
            $class_name = Text::readWord($str, $pos);
            Text::expectChar('{', $str, $pos);
            $object = new $class_name;
            $refl = new \ReflectionObject($object);

            $vars = array();
            while (($name = Text::readWord($str, $pos)) != '}') {
                $prop = $refl->getProperty($name);
                $prop->setAccessible(true);
                $prop
                        ->setValue($object,
                                $vars[$name] = $this
                                        ->unserializeRun($str, $pos));
            }
            // 				if (method_exists($object, '__wakeup'))
            // 				$object->__wakeup();
            if ($class_name == 'DateTime') {
                try {
                    $object = new \DateTime($vars['date'],
                            new \DateTimeZone($vars['timezone']));
                } catch (\Exception $e) {
                    $object = new \DateTime;
                }
            }
            return $object;
        case ')': // end of array
            return self::EOA;
        default:
            throw new Exception\RuntimeException(
                    "Undefined type '$type' at position $pos: \""
                            . substr(substr($str, $pos), 20) . "...\"");
        }
    }
}
