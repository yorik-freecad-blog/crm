<?php
namespace OroCRM\Bundle\DemoDataBundle\DataFixtures\Demo\Migrations\ORM\v1_0;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\ContactBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

class LoadContactGroupData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /** @var ContainerInterface */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroCRM\Bundle\DemoDataBundle\DataFixtures\Demo\Migrations\ORM\v1_0\LoadUserData'];
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $saleUser = $this->getUser($manager, 'sale');

        $groups = array(
            'Behavioural Segmentation' =>  $saleUser,
            'Demographic Segmentation' =>  $this->getUser($manager, 'marketing'),
            'Geographic Segmentation' => $saleUser,
            'Segmentation by occasions' => $saleUser,
            'Segmentation by benefits'  => $saleUser,
        );

        foreach ($groups as $group => $user) {
            $contactGroup = new Group($group);
            $contactGroup->setOwner($user);
            $entityManager->persist($contactGroup);
        }
        $entityManager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param $userName
     * @return User
     */
    protected function getUser(ObjectManager $manager, $userName)
    {
        return $manager->getRepository('OroUserBundle:User')->findOneBy(['username' => $userName]);
    }
}
