<?php

/**
 * Description of App
 *
 * @author loong
 */
final class Dev {
    //put your code here
    
    static public $on = false ;
    
    static public function debug(){
        self::print_callback() ;
        $writer = new \CG\Generator\Writer() ;
        $writer->indent() ;
        $args   = func_get_args() ;
        foreach($args as  $i => & $arg ) {
            $writer->write("\n$i -> ") ;
            $writer->indent() ;
            $visited    = array() ;
            self::export($writer, $visited, $arg, 4 ) ;
            $writer->outdent() ;
        }
        $writer->outdent() ;
        echo $writer->getContent(), "\n" ;
    }
    
    static public function dump( $object, $deep = 4 , $do_print = true ){
        if( !$do_print ) {
            ob_start() ;
        }
        self::print_callback() ;
        $writer = new \CG\Generator\Writer() ;
        $writer->indent() ;
        $writer->write("\n") ;
        $visited    = array() ;
        self::export($writer, $visited, $object, $deep ) ;
        $writer->outdent() ;
        echo $writer->getContent(), "\n" ;
        if( !$do_print ) {
            $out = ob_get_contents();
            ob_end_clean();
            return $out ;
        }
    }
    
    static private function export(\CG\Generator\Writer $writer, array & $visited , $value , $deep = 1 , $counter = 0x3ffff ) {
        $deep-- ;
        $counter-- ;
        if( is_object($value) ) {
            if( $value instanceof \DateTime ) {
                $writer->write(sprintf('\DateTime(%s, %s)', $value->format("Y/m/d H:i:s"), $value->getTimezone()->getName() ) ) ;
            } else if( $value instanceof \DateTimeZone ) {
                $writer->write(sprintf('\DateTimeZone(%s)', $value->getName() ) ) ;
            } else if( $value instanceof \Doctrine\ORM\PersistentCollection ) {
                $writer->write(sprintf('\Doctrine\ORM\PersistentCollection(%s, %s)', spl_object_hash($value), $value->getTypeClass()->getName() ) ) ;
            } else if( $value instanceof \Closure ) {
                $_rc = new \ReflectionFunction($value);
                $writer->write( sprintf('\Closure(%s, file:%s line:[%d,%s])',
                            spl_object_hash($value), 
                            self::fixPath($_rc->getFileName()),
                            $_rc->getStartLine() ,
                            $_rc->getEndLine() 
                        )) ;
            } else {
                $oid = spl_object_hash($value) ;
                $object_class   = get_class($value) ;
                if( isset($visited[$oid]) ) {
                    $writer->write( sprintf("#%s(%s)", $object_class , $oid) );
                } else {
                    $visited[$oid]  = true ;
                    if( $deep > 0 ) {
                        
                        $skip_properties    = array() ;
                        if( $value instanceof  \Doctrine\ORM\Proxy\Proxy ) {
                            $skip_properties  = array_merge(array(
                                '__initializer__' ,
                                '__cloner__' ,
                                '__isInitialized__' ,
                            ), $skip_properties ) ;
                        }
                        
                        $writer->write(sprintf( "%s(%s) { \n", $object_class , $oid )) ;
                        $writer->indent() ;
                        $r = new \ReflectionClass( $object_class ) ;
                        $output = array();
                        foreach( $r->getProperties() as $p ) {
                            if( $p->isStatic() ) {
                                continue ;
                            }
                            if( $counter < 0 ) {
                                $writer->writeln("......") ;
                                break ;
                            }
                            $_p     = $p->getName() ;
                            if( in_array($_p, $skip_properties) ) {
                                continue;
                            }
                            $p->setAccessible( true ) ;
                            $_value = $p->getValue( $value ) ;
                            $writer->write( $_p . ' : ') ;
                            self::export($writer, $visited, $_value, $deep, $counter ) ;
                            $writer->write("\n") ;
                       }
                       $writer->outdent() ;
                       $writer->write("}");
                    } else {
                        $r = new \ReflectionClass( $object_class ) ;
                        $output = array() ;
                        foreach( $r->getProperties() as $p ) {
                            if( count($output) > 1 ) {
                                break ;
                            }
                            if( $p->isStatic() ) {
                                continue ;
                            }
                            $p->setAccessible( true ) ;
                            $_value     = $p->getValue( $value ) ;
                            if( is_object($_value) || is_array($_value) ) {
                                continue ;
                            }
                            $_p     = $p->getName() ;
                            
                            if( 0 === strpos( $_p, '_') ) {
                                continue; ;
                            }
                            
                            if(is_string($_value) ) {
                                if( strlen($_value) > 0xf ) {
                                    $output[ $_p ] = substr($_value, 0xc ) . '..' ;
                                } else {
                                    $output[ $_p ] = $_value ;
                                }
                            } else {
                                $output[ $_p ] = $_value ;
                            }
                        }
                        
                        $writer->write( sprintf("%s(%s)", $object_class, $oid ) );
                        if( !empty($output) ) {
                            $writer
                                    ->indent()
                                    ->write( " = " . json_encode($output) )
                                    ->outdent()
                                    ;
                        }
                    }
                }
            }
        } else if( is_array($value) ) {
            if( $deep > 0 ) {
                $writer->writeln("array(");
                $writer->indent() ;
                foreach($value as $_key => & $_value ) {
                    if( $counter < 0 ) {
                        $writer->writeln("...") ;
                        break ;
                    }
                    $writer->write( $_key . ' => ') ;
                    self::export($writer, $visited, $_value, $deep, $counter ) ;
                    $writer->write("\n") ;
                }
                $writer->outdent() ;
                $writer->writeln(")");
            } else {
                $writer->write( sprintf("array( length = %s ) ", count($value) ) );
            }
        } else if( null === $value ) { 
            $writer->write("null");
        } else if( is_string($value) ) { 
            if(strlen($value) < 0x7f ) {
                $writer->write( var_export($value, 1) );
            } else {
                $writer->write( var_export(substr($value, 0, 0x7f) . '...' , 1) );
            }
            $writer->write(sprintf("%d", strlen($value) )) ;
        } else if(is_bool($value) ) { 
            $writer->write(  $value ? 'true' : 'false' );
        } else if( is_numeric($value) ) { 
            $writer->write( var_export($value, 1) );
        } else {
            $writer->write( sprintf("%s ( %s ) ", gettype($value), var_export($value, 1)) ) ;
        }
    }
    
