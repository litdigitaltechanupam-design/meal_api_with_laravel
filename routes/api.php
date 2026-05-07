<?php

use App\Http\Controllers\Api\Admin\MealPackageController as AdminMealPackageController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\WeeklyMenuController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Admin\WalletController as AdminWalletController;
use App\Http\Controllers\Api\Customer\AddressController;
use App\Http\Controllers\Api\Customer\CalendarController;
use App\Http\Controllers\Api\Customer\MealPackageController as CustomerMealPackageController;
use App\Http\Controllers\Api\Customer\WalletController as CustomerWalletController;
use App\Http\Controllers\Api\Customer\WalletPaymentController as CustomerWalletPaymentController;
use App\Http\Controllers\Api\Customer\WeeklyScheduleController;
use App\Http\Controllers\Api\Payment\WalletPaymentCallbackController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth.token')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
    });
});

Route::prefix('customer')
    ->middleware(['auth.token', 'role:customer'])
    ->group(function (): void {
        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::get('/addresses/{address}', [AddressController::class, 'show']);
        Route::put('/addresses/{address}', [AddressController::class, 'update']);
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);

        Route::get('/meal-packages', [CustomerMealPackageController::class, 'index']);
        Route::get('/meal-packages/{mealPackage}', [CustomerMealPackageController::class, 'show']);

        Route::get('/weekly-menus', [CalendarController::class, 'weeklyMenus']);

        Route::get('/weekly-schedules', [WeeklyScheduleController::class, 'index']);
        Route::post('/weekly-schedules', [WeeklyScheduleController::class, 'store']);
        Route::put('/weekly-schedules/{userWeeklySchedule}', [WeeklyScheduleController::class, 'update']);

        Route::get('/calendar', [CalendarController::class, 'index']);
        Route::post('/calendar', [CalendarController::class, 'store']);
        Route::put('/calendar/{userCalendarOverride}', [CalendarController::class, 'update']);
        Route::delete('/calendar/{userCalendarOverride}', [CalendarController::class, 'destroy']);
        Route::get('/calendar/month-summary', [CalendarController::class, 'monthSummary']);

        Route::get('/wallet', [CustomerWalletController::class, 'show']);
        Route::get('/wallet/transactions', [CustomerWalletController::class, 'transactions']);
        Route::get('/wallet/payment-requests', [CustomerWalletPaymentController::class, 'index']);
        Route::post('/wallet/payment-requests', [CustomerWalletPaymentController::class, 'store']);
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

        Route::get('/wallets', [AdminWalletController::class, 'index']);
        Route::get('/wallets/{wallet}', [AdminWalletController::class, 'show']);
        Route::get('/wallets/{wallet}/transactions', [AdminWalletController::class, 'transactions']);
        Route::post('/wallets/{wallet}/credit', [AdminWalletController::class, 'credit']);
        Route::post('/wallets/{wallet}/debit', [AdminWalletController::class, 'debit']);
    });

Route::prefix('admin')
    ->middleware(['auth.token', 'role:admin'])
    ->group(function (): void {
        Route::patch('/wallets/{wallet}/status', [AdminWalletController::class, 'updateStatus']);
    });

Route::prefix('payment')->group(function (): void {
    Route::post('/wallet/callback', [WalletPaymentCallbackController::class, 'store']);
});
