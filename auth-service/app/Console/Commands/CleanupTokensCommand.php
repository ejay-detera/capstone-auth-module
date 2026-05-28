<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired refresh tokens and password reset tokens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deletedRefreshTokens = DB::table('refresh_tokens')
            ->where('expires_at', '<', now())
            ->delete();

        $deletedPasswordResetTokens = DB::table('password_reset_tokens')
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("Deleted {$deletedRefreshTokens} expired refresh tokens.");
        $this->info("Deleted {$deletedPasswordResetTokens} expired password reset tokens.");
    }
}
