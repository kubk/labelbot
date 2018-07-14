<?php

declare(strict_types=1);

namespace App\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Http\Promise\FulfilledPromise;
use Psr\Http\Message\RequestInterface;

/**
 * Allows to mock Guzzle with predefined responses.
 */
class GuzzleMockFactory
{
    public function createAdapter(array $requestResponseMap): GuzzleAdapter
    {
        $handlerStack = $this->createHandlerStack($requestResponseMap);

        return GuzzleAdapter::createWithConfig(['handler' => $handlerStack]);
    }

    public function createClient(array $requestResponseMap): Client
    {
        return new Client(['handler' => $this->createHandlerStack($requestResponseMap)]);
    }

    private function createHandlerStack(array $requestResponseMap): HandlerStack
    {
        return HandlerStack::create(function (RequestInterface $request) use ($requestResponseMap) {
            $key = $request->getUri()->getPath();

            if ($request->getUri()->getQuery()) {
                $key .= '?' . $request->getUri()->getQuery();
            }

            if (isset($requestResponseMap[$key])) {
                return new FulfilledPromise($requestResponseMap[$key]);
            }

            throw new \InvalidArgumentException(sprintf('Response for key "%s" is not provided', $key));
        });
    }
}
