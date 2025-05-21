<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SearchEnrichmentService
{
    protected $geminiApiKey;
    protected $geminiEndpoint;
    protected $useAI;
    
    public function __construct()
    {
        $this->geminiApiKey = env('GOOGLE_GEMINI_API_KEY');
        $this->geminiEndpoint = env('GOOGLE_GEMINI_ENDPOINT');
    }
    
    /**
     * Process a natural language query into structured search parameters
     *
     * @param string $query The user's natural language query
     * @return array The structured search parameters
     */
    public function processQuery($query)
    {
        // Early exit if AI search is disabled
        if (!$this->useAI) {
            return $this->createSimpleSearchParams($query);
        }
        
        // Generate a cache key based on the query
        $cacheKey = 'search_query_' . md5($query);
        
        // Shorter cache time (4 hours) for balance between freshness and performance
        return Cache::remember($cacheKey, 240, function() use ($query) {
            try {
                // If query is very short (1-2 words), just do simple keyword search
                if (str_word_count($query) <= 2) {
                    return $this->createSimpleSearchParams($query);
                }
                
                // Simpler prompt for faster processing
                $systemPrompt = "Extract search intent from this e-commerce query. Return JSON: " .
                    "{\"terms\":[keywords], \"brands\":[brand names], \"price_min\":number, \"price_max\":number, \"sort\":\"relevance|price_low|price_high\"}";
                
                // Set shorter timeout to prevent waiting too long
                $response = Http::timeout(2)->withHeaders([
                    'Content-Type' => 'application/json'
                ])->post("{$this->geminiEndpoint}?key={$this->geminiApiKey}", [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $systemPrompt . "\n\nQuery: " . $query]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 200 // Reduced token count
                    ]
                ]);
                
                if ($response->successful()) {
                    // Extract and parse the response
                    $result = $response->json();
                    $content = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    
                    // Extract JSON from response (more permissive pattern)
                    preg_match('/(\{.*\})/s', $content, $matches);
                    if (!empty($matches[1])) {
                        $parsedResult = json_decode($matches[1], true);
                        
                        if (json_last_error() === JSON_ERROR_NONE) {
                            // Transform to our expected format
                            return [
                                'interpreted_query' => $query,
                                'search_terms' => $parsedResult['terms'] ?? [$query],
                                'filters' => [
                                    'brands' => $parsedResult['brands'] ?? [],
                                    'price_range' => [
                                        'min' => $parsedResult['price_min'] ?? null,
                                        'max' => $parsedResult['price_max'] ?? null
                                    ]
                                ],
                                'sort' => $parsedResult['sort'] ?? 'relevance'
                            ];
                        }
                    }
                    
                    // Try to extract useful info even if JSON parsing failed
                    return $this->extractFallbackParams($content, $query);
                }
                
                // API call failed, use simple matching
                return $this->createSimpleSearchParams($query);
                
            } catch (\Exception $e) {
                // Don't waste resources logging minor issues
                if (env('APP_ENV') === 'production' && !env('LOG_SEARCH_ERRORS', false)) {
                    // Only log critical errors in production
                    if (str_contains($e->getMessage(), 'cURL error 28')) {
                        Log::info("Search API timeout for query: $query");
                    } else {
                        Log::error("Critical search error: " . $e->getMessage());
                    }
                } else {
                    Log::error("Search exception: {$e->getMessage()} for query: $query");
                }
                
                return $this->createSimpleSearchParams($query);
            }
        });
    }
    
    /**
     * Create simple search parameters without AI processing
     * 
     * @param string $query The user query
     * @return array Search parameters
     */
    protected function createSimpleSearchParams($query)
    {
        // Extract potential price information using regex
        $priceRange = ['min' => null, 'max' => null];
        
        // Look for price indicators
        if (preg_match('/under\s+[\$£€]?(\d+)/i', $query, $matches)) {
            $priceRange['max'] = (int) $matches[1];
        }
        
        if (preg_match('/over\s+[\$£€]?(\d+)/i', $query, $matches)) {
            $priceRange['min'] = (int) $matches[1];
        }
        
        // Extract potential brands
        $commonBrands = ['Apple', 'Samsung', 'Sony', 'LG', 'Xiaomi', 'Google', 'OnePlus', 'HP', 'Dell'];
        $brands = [];
        
        foreach ($commonBrands as $brand) {
            if (stripos($query, $brand) !== false) {
                $brands[] = $brand;
            }
        }
        
        // Determine sort mode
        $sort = 'relevance';
        if (stripos($query, 'cheap') !== false || stripos($query, 'lowest price') !== false) {
            $sort = 'price_low';
        } elseif (stripos($query, 'expensive') !== false || stripos($query, 'high end') !== false) {
            $sort = 'price_high';
        } elseif (stripos($query, 'new') !== false || stripos($query, 'latest') !== false) {
            $sort = 'newest';
        }
        
        // Clean query for search terms (remove common words)
        $cleanQuery = preg_replace('/\b(a|an|the|in|on|at|to|for|with|by|show|me|find|get|under|over)\b/i', '', $query);
        $searchTerms = array_filter(array_map('trim', explode(' ', $cleanQuery)));
        
        return [
            'interpreted_query' => $query,
            'search_terms' => !empty($searchTerms) ? $searchTerms : [$query],
            'filters' => [
                'brands' => $brands,
                'price_range' => $priceRange
            ],
            'sort' => $sort
        ];
    }
    
    /**
     * Try to extract useful data from failed AI response
     * 
     * @param string $content The AI response content
     * @param string $query The original query
     * @return array Search parameters
     */
    protected function extractFallbackParams($content, $query)
    {
        $params = $this->createSimpleSearchParams($query);
        
        // Try to find price information
        if (preg_match('/price.*?(\d+).*?(\d+)/i', $content, $matches)) {
            $params['filters']['price_range']['min'] = min((int)$matches[1], (int)$matches[2]);
            $params['filters']['price_range']['max'] = max((int)$matches[1], (int)$matches[2]);
        }
        
        // Try to find brand names
        $commonBrands = ['Apple', 'Samsung', 'Sony', 'LG', 'Xiaomi', 'Google', 'OnePlus', 'HP', 'Dell'];
        foreach ($commonBrands as $brand) {
            if (stripos($content, $brand) !== false) {
                $params['filters']['brands'][] = $brand;
            }
        }
        
        // Try to identify sort method
        if (stripos($content, 'price_low') !== false || stripos($content, 'lowest') !== false) {
            $params['sort'] = 'price_low';
        } elseif (stripos($content, 'price_high') !== false || stripos($content, 'highest') !== false) {
            $params['sort'] = 'price_high';
        } elseif (stripos($content, 'newest') !== false || stripos($content, 'latest') !== false) {
            $params['sort'] = 'newest';
        }
        
        return $params;
    }
}