<?php

namespace Biblioverse\TypesenseBundle\Client;

use Http\Discovery\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Fix for https://github.com/typesense/typesense-php/issues/77.
 */
class WrapPSR18WithSendMethod implements ClientInterface
{
    public function __construct(private readonly ClientInterface $client, private readonly Psr17Factory $psr17Factory = new Psr17Factory())
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->client->sendRequest($request);
    }

    /**
     * @param array<string, string> $headers
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function send(string $method, string $url, array $headers, ?string $body): ResponseInterface
    {
        $request = $this->psr17Factory->createRequest($method, $url);
        if ($body !== null && $body !== '') {
            $request = $request->withBody($this->psr17Factory->createStream($body));
        }
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        if (!$request instanceof RequestInterface) {
            throw new \InvalidArgumentException('Invalid request');
        }

        return $this->sendRequest($request);
    }
}
