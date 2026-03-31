<?php

/**
 * Fixture for the blog table.
 *
 * References: @blog.{slug}
 * Example: @blog.test-entry
 */
class FixtureBlog extends AbstractFixture
{
	public function getTableName(): string
	{
		return 'blog';
	}

	public function getReferenceBy(): string
	{
		return 'slug';
	}

	/**
	 * @return list<array{
	 *     slug: string,
	 *     title: string,
	 *     __content: string,
	 *     __description: string,
	 *     date: string
	 * }>
	 */
	public function getData(): array
	{
		return [
			[
				'slug' => 'test-entry',
				'title' => 'Test Blog Entry',
				'__content' => '<p>This is the full content of the test blog entry.</p>',
				'__description' => '<p>Short description for the test entry.</p>',
				'date' => '2026-01-15 10:00:00',
			],
			[
				'slug' => 'entry-with-links',
				'title' => 'Entry With Internal Links',
				'__content' => '<p>Content with <a href="?direction=in&id=1">internal link</a>.</p>',
				'__description' => '<p>Description with <a href="?direction=in&id=1">link</a>.</p>',
				'date' => '2026-01-16 12:00:00',
			],
		];
	}
}
