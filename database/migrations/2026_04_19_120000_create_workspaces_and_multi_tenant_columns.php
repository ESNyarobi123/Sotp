<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('brand_name');
            $table->string('public_slug', 96)->unique();
            $table->string('omada_site_id')->nullable();
            $table->string('provisioning_status', 32)->default('pending');
            $table->text('provisioning_error')->nullable();
            $table->timestamp('devices_last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('payment_gateway_settings', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('payment_gateway_settings', function (Blueprint $table) {
            $table->dropUnique(['gateway']);
        });

        Schema::table('payment_gateway_settings', function (Blueprint $table) {
            $table->unique(['workspace_id', 'gateway']);
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique(['ap_mac']);
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->unique(['workspace_id', 'ap_mac']);
        });

        $this->backfillWorkspaces();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique(['workspace_id', 'ap_mac']);
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->unique('ap_mac');
        });

        Schema::table('payment_gateway_settings', function (Blueprint $table) {
            $table->dropUnique(['workspace_id', 'gateway']);
        });

        Schema::table('payment_gateway_settings', function (Blueprint $table) {
            $table->unique('gateway');
        });

        Schema::table('payment_gateway_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });

        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });

        Schema::dropIfExists('workspaces');
    }

    private function backfillWorkspaces(): void
    {
        $users = User::query()->orderBy('id')->get();

        foreach ($users as $user) {
            $slug = Str::slug($user->name).'-'.Str::lower(Str::random(4));
            while (Workspace::where('public_slug', $slug)->exists()) {
                $slug = Str::slug($user->name).'-'.Str::lower(Str::random(6));
            }

            Workspace::create([
                'user_id' => $user->id,
                'brand_name' => $user->name."'s WiFi",
                'public_slug' => $slug,
                'omada_site_id' => null,
                'provisioning_status' => 'ready',
                'provisioning_error' => null,
            ]);
        }

        $defaultWorkspace = Workspace::query()->orderBy('id')->first();

        if (! $defaultWorkspace) {
            return;
        }

        DB::table('plans')->whereNull('workspace_id')->update(['workspace_id' => $defaultWorkspace->id]);
        DB::table('devices')->whereNull('workspace_id')->update(['workspace_id' => $defaultWorkspace->id]);
        DB::table('guest_sessions')->whereNull('workspace_id')->update(['workspace_id' => $defaultWorkspace->id]);
        DB::table('payments')->whereNull('workspace_id')->update(['workspace_id' => $defaultWorkspace->id]);
        DB::table('payment_gateway_settings')->whereNull('workspace_id')->update(['workspace_id' => $defaultWorkspace->id]);
    }
};
