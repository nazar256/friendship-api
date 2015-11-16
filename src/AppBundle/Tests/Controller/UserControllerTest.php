<?php

/**
 * PhpUnit test
 * PHP version 5.6
 * @category Test
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     http://friendship-api.dev
 */

namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\MongoDB\UsersFixture;
use AppBundle\Document\User;
use FOS\RestBundle\Util\Codes;
use TestingBundle\Helper\UniqueParamGenerator;
use TestingBundle\Tests\Controller\RestControllerTestCase;

/**
 * Class UserControllerTest
 *
 * @category Test
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     http://friendship-api.dev
 */
class UserControllerTest extends RestControllerTestCase
{
    const ROUTE_REGISTER = '/api/users/register';
    const ROUTE_USER     = '/api/users/%s';
    const ROUTE_ME       = '/api/users/me';
    const ROUTE_FRIENDS  = '/api/users/me/friends';

    /**
     * Provides parameters for testing registration
     * @return array
     */
    public function registrationParamsProvider()
    {
        return [
            [UniqueParamGenerator::generateTestEmail(), 'SecurePass123', true],
            [UniqueParamGenerator::generateTestEmail(), 'short', false],
            ['invalid@email', 'SecurePass123', false],
            ['invalid@email', 'SecurePass123', false],
            ['invalid@domain.bad', 'SecurePass123', false],
        ];
    }

    /**
     * Tests registration route and params validation
     *
     * @param string $email       email
     * @param string $password    password (not encrypted)
     * @param bool   $mustBeValid if params are valid
     *
     * @return null
     *
     * @dataProvider registrationParamsProvider
     */
    public function testAnyoneCanRegisterWithValidParams(
        $email,
        $password,
        $mustBeValid
    ) {
        $registrationParams = [
            'email'    => $email,
            'password' => $password
        ];
        $response = $this->postRequest(self::ROUTE_REGISTER, $registrationParams);
        $expectedStatusCode = $mustBeValid ? Codes::HTTP_CREATED
            : Codes::HTTP_UNPROCESSABLE_ENTITY;
        $responseData = $this->assertJsonResponse($response, $expectedStatusCode);
        if ($mustBeValid) {
            $this->assertArrayHasKey('id', $responseData);
            $userId = $responseData['id'];
            $user = $this
                ->getDocumentManager()
                ->getRepository('AppBundle:User')
                ->find($userId);
            $this->assertInstanceOf('AppBundle\Document\User', $user);
            $this->assertArrayNotHasKey('password', $responseData);
        } else {
            $this->assertArrayHasKey('code', $responseData);
            $this->assertEquals($responseData['code'], $expectedStatusCode);
            $this->assertArrayHasKey('message', $responseData);
            $this->assertEquals($responseData['message'], 'Validation Failed');
            $this->assertArrayHasKey('errors', $responseData);
            $this->assertArrayHasKey('children', $responseData['errors']);
            $this->assertArrayHasKey('email', $responseData['errors']['children']);
            $this->assertArrayHasKey(
                'password',
                $responseData['errors']['children']
            );
        }
    }

    /**
     * Tests route which returns own info
     * @return null
     */
    public function testUserRouteReturnsFriendsList()
    {
        $user = $this->getUserByEmail(UsersFixture::DEFAULT_EMAIL);
        $this->_logUserIn();
        $response = $this->getRequest(self::ROUTE_ME);
        $responseData = $this->assertJsonResponse($response, Codes::HTTP_OK);

        $this->assertEquals($user->getId(), $responseData['id']);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertEquals($user->getEmail(), $responseData['email']);

        $this->assertArrayNotHasKey('password', $responseData);

        $this->assertArrayHasKey('friends', $responseData);
        $responseFriends = $responseData['friends'];
        $this->assertEquals($user->getFriends(), $responseFriends);

        $this->assertArrayHasKey('requests', $responseData);
        $responseFriendRequests = $responseData['requests'];
        $this->assertEquals($user->getRequests(), $responseFriendRequests);
    }

