<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional per-business branding, shown on PDF/CSV exports and email reports.
 *
 * `logo_key` points at an App\Models\UploadedAsset row ("business-{uuid}-logo"),
 * not a filesystem path — Railway's filesystem is ephemeral.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('logo_key')->nullable()->after('currency');
            $table->string('contact_phone', 40)->nullable()->after('logo_key');
            $table->string('contact_email', 255)->nullable()->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['logo_key', 'contact_phone', 'contact_email']);
        });
    }
};
