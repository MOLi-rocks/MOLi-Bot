<?php

namespace MOLiBot\Services;

use MOLiBot\Repositories\LINENotifyUserRepository;
use \GuzzleHttp\Client as GuzzleHttpClient;

class LINENotifyService
{
    private $lineNotifyUserRepository;

    public function __construct(LINENotifyUserRepository $LINENotifyUserRepository)
    {
        $this->lineNotifyUserRepository = $LINENotifyUserRepository;
    }

    /**
     * @param $access_token string
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getStatus($access_token)
    {
        try {
            $client = new GuzzleHttpClient();

            $response = $client->request('GET', 'https://notify-api.line.me/api/status', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Accept-Encoding' => 'gzip'
                ],
                'timeout' => 10
            ]);

            $response = $response->getBody()->getContents();
            $json = json_decode($response, true);

            return $json;
        } catch (\Exception $e) {
            $status = $e->getCode() ?? 0;
            return [
                'status' => $status,
                'message' => $e->getMessage(),
                'targetType' => '',
                'target' => ''
            ];
        }
    }

    /**
     * @param $token
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateUser($token)
    {
        $result = $this->getStatus($token);

        if (!empty($result['targetType'])) {
            if ($result['target'] != 'null.') {
                $this->lineNotifyUserRepository->updateUserData($token, $result);
            } else {
                $this->lineNotifyUserRepository->updateUserStatus($token, $result['status']);
            }
        }
    }

    /**
     * @return array
     */
    public function getAllStats()
    {
        $total = $this->lineNotifyUserRepository->getTotalStats();

        $user = $this->lineNotifyUserRepository->getUserStats();

        $group = $this->lineNotifyUserRepository->getGroupStats();

        return [
            'Total' => $total,
            'USER' => $user,
            'GROUP' => $group
        ];
    }

    /**
     * @param $token string
     *
     * @return void
     */
    public function createToken($token)
    {
        $this->lineNotifyUserRepository->createToken($token);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getAllToken()
    {
        return $this->lineNotifyUserRepository->getAllToken();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getSendMsgToken()
    {
        return $this->lineNotifyUserRepository->getSendMsgToken();
    }

    /**
     * @param $msg string
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendMsgToAll($msg)
    {
        $result = [];

        $tokens = $this->getSendMsgToken();

        foreach ($tokens as $key => $token) {
            $response = $this->sendMsg($token, $msg);

            $result[] = [
                'token' => $token,
                'status' => $response['status'],
                'message' => $response['message']
            ];

            // LINE 限制一分鐘上限 1000 次，做一些保留次數
            if (($key + 1) % 950 == 0) {
                sleep(62);
            }
        }

        return $result;
    }

    /**
     * @param $access_token string
     * @param $msg string
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendMsg($access_token, $msg)
    {
        try {
            $client = new GuzzleHttpClient();

            $response = $client->request('POST', 'https://notify-api.line.me/api/notify', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Accept-Encoding' => 'gzip'
                ],
                'form_params' => [
                    'message' => $msg,
                ],
                'timeout' => 10
            ]);

            $status = $response->getStatusCode();

            if ($status == 401) {
                $this->lineNotifyUserRepository->updateUserStatus($access_token, $status);
            }

            $rspn = json_decode($response->getBody()->getContents(), true);

            return $rspn;
        } catch (\Exception $e) {
            $status = $e->getCode() ?? 0;
            return [
                'status' => $status,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param $code string|integer
     * @param $redirectUri string
     * @param $clientId string
     * @param $clientSecret string
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getToken($code, $redirectUri, $clientId, $clientSecret)
    {
        try {
            $client = new GuzzleHttpClient();

            $response = $client->request('POST', 'https://notify-bot.line.me/oauth/token', [
                'headers'     => [
                    'User-Agent'    => 'MOLiBot',
                    'cache-control' => 'no-cache'
                ],
                'form_params' => [
                    'grant_type'    => 'authorization_code',
                    'code'          => $code,
                    'redirect_uri'  => $redirectUri,
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret
                ],
                'timeout'     => 10
            ]);

            $status = $response->getStatusCode();

            if ($status / 100 == 2) {
                $response = $response->getBody()->getContents();

                $json = json_decode($response, true);

                $access_token = $json['access_token'];

                $this->createToken($access_token);

                return [
                    'success' => true,
                    'token'   => $access_token,
                    'error'   => ''
                ];
            } else {
                return [
                    'success' => false,
                    'token' => '',
                    'error' => base64_encode($status)
                ];
            }
        } catch (\Exception $e) {
            $status = $e->getCode() ?? 0;

            return [
                'success' => false,
                'token' => '',
                'error' => base64_encode($status . ' - ' . $e->getMessage())
            ];
        }
    }
}