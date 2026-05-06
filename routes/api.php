<?php

use App\Http\Controllers\Api\Admin\MealPackageController as AdminMealPackageController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\WeeklyMenuController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\MealPackageController as UserMealPackageController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Auth\UserCalendarController;
use App\Http\Controllers\Api\Auth\UserAddressController;
use App\Http\Controllers\Api\Auth\UserWeeklyScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth.token')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);

        Route::get('/addresses', [UserAddressController::class, 'index']);
        Route::post('/addresses', [UserAddressController::class, 'store']);
        Route::get('/addresses/{address}', [UserAddressController::class, 'show']);
        Route::put('/addresses/{address}', [UserAddressController::class, 'update']);
        Route::delete('/addresses/{address}', [UserAddressController::class, 'destroy']);

        Route::get('/meal-packages', [UserMealPackageController::class, 'index']);
        Route::get('/meal-packages/{mealPackage}', [UserMealPackageController::class, 'show']);

        Route::get('/weekly-menus', [UserCalendarController::class, 'weeklyMenus']);

        Route::get('/user-weekly-schedules', [UserWeeklyScheduleController::class, 'index']);
        Route::post('/user-weekly-schedules', [UserWeeklyScheduleController::class, 'store']);
        Route::put('/user-weekly-schedules/{userWeeklySchedule}', [UserWeeklyScheduleController::class, 'update']);

        Route::get('/user-calendar', [UserCalendarController::class, 'index']);
        Route::post('/user-calendar', [UserCalendarController::class, 'store']);
        Route::put('/user-calendar/{userCalendarOverride}', [UserCalendarController::class, 'update']);
        Route::delete('/user-calendar/{userCalendarOverride}', [UserCalendarController::class, 'destroy']);
        Route::get('/user-calendar/month-summary', [UserCalendarController::class, 'monthSummary']);
    });
});

Route::prefix('admin')
    ->middleware(['auth.token', 'role:admin'])
    ->group(function (): void {
        Route::get('/meal-packages', [AdminMealPackageController::class, 'index']);
        Route::post('/meal-packages', [AdminMealPackageController::class, 'store']);
        Route::get('/meal-packages/{mealPackage}', [AdminMealPackageController::class, 'show']);
        Route::put('/meal-packages/{mealPackage}', [AdminMealPackageController::class, 'update']);
        Route::patch('/meal-packages/{mealPackage}/status', [AdminMealPackageController::class, 'updateStatus']);

        Route::get('/weekly-menus', [WeeklyMenuController::class, 'index']);
        Route::post('/weekly-menus', [WeeklyMenuController::class, 'store']);
        Route::get('/weekly-menus/{weeklyMenu}', [WeeklyMenuController::class, 'show']);
        Route::put('/weekly-menus/{weeklyMenu}', [WeeklyMenuController::class, 'update']);
        Route::patch('/weekly-menus/{weeklyMenu}/status', [WeeklyMenuController::class, 'updateStatus']);
    });

Route::prefix('management')
    ->middleware(['auth.token', 'role:admin,manager'])
    ->group(function (): void {
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::get('/users/{user}', [UserManagementController::class, 'show']);
        Route::put('/users/{user}', [UserManagementController::class, 'update']);
        Route::put('/users/{user}/password', [UserManagementController::class, 'updatePassword']);
        Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus']);
    });
