<?php

namespace TestingBundle\Tests\Controller;

use AppBundle\Helper\Dictionary\HttpMethod;
use FOS\RestBundle\Util\Codes;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contains helpful methods to simplify REST functional testing
 */
abstract class RestControllerTestCase extends WebTestCase
{

    /**
     * @var Client
     */
    private $client;

    public function setUp()
    {
        $this->client = self::createClient();
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
     * @param string  $route
     * @param array $params
     * @return null|Response
     */
    protected function postRequest($route, array $params)
    {
        $client = $this->getClient();
        $client->request(
            HttpMethod::POST,
            $route,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'],
            json_encode($params)
        );

        return $client->getResponse();
    }
}
