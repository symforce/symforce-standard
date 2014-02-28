<?php


namespace App\AdminBundle\Entity ;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\AdminBundle\Entity\FileRepository")
 * @ORM\Table(name="app_file", indexes={@ORM\Index(name="entity_idx", columns={"class_name", "property_name"}), @ORM\Index(name="session_idx", columns={"session_id"}) } )
 */
class File
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="uuid", unique=true)
     */
    protected $uuid;
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;
    
    /**
     * @ORM\Column(type="string", length=8)
     */
    protected $ext;
    
    /**
     * @ORM\Column(type="string", length=64)
     */
    protected $type;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $size;
    
    /**
     * @ORM\Column(type="string", length=32)
     */
    protected $class_name;
    
    /**
     * @ORM\Column(type="string", length=32)
     */
    protected $property_name;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $entity_id = 0;
    
    /**
     * @ORM\Column(type="boolean")
     */
    protected $is_html_file = false;
    
    /** 
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $session_id;
    
    /**
     * @ORM\Column(type="blob", nullable=true)
     */
    protected $preview;
    
    /**
     * @ORM\Column(type="blob")
     */
    protected $content;
    
    /**
     * @var datetime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var datetime $updated
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updated ;

    public function __construct()
    {
        
    }
    
    public function getId()
    {
        return $this->id ;
    }
    
    public function getName()
    {
        return $this->name ;
    }
    
    public function setName( $name )
    {
        $this->name = $name ;
    }
    
    public function getExt()
    {
        return $this->ext ;
    }
    
    public function setExt( $ext )
    {
        $this->ext = $ext ;
    }
    
    public function getType()
    {
        return $this->type ;
    }
    
    public function setType($type)
    {
        $this->type = $type ;
    }
    
    public function getSize()
    {
        return $this->size ;
    }
    
    public function setSize( $size )
    {
        $this->size = $size ;
    }
    
    public function getUuid()
    {
        return $this->uuid ;
    }
    
    public function setUuid( $value )
    {
        $this->uuid = $value ;
    }
    
    public function getClassName() {
        return $this->class_name;
    }
    
    public function setClassName( $value ) {
         $this->class_name = $value ;
    }
    
    public function getPropertyName() {
        return $this->property_name;
    }
    
    public function setPropertyName( $value ) {
         $this->property_name = $value ;
    }
    
    public function getEntityId () {
        return $this->entity_id ;
    }
    
    public function setEntityId( $value ) {
         $this->entity_id  = $value ;
    }
    
    public function getIsHtmlFile(){
        return $this->is_html_file ;
    }
    
    public function setIsHtmlFile( $value ){
         $this->is_html_file = !! $value ;
    }

    public function setSessionId( $value ) {
        $this->session_id = $value ;
    }
    
    /**
     * @return string
     */
    public function getSessionId() {
        return $this->session_id ;
    }
    
    /**
     * 
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }
    
    public function setCreated(\DateTime $value )
    {
        $this->created = $value ;
    }
    
    /**
     * 
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }
    
    public function setUpdated(\DateTime $value )
    {
        $this->updated = $value ;
    }
    
    public function setPreview( $value ){
        $this->preview = $value ;
    }
    
    public function getPreview(){
        return $this->preview ;
    }
    
    public function setContent( $value ){
        $this->content = $value ;
    }
    
    public function getContent() {
        return $this->content ;
    }
    
    public function __toString() {
        if( $this->is_html_file ) {
            return '/upload/html/' . $this->uuid . '.' . $this->ext ;
        } else {
            return '/upload/file/' . $this->uuid . '.' . $this->ext ;
        }
    }
}