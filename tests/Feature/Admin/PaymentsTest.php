<?php

use App\Livewire\Admin\Payments;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Plan;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use App\Models\WorkspaceWallet;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('payments page shows payment data', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->for($user->workspace)->create();
    $payment = Payment::factory()->completed()->for($plan)->create();

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->assertSee($payment->transaction_id)
        ->assertSee('Payments');
});

test('payments page can filter by status', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->for($user->workspace)->create();
    $completed = Payment::factory()->completed()->for($plan)->create();
    $pending = Payment::factory()->for($plan)->create(['status' => 'pending', 'paid_at' => null]);

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->set('statusFilter', 'completed')
        ->assertSee($completed->transaction_id)
        ->assertDontSee($pending->transaction_id);
});

test('payments page can filter by method', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->for($user->workspace)->create();
    $mpesa = Payment::factory()->completed()->for($plan)->create(['payment_method' => 'mpesa']);
    $airtel = Payment::factory()->completed()->for($plan)->create(['payment_method' => 'airtel']);

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->set('methodFilter', 'mpesa')
        ->assertSee($mpesa->transaction_id)
        ->assertDontSee($airtel->transaction_id);
});

test('payments page can search by phone number', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->for($user->workspace)->create();
    $target = Payment::factory()->completed()->for($plan)->create(['phone_number' => '255712345678']);
    $other = Payment::factory()->completed()->for($plan)->create(['phone_number' => '255798765432']);

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->set('search', '712345')
        ->assertSee('255712345678')
        ->assertDontSee('255798765432');
});

test('payments page shows correct status counts', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->for($user->workspace)->create();
    Payment::factory()->completed()->for($plan)->count(5)->create();
    Payment::factory()->for($plan)->count(3)->create(['status' => 'pending', 'paid_at' => null]);

    $component = Livewire::actingAs($user)->test(Payments::class);

    expect($component->get('completedCount'))->toBe(5);
    expect($component->get('pendingCount'))->toBe(3);
});

