<?php

namespace MediaWiki\Extension\AchievementBadges\Tests\Integration\Special;

use MediaWiki\Extension\AchievementBadges\Achievement;
use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWiki\Extension\AchievementBadges\Special\SpecialShareAchievementBadge;
use SpecialPageTestBase;
use User;

/**
 * @group AchievementBadges
 *
 * @covers \MediaWiki\Extension\AchievementBadges\Special\SpecialShareAchievementBadge
 */
class SpecialShareAchievementBadgeTest extends SpecialPageTestBase {

	/**
	 * @inheritDoc
	 */
	protected function newSpecialPage() {
		return new SpecialShareAchievementBadge();
	}

	public function testExecute() {
		$key = 'share-badge-test';
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS, [
			$key => [
				'type' => 'instant'
			]
		] );
		$user = new User();
		$name = 'ShareAchievementTester';
		$user->setName( $name );
		$user->addToDatabase();
		Achievement::achieve( [ 'user' => $user, 'key' => $key ] );

		list( $html, ) = $this->executeSpecialPage( "$name/$key", null, 'qqx' );
		$this->assertStringContainsString( 'special-shareachievementsbadge-message', $html );
	}

}
