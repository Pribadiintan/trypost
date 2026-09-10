<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\PostImport\Status;
use App\Http\Requests\App\PostImport\StorePostImportRequest;
use App\Http\Resources\App\PostImportResource;
use App\Jobs\PostImport\ParsePostImport;
use App\Jobs\PostImport\ProcessPostImport;
use App\Models\PostImport;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PostImportController extends Controller
{
    public function store(StorePostImportRequest $request): JsonResponse
    {
        [$workspace, $user] = $this->resolveWorkspaceAndUser($request);

        $file = $request->file('file');
        $path = $file->store('post-imports');

        $import = PostImport::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'original_filename' => $file->getClientOriginalName(),
            'path' => $path,
            'status' => Status::Parsing,
        ]);

        ParsePostImport::dispatch($import->id);

        return response()->json(
            (new PostImportResource($import))->resolve(),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, string $import): JsonResponse
    {
        [$workspace, $user] = $this->resolveWorkspaceAndUser($request);

        $model = PostImport::query()
            ->ownedBy($workspace->id, $user->id)
            ->findOrFail($import);

        return response()->json((new PostImportResource($model))->resolve());
    }

    public function preview(Request $request, string $import): JsonResponse
    {
        [$workspace, $user] = $this->resolveWorkspaceAndUser($request);

        $model = PostImport::query()
            ->ownedBy($workspace->id, $user->id)
            ->with('rows')
            ->findOrFail($import);

        return response()->json((new PostImportResource($model))->withRows()->resolve());
    }

    public function process(Request $request, string $import): JsonResponse
    {
        [$workspace, $user] = $this->resolveWorkspaceAndUser($request);

        $model = PostImport::query()
            ->ownedBy($workspace->id, $user->id)
            ->findOrFail($import);

        abort_unless($model->status === Status::PreviewReady, Response::HTTP_CONFLICT);

        ProcessPostImport::dispatch($model->id);

        return response()->json((new PostImportResource($model->fresh()))->resolve());
    }

    /**
     * @return array{0: Workspace, 1: User}
     */
    private function resolveWorkspaceAndUser(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Workspace $workspace */
        $workspace = $user->currentWorkspace;

        $this->authorize('createPost', $workspace);

        return [$workspace, $user];
    }
}
