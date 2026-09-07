<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Actions\Ai\StartPostGeneration;
use App\Models\AiGeneration;
use App\Models\Workspace;
use App\Services\Ai\PostGenerationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class PostCreateController extends Controller
{
    public function create(Request $request): InertiaResponse|RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;

        if (! $workspace) {
            return redirect()->route('app.workspaces.create');
        }

        $this->authorize('createPost', $workspace);

        return Inertia::render('posts/Create', [
            'workspace' => $workspace,
            'catalog' => PostGenerationCatalog::forWorkspace($workspace),
            'date' => $request->query('date'),
            'brandReferenceCount' => $workspace->getMedia('brand_references')->count(),
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;

        if (! $workspace instanceof Workspace) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $this->authorize('createPost', $workspace);

        $result = StartPostGeneration::execute(
            $request->user(),
            $workspace,
            $request->all(),
        );

        return response()->json($result, Response::HTTP_ACCEPTED);
    }

    public function status(Request $request, string $creationId): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;

        if (! $workspace instanceof Workspace) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $generation = AiGeneration::query()
            ->where('creation_id', $creationId)
            ->where('workspace_id', $workspace->id)
            ->first();

        if ($generation === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => $generation->status->value,
            'post_id' => $generation->post_id,
            'error' => $generation->error,
        ]);
    }
}
