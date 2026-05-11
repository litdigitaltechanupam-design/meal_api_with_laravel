<?php

use App\Http\Controllers\Api\Admin\MealPackageController as AdminMealPackageController;
use App\Http\Controllers\Api\Admin\DeliveryController;
use App\Http\Controllers\Api\Admin\AreaController as ManagementAreaController;
use App\Http\Controllers\Api\Admin\DashboardController as ManagementDashboardController;
use App\Http\Controllers\Api\Admin\DeliverymanAreaController;
use App\Http\Controllers\Api\Admin\DeliverymanSubareaController;
use App\Http\Controllers\Api\Admin\MealOrderController as AdminMealOrderController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\ReportController as ManagementReportController;
use App\Http\Controllers\Api\Admin\SubareaController as ManagementSubareaController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\WeeklyMenuController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Admin\WalletController as AdminWalletController;
use App\Http\Controllers\Api\Customer\AddressController;
use App\Http\Controllers\Api\Customer\AreaController;
use App\Http\Controllers\Api\Customer\CalendarController;
use App\Http\Controllers\Api\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Api\Customer\MealPackageController as CustomerMealPackageController;
use App\Http\Controllers\Api\Customer\MealOrderController as CustomerMealOrderController;
use App\Http\Controllers\Api\Customer\NotificationController as CustomerNotificationController;
use App\Http\Controllers\Api\Customer\ReportController as CustomerReportController;
use App\Http\Controllers\Api\Customer\WalletController as CustomerWalletController;
use App\Http\Controllers\Api\Customer\WalletPaymentController as CustomerWalletPaymentController;
use App\Http\Controllers\Api\Customer\WeeklyScheduleController;
use App\Http\Controllers\Api\Deliveryman\DeliveryController as DeliverymanDeliveryController;
use App\Http\Controllers\Api\Deliveryman\DashboardController as DeliverymanDashboardController;
use App\Http\Controllers\Api\Deliveryman\NotificationController as DeliverymanNotificationController;
use App\Http\Controllers\Api\Deliveryman\ReportController as DeliverymanReportController;
use App\Http\Controllers\Api\Admin\NotificationController as ManagementNotificationController;
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
        Route::get('/areas', [AreaController::class, 'index']);
        Route::get('/subareas', [AreaController::class, 'subareas']);

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

        Route::get('/meal-orders', [CustomerMealOrderController::class, 'index']);
        Route::get('/meal-orders/{mealOrder}', [CustomerMealOrderController::class, 'show']);

        Route::get('/dashboard/summary', [CustomerDashboardController::class, 'summary']);
        Route::get('/reports/meal-orders', [CustomerReportController::class, 'mealOrders']);
        Route::get('/reports/wallets', [CustomerReportController::class, 'wallets']);
        Route::get('/reports/refunds', [CustomerReportController::class, 'refunds']);

        Route::get('/notifications', [CustomerNotificationController::class, 'index']);
        Route::get('/notifications/{notification}', [CustomerNotificationController::class, 'show']);
        Route::patch('/notifications/{notification}/read', [CustomerNotificationController::class, 'markAsRead']);
        Route::patch('/notifications/read-all', [CustomerNotificationController::class, 'markAllAsRead']);
    });

