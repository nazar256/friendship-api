<?php
namespace AppBundle\Tests\Controller;

use AppBundle\Document\User;
use AppBundle\Fixtures\UsersFixture;
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
     * {@inheritdoc}
     */
    protected function getFixtures()
    {
        return [
            'AppBundle\Fixtures\UsersFixture'
        ];
    }

    /**
     * @return array
     */
    public function loginParamsProvider()
    {
        return [
            ['invald@email.ololo', UsersFixture::TEST_PASS, Codes::HTTP_NOT_FOUND],
            [UsersFixture::TEST_EMAIL, 'WrongPass', Codes::HTTP_UNAUTHORIZED],
            [UsersFixture::TEST_EMAIL, UsersFixture::TEST_PASS, Codes::HTTP_OK],
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
            $user = $this->getFixture(UsersFixture::REFERENCE_USER);
            $this->assertEquals($user->getId(), $responseData['id']);
            $this->assertArrayHasKey('email', $responseData);
            $this->assertEquals($user->getEmail(), $responseData['email']);
        }
    }
}
