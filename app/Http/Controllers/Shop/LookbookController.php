<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\LookbookService;
use Illuminate\Http\Request;

class LookbookController extends Controller
{
    public function __construct(protected LookbookService $lookbookService) {}

    /**
     * All brand looks for the homepage "Urbanflaky Looks" masonry grid.
     */
    public function index(Request $request)
    {
        $limit = $request->integer('limit') ?: null;

        return response()->json([
            'data' => $this->lookbookService->getLooks($limit),
        ]);
    }
}
