<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Document\User;
use AppBundle\Fixtures\UsersFixture;
use AppBundle\Helper\Dictionary\SystemService;
use FOS\RestBundle\Util\Codes;
use TestingBundle\Helper\UniqueParamGenerator;
use TestingBundle\Tests\Controller\RestControllerTestCase;

/**
 * Class UserControllerTest
 * @package AppBundle\Tests\Controller
 */
class UserControllerTest extends RestControllerTestCase
{
    const ROUTE_REGISTER = '/api/users/register';
    const ROUTE_USER     = '/api/users/%s';

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
            ['invalid@domain.ololo', 'SecurePass123', false],
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
        /** @var User $user */
        $user = $this->getFixture(UsersFixture::REFERENCE_DEFAULT_USER);
        $userRoute = sprintf(self::ROUTE_USER, $user->getId());
        $loginParams = [
            'email'    => UsersFixture::TEST_EMAIL,
            'password' => UsersFixture::TEST_PASS
        ];
        $this->postRequest(SecurityControllerTest::ROUTE_LOGIN, $loginParams);
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

    /**
     * {@inheritdoc}
     */
    protected function getFixtures()
    {
        return [
            'AppBundle\Fixtures\UsersFixture'
        ];
    }
}
