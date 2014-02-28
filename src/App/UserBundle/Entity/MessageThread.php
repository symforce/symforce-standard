<?php

namespace App\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use FOS\MessageBundle\Model\ParticipantInterface;

use App\AdminBundle\Compiler\Annotation as Admin ;

/**
 * @ORM\Entity
 * @ORM\Table(name="app_fos_message_thread")
 * Admin\Entity("app_message_thread")
 */
class MessageThread extends \FOS\MessageBundle\Entity\Thread
{
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    protected $createdBy;

    /**
     * @ORM\OneToMany(
     *   targetEntity="Message",
     *   mappedBy="thread"
     * )
     * @var Message[]|\Doctrine\Common\Collections\Collection
     */
    protected $messages;

    /**
     * @ORM\OneToMany(
     *   targetEntity="MessageThreadMetadata",
     *   mappedBy="thread",
     *   cascade={"all"}
     * )
     * @var MessageThreadMetadata[]|\Doctrine\Common\Collections\Collection
     */
    protected $metadata;    
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $event_type;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $event_source;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $event_target;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $event_data;
    
    public function setEventType($value) {
        $this->event_type = $value ;
    }
    public function setEventSource($value) {
        $this->event_source = $value ;
    }
    public function setEventTarget($value) {
        $this->event_target = $value ;
    }
    public function setEventData($value) {
        $this->event_data = $value ;
    }
    
    public function getEvent() {
        return array(
            'type'  => $this->event_type ,
            'source'  => $this->event_source ,
            'target'  => $this->event_target ,
            'data'  => $this->event_data ,
        );
    }

    /**
     * @var array 
     */
    protected $render_options ;
    
   /**
    * @return array
    */
    public function getRenderOptions() {
        return $this->render_options ;
    }
    
    public function setRenderOptions(array $value ) {
         $this->render_options = $value ;
    }
}
