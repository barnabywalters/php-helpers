<?php

namespace BarnabyWalters\Doctrine\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * StringArray
 *
 * @author barnabywalters
 */
class StringArray extends Type{
    public function getName() {
        return 'stringArray';
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform) {
        if ($platform->getName() == 'postgresql')
            return 'string[]';
        else
            return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }
    
    public function convertToDatabaseValue($value, AbstractPlatform $platform) {
        if ($platform->getName() == 'postgresql') {
            $array = array_map(function ($val) {
                return '"' . addslashes((string) $val) . '"';
            }, $value);
            
            return '{' . join($array, ', ') . '}';
        } else {
            $array = array_map(function ($val) {
                return (string) $val;
            }, $value);
            
            return json_encode($array);
        }
    }
    
    public function convertToPHPValue($value, AbstractPlatform $platform) {
        if ($platform->getName() == 'postgresql') {
            
        } else {
            return json_decode($value);
        }
    }
}

// EOF
