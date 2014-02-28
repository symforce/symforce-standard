<?php


namespace App\AdminBundle\Compiler\MetaType\Admin ;

use App\AdminBundle\Compiler\Annotation;
use App\AdminBundle\Compiler\Generator;

/**
 * Description of Column
 *
 * @author loong
 */
class Column extends EntityAware {
    
    const COLUMN_ANNOT_CLASS   = 'App\\AdminBundle\\Admin\\Annotation\\Column' ;
    const PAGE_ANNOT_CLASS   = 'App\\AdminBundle\\Admin\\Annotation\\Page' ;
    const PAGE_ENTITY_CLASS  = 'App\\AdminBundle\\Entity\\Page' ;
    const USED_NAME =  ' app tmp temp put get post file save admin loader root parent child children tree id list create update delete view action batch action property filter search cache ';
    
    public $column_property ;
    public $title_property ;

    public $id ;
    public $name ;
    
    public $children = array() ;
    
    public function __construct(Entity $admin, $property, Annotation\Column $annot ) {
        $map    = $admin->getPropertyDoctrineAssociationMapping($property) ;
        if( !$map ) {
            $this->throwError("property:%s should set @Doctrine\ORM\Mapping\OneToOne(targetEntity=%s)", $property, self::PAGE_ENTITY_CLASS );
        } else {
            if( self::PAGE_ENTITY_CLASS !== $map['targetEntity'] ) {
                $this->throwError("property:%s should set @Doctrine\ORM\Mapping\OneToOne(targetEntity=%s), you set to:%s", $property, self::PAGE_ENTITY_CLASS , $map['targetEntity'] );
            }
        }
        $this->setAdminObject( $admin ) ;
        $this->column_property    = $property ;
        
        $this->setMyPropertie( $annot ) ;
        if( !$this->title_property  ) {
            if( !$admin->property_value_name  ) {
                $this->throwError("page must set a title property");
            }
            $this->title_property   = $admin->property_value_name ;
        }
    }
    
    protected function set_id( $id ) {
        if( preg_match('/\D/', $id) ) {
            $this->throwError("id(%s) is invalid", $id ) ;
        }
        $this->id = (int) $id ;
    }
    
    protected function set_name( $name ) {
        if( $this->name ) {
            $this->throwError("can not set name agian");
        }
        $name   = trim( strtolower( $name ) ) ;
        if( preg_match('/\s'  . preg_quote($name) . '\s/',  self::USED_NAME ) ) {
            $this->throwError("name can not set to `%s`", $name);
        }
        if( preg_match('/\W/u', $name) ) {
            $this->throwError("name(%s) is invalid", $name ) ;
        }
        $this->name = $name ;
    }
    
    protected function set_title( $value ) {
        if( ! property_exists( $this->admin_object->class_name , $value) ) {
            $this->throwError("title use property:%s not exists ", $value);
        }
        $this->title_property   = $value ;
    }
    
    public function addChild(Page $page){
        $this->children[ $page->admin_object->name ] = $page ;
    }
    
    public function lazyInitialize(){
       
    }
    
    public function compile() {
        if( $this->id  ) {
            $dup_admin_name = array_search( $this->id, $this->admin_object->generator->column_config, true ) ;
            if( $dup_admin_name ) {
                $this->throwError("duplicate page id(%s, %s)", $this->admin_object->name, $dup_admin_name );
            }
        }
        $this->admin_object->generator->column_config[ $this->admin_object->name ] = $this->id ;
    }
}
