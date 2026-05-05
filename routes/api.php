<?php

use App\Http\Controllers\Api\Admin\MealPackageController as AdminMealPackageController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\WeeklyMenuController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\MealPackageController as UserMealPackageController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Auth\UserMealCalendarController;
use App\Http\Controllers\Api\Auth\UserAddressController;
use App\Http\Controllers\Api\Auth\UserWeeklyMealScheduleController;
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

        Route::get('/weekly-menu', [UserMealCalendarController::class, 'weeklyMenu']);

        Route::get('/weekly-schedules', [UserWeeklyMealScheduleController::class, 'index']);
        Route::post('/weekly-schedules', [UserWeeklyMealScheduleController::class, 'store']);
        Route::put('/weekly-schedules/{weeklySchedule}', [UserWeeklyMealScheduleController::class, 'update']);

        Route::get('/meal-calendar', [UserMealCalendarController::class, 'index']);
        Route::post('/meal-calendar', [UserMealCalendarController::class, 'store']);
        Route::put('/meal-calendar/{calendarOverride}', [UserMealCalendarController::class, 'update']);
        Route::delete('/meal-calendar/{calendarOverride}', [UserMealCalendarController::class, 'destroy']);
        Route::get('/meal-calendar/month-summary', [UserMealCalendarController::class, 'month']);
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

        Route::get('/weekly-menu', [WeeklyMenuController::class, 'index']);
        Route::post('/weekly-menu', [WeeklyMenuController::class, 'store']);
        Route::get('/weekly-menu/{weeklyMenuItem}', [WeeklyMenuController::class, 'show']);
        Route::put('/weekly-menu/{weeklyMenuItem}', [WeeklyMenuController::class, 'update']);
        Route::patch('/weekly-menu/{weeklyMenuItem}/status', [WeeklyMenuController::class, 'updateStatus']);
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