Route::prefix('deliveryman')
    ->middleware(['auth.token', 'role:deliveryman'])
    ->group(function (): void {
        Route::get('/deliveries', [DeliverymanDeliveryController::class, 'index']);
        Route::get('/deliveries/today', [DeliverymanDeliveryController::class, 'today']);
        Route::get('/deliveries/{delivery}', [DeliverymanDeliveryController::class, 'show']);
        Route::patch('/deliveries/{delivery}/status', [DeliverymanDeliveryController::class, 'updateStatus']);

        Route::get('/dashboard/summary', [DeliverymanDashboardController::class, 'summary']);
        Route::get('/reports/deliveries', [DeliverymanReportController::class, 'deliveries']);

        Route::get('/notifications', [DeliverymanNotificationController::class, 'index']);
        Route::get('/notifications/{notification}', [DeliverymanNotificationController::class, 'show']);
        Route::patch('/notifications/{notification}/read', [DeliverymanNotificationController::class, 'markAsRead']);
        Route::patch('/notifications/read-all', [DeliverymanNotificationController::class, 'markAllAsRead']);
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

        Route::get('/settings', [SettingController::class, 'index']);
        Route::put('/settings', [SettingController::class, 'update']);

        Route::get('/meal-orders', [AdminMealOrderController::class, 'index']);
        Route::post('/meal-orders/generate', [AdminMealOrderController::class, 'generate']);
        Route::get('/meal-orders/{mealOrder}', [AdminMealOrderController::class, 'show']);
        Route::patch('/meal-orders/{mealOrder}/status', [AdminMealOrderController::class, 'updateStatus']);

        Route::get('/deliveries', [DeliveryController::class, 'index']);
        Route::get('/deliveries/{delivery}', [DeliveryController::class, 'show']);
        Route::patch('/deliveries/{delivery}/status', [DeliveryController::class, 'updateStatus']);
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

        Route::get('/areas', [ManagementAreaController::class, 'index']);
        Route::post('/areas', [ManagementAreaController::class, 'store']);
        Route::get('/areas/{area}', [ManagementAreaController::class, 'show']);
        Route::put('/areas/{area}', [ManagementAreaController::class, 'update']);
        Route::patch('/areas/{area}/status', [ManagementAreaController::class, 'updateStatus']);

        Route::get('/subareas', [ManagementSubareaController::class, 'index']);
        Route::post('/subareas', [ManagementSubareaController::class, 'store']);
        Route::get('/subareas/{subarea}', [ManagementSubareaController::class, 'show']);
        Route::put('/subareas/{subarea}', [ManagementSubareaController::class, 'update']);
        Route::patch('/subareas/{subarea}/status', [ManagementSubareaController::class, 'updateStatus']);

        Route::get('/deliveryman-areas', [DeliverymanAreaController::class, 'index']);
        Route::post('/deliveryman-areas', [DeliverymanAreaController::class, 'store']);
        Route::put('/deliveryman-areas/{deliverymanArea}', [DeliverymanAreaController::class, 'update']);
        Route::delete('/deliveryman-areas/{deliverymanArea}', [DeliverymanAreaController::class, 'destroy']);

        Route::get('/deliveryman-subareas', [DeliverymanSubareaController::class, 'index']);
        Route::post('/deliveryman-subareas', [DeliverymanSubareaController::class, 'store']);
        Route::put('/deliveryman-subareas/{deliverymanSubarea}', [DeliverymanSubareaController::class, 'update']);
        Route::delete('/deliveryman-subareas/{deliverymanSubarea}', [DeliverymanSubareaController::class, 'destroy']);

        Route::post('/meal-orders/{mealOrder}/refund', [AdminMealOrderController::class, 'refund']);
        Route::patch('/deliveries/{delivery}/assign', [DeliveryController::class, 'assign']);

        Route::get('/dashboard/summary', [ManagementDashboardController::class, 'summary']);
        Route::get('/reports/meal-orders', [ManagementReportController::class, 'mealOrders']);
        Route::get('/reports/deliveries', [ManagementReportController::class, 'deliveries']);
        Route::get('/reports/wallets', [ManagementReportController::class, 'wallets']);
        Route::get('/reports/refunds', [ManagementReportController::class, 'refunds']);

        Route::get('/notifications', [ManagementNotificationController::class, 'index']);
        Route::get('/notifications/{notification}', [ManagementNotificationController::class, 'show']);
        Route::patch('/notifications/{notification}/read', [ManagementNotificationController::class, 'markAsRead']);
        Route::patch('/notifications/read-all', [ManagementNotificationController::class, 'markAllAsRead']);
    });

Route::prefix('admin')
    ->middleware(['auth.token', 'role:admin'])
    ->group(function (): void {
        Route::patch('/wallets/{wallet}/status', [AdminWalletController::class, 'updateStatus']);
    });

Route::prefix('payment')->group(function (): void {
    Route::post('/wallet/callback', [WalletPaymentCallbackController::class, 'store']);
});
