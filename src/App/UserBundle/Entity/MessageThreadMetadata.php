<?php

namespace App\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use FOS\MessageBundle\Model\ParticipantInterface;
use App\AdminBundle\Compiler\Annotation as Admin ;

/**
 * @ORM\Entity
 * @ORM\Table(name="app_fos_message_thread_metadata")
 * Admin\Entity("app_message_thread_metadata")
 * 
 */
class MessageThreadMetadata extends \FOS\MessageBundle\Entity\ThreadMetadata
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="MessageThread",
     *   inversedBy="metadata"
     * )
     * @var ThreadInterface
     */
    protected $thread;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @var ParticipantInterface
     */
    protected $participant;
}