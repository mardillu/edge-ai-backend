<?php

declare(strict_types=1);

namespace App\Services\Gemini\Requests;

use App\Services\Gemini\Enums\ModelName;
use App\Services\Gemini\Traits\ArrayTypeValidator;
use App\Services\Gemini\Resources\Content;
use JsonSerializable;

use function json_encode;

class CountTokensRequest implements JsonSerializable, RequestInterface
{
    use ArrayTypeValidator;

    /**
     * @param ModelName $modelName
     * @param Content[] $contents
     */
    public function __construct(
        public readonly ModelName $modelName,
        public readonly array $contents,
    ) {
        $this->ensureArrayOfType($this->contents, Content::class);
    }

    public function getOperation(): string
    {
        return "{$this->modelName->value}:countTokens";
    }

    public function getHttpMethod(): string
    {
        return 'POST';
    }

    public function getHttpPayload(): string
    {
        return (string) $this;
    }

    /**
     * @return array{
     *     model: string,
     *     contents: Content[],
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'model' => $this->modelName->value,
            'contents' => $this->contents,
        ];
    }

    public function __toString(): string
    {
        return json_encode($this) ?: '';
    }
}
