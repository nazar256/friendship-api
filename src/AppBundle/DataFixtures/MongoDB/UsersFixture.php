<?php

/**
 * Class container
 * PHP version 5.6
 * @category Fixture
 * @package AppBundle
 * @author nazar <jura_n@bk.ru>
 * @license MIT @link https://opensource.org/licenses/MIT
 * @link http://friendship-api.dev
 */

namespace AppBundle\DataFixtures\MongoDB;

use AppBundle\Document\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fixture creates User documents for tests
 * @category Controller
 * @package AppBundle
 * @author nazar <jura_n@bk.ru>
 * @license MIT @link https://opensource.org/licenses/MIT
 * @link http://friendship-api.dev
 */
class UsersFixture extends AbstractFixture implements ContainerAwareInterface
{
    const DEFAULT_EMAIL = 'default-email@gmail.com';
    const CLIENT_EMAIL  = 'client@gmail.com';
    const TEST_PASS     = 'SecurePassword123';
    const FRIEND_EMAIL  = 'friend-email@gmail.com';

    /**
     * Service container
     * @var ContainerInterface
     */
    private $_container;

    /**
     * Sets container
     * @param ContainerInterface|null $_container container
     * @return null
     */
    public function setContainer(ContainerInterface $_container = null)
    {
        $this->_container = $_container;
    }

    /**
     * {@inheritdoc}
     * @param ObjectManager $manager Doctrine object manager
     * @return null
     */
    public function load(ObjectManager $manager)
    {
        $encoder = $this->_container->get('security.password_encoder');

        /**
         * Array of users which will be created for tests
         * @var User[] $users
         */
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
        /**
         * User who will request friendship in tests
         * @var User $friendshipRequester
         */
        $friendshipRequester = array_pop($users);
        /**
         * User who will request something from default user (maybe also friendship)
         * @var User $clientUser
         */
        $clientUser = array_pop($users);
        $clientUser->setEmail(self::CLIENT_EMAIL);
        /**
         * Default user for test operations and logging in
         * @var User $defaultUser
         */
        $defaultUser = array_pop($users);
        $defaultUser->setEmail(self::DEFAULT_EMAIL);
        /**
         * One of already added friends who has specific email
         * @var User $friend
         */
        $friend = reset($users);
        $friend->setEmail(self::FRIEND_EMAIL);

        $manager->flush();

        foreach ($users as $friend) {
            $defaultUser->addFriend($friend->getId());
            $friend->addFriend($defaultUser->getId());
        }
        $defaultUser->addRequest($friendshipRequester->getId());

        $manager->flush();
    }
}