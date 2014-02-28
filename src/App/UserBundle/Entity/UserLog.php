<?php

namespace App\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\AdminBundle\Compiler\Annotation as Admin ;

use FOS\UserBundle\Model\GroupInterface ;
use Doctrine\Common\Collections\ArrayCollection ;

use FOS\MessageBundle\Model\ParticipantInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="app_fos_user_log")
 * @Admin\Entity("app_user_log", label="User Logs", string="type", icon="user" )
 * 
 */
class UserLog
{
    
    const TYPE_USER_REG = 'REG' ;
    const TYPE_USER_CONFIRM = 'CONFIRM' ;
    const TYPE_USER_BANK = 'BANK' ;
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * 
     */
    protected $id ;
    
    /**
     * @ORM\Column(type="string", length=32)
     * @Admin\Form(type="choice", label="事件类型", auth=true, choices={"REG" , "CONFIRM" } )
     * @Admin\Table
     */
    protected $type ;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Admin\Form(label="事件数据")
     * @Admin\Table
     */
    public $data ;
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    public $browser ;
    
    /**
     * @ORM\Column(type="string", length=16)
     * @Admin\Form(label="IP")
     * @Admin\Table
     */
    public $ip ;
    
    /**
     * @ORM\Column(type="datetime")
     * @Admin\Form(label="日期")
     * @Admin\Table
     */
    public $date ;
    
    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="logs", cascade={"persist"} )  
     * @Admin\Table()
     */
    protected $user ;
    
    public function __construct() {
        $this->date = new \DateTime('now') ;
    }

    public function getId(){
        return $this->id ;
    }
    
    public function setUser(User $user) {
        $this->user = $user ;
    }
    
    /**
     * @return User
     */
    public function getUser() {
        return $this->user ;
    }
    
    public function setType($type) {
        $this->type = $type ;
    }
    
    /**
     * @return User
     */
    public function getType() {
        return $this->type ;
    }
    
}