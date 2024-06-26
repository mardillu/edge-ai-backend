<?php

declare(strict_types=1);

namespace App\Services\Gemini;

use BadMethodCallException;
use CurlHandle;
use App\Services\Gemini\ClientInterface as GeminiClientInterface;
use App\Services\Gemini\Enums\ModelName;
use App\Services\Gemini\Json\ObjectListParser;
use App\Services\Gemini\Requests\CountTokensRequest;
use App\Services\Gemini\Requests\EmbedContentRequest;
use App\Services\Gemini\Requests\GenerateContentRequest;
use App\Services\Gemini\Requests\GenerateContentStreamRequest;
use App\Services\Gemini\Requests\ListModelsRequest;
use App\Services\Gemini\Requests\RequestInterface;
use App\Services\Gemini\Responses\CountTokensResponse;
use App\Services\Gemini\Responses\EmbedContentResponse;
use App\Services\Gemini\Responses\GenerateContentResponse;
use App\Services\Gemini\Responses\ListModelsResponse;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function extension_loaded;
use function json_decode;
use function sprintf;
use function strtolower;

class Client implements GeminiClientInterface
{
    private string $baseUrl = 'https://generativelanguage.googleapis.com';

    /**
     * @var array<string, string|string[]>
     */
    private array $requestHeaders = [];

    public function __construct(
        private readonly string  $apiKey,
        private ?HttpClientInterface $client = null,
        private ?RequestFactoryInterface $requestFactory = null,
        private ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->client ??= Psr18ClientDiscovery::find();
        $this->requestFactory ??= Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory ??= Psr17FactoryDiscovery::findStreamFactory();
    }

    public function geminiPro(): GenerativeModel
    {
        return $this->generativeModel(ModelName::GeminiPro);
    }

    public function geminiProVision(): GenerativeModel
    {
        return $this->generativeModel(ModelName::GeminiProVision);
    }

    public function generativeModel(ModelName $modelName): GenerativeModel
    {
        return new GenerativeModel(
            $this,
            $modelName,
        );
    }

    public function embeddingModel(ModelName $modelName): EmbeddingModel
    {
        return new EmbeddingModel(
            $this,
            $modelName,
        );
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function generateContent(GenerateContentRequest $request): GenerateContentResponse
    {
        $response = $this->doRequest($request);
        $json = json_decode($response, associative: true);

        return GenerateContentResponse::fromArray($json);
    }

    /**
     * @param GenerateContentStreamRequest $request
     * @param callable(GenerateContentResponse): void $callback
     * @param CurlHandle|null $curl
     * @throws BadMethodCallException
     * @throws RuntimeException
     */
    public function generateContentStream(
        GenerateContentStreamRequest $request,
        callable $callback,
        ?CurlHandle $curl = null,
    ): void {
        if (!extension_loaded('curl')) {
            throw new BadMethodCallException('Gemini API requires `curl` extension for streaming responses');
        }

        $parser = new ObjectListParser(
            /* @phpstan-ignore-next-line */
            static fn (array $arr) => $callback(GenerateContentResponse::fromArray($arr)),
        );

        $writeFunction = static function (CurlHandle $ch, string $str) use ($request, $parser): int {
            $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

            return $responseCode === 200
                ? $parser->consume($str)
                : throw new RuntimeException(
                    sprintf(
                        'Gemini API operation failed: operation=%s, status_code=%d, response=%s',
                        $request->getOperation(),
                        $responseCode,
                        $str,
                    ),
                );
        };

        $ch = $curl ?? curl_init();

        if ($ch === false) {
            throw new RuntimeException('Gemini API cannot initialize streaming content request');
        }

        $headerLines = [];
        foreach ($this->getRequestHeaders() as $name => $values) {
            foreach ((array) $values as $value) {
                $headerLines[] = "{$name}: {$value}";
            }
        }

        curl_setopt($ch, CURLOPT_URL, "{$this->baseUrl}/v1/{$request->getOperation()}");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, $writeFunction);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function embedContent(EmbedContentRequest $request): EmbedContentResponse
    {
        $response = $this->doRequest($request);
        $json = json_decode($response, associative: true);

        return EmbedContentResponse::fromArray($json);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function countTokens(CountTokensRequest $request): CountTokensResponse
    {
        $response = $this->doRequest($request);
        $json = json_decode($response, associative: true);

        return CountTokensResponse::fromArray($json);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function listModels(): ListModelsResponse
    {
        $request = new ListModelsRequest();
        $response = $this->doRequest($request);
        $json = json_decode($response, associative: true);

        return ListModelsResponse::fromArray($json);
    }

    public function withBaseUrl(string $baseUrl): self
    {
        $clone = clone $this;
        $clone->baseUrl = $baseUrl;

        return $clone;
    }

    /**
     * @param array<string, string|string[]> $headers
     * @return self
     */
    public function withRequestHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->requestHeaders = [];

        foreach ($headers as $name => $value) {
            $clone->requestHeaders[strtolower($name)] = $value;
        }

        return $clone;
    }

    /**
     * @return array<string, string|string[]>
     */
    private function getRequestHeaders(): array
    {
        return $this->requestHeaders + [
            'content-type' => 'application/json',
            self::API_KEY_HEADER_NAME => $this->apiKey,
        ];
    }

    /**
     * @throws ClientExceptionInterface
     */
    private function doRequest(RequestInterface $request): string
    {
        if (!isset($this->client, $this->requestFactory, $this->streamFactory)) {
            throw new RuntimeException('Missing client or factory for Gemini API operation');
        }

        $uri = "{$this->baseUrl}/v1/{$request->getOperation()}";
        $httpRequest = $this->requestFactory
            ->createRequest($request->getHttpMethod(), $uri);

        foreach ($this->getRequestHeaders() as $name => $value) {
            $httpRequest = $httpRequest->withAddedHeader($name, $value);
        }

        $payload = $request->getHttpPayload();
        if (!empty($payload)) {
            $stream = $this->streamFactory->createStream($payload);
            $httpRequest = $httpRequest->withBody($stream);
        }

        $response = $this->client->sendRequest($httpRequest);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                sprintf(
                    'Gemini API operation failed: operation=%s, status_code=%d,  response=%s',
                    $request->getOperation(),
                    $response->getStatusCode(),
                    $response->getBody(),
                ),
            );
        }

        return (string) $response->getBody();
    }
}
