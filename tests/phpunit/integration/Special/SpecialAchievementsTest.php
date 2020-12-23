<?php

namespace MediaWiki\Extension\AchievementBadges\Tests\Integration;

use MediaWiki\Extension\AchievementBadges\Achievement;
use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWiki\Extension\AchievementBadges\Special\SpecialAchievements;
use SpecialPageTestBase;
use User;
use UserNotLoggedIn;

/**
 * @group AchievementBadges
 *
 * @covers \MediaWiki\Extension\AchievementBadges\Special\SpecialAchievements
 */
class SpecialAchievementsTest extends SpecialPageTestBase {

	/**
	 * Returns a new instance of the special page under test.
	 *
	 * @return SpecialPage
	 */
	protected function newSpecialPage() {
		return new SpecialAchievements();
	}

	public function testDisabledMsg() {
		$user = $this->getTestUser();

		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx', $user->getUser() );
		$this->assertStringContainsString( '(achievements-summary)', $html,
			'A user can see Special:Achievements' );

		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ENABLE_BETA_FEATURE, true );

		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx', $user->getUser() );
		$this->assertStringContainsString( '(achievementbadges-disabled)', $html,
			'A registered user cannot see Special:Achievements if not enabled it' );

		$this->expectException( UserNotLoggedIn::class );
		$this->executeSpecialPage( '', null, 'qqx', null );
		$this->assertStringContainsString( '(achievementbadges-disabled)', $html,
			'An anonymous user cannot see Special:Achievements during beta period' );

		$user = $this->getMutableTestUser()->getUser();
		$user->setOption( Constants::PREF_KEY_ACHIEVEMENT_ENABLE, '1' );
		$this->expectException( MWException::class,
			'Saving preferences during the test throws exception because the unregistered sign-up achievement' );
		$user->saveSettings();
		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx', $user );
		$this->assertStringContainsString( '(special-achievements-header-not-earning-achievements)', $html,
			'A user who enables AB can see achievements on Special:Achievements' );
	}

	public function testDisplayHint() {
		$user = new User();
		$user->setName( __METHOD__ );
		$user->addToDatabase();

		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx', null );
		$this->assertStringContainsString( 'achievement-hint-sign-up', $html,
			'A user can see a hint for not earning achievement' );

		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx', $user );
		$this->assertStringContainsString( 'achievement-description-sign-up', $html,
			'A user can see a description for obtained achievement' );
	}

	public function testStatsAchievement() {
		$key = 'special-test';
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS, [
			$key => [
				'type' => 'stats',
				'thresholds' => [ 1, 10, 100 ],
			],
		] );
		$user = $this->getTestUser()->getUser();
		$info = [ 'key' => $key, 'user' => $user, 'stats' => 1 ];

		Achievement::sendStats( $info );
		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx', $user );
		$this->assertStringContainsString( 'achievement-description-special-test-0', $html,
			'Achieved achievement should be shown on Special:Achievements' );

		$info['stats'] = 15;
		Achievement::sendStats( $info );
		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx', $user );
		$this->assertStringContainsString( 'achievement-description-special-test-0', $html,
			'Achieved achievement should be shown on Special:Achievements' );
		$this->assertStringContainsString( 'achievement-description-special-test-1', $html,
			'Achieved achievement should be shown on Special:Achievements' );
	}

}
