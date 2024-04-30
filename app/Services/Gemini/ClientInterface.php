<?php

declare(strict_types=1);

namespace App\Services\Gemini;

use CurlHandle;
use App\Services\Gemini\Enums\ModelName;
use App\Services\Gemini\Requests\CountTokensRequest;
use App\Services\Gemini\Requests\EmbedContentRequest;
use App\Services\Gemini\Requests\GenerateContentRequest;
use App\Services\Gemini\Requests\GenerateContentStreamRequest;
use App\Services\Gemini\Responses\CountTokensResponse;
use App\Services\Gemini\Responses\EmbedContentResponse;
use App\Services\Gemini\Responses\GenerateContentResponse;
use App\Services\Gemini\Responses\ListModelsResponse;

/**
 * @since v1.1.0
 */
interface ClientInterface
{
    public const API_KEY_HEADER_NAME = 'x-goog-api-key';

    public function countTokens(CountTokensRequest $request): CountTokensResponse;
    public function generateContent(GenerateContentRequest $request): GenerateContentResponse;
    public function embedContent(EmbedContentRequest $request): EmbedContentResponse;
    public function generativeModel(ModelName $modelName): GenerativeModel;
    public function embeddingModel(ModelName $modelName): EmbeddingModel;
    public function listModels(): ListModelsResponse;
    public function withBaseUrl(string $baseUrl): self;

    /**
     * @param GenerateContentStreamRequest $request
     * @param callable(GenerateContentResponse): void $callback
     * @param CurlHandle|null $curl
     * @return void
     */
    public function generateContentStream(
        GenerateContentStreamRequest $request,
        callable $callback,
        ?CurlHandle $curl = null,
    ): void;
}
