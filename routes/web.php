<?php

use Illuminate\Support\Facades\Route;
use oysterdevs\FileManager\Http\Controllers\FileUploadController;

Route::middleware(['web'])->prefix('uploaded-files')->group(function () {
   
    Route::any('/file-info', [FileUploadController::class, 'file_info'])->name('uploaded-files.info');
    Route::get('/destroy/{id}', [FileUploadController::class,'destroy'])->name('uploaded-files.destroy');
    Route::get('/create/{id?}', [FileUploadController::class,'create'])->name('admin.uploaded-files.create');
    Route::post('/upload', [FileUploadController::class,'uploadFolder'])->name('admin.uploaded-files.upload');
    Route::get('/', [FileUploadController::class, 'index'])->name('admin.uploaded-files.index');
    Route::post('/create-folder', [FileUploadController::class, 'createFolder'])->name('uploaded-files.create-folder');
    Route::get('/uploader/show', [FileUploadController::class, 'show_uploader'])->name('uploader.show');
    Route::post('/uploader/upload', [FileUploadController::class, 'upload'])->name('uploader.upload');
    Route::post('/rename-folder', [FileUploadController::class, 'renameFolder'])->name('uploaded-files.rename-folder');
    Route::delete('/folder/{id}', [FileUploadController::class, 'deleteFolder'])->name('uploaded-files.delete-folder');
    Route::post('/upload-folder', [FileUploadController::class, 'uploadFolder'])->name('admin.uploaded-files.upload-folder');
    Route::get('/download-folder/{id}', [FileUploadController::class, 'downloadFolderAsZip'])->name('admin.uploaded-files.download.folder');
    
});