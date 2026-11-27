<?php

use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\ArticleViewController;
use App\Http\Controllers\Api\V1\AuthorController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\NavigationController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\SidebarController;
use App\Http\Controllers\Api\V1\SitemapController;
use App\Http\Controllers\Api\V1\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:public-api')->group(function () {
        Route::get('/home', HomeController::class);
        Route::get('/sidebar', SidebarController::class);
        Route::get('/navigation', NavigationController::class);
        Route::get('/articles', [ArticleController::class, 'index']);
        Route::get('/articles/{year}/{month}/{day}/{slug}', [ArticleController::class, 'show']);
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/tags', [TagController::class, 'index']);
        Route::get('/authors/{username}', [AuthorController::class, 'show']);
        Route::get('/pages/{slug}', [PageController::class, 'show']);
        Route::get('/sitemap/news', [SitemapController::class, 'news']);
    });

    Route::post('/articles/{id}/comments', [CommentController::class, 'store'])->middleware('throttle:comment-submit');
    Route::post('/articles/{id}/view', [ArticleViewController::class, 'store']);
});
