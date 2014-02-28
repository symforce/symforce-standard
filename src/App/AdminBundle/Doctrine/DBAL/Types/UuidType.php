<?php

namespace App\AdminBundle\Doctrine\DBAL\Types ;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
 
/**
 * Description of UuidType
 *
 * @author loong
 */
class UuidType extends Type
{
 
    const BINARY = 'binary' ;
 
    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        if( !isset($fieldDeclaration['length']) || $fieldDeclaration['length'] < 4 || $fieldDeclaration['length'] >= 255 ) {
            $fieldDeclaration['length'] = 16 ;
        }
        if( $platform instanceof \Doctrine\DBAL\Platforms\SqlitePlatform ) {
            return sprintf('char(%d)', $fieldDeclaration['length']);
        }
        return sprintf('binary(%d)', $fieldDeclaration['length']);
    }
 
    public function getName()
    {       
        return self::BINARY;
    }   
 
    public function convertToPhpValue($value, AbstractPlatform $platform)
    {
        if ($value !== null) {
            $value= unpack('H*', $value);
            $hash = array_shift($value);
            $uuid = substr($hash,  0,  8) . '-' . substr($hash,  8,  4) . '-' . substr($hash, 12,  4) . '-' . substr($hash, 16,  4) . '-' . substr($hash, 20, 12);
            return $uuid;
        }
    }
 
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value !== null) {
            return pack('H*', str_replace('-', '',$value));
        }
    }
 
}