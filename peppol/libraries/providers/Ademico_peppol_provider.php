<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/Abstract_peppol_provider.php';

/**
 * Ademico PEPPOL Provider
 * 
 * Provider for sending documents via Ademico's PEPPOL access point service
 */
class Ademico_peppol_provider extends Abstract_peppol_provider
{
    public function get_provider_info()
    {
        return [
            'id' => 'ademico',
            'name' => 'Ademico PEPPOL',
            'description' => 'Ademico PEPPOL access point integration',
            'version' => '1.0.0',
            'icon' => 'fa-cloud',
            'test_connection' => true
        ];
    }

    public function send($document_type, $ubl_content, $document_data, $sender_info, $receiver_info)
    {
        $settings = $this->get_settings();

        try {
            // Prepare API request
            $endpoint = $this->get_api_endpoint($settings['environment']);
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token($settings)
            ];

            $payload = [
                'document_type' => $document_type,
                'document' => base64_encode($ubl_content),
                'sender' => $sender_info,
                'receiver' => $receiver_info,
                'metadata' => $document_data
            ];

            $response = $this->call_api($endpoint . '/documents/send', $payload, $headers);

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Document sent successfully via Ademico',
                    'document_id' => $response['data']['document_id'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ademico API error: ' . ($response['error'] ?? 'Unknown error')
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }

    public function test_connection()
    {
        $settings = $this->get_settings();

        if (empty($settings['client_id']) || empty($settings['client_secret'])) {
            return [
                'success' => false,
                'message' => 'Client ID and Client Secret are required'
            ];
        }

        try {
            $endpoint = $this->get_api_endpoint($settings['environment']);
            $token = $this->get_access_token($settings);

            if ($token) {
                // Test API health endpoint
                $response = $this->call_api($endpoint . '/health', null, [
                    'Authorization: Bearer ' . $token
                ]);

                if ($response['success']) {
                    return [
                        'success' => true,
                        'message' => 'Connection successful - Ademico API is accessible'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'API health check failed: ' . ($response['error'] ?? 'Unknown error')
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to obtain access token - check credentials'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }

    public function get_setting_inputs()
    {
        return [
            'environment' => [
                'type' => 'select',
                'label' => _l('peppol_environment'),
                'options' => [
                    'sandbox' => _l('peppol_environment_sandbox'),
                    'production' => _l('peppol_environment_production')
                ],
                'default' => 'sandbox',
                'required' => true,
                'help' => _l('peppol_ademico_environment_help')
            ],
            'client_id' => [
                'type' => 'text',
                'label' => _l('peppol_ademico_client_id'),
                'placeholder' => _l('peppol_ademico_client_id_placeholder'),
                'required' => true,
                'help' => _l('peppol_ademico_client_id_help')
            ],
            'client_secret' => [
                'type' => 'password',
                'label' => _l('peppol_ademico_client_secret'),
                'placeholder' => _l('peppol_ademico_client_secret_placeholder'),
                'required' => true,
                'help' => _l('peppol_ademico_client_secret_help')
            ],
            'timeout' => [
                'type' => 'number',
                'label' => _l('peppol_ademico_timeout'),
                'default' => 30,
                'attributes' => ['min' => 5, 'max' => 300],
                'help' => _l('peppol_ademico_timeout_help')
            ],
            'api_version' => [
                'type' => 'hidden',
                'label' => _l('peppol_ademico_api_version'),
                'default' => 'v1',
                'help' => _l('peppol_ademico_api_version_help')
            ]
        ];
    }

    public function supported_documents()
    {
        return ['invoice', 'credit_note', 'purchase_order'];
    }

    /**
     * Get API endpoint based on environment
     */
    private function get_api_endpoint($environment)
    {
        if ($environment === 'production') {
            return 'https://api.ademico.com/peppol/v1';
        } else {
            return 'https://sandbox-api.ademico.com/peppol/v1';
        }
    }

    /**
     * Get access token using client credentials
     */
    private function get_access_token($settings)
    {
        $endpoint = $this->get_api_endpoint($settings['environment']);

        $token_data = [
            'client_id' => $settings['client_id'],
            'client_secret' => $settings['client_secret'],
            'grant_type' => 'client_credentials'
        ];

        $response = $this->call_api($endpoint . '/oauth/token', $token_data, [
            'Content-Type: application/json'
        ]);

        if ($response['success'] && isset($response['data']['access_token'])) {
            return $response['data']['access_token'];
        }

        return false;
    }

    /**
     * Make API call to Ademico
     */
    private function call_api($url, $data = null, $headers = [])
    {
        $settings = $this->get_settings();

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $settings['timeout'],
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        if ($data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        $decoded_response = json_decode($response, true);

        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true, 'data' => $decoded_response];
        } else {
            $error_message = 'HTTP ' . $http_code;
            if ($decoded_response && isset($decoded_response['error'])) {
                $error_message .= ': ' . $decoded_response['error'];
            }
            return ['success' => false, 'error' => $error_message];
        }
    }
}
