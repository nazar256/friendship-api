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
    const REFERENCE_DEFAULT_USER = 'default-user';
    const TEST_EMAIL             = 'someemail@gmail.com';
    const TEST_PASS              = 'SecurePassword123';

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
        $defaultUser = new User();
        $defaultUser->setEmail(self::TEST_EMAIL);
        $defaultUser->setPassword(self::TEST_PASS);
        $encoder = $this->container->get('security.password_encoder');
        $encryptedPassword = $encoder->encodePassword($defaultUser, self::TEST_PASS);
        $defaultUser->setPassword($encryptedPassword);

        $manager->persist($defaultUser);

        /** @var User[] $friends */
        $friends = [];
        for ($userCount = 0; $userCount < 4; $userCount++) {
            $user = new User();
            $email = self::TEST_EMAIL . uniqid();
            $user->setEmail($email);
            $user->setPassword(self::TEST_PASS);
            $friends[] = $user;
            $manager->persist($user);
        }
        /** @var User $friendshipRequester */
        $friendshipRequester = array_pop($friends);

        $manager->flush();

        foreach ($friends as $friend) {
            $defaultUser->addFriend($friend->getId());
            $friend->addFriend($defaultUser->getId());
        }
        $defaultUser->addRequest($friendshipRequester->getId());
        $friendshipRequester->addRequest($defaultUser->getId());

        $manager->flush();
        $this->setReference(self::REFERENCE_DEFAULT_USER, $defaultUser);
    }
}