<?php

// app/Http/Controllers/MailController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\OAuth2\Client\Provider\GenericProvider;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\OAuth;
use App\Models\OAuthDetails;

class MailController extends Controller
{
    public function sendTest(Request $request)
    {
        // Get OAuth data from database
        $oauthDetails = OAuthDetails::latest()->first();
        
        if (!$oauthDetails || !$oauthDetails->refresh_token) {
            return back()->with('error', 'Connect Yahoo first by clicking "Connect Yahoo" button');
        }
        
        // Check if token is expired
        if ($oauthDetails->isExpired()) {
            return back()->with('error', 'Yahoo OAuth token has expired. Please reconnect your Yahoo account.');
        }

        // OAuth2 provider for Yahoo (endpoints are correct for OAuth2)
        $provider = new GenericProvider([
            'clientId'                => config('services.yahoo.client_id'),
            'clientSecret'            => config('services.yahoo.client_secret'),
            'redirectUri'             => config('services.yahoo.redirect'),
            'urlAuthorize'            => 'https://api.login.yahoo.com/oauth2/request_auth',
            'urlAccessToken'          => 'https://api.login.yahoo.com/oauth2/get_token',
            'urlResourceOwnerDetails' => 'https://api.login.yahoo.com/openid/v1/userinfo',
            'scopes'                  => ['mail-w','openid','email'],
        ]);

        // Build PHPMailer
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.mail.yahoo.com';
            $mail->Port       = 587;          // or 465 with SMTPS
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // or ENCRYPTION_SMTPS for 465

            // $mail->Port = 465;
            // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

            $mail->SMTPAuth   = true;
            $mail->AuthType   = 'XOAUTH2';

            // Configure OAuth refresh via provider
            $oauth = new OAuth([
                'provider'      => $provider,
                'clientId'      => config('services.yahoo.client_id'),
                'clientSecret'  => config('services.yahoo.client_secret'),
                'refreshToken'  => $oauthDetails->refresh_token,
                'userName'      => $oauthDetails->email, // FULL Yahoo email
            ]);
            $mail->setOAuth($oauth);

            $mail->setFrom(config('mail.from.address', $oauthDetails->email), config('app.name'));
            $mail->addAddress(env('TEST_RECIPIENT', $oauthDetails->email));
            $mail->Subject = 'Yahoo SMTP OAuth2 test';
            $mail->isHTML(true);
            $mail->Body = '<p>Hello from <b>PHPMailer + Yahoo XOAUTH2</b> 🎉</p>';
            $mail->AltBody = 'Hello from PHPMailer + Yahoo XOAUTH2';

            $mail->send();

            return back()->with('status', 'Test email sent successfully!');
        } catch (MailerException $e) {
            return back()->with('error', 'Mailer error: '.$e->getMessage());
        } catch (\Throwable $t) {
            return back()->with('error', 'Unexpected error: '.$t->getMessage());
        }
    }
}
