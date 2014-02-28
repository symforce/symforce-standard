<?php

namespace App\AdminBundle\Compiler\Generator;

/**
 * A writer implementation.
 */
final class PhpWriter
{
    private $content = '';
    private $indentationLevel = 0;
    private $last_line = false ;

    final public function indent()
    {
        $this->indentationLevel += 4;
        return $this;
    }

    final public function outdent()
    {
        $this->indentationLevel -= 4 ;

        if ($this->indentationLevel < 0) {
            throw new \RuntimeException('The identation level cannot be less than zero.');
        }

        return $this;
    }

    /**
     * @param string $content
     */
    final public function writeln($content)
    {
        $this->write($content."\n");

        return $this;
    }

    /**
     * @param string $content
     */
    final public function write($content)
    {
        $len = strlen($content);
        $offset = 0 ;
        while( $offset < $len ) {
            $pos    = strpos($content, "\n", $offset) ;
            if( false === $pos  ) {
                if( $this->last_line && $this->indentationLevel > 0 ) {
                    $this->content .= str_repeat(' ', $this->indentationLevel );
                }
                if( $offset > 0 ) {
                    $this->content .= substr($content, $offset) ;
                } else {
                    $this->content .= $content ;
                }
                $this->last_line    = false ;
                break ;
            }
            if ($this->indentationLevel > 0 ) {
                $this->content .= str_repeat(' ', $this->indentationLevel );
            }
            if( $pos > $offset ) {
                $this->content .= substr($content, $offset, $pos - $offset) ;
            } 
            $this->content .= "\n";
            $this->last_line    = true ;
            $offset = $pos + 1 ;
        }
        return $this ;
    }

    final public function rtrim()
    {
        $addNl = "\n" === substr($this->content, -1);
        $this->content = rtrim($this->content);

        if ($addNl) {
            $this->content .= "\n";
        }

        return $this;
    }

    final public function reset()
    {
        $this->content = '';
        $this->indentationLevel = 0;
        $this->last_line = false ;
        return $this;
    }

    final public function getContent()
    {
        return $this->content;
    }
}