    static private function print_callback() {
        $o = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 );
        $file   = self::fixPath( $o[1]['file'] ) ;
        $line   = $o[1]['line'] ;
        echo '#', $file, ":", $line;
        
        if( isset($o[2]) ) {
            $fn     = null ;
            if( isset($o[2]['class']) ) {
                $fn     = $o[2]['class'] .  $o[2]['type'] . $o[2]['function'] ;
            } else if( $o[2]['function'] ){
                $fn     = $o[2]['function'] ;
            }
            if( $fn ) {
                echo " @", $fn ;
            }
        }
    }
    
    static private function fixPath( $path ) {
        static $rood_dir    = null ;
        if( null === $rood_dir  ) {
            $rood_dir   = 1 + strlen( dirname( __DIR__ ) ) ;
        } 
        return substr($path, $rood_dir );
    }
    
    static public function isSimpleArray(array & $array) {
        $keys   = array_keys( $array );
        foreach ($keys as $i => $I ) {
            if( $i !== $I ) {
                return false ;
            }
        }
        return true ;
    }
    
    static public function write_file($path, $content ) {
        $need_flush = true ;
        if( file_exists($path) ) {
            $_content = file_get_contents($path) ;
            if( $_content === $content ) {
                $need_flush = false ;
            }
        }
        if( $need_flush ) {
            if (false === @file_put_contents( $path , $content ) ) {
                throw new \RuntimeException('Unable to write file ' . $path );
            }
        }
    }
    
    static public function merge(array & $a, array & $b) { 
        foreach ($b as $key => & $value ) {
            if (isset($a[$key])) {
                if ( is_array($a[$key]) && is_array($value)) {
                    if( !self::isSimpleArray($a[$key]) || !self::isSimpleArray($b[$key]) ) {
                        self::merge($a[$key], $value);
                    } else {
                        foreach($value as $_key => $_value ) {
                            $a[$key][] = $_value ;
                        }
                    }
                    continue ;
                }
            }
            $a[$key] = $value;
        }
    }
    
    static public function type($var) {
        if(is_object($var) ) {
            return get_class($var);
        }
        return gettype($var);
    }

}
