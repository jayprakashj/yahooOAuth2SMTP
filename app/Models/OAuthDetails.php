<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthDetails extends Model
{
    protected $table = 'oauthdetails';
    
    protected $fillable = [
        'email',
        'access_token',
        'refresh_token',
        'expiry_time',
    ];
    
    protected $casts = [
        'expiry_time' => 'datetime',
    ];
    
    /**
     * Check if the token is expired
     */
    public function isExpired()
    {
        if (!$this->expiry_time) {
            return false;
        }
        
        return $this->expiry_time->isPast();
    }
    
    /**
     * Get the latest OAuth details for an email
     */
    public static function getLatestForEmail($email)
    {
        return static::where('email', $email)->latest()->first();
    }
    
    /**
     * Refresh the access token using refresh token
     */
    public function refreshAccessToken()
    {
        if (!$this->refresh_token) {
            throw new \Exception('No refresh token available');
        }
        
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => config('services.yahoo.client_id'),
            'clientSecret'            => config('services.yahoo.client_secret'),
            'redirectUri'             => config('services.yahoo.redirect'),
            'urlAuthorize'            => 'https://api.login.yahoo.com/oauth2/request_auth',
            'urlAccessToken'          => 'https://api.login.yahoo.com/oauth2/get_token',
            'urlResourceOwnerDetails' => 'https://api.login.yahoo.com/openid/v1/userinfo',
            'scopes'                  => ['openid','email'],
        ]);
        
        try {
            $accessToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $this->refresh_token
            ]);
            
            // Update the current record with new tokens
            $this->update([
                'access_token' => $accessToken->getToken(),
                'refresh_token' => $accessToken->getRefreshToken() ?: $this->refresh_token, // Keep old refresh token if new one not provided
                'expiry_time' => $accessToken->getExpires(),
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Token refresh failed', [
                'email' => $this->email,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}
