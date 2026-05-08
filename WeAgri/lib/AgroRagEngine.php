<?php
declare(strict_types=1);

final class AgroRagEngine
{
    private array $knowledgeBase;
    private array $knowledgeChunks;

    private array $stopWords = [
        'a', 'about', 'after', 'all', 'and', 'any', 'are', 'at', 'be', 'been', 'before', 'can', 'could',
        'did', 'do', 'for', 'from', 'get', 'have', 'how', 'i', 'if', 'in', 'into', 'is', 'it', 'my',
        'of', 'on', 'or', 'please', 'should', 'so', 'that', 'the', 'their', 'there', 'this', 'to',
        'was', 'what', 'when', 'why', 'with', 'would', 'your',
    ];

    public function __construct(array $knowledgeBase)
    {
        $this->knowledgeBase = $knowledgeBase;
        $this->knowledgeChunks = self::buildChunksFromKnowledgeBase($knowledgeBase);
    }

    public static function buildChunksFromKnowledgeBase(array $knowledgeBase): array
    {
        $chunks = [];

        foreach ($knowledgeBase as $entry) {
            $knowledgeId = (int) ($entry['id'] ?? 0);
            $title = trim((string) ($entry['title'] ?? 'Untitled entry'));
            $topic = trim((string) ($entry['topic'] ?? 'General Advisory'));
            $source = trim((string) ($entry['source'] ?? 'WeAgri knowledge base'));
            $tags = self::normalizeTagsStatic($entry['tags'] ?? []);
            $recommendations = self::normalizeLinesStatic($entry['recommendations'] ?? []);
            $content = trim((string) ($entry['content'] ?? ''));

            $contentChunks = self::splitContentIntoChunks($content);
            foreach ($contentChunks as $index => $chunkText) {
                $chunks[] = [
                    'chunk_id' => $knowledgeId . ':content:' . $index,
                    'knowledge_id' => $knowledgeId,
                    'chunk_index' => $index,
                    'chunk_type' => 'content',
                    'title' => $title,
                    'topic' => $topic,
                    'source' => $source,
                    'tags' => $tags,
                    'recommendations' => $recommendations,
                    'chunk_text' => $chunkText,
                ];
            }

            $recommendationChunks = array_chunk($recommendations, 2);
            foreach ($recommendationChunks as $index => $recommendationGroup) {
                $chunkText = implode(' ', array_map(
                    static fn(string $line): string => '- ' . $line,
                    $recommendationGroup
                ));

                if ($chunkText === '') {
                    continue;
                }

                $chunks[] = [
                    'chunk_id' => $knowledgeId . ':recommendations:' . $index,
                    'knowledge_id' => $knowledgeId,
                    'chunk_index' => $index,
                    'chunk_type' => 'recommendations',
                    'title' => $title,
                    'topic' => $topic,
                    'source' => $source,
                    'tags' => $tags,
                    'recommendations' => $recommendations,
                    'chunk_text' => $chunkText,
                ];
            }
        }

        return $chunks;
    }

    public function retrieve(string $query, int $limit = 4): array
    {
        $cleanQuery = trim($query);
        $tokens = $this->tokenize($cleanQuery);
        $normalizedQuery = mb_strtolower($cleanQuery);
        $scored = [];

        foreach ($this->knowledgeChunks as $chunk) {
            $score = $this->scoreChunk($chunk, $tokens, $normalizedQuery);
            if ($score <= 0) {
                continue;
            }

            $chunk['score'] = $score;
            $chunk['matched_terms'] = $this->matchedTerms($chunk, $tokens, $normalizedQuery);
            $scored[] = $chunk;
        }

        usort($scored, static fn(array $left, array $right): int => $right['score'] <=> $left['score']);
        $topChunks = array_slice($scored, 0, $limit);
        $documents = $this->aggregateDocuments($topChunks);

        return [
            'tokens' => $tokens,
            'chunks' => $topChunks,
            'documents' => $documents,
            'context' => $this->buildContext($topChunks),
            'prompt' => $this->buildGroundingPrompt($cleanQuery, $topChunks),
        ];
    }

    private function scoreChunk(array $chunk, array $tokens, string $normalizedQuery): int
    {
        $title = mb_strtolower((string) ($chunk['title'] ?? ''));
        $topic = mb_strtolower((string) ($chunk['topic'] ?? ''));
        $text = mb_strtolower((string) ($chunk['chunk_text'] ?? ''));
        $tags = self::normalizeTagsStatic($chunk['tags'] ?? []);
        $score = 0;

        foreach ($tokens as $token) {
            if (str_contains($title, $token)) {
                $score += 7;
            }

            if (str_contains($topic, $token)) {
                $score += 5;
            }

            if (in_array($token, $tags, true)) {
                $score += 5;
            }

            if (str_contains($text, $token)) {
                $score += 3;
            }
        }

        if ($normalizedQuery !== '') {
            if (str_contains($text, $normalizedQuery)) {
                $score += 12;
            }

            if (str_contains($title, $normalizedQuery)) {
                $score += 9;
            }
        }

        $coverage = count($this->matchedTerms($chunk, $tokens, $normalizedQuery));
        if ($coverage > 1) {
            $score += $coverage * 2;
        }

        if (($chunk['chunk_type'] ?? '') === 'recommendations') {
            $score += 1;
        }

        return $score;
    }

