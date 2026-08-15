<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function post($uri, array $data = [], array $headers = [])
    {
        $data['_idempotency_key'] ??= (string) Str::uuid();

        return parent::post($uri, $data, $headers);
    }

    public function postJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        $headers['Idempotency-Key'] ??= (string) Str::uuid();

        return parent::postJson($uri, $data, $headers, $options);
    }

    public function put($uri, array $data = [], array $headers = [])
    {
        $data['_idempotency_key'] ??= (string) Str::uuid();

        return parent::put($uri, $data, $headers);
    }

    public function patch($uri, array $data = [], array $headers = [])
    {
        $data['_idempotency_key'] ??= (string) Str::uuid();

        return parent::patch($uri, $data, $headers);
    }

    public function delete($uri, array $data = [], array $headers = [])
    {
        $data['_idempotency_key'] ??= (string) Str::uuid();

        return parent::delete($uri, $data, $headers);
    }
}
