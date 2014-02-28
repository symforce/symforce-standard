<?php

namespace App\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use FOS\MessageBundle\Model\ParticipantInterface;
use App\AdminBundle\Compiler\Annotation as Admin ;

/**
 * @ORM\Entity
 * @ORM\Table(name="app_fos_message_metadata")
 * Admin\Entity("app_message_metadata")
 * 
 */
class MessageMetadata extends \FOS\MessageBundle\Entity\MessageMetadata
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="Message",
     *   inversedBy="metadata"
     * )
     * @var MessageInterface
     */
    protected $message;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @var ParticipantInterface
     */
    protected $participant;
}