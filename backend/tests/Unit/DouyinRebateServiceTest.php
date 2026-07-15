<?php

namespace Tests\Unit;

use App\Services\DouyinRebateService;
use Tests\TestCase;

class DouyinRebateServiceTest extends TestCase
{
    public function test_it_recursively_sorts_business_parameters(): void
    {
        $json = app(DouyinRebateService::class)->encodeParameters([
            'z' => 1,
            'nested' => ['b' => 2, 'a' => '汉字/emoji😀'],
            'list' => [['d' => 4, 'c' => 3]],
        ]);

        $this->assertSame('{"list":[{"c":3,"d":4}],"nested":{"a":"汉字/emoji😀","b":2},"z":1}', $json);
    }

    public function test_mock_conversion_contains_attribution_and_share_material(): void
    {
        config()->set('services.douyin_rebate.mock', true);

        $result = app(DouyinRebateService::class)->convert('测试抖口令', 'user_1001');

        $product = $result['command_info']['product_info'];
        $this->assertSame('user_1001', $product['external_info']);
        $this->assertNotEmpty($product['share_info']['share_command']);
        $this->assertNotEmpty($product['share_info']['zlink']);
    }
}
