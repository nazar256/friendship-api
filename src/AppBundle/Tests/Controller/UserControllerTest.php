<?php

namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\MongoDB\UsersFixture;
use AppBundle\Document\User;
use AppBundle\Helper\Dictionary\SystemService;
use Doctrine\ODM\MongoDB\DocumentManager;
use FOS\RestBundle\Util\Codes;
use TestingBundle\Helper\UniqueParamGenerator;
use TestingBundle\Tests\Controller\RestControllerTestCase;

/**
 * Class UserControllerTest
 * @package AppBundle\Tests\Controller
 */
class UserControllerTest extends RestControllerTestCase
{
    const ROUTE_REGISTER           = '/api/users/register';
    const ROUTE_USER               = '/api/users/%s';
    const ROUTE_REQUEST_FRIENDSHIP = '/api/users/%s/friendship/request';

    /**
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
     * @dataProvider registrationParamsProvider
     * @param string $email
     * @param string $password
     * @param bool   $mustBeValid
     */
    public function testAnyoneCanRegisterWithValidParams($email, $password, $mustBeValid)
    {
        $registrationParams = [
            'email'    => $email,
            'password' => $password
        ];
        $response = $this->postRequest(self::ROUTE_REGISTER, $registrationParams);
        $expectedStatusCode = $mustBeValid ? Codes::HTTP_CREATED : Codes::HTTP_UNPROCESSABLE_ENTITY;
        $responseData = $this->assertJsonResponse($response, $expectedStatusCode);
        if ($mustBeValid) {
            $this->assertArrayHasKey('id', $responseData);
            $userId = $responseData['id'];
            $user = $this->getService(SystemService::ODM)
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
            $this->assertArrayHasKey('password', $responseData['errors']['children']);
        }
    }

    public function testUserRouteReturnsFriendsList()
    {
        $user = $this->getUserByEmail(UsersFixture::TEST_EMAIL);
        $userRoute = sprintf(self::ROUTE_USER, $user->getId());
        $this->logUserIn();
        $response = $this->getRequest($userRoute);
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

    public function testUserCanSendFriendRequest()
    {
        $clientUser = $this->getUserByEmail(UsersFixture::CLIENT_EMAIL);
        /** @var DocumentManager $dm */
        $dm = $this->getService(SystemService::ODM);
        /** @var User $defaultUser */
        $defaultUser = $this->getUserByEmail(UsersFixture::TEST_EMAIL);
        $this->logUserIn($clientUser->getEmail());
        $route = sprintf(self::ROUTE_REQUEST_FRIENDSHIP, $defaultUser->getId());
        $response = $this->postRequest($route);
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $response->getStatusCode());
        $dm->refresh($defaultUser);
        $this->assertContains($clientUser->getId(), $defaultUser->getRequests());
    }

    /**
     * @param string $email
     */
    private function logUserIn($email = UsersFixture::TEST_EMAIL)
    {
        $loginParams = [
            'email'    => $email,
            'password' => UsersFixture::TEST_PASS
        ];
        $response = $this->postRequest(SecurityControllerTest::ROUTE_LOGIN, $loginParams);
        $this->assertEquals(Codes::HTTP_OK, $response->getStatusCode());
    }
}
