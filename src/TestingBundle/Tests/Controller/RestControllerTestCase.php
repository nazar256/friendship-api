<?php

namespace TestingBundle\Tests\Controller;

use AppBundle\Document\DocumentInterface;
use AppBundle\Helper\Dictionary\HttpMethod;
use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use FOS\RestBundle\Util\Codes;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contains helpful methods to simplify REST functional testing
 * @IgnoreAnnotation("dataProvider")
 * @IgnoreAnnotation("depends")
 */
abstract class RestControllerTestCase extends WebTestCase
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ReferenceRepository
     */
    private $fixtures;

    public function setUp()
    {
        $this->client = static::makeClient();
        /** @var MongoDBExecutor $fixtureExecutor */
        $fixtureExecutor = $this->loadFixtures($this->getFixtures(), null, 'doctrine_mongodb');
        $this->fixtures = $fixtureExecutor->getReferenceRepository();
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        return $this->client;
    }

    /**
     * returns service from container
     * @param string $serviceId
     * @return object
     */
    protected function getService($serviceId)
    {
        return $this->client->getContainer()
                            ->get($serviceId);
    }

    /**
     * @param Response $response
     * @param int      $statusCode
     * @param bool     $checkValidJson
     * @param string   $contentType
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
     * performs POST request
     * @param string $route
     * @param array  $params
     * @return null|Response
     */
    protected function postRequest($route, array $params)
    {
        return $this->makeRequest($route, HttpMethod::POST, [], json_encode($params));
    }

    /**
     * @param string $route
     * @param array  $params
     * @return null|Response
     */
    protected function getRequest($route, array $params = [])
    {
        return $this->makeRequest($route, HttpMethod::GET, $params);
    }

    /**
     * @param string $route
     * @param string $method
     * @param array  $queryParams
     * @param string $requestBody
     * @return null|Response
     */
    private function makeRequest($route, $method, array $queryParams = [], $requestBody = '')
    {
        $client = $this->getClient();
        $client->request(
            $method,
            $route,
            $queryParams,
            [],
            ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'],
            $requestBody
        );

        return $client->getResponse();
    }

    /**
     * @param string $fixtureName
     * @return DocumentInterface
     */
    protected function getFixture($fixtureName)
    {
        return $this->fixtures->getReference($fixtureName);
    }

    /**
     * @return string[]
     */
    protected abstract function getFixtures();
}
