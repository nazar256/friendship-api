<?php

/**
 * Class container
 * PHP version 5.6
 * @category Fixture
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     http://friendship-api.dev
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
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     http://friendship-api.dev
 */
class UsersFixture extends AbstractFixture implements ContainerAwareInterface
{
    const DEFAULT_EMAIL         = 'default-email@gmail.com';
    const CLIENT_EMAIL          = 'client@gmail.com';
    const TEST_PASS             = 'SecurePassword123';
    const FRIEND_EMAIL          = 'friend-email@gmail.com';
    const INITIAL_FRIENDS_COUNT = 6;

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
        // User who will request friendship in tests
        $friendshipRequester = $this->_createUser($manager);
        // User who will request something from default user (maybe also friendship)
        $this->_createUser($manager, self::CLIENT_EMAIL);
        // Default user for test operations and logging in
        $defaultUser = $this->_createUser($manager, self::DEFAULT_EMAIL);

        $friendsByLevels = [];
        for ($nestingLevel = 0; $nestingLevel <= 4; $nestingLevel++) {
            $friendsByLevels[$nestingLevel] = [];
            $howMany = self::INITIAL_FRIENDS_COUNT - $nestingLevel;
            for ($friendCount = 0; $friendCount < $howMany; $friendCount++) {
                $friendsByLevels[$nestingLevel][] = $this->_createUser($manager);
            }
        }
        /**
         * Some of defaultUser friends
         * @var User $someFriend
         */
        $someFriend = $friendsByLevels[0][0];
        $someFriend->setEmail(self::FRIEND_EMAIL);

        $manager->flush();

        // Adding nested friends
        $currentLevelUser = $defaultUser;
        foreach ($friendsByLevels as $nestingLevel => $friends) {
            /**
             * Friend of user of current nesting level
             * @var User $friend
             */
            foreach ($friends as $friend) {
                $currentLevelUser->addFriend($friend->getId());
                $friend->addFriend($currentLevelUser->getId());
            }
            $currentLevelUser = end($friends);
        }

        $defaultUser->addRequest($friendshipRequester->getId());

        $manager->flush();
    }

    /**
     * Creates a new user document and persist it
     * @param ObjectManager $manager object manager
     * @param string|null   $email   email to create user with
     * @return User
     */
    private function _createUser(ObjectManager $manager, $email = null)
    {
        $user = new User();
        $email = $email ?: sprintf('some_%s@gmail.com', uniqid());
        $user->setEmail($email);
        $user->setPassword(self::TEST_PASS);
        $encoder = $this->_container->get('security.password_encoder');
        $encryptedPassword = $encoder->encodePassword($user, self::TEST_PASS);
        $user->setPassword($encryptedPassword);
        $manager->persist($user);

        return $user;
    }
}