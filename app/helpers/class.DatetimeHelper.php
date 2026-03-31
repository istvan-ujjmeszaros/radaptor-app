<?php

class DatetimeHelper
{
	public static function getIsoDatetime(?int $timestamp): string
	{
		return gmdate('Y-m-d H:i:s', $timestamp ?? time());
	}

	public static function isoFromTimestamp(int $timestamp): string
	{
		return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
	}

	public static function isoFromDatetime(string $datetime): ?string
	{
		$dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $datetime, new DateTimeZone('UTC'));

		if ($dt === false) {
			return null;
		}

		return $dt->format('Y-m-d\TH:i:s\Z');
	}
}
