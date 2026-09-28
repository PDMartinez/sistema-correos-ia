<?php
declare(strict_types=1);

function googleClient(): Google\Client
{
    $client = new Google\Client();
    $client->setClientId((string) config('GOOGLE_CLIENT_ID', ''));
    $client->setClientSecret((string) config('GOOGLE_CLIENT_SECRET', ''));
    $client->setRedirectUri((string) config('GOOGLE_REDIRECT_URI', ''));
    $client->setAccessType('offline');
    $client->setPrompt('consent select_account');
    $client->setIncludeGrantedScopes(true);
    $client->setScopes([
        (string) config('GOOGLE_GMAIL_SCOPES', 'https://www.googleapis.com/auth/gmail.readonly'),
    ]);

    return $client;
}
