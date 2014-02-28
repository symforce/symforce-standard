<?php

namespace App\AdminBundle\Service;

use Imagine\Image\ImagineInterface ;
use Imagine\Image\ImageInterface ;

/**
 * Description of Imagine
 *
 * @author loong
 */
class Imagine {
    
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container A ContainerInterface instance
     *
     */
    public function setContainer(\Symfony\Component\DependencyInjection\ContainerInterface $container = null)
    {
        $this->container = $container;
    }
    
    /**
     * @return  ImagineInterface
     */
    public function getImagine(){
        
        $imagine = new \Imagine\Gd\Imagine();
        //$imagine    = new \Imagine\Imagick\Imagine() ;
        //$imagine    = new \Imagine\Gmagick\Imagine ;
        return $imagine ;
    }
    
    
    public function resize(\App\AdminBundle\Entity\File $file, $_crop, array $config ){
        $crop = json_decode($_crop, true) ;
       
        $imagine    = $this->getImagine() ;
        
        $box_size  = new \Imagine\Image\Box( $config[0][0] / $crop['width'] , $config[0][1] / $crop['height']);
        $crop_point  = new \Imagine\Image\Point( $box_size->getWidth() * $crop['left'] , $box_size->getHeight() * $crop['top']);
        $crop_size  = new \Imagine\Image\Box( $config[0][0], $config[0][1] );
        
        while( $crop_point->getX() + $crop_size->getWidth() > $box_size->getWidth() ){
            if( $crop_point->getX() < 1 ) {
                break ;
            }
            $crop_point  = new \Imagine\Image\Point( $crop_point->getX() - 1  , $crop_point->getY() ) ;
        }
        
        while( $crop_point->getY() + $crop_size->getHeight() > $box_size->getHeight() ){
            if( $crop_point->getY() < 1 ) {
                break ;
            }
            $crop_point  = new \Imagine\Image\Point( $crop_point->getX()  , $crop_point->getY() - 1 ) ;
        }
        
        $options    = array(
                'quality'   => 100 ,
            );
        
        $img    = $imagine
                ->load( stream_get_contents($file->getContent()) ) 
                ;
        
        
        $img->resize($box_size)->crop($crop_point, $crop_size );
        
        $data   = $img->get( $file->getExt(), $options ) ;
        $stream = fopen('php://memory','r+');
        fwrite($stream, $data);
        rewind($stream);
        $file->setContent( $stream ) ;
        
        if( $config[1][0] && $config[1][1] ) {
            $small_box  = new \Imagine\Image\Box( $config[1][0], $config[1][1] );
            $img    = $imagine
                ->load( $data )
                ->resize( $small_box ) 
               ;
            
            $small_data   = $img->get( $file->getExt() , $options ) ;
            $small_stream = fopen('php://memory','r+');
            fwrite($small_stream, $small_data);
            rewind($small_stream);
            $file->setPreview( $small_stream ) ;
        }
        
        $file->setUpdated( new \DateTime('now') ) ;
    }
    
}
