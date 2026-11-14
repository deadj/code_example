<?php

namespace App\Application\Item\UseCases;

use App\Domain\Item\DTO\ItemResponseDTO;
use App\Domain\Shared\Interfaces\LoggerInterface;
use App\Infrastructure\Domain\Item\Models\Post;
use Illuminate\Auth\AuthenticationException;

class GetItemUseCase
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {}    

    public function execute(Post $post, int $userId): ItemResponseDTO
    {
        if ($post->owner_id != $userId) {
            $this->logger->error('Owner does not match', [
                'post_id' => $post->id,
                'user_id' => $userId,
            ]);
            throw new AuthenticationException("Owner does not match");
        }
            
        return new ItemResponseDTO(
            postId: $post->id,
            answerText: $post->answer->text,
            postText: $post->original_text,
            publicAvailable: $post->isPublicAvailable,
            public: $post->isPublic,
            replacedCount: $post->replacement_count,
            score: $post->popular?->score,
        );
    }    
}