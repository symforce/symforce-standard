<?php

namespace App\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\AdminBundle\Compiler\Annotation as Admin ;

use FOS\UserBundle\Model\GroupInterface ;
use Doctrine\Common\Collections\ArrayCollection ;

use FOS\MessageBundle\Model\ParticipantInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="app_fos_user")
 * @Admin\Entity("app_user", label="User", position=2, string="username", icon="user", dashboard=true )
 * 
 * @Admin\Table("id", order=true)
 * 
 * @Admin\Form("username", label="User Name", unique="用户名已经被使用了!")
 * @Admin\Table("username", order=true)
 * 
 * @Admin\Form("email", label="Email", unique="地址已经被使用了!")
 * @Admin\Table("email", order=true)
 * 
 * @Admin\Form("enabled")
 * @Admin\Table("enabled", order=true)
 * 
 * @Admin\Form("expiresAt", label="过期日期")
 * 
 * @Admin\Form("plainPassword", type="password", label="Password", auth="super")
 */
class User extends \FOS\UserBundle\Model\User implements ParticipantInterface
{
    const ROLE_USER  = 'ROLE_USER' ;
    const ROLE_MEMBER  = 'ROLE_MEMBER' ;
    const ROLE_ADMIN  = 'ROLE_ADMIN' ;
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @var Group
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="users", cascade={"persist"} )  
     * @Admin\Table
     * @Admin\Form(auth=true)
     * @Admin\Filter
     */
    protected $user_group ;
    
    /**
     * @ORM\Column(type="string", length=18, nullable=true)
     * @Admin\Form(label="身份证")
     */
    public $id_card ;
    
   
    public $registration_term ;
    
    public $id_term ;
    
    /**
     * @ORM\OneToMany(targetEntity="UserLog", mappedBy="user", cascade={"remove"})
     * @Admin\Table()
     */
    public $logs ;
    
    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     * @Admin\Form(label="真实姓名")
     */
    public $real_name ;
    
    /**
     * @ORM\Column(type="integer")
     * @Admin\Form(label="性别", type="choice",  choices={"1":"男", "0":"女" } )
     */
    public $gender = 1 ;
    
    
    /** 
     * @ORM\OneToOne(targetEntity="App\AdminBundle\Entity\File")
     * @Admin\Form(label="头像", type="image", max_size="1m", image_size="128x128", small_size="24x24" )
     */
    public $avatar ;
    
    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     * @Admin\Form(label="座机")
     */
    public $phone_number ;
    
    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     * @Admin\Form(label="手机")
     */
    public $mobile_phone_number ;
    
    /**
     * @var datetime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;
    
    /**
     * @var string
     */
    public $old_password;
    
    public function __construct() {
        parent::__construct();
        $this->enabled = true ;
        $this->created = new \DateTime('now') ;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }
    
    public function getId(){
        return $this->id ;
    }
    
    /**
     * @return Group
     */
    public function getUserGroup() {
        return $this->user_group ;
    }
    
    public function setUserGroup(Group $group ) {
         $this->user_group = $group ; 
    }
    
    /**
     * Returns the user roles
     *
     * @return array The roles
     */
    public function getRoles()
    {
        $roles = $this->roles;

        if( $this->user_group ) {
            $roles = array_merge($roles, $this->user_group->getRoles());
        }
        
        // we need to make sure to have at least one role
        $roles[] = static::ROLE_DEFAULT ;
        return array_unique($roles);
    }
    
    public function isEqual($user){
        if( $user && $user instanceof User ) {
            if( $this->id ) {
                return $this->id === $user->getId() ;
            }
            return $user === $this ;
        }
    }
    
    public function __toString() {
        return $this->username ;
    }
    
    public function getExpiresAt(){
        return $this->expiresAt ;
    }
    
    public function getCredentialsExpireAt(){
        return $this->credentialsExpireAt ;
    }
}