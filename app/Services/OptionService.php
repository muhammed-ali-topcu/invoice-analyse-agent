<?php

namespace App\Services;

use App\Repositories\Contracts\OptionRepositoryInterface;
use Illuminate\Support\Collection;

class OptionService
{
    public function __construct(public OptionRepositoryInterface $optionRepository) {}

    /**
     * Get all options.
     */
    public function getAllOptions(): Collection
    {
        return $this->optionRepository->all();
    }
}
