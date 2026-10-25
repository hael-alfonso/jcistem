<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PublicChapterController extends Controller
{
    public function activities(): View
    {
        return view('public.activities', [
            'stories' => collect(config('chapter_stories'))->sortByDesc('date'),
        ]);
    }

    public function story(string $slug): View
    {
        $story = collect(config('chapter_stories'))->firstWhere('slug', $slug);
        abort_unless($story, 404);
        return view('public.story', compact('story'));
    }
}
