<?php

namespace App\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseTestCase;
use Liip\FunctionalTestBundle\Test\WebTestCase;

/**
 * Description of LoginControllerTest
 *
 * @author loong
 */
class LoginControllerTest extends WebTestCase
{
    public function setUp()
    {
        $classes = array(
            'App\UserBundle\DataFixtures\ORM\LoadUserData',
        );
        $this->loadFixtures($classes);
    }
    
    public function testIndex()
    {
        $client = static::createClient();
        $client->followRedirects();
        
        return ;
        
        $crawler = $client->request('GET', '/admin/login');
        
        $form = $crawler->selectButton('_submit')->form();
        $crawler = $client->submit($form, array(
                'login[username]' => 'super',
                'login[password]' => 'super1',
                'login[captcha][code]'  => 'a11' ,
            ));
        
        // $client->insulate();
        
        $crawler = $client->submit($form);
        
        $this->assertTrue(
            $crawler->filter('#login_password')->count() < 1 ,
            'error'
        );
    }
    
}