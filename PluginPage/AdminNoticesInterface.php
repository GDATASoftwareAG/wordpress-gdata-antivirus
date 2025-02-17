<?php

namespace Gdatacyberdefenseag\GdataAntivirus\PluginPage;

interface AdminNoticesInterface
{
	public static function add_notice(string $text): void;
	public function save_notices(): void;
	public function output_notices(): void;
}
