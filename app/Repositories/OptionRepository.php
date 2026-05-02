<?php

namespace App\Repositories;

use App\Models\Option;
use App\Repositories\Contracts\OptionRepositoryInterface;
use Illuminate\Support\Collection;

class OptionRepository implements OptionRepositoryInterface
{
    /**
     * Get an option by its primary key.
     */
    public function find(int $id): ?Option
    {
        return Option::find($id);
    }

    /**
     * Get an option by its key.
     */
    public function findByKey(string $key): ?Option
    {
        return Option::where('key', $key)->first();
    }

    /**
     * Get the value of an option by its key.
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        $option = $this->findByKey($key);

        return $option ? $option->value : $default;
    }

    /**
     * Create or update an option.
     */
    public function updateOrCreate(string $key, mixed $value): Option
    {
        return Option::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get all options.
     *
     * @return Collection<int, Option>
     */
    public function all(): Collection
    {
        return Option::all();
    }
}
