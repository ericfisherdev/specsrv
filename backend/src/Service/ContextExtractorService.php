<?php

namespace App\Service;

class ContextExtractorService
{
    public function extractSignature(array $context): array
    {
        $signature = [];

        if (isset($context['task_type'])) {
            $signature['task_type'] = $context['task_type'];
        }

        if (isset($context['technologies'])) {
            $signature['technologies'] = is_array($context['technologies'])
                ? $context['technologies']
                : [$context['technologies']];
        }

        if (array_key_exists('complexity', $context)) {
            $signature['complexity'] = $this->categorizeComplexity($context['complexity']);
        }

        if (isset($context['files_count'])) {
            $signature['files_count'] = $context['files_count'];
            // Only set complexity from files_count if complexity wasn't already set
            if (! isset($signature['complexity'])) {
                $signature['complexity'] = $this->categorizeComplexity($context['files_count']);
            }
        }

        if (isset($context['domain'])) {
            $signature['domain'] = $context['domain'];
        }

        if (isset($context['methodology'])) {
            $signature['methodology'] = $context['methodology'];
        }

        if (isset($context['constraints'])) {
            $signature['constraints'] = is_array($context['constraints'])
                ? $context['constraints']
                : [$context['constraints']];
        }

        return $signature;
    }

    public function extractKeywords(array $context): array
    {
        $keywords = [];

        if (isset($context['title'])) {
            $keywords = array_merge($keywords, $this->extractWordsFromText($context['title']));
        }

        if (isset($context['description'])) {
            $keywords = array_merge($keywords, $this->extractWordsFromText($context['description']));
        }

        if (isset($context['tags']) && is_array($context['tags'])) {
            $keywords = array_merge($keywords, $context['tags']);
        }

        return array_unique(array_filter($keywords));
    }

    private function categorizeComplexity(mixed $value): string
    {
        if (is_numeric($value)) {
            $value = (int) $value;
            if ($value <= 2) {
                return 'simple';
            }
            if ($value <= 5) {
                return 'moderate';
            }
            if ($value <= 10) {
                return 'complex';
            }

            return 'very_complex';
        }

        if (is_string($value)) {
            return strtolower($value);
        }

        return 'unknown';
    }

    private function extractWordsFromText(string $text): array
    {
        $words = preg_split('/[\s\W]+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        if (false === $words) {
            return [];
        }

        $significantWords = array_filter($words, function ($word) {
            return strlen($word) >= 3 && ! in_array($word, $this->getStopWords());
        });

        return array_values($significantWords);
    }

    private function getStopWords(): array
    {
        return [
            'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'her', 'was',
            'one', 'our', 'had', 'but', 'not', 'what', 'all', 'were', 'they', 'have',
            'this', 'that', 'with', 'from', 'need', 'want', 'will', 'would', 'could',
            'should', 'must', 'may', 'might', 'can', 'could', 'been', 'being', 'done',
        ];
    }
}
