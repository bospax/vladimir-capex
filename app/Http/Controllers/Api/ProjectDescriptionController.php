<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProjectDescriptionRequest;
use App\Http\Requests\Api\UpdateProjectDescriptionRequest;
use App\Http\Resources\ProjectDescriptionResource;
use App\Imports\ProjectDescriptionImport;
use App\Models\ProjectDescription;
use App\Services\ProjectDescriptionService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProjectDescriptionController extends Controller
{
    private $service;

    public function __construct(ProjectDescriptionService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->list();

        return ProjectDescriptionResource::collection($data);
    }

    public function store(StoreProjectDescriptionRequest $request)
    {
        $project = $this->service->store($request->validated());

        return new ProjectDescriptionResource($project);
    }

    public function show(ProjectDescription $project_description)
    {
         return new ProjectDescriptionResource($project_description);
    }

    public function update(UpdateProjectDescriptionRequest $request, ProjectDescription $project_description)
    {
        $project = $this->service->update($project_description, $request->validated());

        return new ProjectDescriptionResource($project);
    }

    public function destroy(ProjectDescription $project_description)
    {
        $this->service->delete($project_description);

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }

	public function import(Request $request)
	{
		$request->validate([
			'file' => 'required|file|mimes:xlsx,xls,csv'
		]);

		try {
			$import = new ProjectDescriptionImport();
			Excel::import($import, $request->file('file'));

			$failures = $import->failures();

			if ($failures->isNotEmpty()) {

				$errors = [];

				foreach ($failures as $failure) {
					$errors[] = [
						'row' => $failure->row(), // row number
						'column' => $failure->attribute(),
						'errors' => $failure->errors(),
						'value' => $failure->values()[$failure->attribute()] ?? null,
					];
				}

				return response()->json([
					'success' => false,
					'message' => 'Import completed with errors',
					'errors' => $errors
				], 422);
			}

			return response()->json([
				'success' => true,
				'message' => 'All data imported successfully'
			]);

		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Import failed',
				'error' => $e->getMessage()
			], 500);
		}
	}
}