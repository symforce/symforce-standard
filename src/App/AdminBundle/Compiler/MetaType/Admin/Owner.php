<?php


namespace App\AdminBundle\Compiler\MetaType\Admin ;

use App\AdminBundle\Compiler\Annotation;
use App\AdminBundle\Compiler\Generator;

/**
 * Description of Page
 *
 * @author loong
 */
class Owner extends EntityAware {
    
    const OWNER_ANNOT_CLASS = 'App\\AdminBundle\\Compiler\\Annotation\\Owner' ;
    const USER_ENTITY_CLASS   = 'App\UserBundle\Entity\User' ;
    
    public $owner_property ;

    public $id ;
    public $name ;
    
    public $children = array() ;
    
    public function __construct(Entity $admin, $property, Annotation\Owner $annot ) {
        $map    = $admin->getPropertyDoctrineAssociationMapping($property) ;
        if( !$map ) {
            $this->throwError("property:%s should set @Doctrine\ORM\Mapping\OneToOne(targetEntity=%s)", $property, self::PAGE_ENTITY_CLASS );
        }
        
        if( self::USER_ENTITY_CLASS !== $map['targetEntity'] ) {
            $this->throwError("property:%s should set @Doctrine\ORM\Mapping\ManyToOne(targetEntity=%s), you set to:%s", $property, self::USER_ENTITY_CLASS , $map['targetEntity'] );
        }
        
        $this->setAdminObject( $admin ) ;
        $this->owner_property    = $property ;
        $this->setMyPropertie( $annot ) ;
    }
    
    
    public function lazyInitialize(){
        
    }
    
    public function compile(){
        $class  = $this->admin_object->getCompileClass() ;
        $class->addProperty('property_owner_name',  $this->owner_property ) ;
    }
}
