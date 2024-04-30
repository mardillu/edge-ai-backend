<?php

declare(strict_types=1);

namespace App\Services\Gemini\Responses;

use UnexpectedValueException;

class CountTokensResponse
{
    public function __construct(
        public readonly int $totalTokens,
    ) {
        if ($totalTokens < 0) {
            throw new UnexpectedValueException('totalTokens cannot be negative');
        }
    }

    /**
     * @param array{
     *     totalTokens: int,
     * } $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        return new self($array['totalTokens']);
    }
}
