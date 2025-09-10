@if (session('status')) <div style="color:green; padding:10px; background:#e8f5e8; border:1px solid #4caf50; border-radius:4px; margin:10px 0;">{{ session('status') }}</div> @endif
@if (session('error')) <div style="color:red; padding:10px; background:#ffeaea; border:1px solid #f44336; border-radius:4px; margin:10px 0;">{{ session('error') }}</div> @endif

@php
    $oauthDetails = \App\Models\OAuthDetails::latest()->first();
@endphp

<div style="padding:20px; border:1px solid #ddd; border-radius:8px; margin:20px 0;">
    <h2>Yahoo SMTP OAuth2 Test Application</h2>
    
    @if($oauthDetails)
        <div style="background:#e8f5e8; padding:15px; border-radius:4px; margin:15px 0;">
            <h3 style="color:green; margin:0 0 10px 0;">✅ Yahoo Connected</h3>
            <p><strong>Email:</strong> {{ $oauthDetails->email }}</p>
            @if($oauthDetails->expiry_time)
                <p><strong>Token Expires:</strong> {{ $oauthDetails->expiry_time->format('Y-m-d H:i:s') }}</p>
                @if($oauthDetails->isExpired())
                    <p style="color:red;"><strong>⚠️ Token Expired!</strong></p>
                @endif
            @endif
            <p><strong>Connected:</strong> {{ $oauthDetails->created_at->format('Y-m-d H:i:s') }}</p>
        </div>
        
        <div style="margin-top:20px;">
            <a href="/send-test" style="background:#2196f3; color:white; padding:10px 20px; text-decoration:none; border-radius:4px; margin-right:10px;">Send Test Email</a>
            <a href="{{ route('yahoo.refresh') }}" style="background:#ff9800; color:white; padding:10px 20px; text-decoration:none; border-radius:4px; margin-right:10px;">Refresh Token</a>
            <a href="/disconnect-yahoo" style="background:#f44336; color:white; padding:10px 20px; text-decoration:none; border-radius:4px;">Disconnect Yahoo</a>
        </div>
    @else
        <div style="background:#fff3cd; padding:15px; border-radius:4px; margin:15px 0;">
            <h3 style="color:#856404; margin:0 0 10px 0;">⚠️ Yahoo Not Connected</h3>
            <p>Connect your Yahoo account to test SMTP OAuth2 functionality.</p>
        </div>
        
        <div style="margin-top:20px;">
            <a href="{{ route('yahoo.redirect') }}" style="background:#4caf50; color:white; padding:10px 20px; text-decoration:none; border-radius:4px;">Connect Yahoo</a>
        </div>
    @endif
    
    <div style="margin-top:30px; padding:15px; background:#f8f9fa; border-radius:4px;">
        <h3>How to use:</h3>
        <ol>
            <li>Click "Connect Yahoo" to authorize the application</li>
            <li>Complete the Yahoo OAuth flow</li>
            <li>Click "Send Test Email" to test SMTP functionality</li>
            <li>Use "Refresh Token" to get a new access token (works even if expired)</li>
            <li>Check the logs for any issues</li>
        </ol>
        
        <h4 style="margin-top:15px;">Token Management:</h4>
        <ul>
            <li><strong>Refresh Token:</strong> Gets a new access token using the refresh token</li>
            <li><strong>Works when expired:</strong> You can refresh tokens even after they expire</li>
            <li><strong>Automatic updates:</strong> Both database and session are updated with new tokens</li>
        </ul>
    </div>
</div>
