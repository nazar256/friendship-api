<?php
namespace AppBundle\Fixtures;

use AppBundle\Document\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UsersFixture
 * @package AppBundle\Fixtures
 */
class UsersFixture extends AbstractFixture implements ContainerAwareInterface
{
    const REFERENCE_USER = 'user';
    const TEST_EMAIL     = 'someemail@gmail.com';
    const TEST_PASS      = 'SecurePassword123';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setEmail(self::TEST_EMAIL);
        $user->setPassword(self::TEST_PASS);
        $encoder = $this->container->get('security.password_encoder');
        $encryptedPassword = $encoder->encodePassword($user, self::TEST_PASS);
        $user->setPassword($encryptedPassword);

        $this->setReference(self::REFERENCE_USER, $user);

        $manager->persist($user);
        $manager->flush();
    }
}