test('payments page can submit withdrawal request and reserve balance', function () {
    $user = User::factory()->create();

    WorkspaceWallet::create([
        'workspace_id' => $user->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 12000,
        'pending_withdrawal_balance' => 0,
        'lifetime_credited' => 12000,
        'lifetime_withdrawn' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->set('withdrawalAmount', '5000')
        ->set('withdrawalPhoneNumber', '712345678')
        ->call('submitWithdrawalRequest')
        ->assertSet('withdrawalAmount', '')
        ->assertSet('withdrawalPhoneNumber', '');

    $request = WithdrawalRequest::where('workspace_id', $user->workspace->id)->first();

    expect($request)->not->toBeNull();
    expect($request->status)->toBe('pending');
    expect($request->phone_number)->toBe('255712345678');
    expect((float) $request->amount)->toBe(5000.0);

    $wallet = WorkspaceWallet::where('workspace_id', $user->workspace->id)->first();

    expect((float) $wallet->available_balance)->toBe(7000.0);
    expect((float) $wallet->pending_withdrawal_balance)->toBe(5000.0);

    $hold = WalletTransaction::where('workspace_id', $user->workspace->id)
        ->where('type', 'withdrawal_hold')
        ->where('reference_id', $request->id)
        ->first();

    expect($hold)->not->toBeNull();
    expect((float) $hold->balance_after)->toBe(7000.0);
});

test('payments page rejects withdrawal request above available balance', function () {
    $user = User::factory()->create();

    WorkspaceWallet::create([
        'workspace_id' => $user->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 3000,
        'pending_withdrawal_balance' => 0,
        'lifetime_credited' => 3000,
        'lifetime_withdrawn' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->set('withdrawalAmount', '5000')
        ->set('withdrawalPhoneNumber', '712345678')
        ->call('submitWithdrawalRequest')
        ->assertHasErrors(['withdrawalAmount']);

    expect(WithdrawalRequest::where('workspace_id', $user->workspace->id)->count())->toBe(0);

    $wallet = WorkspaceWallet::where('workspace_id', $user->workspace->id)->first();

    expect((float) $wallet->available_balance)->toBe(3000.0);
    expect((float) $wallet->pending_withdrawal_balance)->toBe(0.0);
});

test('payments page shows recent withdrawal requests', function () {
    $user = User::factory()->create();

    $wallet = WorkspaceWallet::create([
        'workspace_id' => $user->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 10000,
        'pending_withdrawal_balance' => 2000,
        'lifetime_credited' => 12000,
        'lifetime_withdrawn' => 0,
    ]);

    WithdrawalRequest::create([
        'workspace_id' => $user->workspace->id,
        'workspace_wallet_id' => $wallet->id,
        'requested_by' => $user->id,
        'reference' => 'WDRSHOW1234',
        'status' => 'pending',
        'amount' => 2000,
        'currency' => 'TZS',
        'phone_number' => '255712345678',
    ]);

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->assertSee('Withdrawals')
        ->assertSee('WDRSHOW1234')
        ->assertSee('255712345678');
});

test('admin sees pending withdrawal review queue', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    $wallet = WorkspaceWallet::create([
        'workspace_id' => $customer->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 4000,
        'pending_withdrawal_balance' => 6000,
        'lifetime_credited' => 10000,
        'lifetime_withdrawn' => 0,
    ]);

    WithdrawalRequest::create([
        'workspace_id' => $customer->workspace->id,
        'workspace_wallet_id' => $wallet->id,
        'requested_by' => $customer->id,
        'reference' => 'WDRADMIN001',
        'status' => 'pending',
        'amount' => 6000,
        'currency' => 'TZS',
        'phone_number' => '255712345678',
    ]);

    Livewire::actingAs($admin)
        ->test(Payments::class)
        ->assertSee('Pending Withdrawal Review Queue')
        ->assertSee('WDRADMIN001')
        ->assertSee('255712345678');
});

test('admin can approve pending withdrawal request', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    $wallet = WorkspaceWallet::create([
        'workspace_id' => $customer->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 5000,
        'pending_withdrawal_balance' => 7000,
        'lifetime_credited' => 12000,
        'lifetime_withdrawn' => 0,
    ]);

    $request = WithdrawalRequest::create([
        'workspace_id' => $customer->workspace->id,
        'workspace_wallet_id' => $wallet->id,
        'requested_by' => $customer->id,
        'reference' => 'WDRAPPROVE1',
        'status' => 'pending',
        'amount' => 7000,
        'currency' => 'TZS',
        'phone_number' => '255754321000',
    ]);

    Livewire::actingAs($admin)
        ->test(Payments::class)
        ->call('approveWithdrawalRequest', $request->id);

    $request->refresh();
    $wallet->refresh();

    expect($request->status)->toBe('approved');
    expect($request->reviewed_by)->toBe($admin->id);
    expect($request->approved_at)->not->toBeNull();
    expect((float) $wallet->available_balance)->toBe(5000.0);
    expect((float) $wallet->pending_withdrawal_balance)->toBe(7000.0);
});

test('admin can reject pending withdrawal request and release held balance', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    $wallet = WorkspaceWallet::create([
        'workspace_id' => $customer->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 2000,
        'pending_withdrawal_balance' => 8000,
        'lifetime_credited' => 10000,
        'lifetime_withdrawn' => 0,
    ]);

    $request = WithdrawalRequest::create([
        'workspace_id' => $customer->workspace->id,
        'workspace_wallet_id' => $wallet->id,
        'requested_by' => $customer->id,
        'reference' => 'WDRREJECT1',
        'status' => 'pending',
        'amount' => 8000,
        'currency' => 'TZS',
        'phone_number' => '255765432100',
    ]);

    Livewire::actingAs($admin)
        ->test(Payments::class)
        ->call('rejectWithdrawalRequest', $request->id);

    $request->refresh();
    $wallet->refresh();

    expect($request->status)->toBe('rejected');
    expect($request->reviewed_by)->toBe($admin->id);
    expect($request->rejected_at)->not->toBeNull();
    expect((float) $wallet->available_balance)->toBe(10000.0);
    expect((float) $wallet->pending_withdrawal_balance)->toBe(0.0);

    $release = WalletTransaction::where('workspace_id', $customer->workspace->id)
        ->where('type', 'withdrawal_rejected')
        ->where('reference_id', $request->id)
        ->first();

    expect($release)->not->toBeNull();
    expect((float) $release->balance_after)->toBe(10000.0);
});

test('non admin cannot review withdrawal requests', function () {
    $user = User::factory()->create();
    $customer = User::factory()->create();

    $wallet = WorkspaceWallet::create([
        'workspace_id' => $customer->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 3000,
        'pending_withdrawal_balance' => 5000,
        'lifetime_credited' => 8000,
        'lifetime_withdrawn' => 0,
    ]);

    $request = WithdrawalRequest::create([
        'workspace_id' => $customer->workspace->id,
        'workspace_wallet_id' => $wallet->id,
        'requested_by' => $customer->id,
        'reference' => 'WDRNOADMIN1',
        'status' => 'pending',
        'amount' => 5000,
        'currency' => 'TZS',
        'phone_number' => '255700000001',
    ]);

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->assertDontSee('Pending Withdrawal Review Queue')
        ->call('approveWithdrawalRequest', $request->id);

    $request->refresh();
    $wallet->refresh();

    expect($request->status)->toBe('pending');
    expect($request->reviewed_by)->toBeNull();
    expect((float) $wallet->available_balance)->toBe(3000.0);
    expect((float) $wallet->pending_withdrawal_balance)->toBe(5000.0);
});

test('admin can send approved withdrawal payout and mark request paid on clickpesa success', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    PaymentGatewaySetting::create([
        'workspace_id' => null,
        'gateway' => 'clickpesa',
        'display_name' => 'ClickPesa',
        'is_active' => true,
        'config' => ['client_id' => 'TEST', 'api_key' => 'key'],
    ]);

    Http::fake([
        '*/generate-token' => Http::response(['success' => true, 'token' => 'Bearer test']),
        '*/create-mobile-money-payout' => Http::response([
            'id' => 'PO123',
            'status' => 'SUCCESS',
            'channel' => 'MOBILE MONEY',
            'channelProvider' => 'MPESA TANZANIA',
            'fee' => '50.00',
            'beneficiary' => ['accountNumber' => '255754321000'],
        ]),
    ]);

    $wallet = WorkspaceWallet::create([
        'workspace_id' => $customer->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 5000,
        'pending_withdrawal_balance' => 7000,
        'lifetime_credited' => 12000,
        'lifetime_withdrawn' => 0,
    ]);

    $request = WithdrawalRequest::create([
        'workspace_id' => $customer->workspace->id,
        'workspace_wallet_id' => $wallet->id,
        'requested_by' => $customer->id,
        'reviewed_by' => $admin->id,
        'reference' => 'WDRPAYOUT1',
        'status' => 'approved',
        'amount' => 7000,
        'currency' => 'TZS',
        'phone_number' => '255754321000',
        'approved_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(Payments::class)
        ->assertSee('Withdrawal Payout Queue')
        ->assertSee('WDRPAYOUT1')
        ->call('sendWithdrawalPayout', $request->id);

    $request->refresh();
    $wallet->refresh();

    expect($request->status)->toBe('paid');
    expect($request->paid_at)->not->toBeNull();
    expect(data_get($request->meta, 'payout_id'))->toBe('PO123');
    expect(data_get($request->meta, 'payout_status'))->toBe('SUCCESS');
    expect((float) $wallet->available_balance)->toBe(5000.0);
    expect((float) $wallet->pending_withdrawal_balance)->toBe(0.0);
    expect((float) $wallet->lifetime_withdrawn)->toBe(7000.0);

    $transaction = WalletTransaction::where('workspace_id', $customer->workspace->id)
        ->where('type', 'withdrawal_paid')
        ->where('reference_id', $request->id)
        ->first();

    expect($transaction)->not->toBeNull();
    expect((float) $transaction->balance_before)->toBe(5000.0);
    expect((float) $transaction->balance_after)->toBe(5000.0);
});

test('admin payout marks approved withdrawal as processing when clickpesa authorizes it', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    PaymentGatewaySetting::create([
        'workspace_id' => null,
        'gateway' => 'clickpesa',
        'display_name' => 'ClickPesa',
        'is_active' => true,
        'config' => ['client_id' => 'TEST', 'api_key' => 'key'],
    ]);

    Http::fake([
        '*/generate-token' => Http::response(['success' => true, 'token' => 'Bearer test']),
        '*/create-mobile-money-payout' => Http::response([
            'id' => 'PO124',
            'status' => 'AUTHORIZED',
            'channel' => 'MOBILE MONEY',
            'channelProvider' => 'TIGO PESA',
        ]),
    ]);

    $wallet = WorkspaceWallet::create([
        'workspace_id' => $customer->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 2000,
        'pending_withdrawal_balance' => 6000,
        'lifetime_credited' => 8000,
        'lifetime_withdrawn' => 0,
    ]);

    $request = WithdrawalRequest::create([
        'workspace_id' => $customer->workspace->id,
        'workspace_wallet_id' => $wallet->id,
        'requested_by' => $customer->id,
        'reviewed_by' => $admin->id,
        'reference' => 'WDRPAYOUT2',
        'status' => 'approved',
        'amount' => 6000,
        'currency' => 'TZS',
        'phone_number' => '255712300000',
        'approved_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(Payments::class)
        ->call('sendWithdrawalPayout', $request->id);

    $request->refresh();
    $wallet->refresh();

    expect($request->status)->toBe('processing');
    expect($request->paid_at)->toBeNull();
    expect(data_get($request->meta, 'payout_status'))->toBe('AUTHORIZED');
    expect((float) $wallet->available_balance)->toBe(2000.0);
    expect((float) $wallet->pending_withdrawal_balance)->toBe(6000.0);
    expect((float) $wallet->lifetime_withdrawn)->toBe(0.0);
    expect(WalletTransaction::where('workspace_id', $customer->workspace->id)->where('type', 'withdrawal_paid')->count())->toBe(0);
});

test('admin payout marks withdrawal failed when clickpesa call fails', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    PaymentGatewaySetting::create([
        'workspace_id' => null,
        'gateway' => 'clickpesa',
        'display_name' => 'ClickPesa',
        'is_active' => true,
        'config' => ['client_id' => 'TEST', 'api_key' => 'key'],
    ]);

    Http::fake([
        '*/generate-token' => Http::response(['success' => true, 'token' => 'Bearer test']),
        '*/create-mobile-money-payout' => Http::response(['message' => 'Insufficient float'], 422),
    ]);

    $wallet = WorkspaceWallet::create([
        'workspace_id' => $customer->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 1000,
        'pending_withdrawal_balance' => 4000,
        'lifetime_credited' => 5000,
        'lifetime_withdrawn' => 0,
    ]);

    $request = WithdrawalRequest::create([
        'workspace_id' => $customer->workspace->id,
        'workspace_wallet_id' => $wallet->id,
        'requested_by' => $customer->id,
        'reviewed_by' => $admin->id,
        'reference' => 'WDRPAYOUT3',
        'status' => 'approved',
        'amount' => 4000,
        'currency' => 'TZS',
        'phone_number' => '255700123456',
        'approved_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(Payments::class)
        ->call('sendWithdrawalPayout', $request->id);

    $request->refresh();
    $wallet->refresh();

    expect($request->status)->toBe('failed');
    expect($request->paid_at)->toBeNull();
    expect($request->failure_reason)->toContain('Mobile money payout failed');
    expect((float) $wallet->available_balance)->toBe(1000.0);
    expect((float) $wallet->pending_withdrawal_balance)->toBe(4000.0);
    expect((float) $wallet->lifetime_withdrawn)->toBe(0.0);
});

test('admin can refresh processing withdrawal payout and mark request paid on clickpesa success', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    PaymentGatewaySetting::create([
        'workspace_id' => null,
        'gateway' => 'clickpesa',
        'display_name' => 'ClickPesa',
        'is_active' => true,
        'config' => ['client_id' => 'TEST', 'api_key' => 'key'],
    ]);

    Http::fake([
        '*/generate-token' => Http::response(['success' => true, 'token' => 'Bearer test']),
        '*/payouts/*' => Http::response([
            [
                'id' => 'PO125',
                'orderReference' => 'WDRPAYOUT4',
                'status' => 'SUCCESS',
                'channel' => 'MOBILE MONEY',
                'channelProvider' => 'MPESA TANZANIA',
                'fee' => '50.00',
                'beneficiary' => ['beneficiaryMobileNumber' => '255754321000'],
            ],
        ]),
    ]);

    $wallet = WorkspaceWallet::create([
        'workspace_id' => $customer->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 3000,
        'pending_withdrawal_balance' => 4000,
        'lifetime_credited' => 7000,
        'lifetime_withdrawn' => 0,
    ]);

    $request = WithdrawalRequest::create([
        'workspace_id' => $customer->workspace->id,
        'workspace_wallet_id' => $wallet->id,
        'requested_by' => $customer->id,
        'reviewed_by' => $admin->id,
        'reference' => 'WDRPAYOUT4',
        'status' => 'processing',
        'amount' => 4000,
        'currency' => 'TZS',
        'phone_number' => '255754321000',
        'approved_at' => now(),
        'meta' => ['payout_id' => 'PO125', 'payout_status' => 'AUTHORIZED'],
    ]);

    Livewire::actingAs($admin)
        ->test(Payments::class)
        ->assertSee('Withdrawal Payout Queue')
        ->assertSee('Refresh Status')
        ->call('refreshWithdrawalPayoutStatus', $request->id);

    $request->refresh();
    $wallet->refresh();

    expect($request->status)->toBe('paid');
    expect($request->paid_at)->not->toBeNull();
    expect(data_get($request->meta, 'payout_status'))->toBe('SUCCESS');
    expect((float) $wallet->available_balance)->toBe(3000.0);
    expect((float) $wallet->pending_withdrawal_balance)->toBe(0.0);
    expect((float) $wallet->lifetime_withdrawn)->toBe(4000.0);

    $transaction = WalletTransaction::where('workspace_id', $customer->workspace->id)
        ->where('type', 'withdrawal_paid')
        ->where('reference_id', $request->id)
        ->first();

    expect($transaction)->not->toBeNull();
});

test('admin can refresh processing withdrawal payout and mark request failed on clickpesa reversal', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    PaymentGatewaySetting::create([
        'workspace_id' => null,
        'gateway' => 'clickpesa',
        'display_name' => 'ClickPesa',
        'is_active' => true,
        'config' => ['client_id' => 'TEST', 'api_key' => 'key'],
    ]);

    Http::fake([
        '*/generate-token' => Http::response(['success' => true, 'token' => 'Bearer test']),
        '*/payouts/*' => Http::response([
            [
                'id' => 'PO126',
                'orderReference' => 'WDRPAYOUT5',
                'status' => 'REVERSED',
                'channel' => 'MOBILE MONEY',
                'channelProvider' => 'AIRTEL MONEY',
            ],
        ]),
    ]);

    $wallet = WorkspaceWallet::create([
        'workspace_id' => $customer->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 2000,
        'pending_withdrawal_balance' => 5000,
        'lifetime_credited' => 7000,
        'lifetime_withdrawn' => 0,
    ]);

    $request = WithdrawalRequest::create([
        'workspace_id' => $customer->workspace->id,
        'workspace_wallet_id' => $wallet->id,
        'requested_by' => $customer->id,
        'reviewed_by' => $admin->id,
        'reference' => 'WDRPAYOUT5',
        'status' => 'processing',
        'amount' => 5000,
        'currency' => 'TZS',
        'phone_number' => '255711111111',
        'approved_at' => now(),
        'meta' => ['payout_id' => 'PO126', 'payout_status' => 'PROCESSING'],
    ]);

    Livewire::actingAs($admin)
        ->test(Payments::class)
        ->call('refreshWithdrawalPayoutStatus', $request->id);

    $request->refresh();
    $wallet->refresh();

    expect($request->status)->toBe('failed');
    expect($request->paid_at)->toBeNull();
    expect($request->failure_reason)->toBe('ClickPesa payout returned REVERSED.');
    expect(data_get($request->meta, 'payout_status'))->toBe('REVERSED');
    expect((float) $wallet->available_balance)->toBe(2000.0);
    expect((float) $wallet->pending_withdrawal_balance)->toBe(5000.0);
    expect((float) $wallet->lifetime_withdrawn)->toBe(0.0);
});

test('non admin cannot trigger withdrawal payout', function () {
    $user = User::factory()->create();
    $customer = User::factory()->create();

    $wallet = WorkspaceWallet::create([
        'workspace_id' => $customer->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 2000,
        'pending_withdrawal_balance' => 3000,
        'lifetime_credited' => 5000,
        'lifetime_withdrawn' => 0,
    ]);

    $request = WithdrawalRequest::create([
        'workspace_id' => $customer->workspace->id,
        'workspace_wallet_id' => $wallet->id,
        'requested_by' => $customer->id,
        'reference' => 'WDRNOPAYOUT',
        'status' => 'approved',
        'amount' => 3000,
        'currency' => 'TZS',
        'phone_number' => '255700000111',
    ]);

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->assertDontSee('Withdrawal Payout Queue')
        ->call('sendWithdrawalPayout', $request->id);

    $request->refresh();
    $wallet->refresh();

    expect($request->status)->toBe('approved');
    expect((float) $wallet->pending_withdrawal_balance)->toBe(3000.0);
    expect((float) $wallet->lifetime_withdrawn)->toBe(0.0);
});

test('non admin cannot refresh withdrawal payout status', function () {
    $user = User::factory()->create();
    $customer = User::factory()->create();

    $wallet = WorkspaceWallet::create([
        'workspace_id' => $customer->workspace->id,
        'currency' => 'TZS',
        'available_balance' => 2000,
        'pending_withdrawal_balance' => 3000,
        'lifetime_credited' => 5000,
        'lifetime_withdrawn' => 0,
    ]);

    $request = WithdrawalRequest::create([
        'workspace_id' => $customer->workspace->id,
        'workspace_wallet_id' => $wallet->id,
        'requested_by' => $customer->id,
        'reference' => 'WDRNOREFRESH',
        'status' => 'processing',
        'amount' => 3000,
        'currency' => 'TZS',
        'phone_number' => '255700000111',
    ]);

    Livewire::actingAs($user)
        ->test(Payments::class)
        ->assertDontSee('Withdrawal Payout Queue')
        ->call('refreshWithdrawalPayoutStatus', $request->id);

    $request->refresh();
    $wallet->refresh();

    expect($request->status)->toBe('processing');
    expect((float) $wallet->pending_withdrawal_balance)->toBe(3000.0);
    expect((float) $wallet->lifetime_withdrawn)->toBe(0.0);
});
