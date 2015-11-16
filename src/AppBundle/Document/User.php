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

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fixture creates User documents for tests
 *
 * @category Controller
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     http://friendship-api.dev
 *
 * @MongoDB\Document(repositoryClass="AppBundle\DocumentRepository\UserRepository")
 */
class User implements UserInterface, \Serializable
{
    const ALIAS = 'user';

    /**
     * ObjectId
     *
     * @var string
     *
     * @MongoDB\Id
     */
    private $_id;

    /**
     * User's email
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Email(checkMX=true, checkHost=true)
     * @MongoDB\String
     * @MongoDB\Index()
     */
    private $_email;

    /**
     * User's password (encrypted)
     *
     * @var string
     *
     * @MongoDB\String
     * @Assert\NotBlank()
     * @Assert\Length(min="8", minMessage="Your password must contain at least 8
     *     characters")
     */
    private $_password;

    /**
     * Array of subscribed friends (friend requests of current user)
     *
     * @var string[]
     *
     * @MongoDB\Collection()
     */
    private $_friends = [];

    /**
     * Array of friendship requests (which users requested current user to friends)
     *
     * @var string[]
     *
     * @MongoDB\Collection()
     */
    private $_requests = [];

    /**
     * Returns ID
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Returns password
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Sets encrypted password
     *
     * @param string $_password encrypted password
     *
     * @return $this
     */
    public function setPassword($_password)
    {
        $this->_password = $_password;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @return string[]
     */
    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    /**
     * {@inheritdoc}
     * @return null
     */
    public function getSalt()
    {
        return null; //salt is not needed since we use bcrypt
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->getEmail();
    }

    /**
     * Returns email
     * @return string
     */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * Sets an email
     *
     * @param string $_email new email
     *
     * @return $this
     */
    public function setEmail($_email)
    {
        $this->_email = $_email;

        return $this;
    }

    /**
     * Returns friend IDs
     * @return string[]
     */
    public function getFriends()
    {
        return $this->_friends;
    }

    /**
     * Adds a new friend
     * @param string $friendId new friend id
     * @return $this
     */
    public function addFriend($friendId)
    {
        if (!$this->hasFriend($friendId)) {
            $this->_friends[] = $friendId;
        }

        return $this;
    }

    /**
     * Checks if current user already has certain friend
     * @param string $friendId friend id to check
     * @return bool
     */
    public function hasFriend($friendId)
    {
        return in_array($friendId, $this->_friends);
    }

    /**
     * Returns array of IDs of friendship requests
     * @return string[]
     */
    public function getRequests()
    {
        return $this->_requests;
    }

    /**
     * Removes friend request
     * @param string $requesterId ID of user
     * @return $this
     */
    public function removeRequest($requesterId)
    {
        $this->_requests = array_diff($this->_requests, [$requesterId]);

        return $this;
    }

    /**
     * Checks if current user was requested a friendship by certain user
     * @param string $requesterId user id who requested friendship
     * @return bool
     */
    public function hasRequest($requesterId)
    {
        return in_array($requesterId, $this->_friends);
    }

    /**
     * Adds friendship request
     * @param string $userId ID of user who requests friendship
     * @return $this
     */
    public function addRequest($userId)
    {
        if (!in_array($userId, $this->_requests)) {
            $this->_requests[] = $userId;
        }

        return $this;
    }


    /**
     * {@inheritdoc}
     * @return null
     */
    public function eraseCredentials()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function serialize()
    {
        return serialize(
            [
                $this->_id,
                $this->_email,
                $this->_password,
            ]
        );
    }

    /**
     * {@inheritdoc}
     * @param string $serialized serialized string
     * @return User
     */
    public function unSerialize($serialized)
    {
        list (
            $this->_id,
            $this->_email,
            $this->_password,
            ) = unserialize($serialized);
    }
}
