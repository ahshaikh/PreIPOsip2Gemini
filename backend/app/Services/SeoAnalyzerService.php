<?php
// V-FINAL-1730-527 (Created)

namespace App\Services;

use App\Models\Page;
use Illuminate\Support\Str;

class SeoAnalyzerService
{
    /**
     * FSD-SEO-003: Analyze a Page model for SEO score.
     */
    public function analyze(Page $page): array
    {
        $content = $this->extractTextFromContent($page->content);
        $title = $page->seo_meta['title'] ?? $page->title;
        $description = $page->seo_meta['description'] ?? Str::limit(strip_tags($content), 155);
        $keyword = $this->extractKeyword($page->title); // Simple keyword = first word

        $recommendations = [];
        $score = 100;

        // 1. Title Length (Optimal 50-60)
        $titleLength = strlen($title);
        if ($titleLength < 40) {
            $recommendations[] = "Title is too short ({$titleLength} chars). Aim for 50-60.";
            $score -= 10;
        } elseif ($titleLength > 70) {
            $recommendations[] = "Title is too long ({$titleLength} chars). Aim for 50-60.";
            $score -= 10;
        }

        // 2. Meta Description Length (Optimal 150-160)
        $descLength = strlen($description);
        if ($descLength < 130) {
            $recommendations[] = "Meta description is too short ({$descLength} chars). Aim for 150-160.";
            $score -= 10;
        } elseif ($descLength > 165) {
            $recommendations[] = "Meta description is too long ({$descLength} chars). Aim for 150-160.";
            $score -= 10;
        }

        // 3. Keyword in Title
        if (!Str::contains(strtolower($title), strtolower($keyword))) {
            $recommendations[] = "Keyword '{$keyword}' not found in title.";
            $score -= 15;
        }

        // 4. Content Length (Optimal 300+)
        $wordCount = str_word_count(strip_tags($content));
        if ($wordCount < 300) {
            $recommendations[] = "Content is too short ({$wordCount} words). Aim for 300+.";
            $score -= 10;
        }

        // 5. Image Alt Tags (FSD-SEO-003)
        $missingAltTags = 0;
        if (is_array($page->content)) {
            foreach ($page->content as $block) {
                if ($block['type'] === 'image' && empty($block['alt'])) {
                    $missingAltTags++;
                }
            }
        }
        if ($missingAltTags > 0) {
            $recommendations[] = "{$missingAltTags} image(s) are missing alt text.";
            $score -= 10;
        }

        if ($score < 0) $score = 0;
        if (empty($recommendations) && $score = 100) {
             $recommendations[] = "Great job! This page is well-optimized.";
        }

        return [
            'score' => $score,
            'recommendations' => $recommendations,
            'analysis' => [
                'Title' => $title . " ({$titleLength} chars)",
                'Description' => $description . " ({$descLength} chars)",
                'Keyword' => $keyword,
                'Word Count' => $wordCount,
            ]
        ];
    }

    /**
     * Helper to get plain text from our block editor JSON.
     */
    private function extractTextFromContent($content): string
    {
        if (is_string($content)) return $content;
        if (!is_array($content)) return '';

        $text = '';
        foreach ($content as $block) {
            if ($block['type'] === 'heading') $text .= $block['text'] . " ";
            if ($block['type'] === 'text') $text .= $block['content'] . " ";
        }
        return $text;
    }

    /**
     * Simple keyword extractor (uses first 1-2 words of title).
     */
    private function extractKeyword($title): string
    {
        return Str::words($title, 1, '');
    }
}