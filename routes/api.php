<?php

use App\Http\Controllers\Api\MainCapexController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ParticularController;
use App\Http\Controllers\Api\ProjectDescriptionController;
use App\Http\Controllers\Api\TypeOfExpenditureController;
use App\Http\Controllers\Api\TypeOfSubCapexController;
use App\Http\Controllers\Api\ApproverUnitController;
use App\Http\Controllers\Api\MainCapexEstimatorController;
use App\Http\Controllers\Api\MainCapexFaController;
use App\Http\Controllers\Api\MainCapexRequestorController;

Route::middleware('auth:sanctum')->group(function () {

	Route::get('main-capex/{id}/timeline', [MainCapexController::class, 'timeline']);
	Route::get('main-capex/{id}/history', [MainCapexController::class, 'history']);
	Route::post('project-descriptions/import', [ProjectDescriptionController::class, 'import']);

	Route::apiResource('type-of-expenditures', TypeOfExpenditureController::class);
	Route::apiResource('project-descriptions', ProjectDescriptionController::class);
	Route::apiResource('type-of-subcapex',TypeOfSubCapexController::class);
	Route::apiResource('particulars', ParticularController::class);
	Route::apiResource('main-capex', MainCapexController::class);
	
	Route::prefix('approver-units')->group(function () {
		Route::get('/{one_charging_id}', [ApproverUnitController::class, 'show']);
		Route::post('/', [ApproverUnitController::class, 'store']);
		Route::get('/', [ApproverUnitController::class, 'index']);
		Route::put('{one_charging_id}', [ApproverUnitController::class, 'update']);
		Route::delete('{one_charging_id}', [ApproverUnitController::class, 'destroy']);
	});

	Route::prefix('requestor')->group(function () {
		Route::get('main-capex/tab', [MainCapexRequestorController::class, 'getDataByRequestorTab']);
	});

	Route::prefix('fa')->group(function () {
		Route::get('/main-capex/tab', [MainCapexFaController::class, 'getDataByFaTab']);
		Route::post('/main-capex/{id}/return', [MainCapexFaController::class, 'return']);
		Route::post('/main-capex/{id}/reject', [MainCapexFaController::class, 'reject']);
		Route::post('/main-capex/{id}/submit', [MainCapexFaController::class, 'submit']);
	});

	Route::prefix('estimator')->group(function () {
		Route::get('/main-capex', [MainCapexEstimatorController::class, 'index']);
		Route::get('/main-capex/{id}', [MainCapexEstimatorController::class, 'show']);
		Route::post('/main-capex/{id}/estimate', [MainCapexEstimatorController::class, 'saveEstimation']);
		Route::post('/main-capex/{id}/return', [MainCapexEstimatorController::class, 'return']);
	});
});

Route::get('/php-info', function () {
	return [
        'php_binary' => PHP_BINARY,
        'zip_loaded' => class_exists('ZipArchive'),
        'extensions' => get_loaded_extensions(),
    ];
});
