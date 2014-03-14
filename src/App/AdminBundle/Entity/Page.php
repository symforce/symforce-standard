<?php

namespace App\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\AdminBundle\Compiler\Annotation as Admin ;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name="app_pages")
 * @Admin\Entity("app_page", label="Page", icon="archive", position=3, menu="admin_group", dashboard=true , groups={
 *      "default": "默认",
 *      "seo":"SEO"
 * })
 * 
 * @Gedmo\Tree(type="nested") 
 */
class Page
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Admin\Table()
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     * @Admin\Form
     * @Admin\Table
     * @Admin\ToString
     */
    public $title;
    
    /**
     * @Gedmo\Slug(fields={"title"}, updatable=false )
     * @ORM\Column(length=255, unique=true)
     * @Admin\Form(group="seo")
     * @Admin\Table()
     */
    public $slug;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Admin\Form(group="seo")
     */
    public $meta_keywords ;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Admin\Form(group="seo")
     */
    public $meta_description ;
    
    /**
     * @ORM\Column(type="integer")
     * @Admin\Form(position=1, group="seo")
     * @Admin\Table()
     */
    public $order_by = 0 ;
    
    /** 
     * @ORM\OneToOne(targetEntity="App\AdminBundle\Entity\File")
     * @Admin\Form(type="image", max_size="1m", image_size="120x130", small_size="12x12" )
     */
    public $image;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     * @Admin\Form(type="html")
     * @Gedmo\Translatable
     */
    public $content ;
    
    /**
     * @var datetime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    public $created ;

    /**
     * @var datetime $updated
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    public $updated;
    
    
    // ========= Page ===========
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Admin\Form(auth=true)
     * @Admin\Table
     */
    public $admin_class ;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Admin\Form(auth=true)
     * @Admin\Table
     */
    public $admin_page_property ;
    
    /**
     * @ORM\Column(type="boolean")
     * @Admin\Form(auth=true)
     * @Admin\Table
     */
    public $admin_is_root = false ;
    
    /**
     * @ORM\Column(type="integer")
     * @Admin\Form(auth=true)
     * @Admin\Table
     */
    public $admin_entity_id = 0 ;
    
    // ========= tree ========
    
    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer")
     */
    protected $tree_left_node;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(type="integer")
     */
    protected $tree_level ;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer")
     */
    protected $tree_right_node;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $tree_root_node;
    
    /**
     * @Gedmo\TreePath
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $tree_path ;
    
    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="children", cascade={"persist"} )
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parent;

    
    /**
     * @ORM\Column(type="boolean")
     * @Admin\Form(auth=true)
     * @Admin\Table
     * @Admin\TreeLeaf
     */
    public $tree_leaf = 0 ;
    
    /**
     * @ORM\OneToMany(targetEntity="Page", mappedBy="parent")
     * @ORM\OrderBy({"tree_left_node" = "ASC"})
     */
    private $children;
    

    public function __construct()
    {
        
    }
    
    public function getId()
    {
        return $this->id ;
    }
    
    public function setParent(Page $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * @return Page
     */
    public function getParent()
    {
        return $this->parent;
    }
    
}