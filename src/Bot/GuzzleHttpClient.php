<?php

namespace App\Bot;

use BotMan\BotMan\Interfaces\HttpInterface;
use GuzzleHttp\Client;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see \BotMan\BotMan\Http\Curl
 * Default HTTP client for BotMan is not customizable because curl options are hardcoded inside the Curl class
 *
 * Using Guzzle allows to configure proxy and logging.
 * Guzzle is pretty much the de facto standard for making HTTP requests in PHP these days
 */
class GuzzleHttpClient implements HttpInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * Converts PSR-7 response to HttpFoundation response
     *
     * @var HttpFoundationFactory
     */
    private $factory;

    public function __construct(Client $client, HttpFoundationFactory $factory)
    {
        $this->client = $client;
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function get(
        $url,
        array $urlParameters = [],
        array $headers = [],
        $asJSON = false
    ): Response {
        $psrResponse = $this->client->get($url, [
            'headers' => $headers,
            'http_errors' => false,
            'query' => $urlParameters
        ]);

        return $this->factory->createResponse($psrResponse);
    }

    /**
     * {@inheritdoc}
     */
    public function post(
        $url,
        array $urlParameters = [],
        array $postParameters = [],
        array $headers = [],
        $asJSON = false
    ): Response {
        $options = [
            'headers' => $headers,
            'http_errors' => false,
            'query' => $urlParameters
        ];

        $postKey = $asJSON ? 'json' : 'form_params';
        $options[$postKey] = $postParameters;

        $psrResponse = $this->client->post($url, $options);

        return $this->factory->createResponse($psrResponse);
    }
}