<?php

declare(strict_types=1);

namespace App\Tests\Bot;

use App\Bot\GuzzleHttpClient;
use App\Tests\GuzzleMockFactory;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

class GuzzleHttpClientTest extends TestCase
{
    /**
     * @var GuzzleHttpClient
     */
    private $client;

    public function setUp(): void
    {
        $guzzleMockFactory = new GuzzleMockFactory();
        $guzzle = $guzzleMockFactory->createCacheable();
        $this->client = new GuzzleHttpClient($guzzle, new HttpFoundationFactory());
    }

    public function testItCanSendGetRequestWithHeadersAndQueryString(): void
    {
        $headers = ['Accept' => 'application/json'];
        $urlParameters = ['param1' => 1, 'param2' => 2];
        $response = $this->client->get('http://httpbin.org/get', $urlParameters, $headers);
        $responseArray = json_decode($response->getContent(), true);
        $this->assertEquals('application/json', $responseArray['headers']['Accept']);
        $this->assertEquals(1, $responseArray['args']['param1']);
        $this->assertEquals(2, $responseArray['args']['param2']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testItDoesNotThrowOnClientErrors(): void
    {
        $response = $this->client->get('http://httpbin.org/status/404');
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testItCanSendPostRequest(): void
    {
        $headers = ['Accept' => 'application/json'];
        $urlParameters = ['get_param' => 1];
        $postParameters = ['post_param' => 2];
        $response = $this->client->post('http://httpbin.org/post', $urlParameters, $postParameters, $headers);
        $responseArray = json_decode($response->getContent(), true);
        $this->assertEquals('application/json', $responseArray['headers']['Accept']);
        $this->assertEquals(1, $responseArray['args']['get_param']);
        $this->assertEquals(2, $responseArray['form']['post_param']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testItCanSendPostRequestAsJson(): void
    {
        $headers = ['Accept' => 'application/json'];
        $urlParameters = ['get_param' => 1];
        $postParameters = ['post_param' => 2];
        $asJson = true;
        $response = $this->client->post('http://httpbin.org/anything', $urlParameters, $postParameters, $headers, $asJson);
        $responseArray = json_decode($response->getContent(), true);
        $this->assertEquals('application/json', $responseArray['headers']['Accept']);
        $this->assertEquals(1, $responseArray['args']['get_param']);
        $this->assertEquals(2, $responseArray['json']['post_param']);
        $this->assertEmpty($responseArray['form']);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
