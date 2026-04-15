<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store which social OAuth provider (if any) a user signed up with and the
 * opaque subject ID returned by that provider. Users who sign up with an
 * email/password never populate these columns. Users who sign up with, e.g.,
 * Google get {provider: 'google', provider_id: '10769150350006150715111'}.
 *
 * We don't store the OAuth access token — only the stable subject ID used to
 * recognise returning users. Email is still the primary identity key and
 * remains unique; the provider columns are a hint, not the source of truth.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider', 30)->nullable()->after('password');
            $table->string('provider_id', 191)->nullable()->after('provider');
            $table->unique(['provider', 'provider_id'], 'users_provider_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_provider_subject_unique');
            $table->dropColumn(['provider', 'provider_id']);
        });
    }
};
