<?php

namespace App\AdminBundle\Event;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Form ;

class FormEvent extends Event
{
    /**
     * @var Response 
     */
    protected $response;
    /**
     * @var Request 
     */
    protected $request;
    /**
     * @var Form
     */
    protected $form;

    public function __construct(Form $form, Request $request)
    {
        $this->form = $form;
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request ;
    }
    
    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->form ;
    }
    
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}