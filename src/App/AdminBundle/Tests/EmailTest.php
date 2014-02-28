<?php

namespace App\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


/**
 * Description of EmailTest
 *
 * @author loong
 */
class EmailTest extends WebTestCase {
    
    /**
    * @test
    */
    public function testEmail()
    {
        $client = static::createClient();
        $client->followRedirects();
        
        $container = static::$kernel->getContainer();
        $mailer = $container->get('swiftmailer.mailer');
        
        $from   = $container->getParameter('mailer_sender_address') ;
        $to   = $container->getParameter('mailer_delivery_address') ;

        $message = \Swift_Message::newInstance()
            ->setSubject('Hello Email')
            ->setFrom($from)
            ->setTo($to)
            ->setBody('body <strong>content</strong>', 'text/html')
        ;
        $mailer->send($message);
    }
}
