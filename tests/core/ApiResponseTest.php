<?php

use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase
{
	public function testSuccessCreatesOkResponse(): void
	{
		$response = ApiResponse::success(['id' => 1]);
		$array = $response->toArray();

		$this->assertTrue($array['ok']);
		$this->assertEquals(['id' => 1], $array['data']);
		$this->assertArrayNotHasKey('error', $array);
	}

	public function testSuccessWithMeta(): void
	{
		$response = ApiResponse::success(['id' => 1], ['page' => 1, 'total' => 10]);
		$array = $response->toArray();

		$this->assertTrue($array['ok']);
		$this->assertArrayHasKey('meta', $array);
		$this->assertEquals(['page' => 1, 'total' => 10], $array['meta']);
	}

	public function testSuccessWithMessage(): void
	{
		$response = ApiResponse::success(null, null, 'Record created');
		$array = $response->toArray();

		$this->assertTrue($array['ok']);
		$this->assertArrayHasKey('message', $array);
		$this->assertEquals('Record created', $array['message']);
	}

	public function testErrorCreatesFailedResponse(): void
	{
		$error = new ApiError('NOT_FOUND', 'Resource not found');
		$response = ApiResponse::error($error);
		$array = $response->toArray();

		$this->assertFalse($array['ok']);
		$this->assertArrayHasKey('error', $array);
		$this->assertEquals('NOT_FOUND', $array['error']['code']);
		$this->assertArrayNotHasKey('data', $array);
	}

	public function testErrorWithHttpCode(): void
	{
		$error = new ApiError('NOT_FOUND', 'Resource not found');
		$response = ApiResponse::error($error, 404);

		$this->assertEquals(404, $response->getHttpCode());
	}

	public function testToArrayStripsNullKeys(): void
	{
		$response = ApiResponse::success(['id' => 1]);
		$array = $response->toArray();

		$this->assertArrayNotHasKey('meta', $array);
		$this->assertArrayNotHasKey('message', $array);
		$this->assertArrayNotHasKey('error', $array);
	}

	public function testSuccessToArrayProducesValidJson(): void
	{
		$response = ApiResponse::success(['foo' => 'bar']);
		$json = json_encode($response->toArray());
		$decoded = json_decode($json, true);

		$this->assertTrue($decoded['ok']);
		$this->assertEquals(['foo' => 'bar'], $decoded['data']);
	}

	public function testErrorToArrayProducesValidJson(): void
	{
		$error = new ApiError('BAD', 'Something went wrong');
		$response = ApiResponse::error($error, 400);
		$json = json_encode($response->toArray());
		$decoded = json_decode($json, true);

		$this->assertFalse($decoded['ok']);
		$this->assertEquals('BAD', $decoded['error']['code']);
		$this->assertEquals('Something went wrong', $decoded['error']['message']);
	}
}
