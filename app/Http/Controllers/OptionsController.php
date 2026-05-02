<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOptionRequest;
use App\Services\OptionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OptionsController extends Controller
{
    public function __construct(public OptionService $optionService) {}

    /**
     * Show the list of all options.
     */
    public function index(): Response
    {
        $options = $this->optionService->getAllOptions();

        return Inertia::render('options/Index', [
            'options' => $options,
        ]);
    }

    /**
     * Show the edit form for a single option.
     */
    public function edit(int $id): Response
    {
        $option = $this->optionService->findOption($id);

        abort_if($option === null, 404);

        return Inertia::render('options/Edit', [
            'option' => $option,
        ]);
    }

    /**
     * Update the option's value.
     */
    public function update(UpdateOptionRequest $request, int $id): RedirectResponse
    {
        $this->optionService->updateOption($id, $request->validated()['value'] ?? null);

        return redirect()->route('options.index')->with('success', 'Option updated successfully.');
    }
}
