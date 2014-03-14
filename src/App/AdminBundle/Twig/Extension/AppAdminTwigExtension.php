<?php

namespace App\AdminBundle\Twig\Extension;

use CG\Core\ClassUtils;

use Symfony\Component\DependencyInjection\ContainerInterface;

class AppAdminTwigExtension extends \Twig_Extension
{
    protected $loader;
    
    /**
     * @var \App\AdminBundle\Compiler\Loader\AdminLoader
     */
    protected $admin_loader;
    
    /**
     * @var ContainerInterface 
     */
    protected $container ;

    public function __construct(\Twig_LoaderInterface $loader)
    {
        $this->loader = $loader;
    }
    
    public function setContainer(ContainerInterface $container ){
        $this->container    = $container ;
        $this->admin_loader = $container->get('app.admin.loader') ;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'app_debug' => new \Twig_Function_Method($this, 'app_debug', array('is_safe' => array('html'))) ,
            'app_param' => new \Twig_Function_Method($this, 'app_param', array('is_safe' => array('html'))) ,
            
            'app_date_format'   => new \Twig_Function_Method($this, 'app_date_format') ,
            'app_date_diff'   => new \Twig_Function_Method($this, 'app_date_diff') ,
            'app_date_countdown'    => new \Twig_Function_Method($this, 'app_date_countdown', array('is_safe' => array('html'))) ,
            
            'app_locale_form'   => new \Twig_Function_Method($this, 'app_locale_form') ,
            'app_auth'   => new \Twig_Function_Method($this, 'app_auth') ,
            'app_admin_class'   => new \Twig_Function_Method($this, 'app_class') ,
            'app_admin'   => new \Twig_Function_Method($this, 'app_admin') ,
            'app_admin_path'   => new \Twig_Function_Method($this, 'app_admin_path') ,
            'app_path'   => new \Twig_Function_Method($this, 'app_page_path') ,
            'app_now'   => new \Twig_Function_Method($this, 'app_now') ,
            'twig_macro_exists'  => new \Twig_Function_Method($this, 'twig_macro_exists') ,
            'app_money' => new \Twig_Function_Method($this, 'app_money') ,
            
            'app_check_class' => new \Twig_Function_Method($this, 'app_check_class') ,
            
            'app_picker_format' => new \Twig_Function_Method($this, 'app_picker_format') ,
            'app_string_cut' => new \Twig_Function_Method($this, 'string_cut', array('is_safe' => array('html')) ) ,
            
            'app_percent' => new \Twig_Function_Method($this, 'app_percent', array('is_safe' => array('html')) ) ,
            
        );
    }
    
    public function app_check_class($object, $class) {
        if( !is_object($object) ) {
            throw new \Exception(sprintf("expect class(%s), get(%s)", $class, gettype($object))) ;
        } else if( !($object instanceof $class) ) {
            throw new \Exception(sprintf("expect class(%s), get(%s)", $class,  get_class($object))) ;
        }
    }
    
    public function twig_macro_exists($twig, $macro){
        // $rc = new \ReflectionClass($twig) ; echo $rc->getFileName() , "\n";
        return method_exists($twig, 'get' . $macro ) ;
    }
    
    
    public function app_money($value, $per = 2 , $currency = 'CNY' ){
        $locale = \Locale::getDefault();
        $format = new \NumberFormatter($locale, \NumberFormatter::CURRENCY) ;
        return $pattern = $format->formatCurrency($value, $currency) ;
    }
    
    public function app_now(){
        return time() ; 
    }
    
    public function app_locale_form( \Symfony\Component\HttpFoundation\Request $reqest ) {
        return $this->container->get('app.locale.listener')->getInlineForm($reqest) ;
    }

    public function app_auth($admin_name, $action_name = null , $object = null ){
        return $this->admin_loader->auth($admin_name, $action_name, $object ); 
    }
    
    public function app_admin($admin_name){
        return $this->admin_loader->getAdminByName($admin_name); 
    }
    
    public function app_class($admin_class){
        return $this->admin_loader->getAdminByClass($admin_class); 
    }
    
    public function app_admin_path( $admin, $action, $object = null , $options = array() ) {
        $admin  = $this->admin_loader->getAdminByName($admin) ;
        return $admin->path($action, $object, $options ) ;
    }
    
    public function app_debug( $o , $exit = true ) {
         \Dev::dump($o, 8 ) ;
         if( $exit ) {
             exit ;
         } 
    }
    
    public function app_page_path( $action, $object = null , $options = array() ) {
        $cache = $this->container->get('app.page.service') ;
        return $cache->path($action, $object, $options ) ;
    }
    
    public function app_param( $node )
    {
        if( $this->container->hasParameter($node) ) {
            return $this->container->getParameter($node) ;
        }
        return $node ;
    }
    
    public function app_date_format($data, $format) {
        if( $data instanceof \DateTime ) {
            return $data->format( $format ) ;
        }
        return $data ;
    }
    
    public function app_date_countdown(\DateTime $date, $stop_text = null ){
        $options    = array(
            'date'  => $date->format('Y-m-d H:i:s') ,
        ) ;
        if( $stop_text ) {
            $options['pass']    = $stop_text ;
        }
        return '<span class="app_countdown" data='. var_export(json_encode($options), 1).'></span>';
    }
    
    public function app_date_diff(\DateTime $date, $now = null , $text = null ) {
        if( null === $now ) {
            $now = time() ;
        }
        $pass = $date->getTimestamp() - $now ;
        if( $pass < 1 ) {
            return $text ;
        }
        $day    = 24 * 3600 ;
        if( $pass > $day ) {
            return ceil( $pass / $day ) . '天' ;
        }
        $hour   = 3600 ;
        if( $pass > $hour ) {
            return ceil( $pass / $hour ) . '小时' ;
        }
        $minute   = 60 ;
        if( $pass > $minute ) {
            return ceil( $pass / $minute ) . '分' ;
        }
        return $pass . '秒' ;
    }
    
    public function app_picker_format($format, $type ) {
        static $cache   = array() ;
        if( isset($cache[$type][$format]) ) {
            return $cache[$type][$format] ;
        }
        /**
         * @TODO fix format and add apc cache
         */
        static $map = array(
            'date'   => array(
                'Y' => 'yyyy' ,
                'y' => 'yy' ,
                'm' => 'mm' ,
                'n' => 'm' ,
                'd' => 'dd' ,
                'j' => 'd' ,
                'H' =>  'HH' , 
                'G' =>  'H' , 
                'i' => 'II' ,
                's' => 'SS' ,
            ) ,
            'datetime'  => array(
                'Y' => 'yyyy' ,
                'y' => 'yy' ,
                'm' => 'mm' ,
                'n' => 'm' ,
                'd' => 'dd' ,
                'j' => 'd' ,
                'H' =>  'hh' , 
                'G' =>  'h' , 
                'i' => 'ii' ,
                's' => 'ss' ,
            ) ,
        );
        
        if( !isset($map[$type]) ) {
            throw new \Exception(sprintf("unknow type(%s), accept(%s)", $type, join(",", array_keys($map) ) ));
        }
        
        $_format = preg_replace_callback('/\w/', function($m) use ( & $map, $type ){
            $_key   = $m[0] ;
            if( isset( $map[$type][ $_key ]) ) {
                return $map[$type][ $_key ] ;
            }
            return $_key ;
        }, $format); 
        
        $cache[$type][$format] = $_format ;
        return $_format ;
    }
    
    public function string_cut( $content, $limit = 29 ) {
        $code   = strip_tags($content);
        if( mb_strlen($code, 'UTF-8') > $limit ) {
            $code = mb_substr( $code , 0, $limit, 'UTF-8') ;
            $code = $code  . '...' ;
        }
        return  $code ; 
    }
    
    public function app_percent($number){
        return $number * 100 / 100 ;
    }




    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'app.admin';
    }
}
