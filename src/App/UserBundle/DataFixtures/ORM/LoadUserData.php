<?php

namespace App\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\UserBundle\Entity\User;

use Symfony\Component\DependencyInjection\ContainerAwareInterface ;

class LoadUserData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    protected $container ;
    public function setContainer(\Symfony\Component\DependencyInjection\ContainerInterface $container = null){
        $this->container = $container ;
    }

    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        
        $user = new User();
        $user->setUsername('super');
        $user->setEmail('super@user.com');
        $user->setPlainPassword('super');
        $user-> setEnabled(true);
        $user->setSuperAdmin(true);
        $userManager->updateUser($user, true);
        
        $this->addReference('super-user', $user);
    }
    
    public function getOrder()
    {
        return 1;
    }
}