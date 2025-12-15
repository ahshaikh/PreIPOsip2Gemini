<?php

namespace App\Jobs;

use App\Models\KbArticle;
use App\Models\KbArticleView;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessKbView implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $articleId;
    protected $ipAddress;
    protected $userAgent;
    protected $userId;

    /**
     * Create a new job instance.
     *
     * @param int $articleId
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param int|null $userId
     */
    public function __construct($articleId, $ipAddress, $userAgent, $userId = null)
    {
        $this->articleId = $articleId;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Log the individual view detail
        KbArticleView::create([
            'kb_article_id' => $this->articleId,
            'user_id'       => $this->userId,
            'ip_address'    => $this->ipAddress,
            'user_agent'    => $this->userAgent,
        ]);

        // 2. Increment the aggregated counter on the article
        // Using query builder for atomic increment without loading model
        KbArticle::where('id', $this->articleId)->increment('views');
    }
}