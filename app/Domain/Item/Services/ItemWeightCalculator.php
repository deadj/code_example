<?php

namespace App\Domain\Item\Services;

use App\Domain\Popular\DTO\WeightCalculatorDTO;

class ItemWeightCalculator
{
    public const int ADDED_SCORE = 1;
    public const int ADDED_HOURS = 2;
        
    protected float $coef;

    public function __construct(float $coef)
    {
        $this->coef = $coef;
    }

    public function getWeight(WeightCalculatorDTO $weightDTO): float
    {
        $diffHours = $weightDTO->itemCreatedAt->diffInHours($weightDTO->now);
        $weight = ($weightDTO->itemScore + static::ADDED_SCORE) / ($diffHours + static::ADDED_HOURS) ** $this->coef;
        
        return $weight;        
    }
}