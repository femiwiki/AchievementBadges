<?php

namespace MediaWiki\Extension\AchievementBadges\Tests\Unit;

use MediaWiki\Extension\AchievementBadges\Hooks\HookRunner;
use MediaWiki\Tests\HookContainer\HookRunnerTestBase;

/**
 * @covers \MediaWiki\Extension\AchievementBadges\Hooks\HookRunner
 */
class HookRunnerTest extends HookRunnerTestBase {

	public function provideHookRunners() {
		yield HookRunner::class => [ HookRunner::class ];
	}
}
