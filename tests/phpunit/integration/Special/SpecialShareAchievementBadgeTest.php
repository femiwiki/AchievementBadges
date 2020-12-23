<?php

namespace MediaWiki\Extension\AchievementBadges\Tests\Integration\Special;

// use MediaWiki\Extension\AchievementBadges\Achievement;
// use MediaWiki\Extension\AchievementBadges\Constants;
use SpecialPageTestBase;

// use User;
// use UserNotLoggedIn;
// use Wikimedia\TestingAccessWrapper;

/**
 * @group AchievementBadges
 *
 * @covers \MediaWiki\Extension\AchievementBadges\Special\SpecialShareAchievementBadge
 */
class SpecialShareAchievementBadgeTest extends SpecialPageTestBase {

	/**
	 * Returns a new instance of the special page under test.
	 *
	 * @return SpecialPage
	 */
	protected function newSpecialPage() {
		return new SpecialAchievements();
	}

	public function testNothing() {
		$user = $this->getTestUser();
		$this->assertSame( 1, 1 );
	}

}
