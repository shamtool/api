<?php

require_once __DIR__ . '/DiscordBasicUser.php';

/**
 * Interaction with Discord OAuth
 */
class DiscordOAuth {
    private string $apiBaseUrl = "https://discord.com/api/";
    private ?string $accessToken = null;
    
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private array $scope;

    /**
     * @param string $redirectUri The URi that OAuth will redirect to with an additional `code` query parameter. 
     */
    public function __construct(string $clientId, string $clientSecret, string $redirectUri, ?array $scope = ['identify']) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->scope = $scope;
    }

    /**
     * Issues an API request to Discord
     */
    protected function apiRequest(string $path, ?array $params = null, ?array $headers = []) : array {
        $ch = curl_init($this->apiBaseUrl . $path);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      
        $response = curl_exec($ch);

        if ($params != null)
          curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
      
        $headers[] = 'Accept: application/json';
      
        if ($this->accessToken)
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
      
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        if (!$response) {
            error_log("Discord API interaction failed with error : " . curl_error($ch));
            throw new Exception("Discord API interaction failed with error " . curl_errno($ch));
        }

        $ret = json_decode($response, true);
        if (($ret['error'] ?? null) !== null && ($ret['error_description'] ?? null) !== null) {
            // Discord error
            throw new Exception("Discord API interaction failed with error : " . $ret['error_description']);
        }
        if (($ret['code'] ?? null) !== null && ($ret['message'] ?? null) !== null) {
            // Discord error
            throw new Exception("Discord API interaction failed with error : " . $ret['message']);
        }

        return $ret;
    }

    public function getOAuthUrl() : string {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scope)
        ];

        return $this->apiBaseUrl . "oauth2/authorize?" . http_build_query($params);
    }

    /**
     * Exchanges auth code for an access token
     * 
     * @throws Exception On failure or auth code not set
     */
    public function exchangeAccessToken(string $authCode) {
        $response = $this->apiRequest("oauth2/token", [
            'client_id' => $this->clientId,
            'client_secret'=> $this->clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'redirect_uri' => $this->redirectUri
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]);
        
        $this->accessToken = $response['access_token'];
    }

    public function getBasicUser() : DiscordBasicUser {
        $response = $this->apiRequest("users/@me");

        $user = new DiscordBasicUser();
        $user->id = $response['id'];
        $user->username = $response['username'];
        $user->discriminator = $response['discriminator'];

        return $user;
    }
}
