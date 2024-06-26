<?php

declare(strict_types=1);

namespace App\Services\Gemini\Enums;

enum TaskType: string
{
    case TASK_TYPE_UNSPECIFIED = 'TASK_TYPE_UNSPECIFIED';
    case RETRIEVAL_QUERY = 'RETRIEVAL_QUERY';
    case RETRIEVAL_DOCUMENT = 'RETRIEVAL_DOCUMENT';
    case SEMANTIC_SIMILARITY = 'SEMANTIC_SIMILARITY';
    case CLASSIFICATION = 'CLASSIFICATION';
    case CLUSTERING = 'CLUSTERING';
}
