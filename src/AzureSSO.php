<?php

namespace ZeroWeb;

use Exception;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpFoundation\Request;

class AzureSSO
{

    const sessionKey = "__AzureSSO__";
    private array $config;
    private Request $request;
    private CurlHttpClient $httpClient;

    private array $groups = [];

    public function __construct($config, Request $request)
    {
        $this->config = $config;
        $this->request = $request;
        $this->httpClient = new CurlHttpClient();
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        if (!empty($_SESSION[AzureSSO::sessionKey]['auth']['access_token'])) {
            return $_SESSION[AzureSSO::sessionKey]['auth']['access_token'];
        }
    }

    public function authenticate()
    {
        if (($this->request->getMethod() === 'GET') && empty($this->request->get('code'))) {
            $url = 'https://login.microsoftonline.com/' . $this->config['tenantId'] . '/oauth2/v2.0/authorize?';
            $url .= 'state=' . session_id();
            $url .= '&scope=' . $this->config['scope'];
            $url .= '&response_type=code';
            $url .= '&approval_prompt=auto';
            $url .= '&client_id=' . $this->config['clientId'];
            $url .= '&redirect_uri=' . urlencode($this->config['returnUrl']);
            header("Location: $url");
            exit;
        } else {
            $response = $this->httpClient->request("POST", 'https://login.microsoftonline.com/' . $this->config['tenantId'] . '/oauth2/v2.0/token', [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'body' => [
                    'grant_type' => 'authorization_code',
                    'code' => $this->request->get('code'),
                    'client_id' => $this->config['clientId'],
                    'client_secret' => $this->config['clientSecret'],
                    'redirect_uri' => $this->config['returnUrl'],
                ],
            ]);
            $statusCode = $response->getStatusCode();
            $content = json_decode($response->getContent(false), true);
            if ($statusCode != 200) {
                if ($content['error'] == 'invalid_grant') {
                    header("Location: " . $this->request->getPathInfo());
                    exit;
                } else {
                    throw new Exception($content['error_description']);
                }
            } else {
                $_SESSION[AzureSSO::sessionKey] = [];
                $_SESSION[AzureSSO::sessionKey]['auth'] = $content;
                $_SESSION[AzureSSO::sessionKey]['me'] = $this->getUserInfo();
                if ($this->config['returnGroups']) {
                    $_SESSION[AzureSSO::sessionKey]['groups'] = $this->getGroups();
                }
            }
        }
    }

    /**
     * @return mixed
     */
    public function getUserInfo()
    {
        $url = 'https://graph.microsoft.com/v1.0/me';
        $response = $this->httpClient->request("GET", $url, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getToken(),
            ]
        ]);
        $statusCode = $response->getStatusCode();
        $content = json_decode($response->getContent(false), true);
        if ($statusCode != 200) {
            throw new Exception($content['error_description']);
        } else {
            return $content;
        }
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws Exception
     */
    public function getGroups($url = null)
    {
        if (empty($url)) {
            $url = 'https://graph.microsoft.com/v1.0/me/memberOf';
        }
        $response = $this->httpClient->request("GET", $url, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getToken(),
            ]
        ]);
        $statusCode = $response->getStatusCode();
        $content = json_decode($response->getContent(false), true);
        if ($statusCode != 200) {
            throw new Exception($content['error_description']);
        } else {
            foreach ($content['value'] as $group) {
                if (!empty($group['displayName'])) {
                    $this->groups[] = $group['displayName'];
                }
            }
            if (!empty($content['@odata.nextLink'])) {
                $this->getGroups($content['@odata.nextLink']);
            }
        }
        return $this->groups;
    }
}