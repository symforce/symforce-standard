<?php

namespace App\AdminBundle\Compiler\Cache ;

trait AdminOwner {
    
    protected $property_owner_name ;
    
    /**
     * @var \AppAdminCache\AppUser\AdminAppUser
     */
    protected $owner_admin ;
    
    public function hasOwner() {
        return $this->property_owner_name ;
    }


    /**
     * @return \AppAdminCache\AppUser\AdminAppUser
     */
    public function getOwnerAdmin() {
        if( !$this->property_owner_name ) {
            throw new \Exception("big error") ;
        }
        if( null === $this->owner_admin ) {
            $this->owner_admin   = $this->admin_loader->getAdminByClass( \App\AdminBundle\Compiler\MetaType\Admin\Owner::USER_ENTITY_CLASS ) ;
        }
        return $this->owner_admin ;
    }
    
    public function getRouteOwnerIdentity() {
        return 'mine' ;
    }
    
    public function setRouteOwnerIdentity( $id ) {
        
    }
    
    public function getRouteOwnerId() {
        return 0 ;
    }
    
    public function setRouteOwnerId( $id ) {
        
    }
    
    public function getObjectOwner( $object ) {
        if( !($object instanceof $this->class_name ) ) {
            throw new \Exception("big error") ;
        }
        return $this->getReflectionProperty( $this->property_owner_name )->getValue( $object ) ;
    }
    
    /**
     * @param object $object
     * @return array
     * @throws \Exception
     */
    public function getOwnerFormChoices( $object ) {
        if( !($object instanceof $this->class_name ) ) {
            throw new \Exception("big error") ;
        }
        $admin  = $this->getOwnerAdmin() ;
        
        $dql    = $admin->getRepository()->createQueryBuilder('u');
        $dql->orderBy('u.email', 'asc' );
        
        $users  = $dql->getQuery()->execute() ;
        
        $choices = array() ;
        foreach($users as $user ){
            $id = $admin->getReflectionProperty( $this->property_id_name )->getValue( $user ) ;
            $choices[ $id ] = $admin->string($user) ;
        }
        return $choices ;
    }
}