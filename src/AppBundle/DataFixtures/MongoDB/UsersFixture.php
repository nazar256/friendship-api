<?php
namespace AppBundle\DataFixtures\MongoDB;

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
    const TEST_EMAIL   = 'some-email@gmail.com';
    const CLIENT_EMAIL = 'client@gmail.com';
    const TEST_PASS    = 'SecurePassword123';

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
        $encoder = $this->container->get('security.password_encoder');

        /** @var User[] $users */
        $users = [];
        for ($userCount = 0; $userCount < 6; $userCount++) {
            $user = new User();
            $email = sprintf('some_%s@gmail.com', uniqid());
            $user->setEmail($email);
            $user->setPassword(self::TEST_PASS);
            $encryptedPassword = $encoder->encodePassword($user, self::TEST_PASS);
            $user->setPassword($encryptedPassword);
            $users[] = $user;
            $manager->persist($user);
        }
        /** @var User $friendshipRequester */
        $friendshipRequester = array_pop($users);
        /** @var User $clientUser */
        $clientUser = array_pop($users);
        $clientUser->setEmail(self::CLIENT_EMAIL);
        /** @var User $defaultUser */
        $defaultUser = array_pop($users);
        $defaultUser->setEmail(self::TEST_EMAIL);

        $manager->flush();

        foreach ($users as $friend) {
            $defaultUser->addFriend($friend->getId());
            $friend->addFriend($defaultUser->getId());
        }
        $defaultUser->addRequest($friendshipRequester->getId());

        $manager->flush();
    }
}