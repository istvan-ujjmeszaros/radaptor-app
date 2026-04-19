<?php

final class FormInputDateTimeConversionTest extends TransactionedTestCase
{
	private array $originalGet = [];
	private array $originalPost = [];

	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();
		$this->originalGet = $_GET;
		$this->originalPost = $_POST;
	}

	#[\Override]
	protected function tearDown(): void
	{
		$_GET = $this->originalGet;
		$_POST = $this->originalPost;
		parent::tearDown();
	}

	public function testUnchangedPostedValueInUpdateModeIsNotConvertedAgain(): void
	{
		$_GET = ['item_id' => '1'];
		$_POST = [];
		RequestContextHolder::initializeRequest(get: $_GET, post: $_POST);

		$form = new class ('x1', $this->createMock(iWebpageComposer::class)) extends AbstractForm {
			public function __construct(string $formId, iWebpageComposer $composer)
			{
				parent::__construct('DummyDateTimeForm', $formId, $composer);
			}

			public static function getName(): string
			{
				return 'Dummy';
			}

			public static function getDescription(): string
			{
				return 'Dummy';
			}

			public static function getListVisibility(): bool
			{
				return true;
			}

			public static function getDefaultPathForCreation(): array
			{
				return [
					'path' => '/',
					'resource_name' => 'index.html',
					'layout' => 'public_default',
				];
			}

			public function hasRole(): bool
			{
				return true;
			}

			public function commit(): void
			{
			}

			public function setMetadata(): void
			{
			}

			public function setInitValues(): void
			{
				$this->initvalues = [
					'start_time' => '2026-03-05 09:00:00',
				];
			}

			public function makeInputs(): void
			{
				new FormInputDateTime('start_time', $this);
			}
		};

		$input = $form->getInput('start_time');
		$this->assertInstanceOf(FormInputDateTime::class, $input);
		$inputId = $input->id;
		$this->assertNotNull($inputId);

		RequestContextHolder::initializeRequest(post: [
			$inputId => '2026-03-05 09:00',
			'client_timezone' => 'Europe/Budapest',
		]);
		$input->setValue('2026-03-05 09:00');

		$this->assertSame('2026-03-05 09:00:00', $input->getValue());
	}

	public function testChangedPostedValueIsConvertedFromClientTimezoneToUtc(): void
	{
		$_GET = ['item_id' => '1'];
		$_POST = [];
		RequestContextHolder::initializeRequest(get: $_GET, post: $_POST);

		$form = new class ('x2', $this->createMock(iWebpageComposer::class)) extends AbstractForm {
			public function __construct(string $formId, iWebpageComposer $composer)
			{
				parent::__construct('DummyDateTimeForm', $formId, $composer);
			}

			public static function getName(): string
			{
				return 'Dummy';
			}

			public static function getDescription(): string
			{
				return 'Dummy';
			}

			public static function getListVisibility(): bool
			{
				return true;
			}

			public static function getDefaultPathForCreation(): array
			{
				return [
					'path' => '/',
					'resource_name' => 'index.html',
					'layout' => 'public_default',
				];
			}

			public function hasRole(): bool
			{
				return true;
			}

			public function commit(): void
			{
			}

			public function setMetadata(): void
			{
			}

			public function setInitValues(): void
			{
				$this->initvalues = [
					'start_time' => '2026-03-05 09:00:00',
				];
			}

			public function makeInputs(): void
			{
				new FormInputDateTime('start_time', $this);
			}
		};

		$input = $form->getInput('start_time');
		$this->assertInstanceOf(FormInputDateTime::class, $input);
		$inputId = $input->id;
		$this->assertNotNull($inputId);

		RequestContextHolder::initializeRequest(post: [
			$inputId => '2026-03-05 10:00',
			'client_timezone' => 'Europe/Budapest',
		]);
		$input->setValue('2026-03-05 10:00');

		$this->assertSame('2026-03-05 09:00:00', $input->getValue());
	}
}
