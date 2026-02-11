<?php

class PagBankService
{
    private string $email;
    private string $token;
    private string $apiUrl;
    private bool $sandbox;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/pagbank.php';

        $this->email   = trim((string)$config['email']);
        $this->token   = trim((string)$config['token']);
        $this->apiUrl  = rtrim((string)$config['api_url'], '/');
        $this->sandbox = $config['sandbox'];
    }

    

    public function createOrder(array $params): array
    {
        $payload = [
            'reference_id' => $params['reference'],
            'customer' => $params['customer'],
            'items' => $params['items'],
            'qr_codes' => [
                [
                    'amount' => [
                        'value' => $params['amount']
                    ],
                    'expiration_date' => $params['expiration_date'] ?? date('Y-m-d\TH:i:sP', strtotime('+1 day'))
                ]
            ],
            'notification_urls' => [$params['notification_url'] ?? '']
        ];

        return $this->request('', 'POST', $payload, true);
    }

    

    public function createTransaction(array $params): array
    {
        $payload = [
            'email'           => $this->email,
            'token'           => $this->token,
            'reference'       => $params['reference'],          
            'senderHash'      => $params['senderHash'] ?? '',   
            'items'           => $params['items'],              
            'shipping'        => $params['shipping'] ?? null,   
            'sender'          => $params['sender'],             
            'extraAmount'     => $params['extraAmount'] ?? 0,   
            'notificationUrl' => $params['notificationUrl'],    
            'redirectURL'     => $params['redirectURL'] ?? null, 
        ];

        return $this->request('transactions', 'POST', $payload);
    }

    

    public function createCheckoutSession(array $params): array
    {
        $payload = [
            'referenceId'          => $params['reference'],
            'items'                => $params['items'],
            'customer'             => $params['customer'],
            'metadata'             => $params['metadata'] ?? null,
            'closes_at'            => $params['closes_at'] ?? null,
            'redirect_url'         => $params['redirect_url'] ?? null,
            'return_url'           => $params['return_url'] ?? null,
            'notification_urls'    => [$params['notification_url'] ?? null],
        ];

        return $this->request('checkout/sessions', 'POST', $payload, true);
    }

    

    public function createCardOrder(array $params): array
    {
        $payload = [
            'reference_id'      => $params['reference'],
            'customer'          => $params['customer'],
            'items'             => $params['items'],
            'notification_urls' => [$params['notification_url'] ?? ''],
            'charges' => [
                [
                    'amount' => [
                        'value' => $params['amount'],
                        'currency' => 'BRL'
                    ],
                    'payment_method' => [
                        'type' => 'CREDIT_CARD',
                        'installments' => $params['installments'] ?? 1,
                        'capture' => true,
                        'card' => [
                            'encrypted' => $params['card_encrypted'],
                            'security_code' => $params['card_cvv'] ?? null,
                            'holder' => [
                                'name' => $params['customer']['name'] ?? 'Cliente',
                            ],
                            'store' => false,
                        ],
                    ],
                ],
            ],
        ];

        return $this->request('', 'POST', $payload, true);
    }

    

    public function createCardOrderDirect(array $params): array
    {
        $cardData = $params['card_data'];

        $payload = [
            'reference_id'      => $params['reference'],
            'customer'          => $params['customer'],
            'items'             => $params['items'],
            'notification_urls' => [$params['notification_url'] ?? ''],
            'charges' => [
                [
                    'amount' => [
                        'value' => $params['amount'],
                        'currency' => 'BRL'
                    ],
                    'payment_method' => [
                        'type' => 'CREDIT_CARD',
                        'installments' => $params['installments'] ?? 1,
                        'capture' => true,
                        'card' => [
                            'number' => $cardData['number'],
                            'exp_month' => $cardData['exp_month'],
                            'exp_year' => $cardData['exp_year'],
                            'security_code' => $cardData['security_code'],
                            'holder' => [
                                'name' => $cardData['holder'],
                            ],
                            'store' => false,
                        ],
                    ],
                ],
            ],
        ];

        return $this->request('', 'POST', $payload, true);
    }

    

    public function createBoletoOrder(array $params): array
    {
        $payload = [
            'reference_id'      => $params['reference'],
            'customer'          => $params['customer'],
            'items'             => $params['items'],
            'notification_urls' => [$params['notification_url'] ?? ''],
            'charges' => [
                [
                    'amount' => [
                        'value' => $params['amount'],
                        'currency' => 'BRL'
                    ],
                    'payment_method' => [
                        'type' => 'BOLETO',
                        'boleto' => [
                            'template' => $params['template'] ?? 'COBRANCA',
                            'due_date' => $params['due_date'] ?? date('Y-m-d', strtotime('+3 days')),
                            'days_until_expiration' => (string)($params['days_until_expiration'] ?? 3),
                            'instruction_lines' => [
                                'line_1' => 'Pagamento do pedido ' . ($params['reference'] ?? ''),
                                'line_2' => 'Válido até o vencimento'
                            ],
                            'holder' => [
                                'name' => $params['customer']['name'] ?? 'Cliente Intelecto',
                                'tax_id' => $params['customer']['tax_id'] ?? '12345678909',
                                'email' => $params['customer']['email'] ?? null,
                                'address' => [
                                    'street' => $params['customer']['address']['street'] ?? 'Rua Teste',
                                    'number' => $params['customer']['address']['number'] ?? '123',
                                    'complement' => $params['customer']['address']['complement'] ?? 'Sala 1',
                                    'locality' => $params['customer']['address']['locality'] ?? 'Centro',
                                    'city' => $params['customer']['address']['city'] ?? 'Sao Paulo',
                                    'region' => $params['customer']['address']['region'] ?? 'SP',
                                    'region_code' => $params['customer']['address']['region_code'] ?? 'SP',
                                    'country' => $params['customer']['address']['country'] ?? 'BRA',
                                    'postal_code' => $params['customer']['address']['postal_code'] ?? '01001000',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->request('', 'POST', $payload, true);
    }

    

    public function getTransaction(string $transactionCode): array
    {
        return $this->request("transactions/{$transactionCode}", 'GET', [], true);
    }

    public function getOrder(string $orderId): array
    {
        return $this->request($orderId, 'GET', [], true);
    }

    

    private function request(string $endpoint, string $method = 'GET', array $data = [], bool $useBearer = false): array
    {
        try {
            $url = $this->apiUrl . ($endpoint ? '/' . $endpoint : '');

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json'
            ];

            if ($useBearer) {
                
                $headers[] = 'Authorization: Bearer ' . $this->token;
            } else {
                
                $auth = base64_encode($this->email . ':' . $this->token);
                $headers[] = 'Authorization: Basic ' . $auth;
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            
            if ($method !== 'GET' && !empty($data)) {
                $jsonData = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

                
                error_log("PagBank Request URL: $url");
                error_log("PagBank Request Data: $jsonData");
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            
            error_log("PagBank Response Code: $httpCode");
            error_log("PagBank Response: $response");

            if ($error) {
                return [
                    'success' => false,
                    'error'   => $error,
                    'code'    => 0
                ];
            }

            $decoded = json_decode($response, true);

            return [
                'success' => in_array($httpCode, [200, 201]),
                'code'    => $httpCode,
                'data'    => $decoded,
                'raw'     => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'code'    => 0
            ];
        }
    }

    

    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->token);
        return hash_equals($expectedSignature, $signature);
    }

    

    public static function formatItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'name'        => $item['nome'] ?? $item['name'],
                'quantity'    => (int) ($item['quantidade'] ?? $item['quantity'] ?? 1),
                'unit_amount' => (int) (($item['valor'] ?? $item['unit_amount'] ?? 0) * 100),
            ];
        }, $items);
    }

    

    public static function formatCustomer(array $customer): array
    {
        
        $telefone = preg_replace('/\D/', '', $customer['telefone'] ?? $customer['phone'] ?? '');
        $ddd = substr($telefone, 0, 2);
        $numero = substr($telefone, 2);

        
        $taxId = preg_replace('/\D/', '', $customer['cpf'] ?? $customer['tax_id'] ?? '');
        if (empty($taxId)) {
            $taxId = '12345678909'; 
        }

        return [
            'name'   => $customer['nome'] ?? $customer['name'],
            'email'  => $customer['email'],
            'tax_id' => $taxId,
            'phones' => [
                [
                    'country' => '55',
                    'area'    => $ddd ?: '11',
                    'number'  => $numero ?: '999999999',
                    'type'    => 'MOBILE'
                ]
            ]
        ];
    }
}
