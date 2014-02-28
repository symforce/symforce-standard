<?php

namespace App\AdminBundle\Doctrine\DBAL\Types ;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
 
/**
 * Description of UuidType
 *
 * @author loong
 */
class BlobType extends Type
{ 
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getBlobTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }
        
        /*
        if (is_string($value)) {
            $value = fopen('data://text/plain;base64,' . base64_encode($value), 'r');
        } 
        
        if ( ! is_resource($value)) {
            throw ConversionException::conversionFailed($value, self::BLOB);
        }
        */
        
        $value= unpack('H*', $value);
        return array_shift($value);
        return $value;
    }
    
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value !== null) {
            return pack('H*', $value);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Type::BLOB ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType()
    {
        return \PDO::PARAM_LOB;
    }
}