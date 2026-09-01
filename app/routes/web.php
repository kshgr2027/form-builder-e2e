<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestController;

/*
|--------------------------------------------------------------------------
| Web Routes Common LMS
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::any('/', [GuestController::class, 'interviewAssessment'])->name('interview.assessment');
 Route::get('/forms', [App\Http\Controllers\FormBuilderController::class, 'getFormsNew'])->name('form-builder.index');
  Route::post('/forms', [App\Http\Controllers\FormBuilderController::class, 'store'])->name('form-builder.store');
      Route::post('/forms/upload-sample-file', [App\Http\Controllers\FormBuilderController::class, 'uploadSampleFile'])->name('form-builder.uploadSampleFile');
    Route::post('/form-builder/delete-sample-file', [App\Http\Controllers\FormBuilderController::class,'deleteSampleFile'])->name('form-builder.deleteSampleFile');
    Route::get('/get-forms', [App\Http\Controllers\FormBuilderController::class, 'getForms'])->name('getForms');
    Route::post('/form-status', [App\Http\Controllers\FormBuilderController::class, 'formStatus'])->name('formStatus');
        Route::get('submit/{slug}', [App\Http\Controllers\FormSubmissionController::class, 'show'])->name('form-submission.show');
    Route::post('submit/{slug}', [App\Http\Controllers\FormSubmissionController::class, 'store'])->name('form-submission.store');
    Route::get('s/{unique_string}', [App\Http\Controllers\FormSubmissionController::class, 'short_link_show'])->name('form-submission.short_link_show');
    Route::post('/forms/{formTemplate}', [App\Http\Controllers\FormBuilderController::class, 'deleteForm'])->name('form-builder.destroy');
    Route::post('/forms-unarchive/{formTemplate}', [App\Http\Controllers\FormBuilderController::class, 'unarchiveForm'])->name('form-builder.unarchive');
