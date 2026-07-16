<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Illuminate\Support\Facades\Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Public Customer Feedback
Route::get('/feedback/{uuid}', [\App\Http\Controllers\FeedbackController::class, 'show'])->name('feedback.show');
Route::post('/feedback/{uuid}', [\App\Http\Controllers\FeedbackController::class, 'store'])->name('feedback.store');

// Survey link health check (no customer data beyond a sample URL)
Route::get('/feedback-health', function () {
    $sample = \App\Models\Customer::whereNotNull('survey_uuid')
        ->where('survey_uuid', '!=', '')
        ->value('survey_uuid');

    return response()->json([
        'ok' => true,
        'feedback_route' => 'registered',
        'customers_with_survey_uuid' => \App\Models\Customer::whereNotNull('survey_uuid')->where('survey_uuid', '!=', '')->count(),
        'sample_test_url' => $sample ? url('/feedback/' . $sample) : null,
        'hint' => $sample
            ? 'Open sample_test_url in a browser. If that page loads the survey form, links are healthy.'
            : 'No survey_uuid in this database. Open a customer in CRM or send a survey to generate one.',
    ]);
});

// WhatsApp orders table health check
Route::get('/whatsapp-orders-health', function () {
    $exists = \Illuminate\Support\Facades\Schema::hasTable('whatsapp_orders');
    $count = $exists ? \App\Models\WhatsAppOrder::count() : 0;

    return response()->json([
        'ok' => $exists,
        'table' => 'whatsapp_orders',
        'table_exists' => $exists,
        'orders_count' => $count,
        'hint' => $exists
            ? 'Table is ready. New WhatsApp /order completions should appear in CRM.'
            : 'Run: php artisan migrate --force',
    ]);
});

