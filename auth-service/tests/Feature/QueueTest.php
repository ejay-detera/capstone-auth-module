<?php

namespace Tests\Feature;

use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QueueTest extends TestCase
{
    /**
     * Test that the WelcomeEmail is correctly queued when sent.
     */
    public function test_welcome_email_is_correctly_queued()
    {
        Mail::fake();

        $email = 'queue-test-' . time() . '@sbsi.com';
        $password = 'TempPass123!';

        // Dispatch WelcomeEmail
        Mail::to($email)->send(new WelcomeEmail($email, $password));

        // Assert that the WelcomeEmail was queued to the target email address
        Mail::assertQueued(WelcomeEmail::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });
    }

    /**
     * Real live test that actually sends the WelcomeEmail to Mailtrap (no Mail::fake()!).
     */
    public function test_welcome_email_actually_sends_to_mailtrap()
    {
        $email = 'real-queue-test-' . time() . '@sbsi.com';
        $password = 'TempPass123!';

        // Dispatch WelcomeEmail live to the queue (no fake)
        Mail::to($email)->send(new WelcomeEmail($email, $password));

        $this->assertTrue(true);
    }
}
