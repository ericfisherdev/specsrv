<?php

namespace App\Service;

use App\Entity\AgentInteraction;

class PatternAnalyzerService
{
    public function analyzeSimilarity(array $context1, array $context2): float
    {
        $intersection = array_intersect_key($context1, $context2);
        $union = array_unique(array_merge(array_keys($context1), array_keys($context2)));
        
        if (empty($union)) {
            return 0.0;
        }
        
        $keysSimilarity = count($intersection) / count($union);
        
        $valueSimilarity = 0.0;
        $matchingKeys = 0;
        
        foreach ($intersection as $key => $value1) {
            $value2 = $context2[$key];
            
            if ($value1 === $value2) {
                $valueSimilarity += 1.0;
            } elseif (is_string($value1) && is_string($value2)) {
                $semanticSim = $this->calculateSemanticSimilarity($value1, $value2);
                $valueSimilarity += $semanticSim;
            } elseif (is_array($value1) && is_array($value2)) {
                $arraySim = $this->calculateArraySimilarity($value1, $value2);
                $valueSimilarity += $arraySim;
            }
            
            $matchingKeys++;
        }
        
        if ($matchingKeys === 0) {
            return $keysSimilarity;
        }
        
        $avgValueSimilarity = $valueSimilarity / $matchingKeys;
        
        return ($keysSimilarity * 0.4) + ($avgValueSimilarity * 0.6);
    }
    
    public function identifyPatternType(array $executionSteps): string
    {
        $stepTypes = array_map(fn($step) => $step['type'] ?? 'unknown', $executionSteps);
        
        if (in_array('code_generation', $stepTypes) || in_array('implementation', $stepTypes)) {
            return 'implementation';
        } elseif (in_array('validation', $stepTypes) || in_array('testing', $stepTypes) || in_array('quality_check', $stepTypes)) {
            return 'quality_assurance';
        } elseif (in_array('analysis', $stepTypes) || in_array('research', $stepTypes) || in_array('investigation', $stepTypes)) {
            return 'analysis';
        } elseif (in_array('debugging', $stepTypes) || in_array('error_resolution', $stepTypes) || in_array('troubleshooting', $stepTypes)) {
            return 'debugging';
        } elseif (in_array('refactoring', $stepTypes) || in_array('optimization', $stepTypes) || in_array('improvement', $stepTypes)) {
            return 'optimization';
        } else {
            return 'general';
        }
    }
    
    public function extractKeyFeatures(array $context): array
    {
        $features = [];
        
        if (isset($context['task_type'])) {
            $features['task_type'] = $context['task_type'];
        }
        
        if (isset($context['technologies'])) {
            $features['tech_stack'] = is_array($context['technologies']) 
                ? $context['technologies'] 
                : [$context['technologies']];
        }
        
        if (isset($context['files_count'])) {
            $features['complexity'] = $this->categorizeComplexity($context['files_count']);
        }
        
        if (isset($context['domain'])) {
            $features['domain'] = $context['domain'];
        }
        
        if (isset($context['project_size'])) {
            $features['project_scale'] = $this->categorizeProjectSize($context['project_size']);
        }
        
        if (isset($context['time_constraints'])) {
            $features['urgency'] = $this->categorizeUrgency($context['time_constraints']);
        }
        
        if (isset($context['quality_requirements'])) {
            $features['quality_level'] = $context['quality_requirements'];
        }
        
        return $features;
    }
    
    public function calculatePatternConfidence(AgentInteraction $interaction, array $similarInteractions): float
    {
        if (empty($similarInteractions)) {
            return $interaction->getSuccessScore() ?? 0.0;
        }
        
        $avgSimilarSuccess = array_sum(array_map(fn($i) => $i->getSuccessScore() ?? 0.0, $similarInteractions)) / count($similarInteractions);
        $consistencyBonus = $this->calculateConsistencyBonus($interaction, $similarInteractions);
        $volumeBonus = min(0.1, count($similarInteractions) * 0.02);
        
        $mySuccessScore = $interaction->getSuccessScore() ?? 0.0;
        $baseConfidence = ($mySuccessScore * 0.6) + ($avgSimilarSuccess * 0.4);
        
        return min(1.0, $baseConfidence + $consistencyBonus + $volumeBonus);
    }
    
    public function extractSolutionTemplate(AgentInteraction $interaction): array
    {
        $template = [
            'approach' => $this->extractApproach($interaction->getExecutionSteps()),
            'key_steps' => $this->extractKeySteps($interaction->getExecutionSteps()),
            'tools_used' => $this->extractToolsUsed($interaction->getExecutionSteps()),
            'success_indicators' => $this->extractSuccessIndicators($interaction->getOutputResult()),
            'time_estimate' => $this->categorizeExecutionTime($interaction->getExecutionTimeMs() ?? 0),
        ];
        
        if ($interaction->getErrorLog()) {
            $template['common_pitfalls'] = $this->extractCommonPitfalls($interaction->getErrorLog());
        }
        
        return $template;
    }
    
