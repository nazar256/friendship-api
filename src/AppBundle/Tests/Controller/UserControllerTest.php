<?php

namespace AppBundle\Tests\Controller;

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

    /**
     * {@inheritdoc}
     */
    protected function getFixtures()
    {
        return [];
    }
}