    /**
     * Returns params for friendship requests
     * @return array
     */
    public function friendRequestDataProvider()
    {
        return [
            [UsersFixture::CLIENT_EMAIL, Codes::HTTP_NO_CONTENT],
            [UsersFixture::FRIEND_EMAIL, Codes::HTTP_CONFLICT],
            ['non-existent-user@email.com', Codes::HTTP_NOT_FOUND]
        ];
    }

    /**
     * Tests friend requesting feature
     * @dataProvider friendRequestDataProvider
     * @param string $friendEmail        email of user who will be requested a
     *                                   friendship
     * @param int    $expectedStatusCode HTTP status code
     * @return null
     */
    public function testUserCanSendFriendRequest($friendEmail, $expectedStatusCode)
    {
        $desiredFriend = $this->getUserByEmail($friendEmail);
        $this->_logUserIn();
        $friendId = $desiredFriend ? $desiredFriend->getId() : 'non-existent-id';
        $route = sprintf(self::ROUTE_USER, $friendId);
        $response = $this->linkRequest($route);
        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
        if ($expectedStatusCode === Codes::HTTP_NO_CONTENT) {
            /**
             * User, who makes friend requests
             * @var User $defaultUser
             */
            $defaultUser = $this->getUserByEmail(UsersFixture::DEFAULT_EMAIL);
            $desiredFriend = $this->getUserByEmail($friendEmail);
            $this->assertContains($friendId, $defaultUser->getFriends());
            $defaultUserId = $defaultUser->getId();
            $this->assertContains($defaultUserId, $desiredFriend->getRequests());
        }
    }

    /**
     * Tests that LINK request also accepts friendship request
     * @return null
     */
    public function testUserCanAcceptFriendRequest()
    {
        $defaultUser = $this->getUserByEmail(UsersFixture::DEFAULT_EMAIL);
        $friendRequests = $defaultUser->getRequests();
        /**
         * User who has requested friendship (already defined in fixtures)
         * @var User $requester
         */
        $requesterId = reset($friendRequests);
        $this->_logUserIn();
        $route = sprintf(self::ROUTE_USER, $requesterId);
        $response = $this->linkRequest($route);

        $this->assertEquals(Codes::HTTP_NO_CONTENT, $response->getStatusCode());
        $defaultUser = $this->getUserByEmail(UsersFixture::DEFAULT_EMAIL);
        $this->assertContains($requesterId, $defaultUser->getFriends());
        $this->assertNotContains($requesterId, $defaultUser->getRequests());
        $this->reloadFixtures([UsersFixture::class]);
    }

    /**
     * Provides nesting level and expected result friends count
     * @return array
     */
    public function nestingLevelProvider()
    {
        $expectedValues = [];
        $totalUserCount = 0;
        for ($nestingLevel = 0; $nestingLevel <= 4; $nestingLevel++) {
            $totalUserCount +=
                UsersFixture::INITIAL_FRIENDS_COUNT - $nestingLevel;
            $expectedValues[] = [$nestingLevel, $totalUserCount];
        }

        return $expectedValues;
    }

    /**
     * Tests total friends amount with specific nesting level
     * @dataProvider nestingLevelProvider
     * @param int $nestingLevel  how many times to retrieve users of users
     * @param int $friendsAmount expected total amount of found friends
     * @return null
     */
    public function testUserCanRequestFriendsOfFriends($nestingLevel, $friendsAmount)
    {
        $this->_logUserIn();
        $response = $this->getRequest(
            self::ROUTE_FRIENDS,
            ['nesting' => $nestingLevel]
        );

        $responseData = $this->assertJsonResponse($response, Codes::HTTP_OK);
        $this->assertCount($friendsAmount, $responseData);
    }

    /**
     * Logs user with specified email in
     * @param string $email email of user to log in
     * @return null
     */
    private function _logUserIn($email = UsersFixture::DEFAULT_EMAIL)
    {
        $loginParams = [
            'email'    => $email,
            'password' => UsersFixture::TEST_PASS
        ];
        $response = $this->postRequest(
            SecurityControllerTest::ROUTE_LOGIN,
            $loginParams
        );
        $this->assertEquals(Codes::HTTP_OK, $response->getStatusCode());
    }
}
