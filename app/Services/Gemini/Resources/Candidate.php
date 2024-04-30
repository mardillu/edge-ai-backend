<?php

declare(strict_types=1);

namespace App\Services\Gemini\Resources;

use App\Services\Gemini\Enums\FinishReason;
use App\Services\Gemini\Enums\Role;
use App\Services\Gemini\Traits\ArrayTypeValidator;
use UnexpectedValueException;
use App\Services\Gemini\Resources\Parts\TextPart;


class Candidate
{
    use ArrayTypeValidator;

    /**
     * @param Content $content
     * @param FinishReason $finishReason
     * @param CitationMetadata $citationMetadata
     * @param SafetyRating[] $safetyRatings
     * @param int $tokenCount
     * @param int $index
     */
    public function __construct(
        public readonly Content $content,
        public readonly FinishReason $finishReason,
        public readonly CitationMetadata $citationMetadata,
        public readonly array $safetyRatings,
        public readonly int $tokenCount,
        public readonly int $index,
    ) {
        if ($tokenCount < 0) {
            throw new UnexpectedValueException('tokenCount cannot be negative');
        }

        if ($index < 0) {
            throw new UnexpectedValueException('index cannot be negative');
        }

        $this->ensureArrayOfType($safetyRatings, SafetyRating::class);
    }

    /**
     * @param array{
     *     citationMetadata: array{citationSources: array<int, array{startIndex?: int|null, endIndex?: int|null, uri?: string|null, license?: string|null}>},
     *     safetyRatings: array<int, array{category: string, probability: string, blocked: bool|null}>,
     *     content: array{parts: array<int, array{text: string, inlineData: array{mimeType: string, data: string}}>, role: string},
     *     finishReason: string,
     *     tokenCount: int,
     *     index: int,
     * } $candidate
     * @return self
     */
    public static function fromArray(array $candidate): self
    {
        $citationMetadata = isset($candidate['citationMetadata'])
            ? CitationMetadata::fromArray($candidate['citationMetadata'])
            : new CitationMetadata();

        $safetyRatings = array_map(
            static fn (array $rating): SafetyRating => SafetyRating::fromArray($rating),
            $candidate['safetyRatings'] ?? [],
        );

        // check if $candidate contains a content array
        if (!array_key_exists('content', $candidate) || !is_array($candidate['content'])) {
            $content = new Content([
                new TextPart('Oops, that did not work. Try again.'),
            ], Role::User);
        } else {
            $content = Content::fromArray($candidate['content']);
        }

        return new self(
            $content,
            FinishReason::from($candidate['finishReason']),
            $citationMetadata,
            $safetyRatings,
            $candidate['tokenCount'] ?? 0,
            $candidate['index'] ?? 0,
        );
    }
}
