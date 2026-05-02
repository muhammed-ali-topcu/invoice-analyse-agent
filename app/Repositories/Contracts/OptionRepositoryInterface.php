<?php

namespace App\Repositories\Contracts;

use App\Models\Option;
use Illuminate\Support\Collection;

interface OptionRepositoryInterface
{
    /**
     * Get an option by its key.
     */
    public function findByKey(string $key): ?Option;

    /**
     * Get the value of an option by its key.
     */
    public function getValue(string $key, mixed $default = null): mixed;

    /**
     * Create or update an option.
     */
    public function updateOrCreate(string $key, mixed $value): Option;

    /**
     * Get all options.
     *
     * @return Collection<int, Option>
     */
    public function all(): Collection;
}
