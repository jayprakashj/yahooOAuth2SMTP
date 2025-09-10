<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\OAuthDetails;

class YahooAuthController extends Controller
{
    public function connect()
    {
        // Use League OAuth2 Client directly for Yahoo OAuth2
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => config('services.yahoo.client_id'),
            'clientSecret'            => config('services.yahoo.client_secret'),
            'redirectUri'             => config('services.yahoo.redirect'),
            'urlAuthorize'            => 'https://api.login.yahoo.com/oauth2/request_auth',
            'urlAccessToken'          => 'https://api.login.yahoo.com/oauth2/get_token',
            'urlResourceOwnerDetails' => 'https://api.login.yahoo.com/openid/v1/userinfo',
            'scopes'                  => ['openid','email','mail-w'],
        ]);

        // Request scopes needed for SMTP XOAUTH2
        \Log::info('Initiating Yahoo OAuth connection', [
            'redirect_uri' => config('services.yahoo.redirect'),
            'client_id' => config('services.yahoo.client_id'),
        ]);
        
        $authUrl = $provider->getAuthorizationUrl([
            'scope' => ['openid','email','mail-w']
        ]);
        
        // Store state for CSRF protection
        session(['oauth2state' => $provider->getState()]);
        
        return redirect($authUrl);
    }

    public function getToken(Request $request)
    {
        try {
            // Check if we have an authorization code
            if (!$request->has('code')) {
                return redirect('/')->with('error', 'Authorization code not found. Please try connecting again.');
            }

            // Log the request parameters for debugging
            \Log::info('OAuth callback received', [
                'code' => $request->get('code'),
                'state' => $request->get('state'),
                'error' => $request->get('error'),
                'error_description' => $request->get('error_description'),
            ]);

            // Check for OAuth errors
            if ($request->has('error')) {
                return redirect('/')->with('error', 'OAuth error: ' . $request->get('error_description', $request->get('error')));
            }

            // Verify state parameter for CSRF protection
            $state = $request->get('state');
            if (!$state || $state !== session('oauth2state')) {
                return redirect('/')->with('error', 'Invalid state parameter. Please try connecting again.');
            }

            // Use League OAuth2 Client directly for token exchange
            $provider = new \League\OAuth2\Client\Provider\GenericProvider([
                'clientId'                => config('services.yahoo.client_id'),
                'clientSecret'            => config('services.yahoo.client_secret'),
                'redirectUri'             => config('services.yahoo.redirect'),
                'urlAuthorize'            => 'https://api.login.yahoo.com/oauth2/request_auth',
                'urlAccessToken'          => 'https://api.login.yahoo.com/oauth2/get_token',
                'urlResourceOwnerDetails' => 'https://api.login.yahoo.com/openid/v1/userinfo',
                'scopes'                  => ['openid','email','mail-w'],
            ]);

            // Exchange authorization code for access token
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $request->get('code')
            ]);

            // Get user details
            $resourceOwner = $provider->getResourceOwner($accessToken);
            $userDetails = $resourceOwner->toArray();

            // Store OAuth data in database
            $email = $userDetails['email'] ?? null;
            if ($email) {
                OAuthDetails::updateOrCreate(
                    ['email' => $email],
                    [
                        'access_token' => $accessToken->getToken(),
                        'refresh_token' => $accessToken->getRefreshToken(),
                        'expiry_time' => $accessToken->getExpires(),
                    ]
                );
                
                // Also store in session for UI display
                session([
                    'yahoo_email' => $email,
                    'yahoo_access_token' => $accessToken->getToken(),
                    'yahoo_refresh_token' => $accessToken->getRefreshToken(),
                    'yahoo_token_expires_at' => $accessToken->getExpires() ? $accessToken->getExpires()->format('Y-m-d H:i:s') : null,
                ]);
            }

            // Clear the state from session
            session()->forget('oauth2state');

            return redirect('/')->with('status', 'Yahoo connected! You can send mail now.');
        } catch (\Exception $e) {
            \Log::error('OAuth token exchange failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all(),
            ]);
            
            return redirect('/')->with('error', 'Failed to connect Yahoo: ' . $e->getMessage());
        }
    }
    
    public function refreshToken()
    {
        try {
            $oauthDetails = OAuthDetails::latest()->first();
            
            if (!$oauthDetails) {
                return redirect('/')->with('error', 'No OAuth details found. Please connect Yahoo first.');
            }
            
            if (!$oauthDetails->refresh_token) {
                return redirect('/')->with('error', 'No refresh token available. Please reconnect Yahoo.');
            }
            
            // Refresh the access token
            $oauthDetails->refreshAccessToken();
            
            // Update session data as well
            session([
                'yahoo_email' => $oauthDetails->email,
                'yahoo_access_token' => $oauthDetails->access_token,
                'yahoo_refresh_token' => $oauthDetails->refresh_token,
                'yahoo_token_expires_at' => $oauthDetails->expiry_time ? $oauthDetails->expiry_time->format('Y-m-d H:i:s') : null,
            ]);
            
            return redirect('/')->with('status', 'Yahoo token refreshed successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Token refresh failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect('/')->with('error', 'Failed to refresh token: ' . $e->getMessage());
        }
    }
}
