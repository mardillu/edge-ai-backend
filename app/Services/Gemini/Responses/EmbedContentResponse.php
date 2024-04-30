<?php

declare(strict_types=1);

namespace App\Services\Gemini\Responses;

use App\Services\Gemini\Resources\ContentEmbedding;

class EmbedContentResponse
{
    public function __construct(
        public readonly ContentEmbedding $embedding,
    ) {
    }

    /**
     * @param array{
     *     embedding: array{values: float[]}
     * } $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentEmbedding::fromArray($array['embedding']),
        );
    }
}
