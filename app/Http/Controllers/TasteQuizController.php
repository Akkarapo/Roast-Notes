<?php

namespace App\Http\Controllers;

use App\Enums\BrewMethod;
use App\Http\Requests\TasteQuizRequest;
use App\Models\FlavorCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TasteQuizController extends Controller
{
    public function edit(Request $request): View
    {
        return view('taste-quiz.edit', [
            'user' => $request->user()->load('tastePreferences'),
            'categories' => FlavorCategory::topLevel()->with('children.notes')->orderBy('id')->get(),
            'brewMethods' => BrewMethod::cases(),
        ]);
    }

    public function update(TasteQuizRequest $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user) {
            $user->tastePreferences()->sync($request->input('flavors'));
            $user->forceFill([
                'preferred_roast_level' => $request->roastLevel(),
                'preferred_brew_method' => $request->enum('brew', BrewMethod::class),
                'taste_quiz_completed_at' => now(),
            ])->save();
        });

        return to_route('home')->with('flash', __('Taste profile saved — here are beans picked for you.'));
    }
}