    private function matchedTerms(array $chunk, array $tokens, string $normalizedQuery): array
    {
        $title = mb_strtolower((string) ($chunk['title'] ?? ''));
        $topic = mb_strtolower((string) ($chunk['topic'] ?? ''));
        $text = mb_strtolower((string) ($chunk['chunk_text'] ?? ''));
        $tags = self::normalizeTagsStatic($chunk['tags'] ?? []);
        $matches = [];

        foreach ($tokens as $token) {
            if (
                str_contains($title, $token)
                || str_contains($topic, $token)
                || str_contains($text, $token)
                || in_array($token, $tags, true)
            ) {
                $matches[] = $token;
            }
        }

        if ($normalizedQuery !== '' && (str_contains($title, $normalizedQuery) || str_contains($text, $normalizedQuery))) {
            $matches[] = $normalizedQuery;
        }

        return array_values(array_unique($matches));
    }

    private function aggregateDocuments(array $chunks): array
    {
        $documents = [];

        foreach ($chunks as $chunk) {
            $knowledgeId = (int) ($chunk['knowledge_id'] ?? 0);

            if (!isset($documents[$knowledgeId])) {
                $documents[$knowledgeId] = [
                    'id' => $knowledgeId,
                    'title' => (string) ($chunk['title'] ?? 'Untitled entry'),
                    'topic' => (string) ($chunk['topic'] ?? 'General Advisory'),
                    'content' => (string) ($chunk['chunk_text'] ?? ''),
                    'source' => (string) ($chunk['source'] ?? 'WeAgri knowledge base'),
                    'tags' => self::normalizeTagsStatic($chunk['tags'] ?? []),
                    'recommendations' => self::normalizeLinesStatic($chunk['recommendations'] ?? []),
                    'score' => (int) ($chunk['score'] ?? 0),
                    'supporting_excerpt' => (string) ($chunk['chunk_text'] ?? ''),
                    'supporting_chunks' => [],
                ];
            }

            $documents[$knowledgeId]['score'] = max((int) $documents[$knowledgeId]['score'], (int) ($chunk['score'] ?? 0));
            $documents[$knowledgeId]['supporting_chunks'][] = [
                'chunk_type' => (string) ($chunk['chunk_type'] ?? 'content'),
                'chunk_text' => (string) ($chunk['chunk_text'] ?? ''),
                'score' => (int) ($chunk['score'] ?? 0),
            ];
        }

        usort($documents, static fn(array $left, array $right): int => $right['score'] <=> $left['score']);

        return array_slice(array_values($documents), 0, 3);
    }

    private function buildContext(array $chunks): string
    {
        if ($chunks === []) {
            return '';
        }

        $lines = [];
        foreach ($chunks as $chunk) {
            $lines[] = sprintf(
                '[%s | %s | %s] %s',
                (string) ($chunk['title'] ?? 'Untitled'),
                (string) ($chunk['topic'] ?? 'General Advisory'),
                (string) ($chunk['source'] ?? 'WeAgri knowledge base'),
                trim((string) ($chunk['chunk_text'] ?? ''))
            );
        }

        return implode("\n", $lines);
    }

    private function buildGroundingPrompt(string $query, array $chunks): string
    {
        if ($chunks === []) {
            return '';
        }

        return "User question:\n{$query}\n\nRetrieved agricultural context:\n" . $this->buildContext($chunks)
            . "\n\nAnswer using the retrieved context first. Keep the guidance practical, concise, and farmer-friendly. If the context is weak, say what still needs checking.";
    }

    private function tokenize(string $message): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($message), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_unique($words), function (string $word): bool {
            return mb_strlen($word) > 2 && !in_array($word, $this->stopWords, true);
        }));
    }

    private static function splitContentIntoChunks(string $content, int $maxLength = 280): array
    {
        $content = trim((string) preg_replace('/\s+/', ' ', $content));
        if ($content === '') {
            return [];
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($sentences === []) {
            return [$content];
        }

        $chunks = [];
        $buffer = '';

        foreach ($sentences as $sentence) {
            $candidate = $buffer === '' ? $sentence : $buffer . ' ' . $sentence;
            if (mb_strlen($candidate) <= $maxLength) {
                $buffer = $candidate;
                continue;
            }

            if ($buffer !== '') {
                $chunks[] = $buffer;
            }

            if (mb_strlen($sentence) <= $maxLength) {
                $buffer = $sentence;
                continue;
            }

            $parts = preg_split('/,\s+/u', $sentence, -1, PREG_SPLIT_NO_EMPTY) ?: [$sentence];
            $partBuffer = '';
            foreach ($parts as $part) {
                $partCandidate = $partBuffer === '' ? $part : $partBuffer . ', ' . $part;
                if (mb_strlen($partCandidate) <= $maxLength) {
                    $partBuffer = $partCandidate;
                    continue;
                }

                if ($partBuffer !== '') {
                    $chunks[] = $partBuffer;
                }

                $partBuffer = mb_substr($part, 0, $maxLength);
            }

            $buffer = $partBuffer;
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return $chunks !== [] ? $chunks : [$content];
    }

    private static function normalizeTagsStatic(array|string $tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/\s*,\s*/', mb_strtolower($tags), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return array_values(array_filter(array_map(
            static fn(string $tag): string => trim(mb_strtolower($tag)),
            $tags
        )));
    }

    private static function normalizeLinesStatic(array|string|null $lines): array
    {
        if ($lines === null) {
            return [];
        }

        if (is_string($lines)) {
            $lines = preg_split('/\r\n|\r|\n|\|{2}/', $lines, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return array_values(array_filter(array_map(
            static fn(string $line): string => trim($line),
            $lines
        )));
    }
}
