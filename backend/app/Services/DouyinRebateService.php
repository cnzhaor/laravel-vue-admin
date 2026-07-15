<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class DouyinRebateService
{
    public function status(): array
    {
        $mock = (bool) config('services.douyin_rebate.mock');
        $configured = collect(['app_key', 'app_secret', 'access_token', 'pid'])
            ->every(fn (string $key) => filled(config("services.douyin_rebate.{$key}")));

        return [
            'mode' => $mock ? 'mock' : 'live',
            'configured' => $configured,
            'pid' => $this->maskedPid(),
            'convert_method' => 'buyin.doukeCommandParseAndShare',
            'bill_method' => 'buyin.douKeSettleBillList',
        ];
    }

    public function convert(string $command, ?string $externalInfo = null): array
    {
        $externalInfo ??= 'demo_'.now()->format('YmdHis');

        if ((bool) config('services.douyin_rebate.mock')) {
            return $this->mockConversion($command, $externalInfo);
        }

        $this->ensureConfigured();

        return $this->call('buyin.doukeCommandParseAndShare', [
            'command' => $command,
            'share_params' => [
                'pid' => (string) config('services.douyin_rebate.pid'),
                'external_info' => $externalInfo,
                'need_qr_code' => false,
                'need_zlink' => true,
            ],
        ]);
    }

    public function bills(CarbonInterface $date, int $page = 1, int $pageSize = 20): array
    {
        if ((bool) config('services.douyin_rebate.mock')) {
            return $this->mockBills($date, $page, $pageSize);
        }

        $this->ensureConfigured();

        return $this->call('buyin.douKeSettleBillList', [
            'start_time' => $date->format('Y-m-d'),
            'end_time' => $date->format('Y-m-d'),
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }

    public function sign(string $method, string $paramJson, string $timestamp): string
    {
        $secret = (string) config('services.douyin_rebate.app_secret');
        $pattern = 'app_key'.config('services.douyin_rebate.app_key')
            .'method'.$method
            .'param_json'.$paramJson
            .'timestamp'.$timestamp
            .'v2';

        return hash_hmac('sha256', $secret.$pattern.$secret, $secret);
    }

    public function encodeParameters(array $parameters): string
    {
        return json_encode(
            $this->sortRecursively($parameters),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    private function call(string $method, array $parameters): array
    {
        $paramJson = $this->encodeParameters($parameters);
        $timestamp = now('Asia/Shanghai')->format('Y-m-d H:i:s');
        $query = [
            'app_key' => config('services.douyin_rebate.app_key'),
            'method' => $method,
            'access_token' => config('services.douyin_rebate.access_token'),
            'timestamp' => $timestamp,
            'v' => '2',
            'sign_method' => 'hmac-sha256',
            'sign' => $this->sign($method, $paramJson, $timestamp),
        ];

        try {
            $response = Http::acceptJson()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout((int) config('services.douyin_rebate.timeout', 10))
                ->withQueryParameters($query)
                ->send('POST', (string) config('services.douyin_rebate.endpoint'), ['body' => $paramJson])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            throw new InvalidArgumentException('抖音接口请求失败：'.$exception->getMessage(), previous: $exception);
        }

        if (($response['code'] ?? null) !== 10000) {
            $message = $response['sub_msg'] ?? $response['msg'] ?? '未知错误';
            throw new InvalidArgumentException("抖音接口返回失败：{$message}");
        }

        return $response['data'] ?? [];
    }

    private function sortRecursively(array $value): array
    {
        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = $this->sortRecursively($item);
            }
        }
        unset($item);

        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return $value;
    }

    private function ensureConfigured(): void
    {
        if (! $this->status()['configured']) {
            throw new InvalidArgumentException('Live 模式缺少 DOUYIN_APP_KEY、DOUYIN_APP_SECRET、DOUYIN_ACCESS_TOKEN 或 DOUYIN_PID。');
        }
    }

    private function maskedPid(): ?string
    {
        $pid = (string) config('services.douyin_rebate.pid');
        if ($pid === '') {
            return null;
        }

        return strlen($pid) <= 8 ? str_repeat('*', strlen($pid)) : substr($pid, 0, 4).'****'.substr($pid, -4);
    }

    private function mockConversion(string $command, string $externalInfo): array
    {
        $id = substr(hash('sha256', $command.$externalInfo), 0, 12);

        return [
            'command_info' => [
                'command_type' => '1',
                'pid' => config('services.douyin_rebate.pid') ?: 'dy_demo_001_001',
                'product_info' => [
                    'product_id' => '3530123456789012345',
                    'title' => 'Mock 商品：便携式随行杯',
                    'price' => 5990,
                    'cos_ratio' => 20,
                    'estimated_commission' => 1198,
                    'external_info' => $externalInfo,
                    'share_info' => [
                        'share_command' => "7:/ Mock抖音口令 {$id} 复制打开抖音",
                        'share_link' => "https://haohuo.jinritemai.com/ecommerce/trade/detail/index.html?id=3530123456789012345&external_info={$externalInfo}",
                        'zlink' => "https://z.douyin.com/{$id}",
                    ],
                ],
            ],
        ];
    }

    private function mockBills(CarbonInterface $date, int $page, int $pageSize): array
    {
        $orders = [
            ['order_id' => '6926202607150001', 'product_name' => '便携式随行杯', 'pay_amount' => 5990, 'commission_amount' => 1198, 'status' => '已结算', 'settle_time' => $date->format('Y-m-d').' 10:32:18'],
            ['order_id' => '6926202607150002', 'product_name' => '降噪蓝牙耳机', 'pay_amount' => 12900, 'commission_amount' => 1935, 'status' => '待结算', 'settle_time' => $date->format('Y-m-d').' 14:08:52'],
        ];

        return [
            'list' => array_slice($orders, ($page - 1) * $pageSize, $pageSize),
            'total' => count($orders),
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }
}
