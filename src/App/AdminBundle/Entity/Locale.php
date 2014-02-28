<?php

namespace App\AdminBundle\Entity ;

/**
 * @author ChangLong changl@shinetechchina.com
 */
class Locale
{
	
	private $locale ;
	private $redirect_url ;
	
	public function setLocale($locale){
		$this->locale 	= $locale  ;
	}
	
	public function getLocale(){
		return $this->locale  ;
	}
	
	public function setRedirectUrl($redirect_url) {
		$this->redirect_url 	= $redirect_url  ;
	}
	
	public function getRedirectUrl(){
		return $this->redirect_url  ;
	}
}