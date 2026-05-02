<?php

namespace App\Http\Controllers;

use App\Services\OptionService;
use Inertia\Inertia;

class OptionsController extends Controller
{
    public function __construct(public OptionService $optionService) {}

    public function index()
    {
        $options = $this->optionService->getAllOptions();

        return Inertia::render('options/Index', [
            'options' => $options,
        ]);
    }
}