// Privacy Policy
Route::get('/privacy-policy', function () {
    return view('privacy_policy');
})->name('privacy_policy');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/statistics', [ReportController::class, 'statistics'])->name('reports.statistics');
    Route::get('/reports/surveys', [ReportController::class, 'surveys'])->name('reports.surveys');
    Route::post('/reports/login-logs/{id}/logout', [ReportController::class, 'forceLogout'])->name('reports.force_logout');
    Route::get('/customers/follow-ups', [CustomerController::class, 'followUps'])->name('customers.follow_ups');
    Route::get('/customers/sales-records', [CustomerController::class, 'salesRecords'])->name('customers.sales_records');
    Route::get('/customers/sms-reminders', [CustomerController::class, 'smsReminders'])->name('customers.sms_reminders');
    Route::get('/customers/bulk-delegate', [CustomerController::class, 'bulkDelegateView'])->name('customers.bulk_delegate');
    Route::post('/customers/bulk-delegate', [CustomerController::class, 'processBulkDelegate'])->name('customers.process_bulk_delegate');
    Route::post('/customers/sms-reminders/send', [CustomerController::class, 'sendBulkSms'])->name('customers.send_bulk_sms');
    Route::patch('/customers/{customer}/quick-update', [CustomerController::class, 'quickUpdate'])->name('customers.quick_update');
    Route::patch('/customers/{customer}/delegate', [CustomerController::class, 'delegate'])->name('customers.delegate');
    Route::post('/customers/send-all-reminders', [CustomerController::class, 'sendAllReminders'])->name('customers.send_all_reminders');
    Route::post('/customers/{customer}/send-sms', [CustomerController::class, 'sendSms'])->name('customers.send_sms');
    Route::post('/customers/{customer}/send-survey', [CustomerController::class, 'sendSurvey'])->name('customers.send_survey');
    Route::post('/customers/{customer}/new-transaction', [CustomerController::class, 'newTransaction'])->name('customers.new_transaction');
    Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');
    
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    
    Route::post('/staff/{user}/reset-password', [\App\Http\Controllers\StaffController::class, 'resetPassword'])->name('staff.reset_password');
    Route::post('/staff/{user}/toggle-status', [\App\Http\Controllers\StaffController::class, 'toggleStatus'])->name('staff.toggle_status');
    Route::post('/staff/{user}/adjust-kpi', [\App\Http\Controllers\StaffController::class, 'adjustKpi'])->name('staff.adjust_kpi');
    
    Route::post('/customers/check-duplicate', [\App\Http\Controllers\CustomerController::class, 'checkDuplicate'])->name('customers.check_duplicate');
    Route::get('/customers/import', [\App\Http\Controllers\CustomerController::class, 'import'])->name('customers.import');
    Route::post('/customers/import', [\App\Http\Controllers\CustomerController::class, 'processImport'])->name('customers.process_import');
    Route::get('/customers/download-template', [\App\Http\Controllers\CustomerController::class, 'downloadTemplate'])->name('customers.download_template');
    Route::resource('customers', \App\Http\Controllers\CustomerController::class);
    Route::resource('staff', \App\Http\Controllers\StaffController::class);
    
    // Campaigns
    Route::get('/campaigns/calendar', [\App\Http\Controllers\CampaignController::class, 'calendar'])->name('campaigns.calendar');
    Route::get('/campaigns/events', [\App\Http\Controllers\CampaignController::class, 'events'])->name('campaigns.events');
    Route::resource('campaigns', \App\Http\Controllers\CampaignController::class);
    
    // Branch Management
    Route::post('/branches/{branch}/toggle-status', [\App\Http\Controllers\BranchController::class, 'toggleStatus'])->name('branches.toggle_status');
    Route::resource('branches', \App\Http\Controllers\BranchController::class);

    // Security Audit Logs
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit_logs.index');

    // KPI & Performance
    Route::group(['prefix' => 'kpi', 'as' => 'kpi.'], function() {
        Route::get('/leaderboard', [\App\Http\Controllers\KpiController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/guide', [\App\Http\Controllers\KpiController::class, 'guide'])->name('guide');
        Route::get('/activities', [\App\Http\Controllers\KpiController::class, 'activities'])->name('activities');
        Route::get('/attendance', [\App\Http\Controllers\KpiController::class, 'attendance'])->name('attendance');
        Route::get('/officer/{id}', [\App\Http\Controllers\KpiController::class, 'officerProfile'])->name('officer_profile');
        Route::get('/comparison', [\App\Http\Controllers\KpiController::class, 'branchComparison'])->name('comparison');
        Route::post('/note', [\App\Http\Controllers\KpiController::class, 'storeNote'])->name('store_note');
        Route::delete('/note/{id}', [\App\Http\Controllers\KpiController::class, 'deleteNote'])->name('delete_note');
    });

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read_all');
    Route::get('/notifications/fetch', [\App\Http\Controllers\NotificationController::class, 'fetchUnread'])->name('notifications.fetch');

    // Supplier Management
    Route::post('/suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle_status');
    Route::resource('suppliers', SupplierController::class);

    // WhatsApp Live Chat
    Route::get('/whatsapp/chat', [\App\Http\Controllers\WhatsAppChatController::class, 'index'])->name('whatsapp.chat');
    Route::get('/whatsapp/chat/threads-data', [\App\Http\Controllers\WhatsAppChatController::class, 'threadsData'])->name('whatsapp.chat.threads_data');
    Route::get('/whatsapp/chat/search-customers', [\App\Http\Controllers\WhatsAppChatController::class, 'searchCustomers'])->name('whatsapp.chat.search_customers');
    Route::get('/whatsapp/chat/thread/{phone}', [\App\Http\Controllers\WhatsAppChatController::class, 'thread'])->name('whatsapp.chat.thread');
    Route::post('/whatsapp/chat/send', [\App\Http\Controllers\WhatsAppChatController::class, 'sendMessage'])->name('whatsapp.chat.send');
    Route::post('/whatsapp/chat/toggle-bot/{phone}', [\App\Http\Controllers\WhatsAppChatController::class, 'toggleBot'])->name('whatsapp.chat.toggle_bot');
    Route::post('/whatsapp/chat/mark-read/{phone}', [\App\Http\Controllers\WhatsAppChatController::class, 'markRead'])->name('whatsapp.chat.mark_read');
    Route::post('/whatsapp/chat/claim/{phone}', [\App\Http\Controllers\WhatsAppChatController::class, 'claimChat'])->name('whatsapp.chat.claim');
    Route::post('/whatsapp/chat/transfer/{phone}', [\App\Http\Controllers\WhatsAppChatController::class, 'transferChat'])->name('whatsapp.chat.transfer');

    // WhatsApp Bot Orders
    Route::get('/whatsapp/orders', [\App\Http\Controllers\WhatsAppOrderController::class, 'index'])->name('whatsapp.orders.index');
    Route::get('/whatsapp/orders/{orderNumber}', [\App\Http\Controllers\WhatsAppOrderController::class, 'show'])->name('whatsapp.orders.show');
    Route::patch('/whatsapp/orders/{orderNumber}/status', [\App\Http\Controllers\WhatsAppOrderController::class, 'updateStatus'])->name('whatsapp.orders.update_status');
});
