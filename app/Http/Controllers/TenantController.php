<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class TenantController extends Controller
{
    public function current(): JsonResponse
    {
        $tenant = tenant();

        if (!$tenant) {
            return response()->json([
                'message' => 'No tenant found',
            ], 404);
        }

        return response()->json([
            'id' => $tenant->id,
            'name' => $tenant->name,
        ]);
    }
}
