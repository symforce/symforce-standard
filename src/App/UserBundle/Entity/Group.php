<?php

namespace App\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\AdminBundle\Compiler\Annotation as Admin ;

use Doctrine\Common\Collections\ArrayCollection ;

/**
 * @ORM\Entity
 * @ORM\Table(name="app_fos_group")
 * @Admin\Entity("app_group", label="Group", icon="group", position=1, menu="admin_group", dashboard=true, class="App\UserBundle\Admin\GroupAdmin" )
 * 
 * @Admin\Action("update") 
 * @Admin\Action("delete")
 * 
 */
class Group implements \FOS\UserBundle\Model\GroupInterface 
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Admin\Table
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string", unique=true)
     * @Gedmo\Translatable
     * @Admin\Form()
     * @Admin\Table
     * @Admin\ToString
     */
    protected $name;
    
    /**
     * @Gedmo\Slug(fields={"name"}, updatable=false )
     * @ORM\Column(length=255, unique=false)
     * @Admin\Table()
     * @Admin\Form(auth="super")
     */
    public $slug ;
    
    /**
     * @ORM\Column(type="array")
     * @Admin\Form(type="choice", multiple=true, auth="super", choices={"ROLE_USER" , "ROLE_MEMBER", "ROLE_ADMIN",  "ROLE_ADMIN_EDITOR" , "ROLE_ADMIN_MANAGER", "ROLE_ADMIN_PARTNER", "ROLE_ADMIN_BOSS" } )
     */
    protected $roles ;
    
    /**
     * @ORM\OneToMany(targetEntity="User", mappedBy="user_group")
     * @Admin\Table()
     */
    public $users ;
    
    /**
     * @ORM\Column(type="array", nullable=true)
     * @Admin\Form(label="权限", type="authorize", auth="super")
     */
    protected $authorize ;
    
    
    /**
     * @ORM\Column(type="boolean")
     * @Admin\Form(label="默认组", auth=true)
     * @Admin\Table
     */
    public $default_group = false ;
    
    /**
     * @ORM\Column(type="boolean")
     * @Admin\Form(label="认证组", auth=true)
     * @Admin\Table
     */
    public $trust_group = false ;
    
    public function __construct(){
        $this->roles = new ArrayCollection() ;
    }
    
    /**
     * @param string $role
     *
     * @return Group
     */
    public function addRole($role)
    {
        $role   = strtoupper($role) ;
        if (!$this->roles->contains($role) ) {
            $this->roles->add($role) ;
        }
        $this->roles    = new ArrayCollection( $this->roles->toArray() ) ;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $role
     */
    public function hasRole($role)
    {
        $role   = strtoupper($role) ;
        return $this->roles->contains($role) ; 
    }

    public function getRoles()
    {
        return $this->roles->getValues() ;
    }
    
    /**
     * @param string $role
     *
     * @return Group
     */
    public function removeRole($role)
    {
        $this->roles->removeElement( $role ) ;
        $this->roles    = new ArrayCollection( $this->roles->toArray() ) ;
        return $this;
    }

    /**
     * @param string $name
     *
     * @return Group
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param array $roles
     *
     * @return Group
     */
    public function setRoles(array $roles)
    {
        $this->roles = new ArrayCollection($roles) ;
        return $this;
    }
    
    /**
     * @return array
     */
    public function getAuthorize(){
        return $this->authorize ;
    }
    
    public function setAuthorize(array $value = null) {
        $this->authorize    = $value ;
    }
    
    public function auth($admin_name, $action_name){
        if( $this->authorize ) {
            if( isset( $this->authorize[$admin_name]['action']) ) {
                if( null !== $action_name ) {
                    return isset( $this->authorize[$admin_name]['action'][ $action_name ] );
                } 
                return true ;
            }
        }
    }
    
    public function isPropertyVisiable($admin_name, $property_name, $action_name ){
        if( $this->authorize ) {
            return isset( $this->authorize[$admin_name]['property'][$property_name][$action_name]) ;
        }
    }
    
    public function isPropertyReadonly($admin_name, $property_name, $action_name ){
        if( $this->authorize ) {
            if( isset( $this->authorize[$admin_name]['property'][$property_name][$action_name] ) ) {
                return "2" === $this->authorize[$admin_name]['property'][$property_name][$action_name] ;
            }
        } 
        return false ;
    }
    
    public function isOwnerVisiable($admin_name, $action_name ){
        if( $this->authorize ) {
            return isset( $this->authorize[$admin_name]['owner'][$action_name]) ;
        }
    }
    
    public function __toString() {
        return $this->name ;
    }
    
    
    public function isEqual($group){
        if( $group && $group instanceof Group ) {
            if( $this->id ) {
                return $this->id === $group->getId() ;
            }
            return $group === $this ;
        }
    }
    
}