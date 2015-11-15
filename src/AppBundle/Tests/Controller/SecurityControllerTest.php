<?php
namespace AppBundle\Tests\Controller;

use AppBundle\DataFixtures\MongoDB\UsersFixture;
use AppBundle\Document\User;
use FOS\RestBundle\Util\Codes;
use TestingBundle\Tests\Controller\RestControllerTestCase;

/**
 * Class SecurityControllerTest
 * @package AppBundle\Tests\Controller
 */
class SecurityControllerTest extends RestControllerTestCase
{
    const ROUTE_LOGIN = '/api/users/login';

    /**
     * @return array
     */
    public function loginParamsProvider()
    {
        return [
            ['invalid@email.bad', UsersFixture::TEST_PASS, Codes::HTTP_NOT_FOUND],
            [UsersFixture::DEFAULT_EMAIL, 'WrongPass', Codes::HTTP_UNAUTHORIZED],
            [UsersFixture::DEFAULT_EMAIL, UsersFixture::TEST_PASS, Codes::HTTP_OK],
        ];
    }

    /**
     * @param string $email
     * @param string $password
     * @param int    $expectedStatusCode
     * @dataProvider loginParamsProvider
     */
    public function testUserLogsInWithCorrectPassword($email, $password, $expectedStatusCode)
    {
        $loginParams = [
            'email'    => $email,
            'password' => $password
        ];
        $response = $this->postRequest(self::ROUTE_LOGIN, $loginParams);
        $responseData = $this->assertJsonResponse($response, $expectedStatusCode);
        if ($expectedStatusCode === Codes::HTTP_OK) {
            $this->assertArrayHasKey('id', $responseData);
            /** @var User $user */
            $user = $this->getUserByEmail(UsersFixture::DEFAULT_EMAIL);
            $this->assertEquals($user->getId(), $responseData['id']);
            $this->assertArrayHasKey('email', $responseData);
            $this->assertEquals($user->getEmail(), $responseData['email']);
        }
    }
}
