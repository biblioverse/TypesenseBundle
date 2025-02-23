<?php

namespace Biblioverse\TypesenseBundle\Client;

use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\HttpMethodsClientInterface;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface as HttpClient;
use Typesense\Client;
use Typesense\Exceptions\ConfigError;

class ClientSingletonFactory
{
    private ?ClientInterface $client = null;

    /**
     * @param array<string, mixed> $defaultConfig
     */
    public function __construct(private readonly string $uri, #[\SensitiveParameter] private readonly string $apiKey, private readonly ?HttpClient $httpClient, private readonly int $connectionTimeoutSeconds = 5, private readonly array $defaultConfig = [])
    {
    }

    /**
     * @throws ConfigError
     */
    public function __invoke(): ClientInterface
    {
        if ($this->client instanceof ClientInterface) {
            return $this->client;
        }

        $this->client = new ClientAdapter(new Client($this->getConfiguration()));

        return $this->client;
    }

    public function reset(): void
    {
        $this->client = null;
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfiguration(): array
    {
        $urlParsed = parse_url($this->uri);
        if ($urlParsed === false || empty($urlParsed['host']) || empty($urlParsed['port']) || empty($urlParsed['scheme'])) {
            throw new \InvalidArgumentException('Invalid URI .'.$this->uri);
        }

        $config = [
            'nodes' => [
                [
                    'host' => $urlParsed['host'],
                    'port' => $urlParsed['port'],
                    'protocol' => $urlParsed['scheme'],
                ],
            ],
            'client' => $this->getClient(),
            'api_key' => $this->apiKey,
            'connection_timeout_seconds' => $this->connectionTimeoutSeconds,
        ];

        return array_merge($this->defaultConfig, $config);
    }

    private function getClient(): HttpMethodsClientInterface
    {
        $client = $this->httpClient ?? (new Psr18ClientDiscovery())->find();

        return new HttpMethodsClient(
            $client,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );
    }
}
