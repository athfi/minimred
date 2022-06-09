<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectRedcap;
use App\Http\Controllers\ProjectSimulationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/projects');
//    return view('dashboard');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        return redirect('/projects');
//        return view('dashboard');
    })->name('dashboard');
});



//Project
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('projects', [ProjectController::class, 'index'])
        ->name('projects');
    Route::get('project/{project}/switch', [ProjectController::class, 'switch'])
        ->name('project.switch');
    Route::get('project', [ProjectController::class, 'show'])
        ->name('project');
    Route::get('project/minimisation', [ProjectController::class, 'minimisationSetting' ])
        ->name('minimisation');
    Route::get('project/create', [ProjectController::class, 'create'])
        ->name('projects.create');
    Route::delete('project/{project}/delete', [ProjectController::class, 'destroy'])
        ->name('project.destroy');
    Route::get('project/records', [ProjectController::class, 'records'])
        ->name('project.records');
    Route::get('project/record/{record_id}', [ProjectController::class, 'record'])
        ->name('project.record');
    Route::get('project/record/{record_id}/randomise', [ProjectController::class, 'randomise'])
        ->name('project.randomise');
    Route::post('project/record/{record_id}/randomise', [ProjectController::class, 'minimise'])
        ->name('project.minimise');

    Route::get('project/reset_redcap_data', [ProjectController::class, 'resetRedcap'])
        ->name('project.reset_redcap');
});

//Simulation
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('project/simulation', [ProjectSimulationController::class, 'index'])
        ->name('project.simulation');
});


Route::post('/redcap/login', [ProjectRedcap::class, 'login'])
    ->name('redcap.login');

Route::get('/redcap', [ProjectRedcap::class, 'records'])
    ->name('home');

Route::post('/redcap/logout', [ProjectRedcap::class, 'logout'])
    ->name('redcap.logout');
Route::get('/redcap/logout', [ProjectRedcap::class, 'logout'])
    ->name('redcap.logout-get');

Route::get('/redcap/records', [ProjectRedcap::class, 'records'])
    ->name('redcap.records');
Route::get('/redcap/record/{record_id}', [ProjectRedcap::class, 'record'])
    ->name('redcap.record');
Route::get('/redcap/record/{record_id}/randomise', [ProjectRedcap::class, 'randomise'])
    ->name('redcap.randomise');
Route::post('/redcap/record/{record_id}/randomise', [ProjectRedcap::class, 'minimise'])
    ->name('redcap.minimise');



