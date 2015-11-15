<?php

/**
 * PhpUnit abstract test
 * PHP version 5.6
 * @category Test
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     http://friendship-api.dev
 */

namespace TestingBundle\Tests\Controller;

use AppBundle\Document\User;
use AppBundle\Helper\Dictionary\HttpMethod;
use AppBundle\Helper\Dictionary\SystemService;
use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\ODM\MongoDB\DocumentManager;
use FOS\RestBundle\Util\Codes;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contains helpful methods to simplify REST functional testing
 *
 * @category Test
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     http://friendship-api.dev
 *
 * @IgnoreAnnotation("dataProvider")
 * @IgnoreAnnotation("depends")
 */
abstract class RestControllerTestCase extends WebTestCase
{
    /**
     * Http client (Symfony component)
     * @var Client
     */
    private $_client;

    /**
     * {@inheritdoc}
     * @return null
     */
    public function setUp()
    {
        $this->_client = static::makeClient();
    }

    /**
     * Returns client object
     * @return Client
     */
    protected function getClient()
    {
        return $this->_client;
    }

    /**
     * Returns service from container
     * @param string $serviceId id of service
     * @return object
     */
    protected function getService($serviceId)
    {
        return $this
            ->_client
            ->getContainer()
            ->get($serviceId);
    }

    /**
     * Check status code, parses JSON response, returns parsed result array
     * @param Response $response       response object
     * @param int      $statusCode     HTTP status code
     * @param bool     $checkValidJson if json validation is needed
     * @param string   $contentType    like application/json
     * @return array|null
     */
    protected function assertJsonResponse(
        Response $response,
        $statusCode = Codes::HTTP_OK,
        $checkValidJson = true,
        $contentType = 'application/json'
    ) {
        $content = $response->getContent();
        $this->assertEquals(
            $statusCode,
            $response->getStatusCode(),
            $content
        );
        $this->assertTrue(
            $response->headers->contains('Content-Type', $contentType),
            $response->headers
        );

        if ($checkValidJson) {
            $this->assertJson(
                $content,
                sprintf('got invalid json: %s', $content)
            );

            return json_decode($content, true);
        }

        return null;
    }

    /**
     * Performs POST request
     * @param string $route  controller route
     * @param array  $params request params
     * @return null|Response
     */
    protected function postRequest($route, array $params = [])
    {
        return $this->_makeRequest(
            $route,
            HttpMethod::POST,
            [],
            json_encode($params)
        );
    }

    /**
     * Performs LINK request
     * @param string $route  controller route
     * @param array  $params request params (are send as JSON body)
     * @return null|Response
     */
    protected function linkRequest($route, array $params = [])
    {
        return $this->_makeRequest(
            $route,
            HttpMethod::LINK,
            [],
            json_encode($params)
        );
    }

    /**
     * Performs GET request
     * @param string $route  route
     * @param array  $params GET parameters
     * @return null|Response
     */
    protected function getRequest($route, array $params = [])
    {
        return $this->_makeRequest($route, HttpMethod::GET, $params);
    }

    /**
     * Performs HTTP request (simulates within Symfony)
     * @param string $route       route
     * @param string $method      HTTP method
     * @param array  $queryParams GET parameters
     * @param string $requestBody request body (JSON string)
     * @return null|Response
     */
    private function _makeRequest(
        $route,
        $method,
        array $queryParams = [],
        $requestBody = ''
    ) {
        $client = $this->getClient();
        $client->request(
            $method,
            $route,
            $queryParams,
            [],
            [
                'HTTP_ACCEPT'  => 'application/json',
                'CONTENT_TYPE' => 'application/json'
            ],
            $requestBody
        );

        return $client->getResponse();
    }

    /**
     * Fetches user from DB by email
     * @param string $email User's email
     * @return User
     */
    protected function getUserByEmail($email)
    {
        return $this
            ->getDocumentManager()
            ->getRepository('AppBundle:User')
            ->findOneBy(['email' => $email]);
    }

    /**
     * Returns Doctrine MongoDB document manager service
     * @return DocumentManager
     */
    protected function getDocumentManager()
    {
        return $this->getService(SystemService::ODM);
    }

    /**
     * Reloads fixtures in order to have clean data
     * @param array $fixtures fixture fully qualified class names
     * @return AbstractExecutor|null
     */
    protected function reloadFixtures(array $fixtures)
    {
        return $this->loadFixtures($fixtures, null, 'doctrine_mongodb');
    }
}