    private function calculateSemanticSimilarity(string $str1, string $str2): float
    {
        similar_text(strtolower($str1), strtolower($str2), $percent);
        return $percent / 100;
    }
    
    private function calculateArraySimilarity(array $arr1, array $arr2): float
    {
        $intersection = array_intersect($arr1, $arr2);
        $union = array_unique(array_merge($arr1, $arr2));
        
        if (empty($union)) {
            return 1.0;
        }
        
        return count($intersection) / count($union);
    }
    
    private function categorizeComplexity(mixed $value): string
    {
        if (is_numeric($value)) {
            $value = (int) $value;
            if ($value <= 2) return 'simple';
            if ($value <= 5) return 'moderate';
            if ($value <= 10) return 'complex';
            return 'very_complex';
        }
        
        return 'unknown';
    }
    
    private function categorizeProjectSize(mixed $value): string
    {
        if (is_numeric($value)) {
            $value = (int) $value;
            if ($value <= 100) return 'small';
            if ($value <= 1000) return 'medium';
            if ($value <= 10000) return 'large';
            return 'enterprise';
        }
        
        return 'unknown';
    }
    
    private function categorizeUrgency(mixed $value): string
    {
        if (is_numeric($value)) {
            $hours = (int) $value;
            if ($hours <= 4) return 'urgent';
            if ($hours <= 24) return 'high';
            if ($hours <= 168) return 'normal';
            return 'low';
        }
        
        return strtolower((string) $value);
    }
    
    private function calculateConsistencyBonus(AgentInteraction $interaction, array $similarInteractions): float
    {
        $myScore = $interaction->getSuccessScore() ?? 0.0;
        $scores = array_map(fn($i) => $i->getSuccessScore() ?? 0.0, $similarInteractions);
        
        $variance = $this->calculateVariance($scores);
        
        if ($variance < 0.1) {
            return 0.05;
        } elseif ($variance < 0.2) {
            return 0.02;
        }
        
        return 0.0;
    }
    
    private function calculateVariance(array $values): float
    {
        if (empty($values)) return 0.0;
        
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($x) => pow($x - $mean, 2), $values);
        
        return array_sum($squaredDiffs) / count($values);
    }
    
    private function extractApproach(array $executionSteps): string
    {
        if (empty($executionSteps)) {
            return 'unknown';
        }
        
        $firstStepType = $executionSteps[0]['type'] ?? 'unknown';
        
        return match($firstStepType) {
            'analysis' => 'analytical',
            'research' => 'research_based',
            'implementation' => 'direct_implementation',
            'testing' => 'test_driven',
            default => 'standard'
        };
    }
    
    private function extractKeySteps(array $executionSteps): array
    {
        return array_map(function($step) {
            return [
                'type' => $step['type'] ?? 'unknown',
                'description' => $step['description'] ?? '',
                'outcome' => $step['outcome'] ?? ''
            ];
        }, $executionSteps);
    }
    
    private function extractToolsUsed(array $executionSteps): array
    {
        $tools = [];
        
        foreach ($executionSteps as $step) {
            if (isset($step['tools'])) {
                $tools = array_merge($tools, is_array($step['tools']) ? $step['tools'] : [$step['tools']]);
            }
        }
        
        return array_unique($tools);
    }
    
    private function extractSuccessIndicators(array $outputResult): array
    {
        $indicators = [];
        
        if (isset($outputResult['tests_passed'])) {
            $indicators['tests_passed'] = $outputResult['tests_passed'];
        }
        
        if (isset($outputResult['performance_improvement'])) {
            $indicators['performance_improved'] = $outputResult['performance_improvement'];
        }
        
        if (isset($outputResult['code_quality_score'])) {
            $indicators['code_quality'] = $outputResult['code_quality_score'];
        }
        
        return $indicators;
    }
    
    private function categorizeExecutionTime(int $timeMs): string
    {
        if ($timeMs <= 5000) return 'very_fast';
        if ($timeMs <= 30000) return 'fast';
        if ($timeMs <= 180000) return 'moderate';
        if ($timeMs <= 600000) return 'slow';
        return 'very_slow';
    }
    
    private function extractCommonPitfalls(array $errorLog): array
    {
        $pitfalls = [];
        
        foreach ($errorLog as $error) {
            if (isset($error['type'])) {
                $pitfalls[] = [
                    'type' => $error['type'],
                    'message' => $error['message'] ?? '',
                    'resolution' => $error['resolution'] ?? ''
                ];
            }
        }
        
        return $pitfalls;
    }
}