<?php

namespace MediaWiki\Extension\AchievementBadges\Tests\Integration;

use MediaWiki\Extension\AchievementBadges\Achievement;
use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWiki\Extension\AchievementBadges\Special\SpecialAchievements;
use MWException;
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
	 * @inheritDoc
	 */
	protected function newSpecialPage() {
		return new SpecialAchievements();
	}

	/** @return array */
	public function provideExecutionVariables() {
		$anon = null;
		$loggedInUser = new User();
		$loggedInUser->setName( 'loggedInUser' );
		$loggedInUser->addToDatabase();
		$betaEnabledUser = new User();
		$betaEnabledUser->setName( 'betaEnabledUser' );
		$betaEnabledUser->addToDatabase();
		$betaEnabledUser->setOption( Constants::PREF_KEY_ACHIEVEMENT_ENABLE, '1' );
		$betaEnabledUser->saveSettings();
		$duringBeta = true;
		$notDuringBeta = false;
		$noSubPage = '';

		$otherDisabledUserName = 'otherDisabledUser';
		$otherDisabledUser = new User();
		$otherDisabledUser->setName( $otherDisabledUserName );
		$otherDisabledUser->addToDatabase();
		$otherEnabledUserName = 'otherEnabledUser';
		$otherEnabledUser = new User();
		$otherEnabledUser->setName( $otherEnabledUserName );
		$otherEnabledUser->setOption( Constants::PREF_KEY_ACHIEVEMENT_ENABLE, '1' );
		$otherEnabledUser->saveSettings();
		$otherUserName = $otherDisabledUserName;
		$ok = 'achievements-summary';
		return [
			[
				$notDuringBeta, $anon, $noSubPage, $ok,
				'An anonymous user cannot see Special:Achievements',
			],
			[
				$notDuringBeta, $anon, $otherUserName, $ok,
				'An anonymous user cannot see Special:Achievements for other',
			],
			[
				$notDuringBeta, $loggedInUser, $noSubPage, $ok,
				'A registered user can see Special:Achievements',
			],
			[
				$notDuringBeta, $loggedInUser, $otherUserName, $ok,
				'A registered user can see Special:Achievements for other',
			],
			// During beta period
			[
				$duringBeta, $anon, $noSubPage, UserNotLoggedIn::class,
				'An anonymous user cannot see Special:Achievements during beta period',
			],
			[
				$duringBeta, $anon, $otherDisabledUserName, 'achievementbadges-target-not-disabled-ab',
				'If the target user does not enable AB, no one can see the list',
			],
			[
				$duringBeta, $anon, $otherEnabledUserName, $ok,
				'If the target user enables AB, everyone can see the list',
			],
			[
				$duringBeta, $loggedInUser, $noSubPage, 'achievementbadges-disabled',
				'A registered user cannot see Special:Achievements during beta period if not enabled it',
			],
			[
				$duringBeta, $loggedInUser, $otherDisabledUserName, 'achievementbadges-target-not-disabled-ab',
				'If the target user does not enable AB, no one can see the list',
			],
			[
				$duringBeta, $loggedInUser, $otherEnabledUserName, $ok,
				'If the target user enables AB, everyone can see the list',
			],
			[
				$duringBeta, $betaEnabledUser, $noSubPage, $ok,
				'A user who enables AB can see achievements on Special:Achievements during beta period',
			],
			[
				$duringBeta, $betaEnabledUser, $otherDisabledUserName, 'achievementbadges-target-not-disabled-ab',
				'If the target user does not enable AB, no one can see the list',
			],
			[
				$duringBeta, $betaEnabledUser, $otherEnabledUserName, $ok,
				'If the target user enables AB, everyone can see the list',
			],
		];
	}

	/**
	 * @dataProvider provideExecutionVariables
	 *
	 * @param bool $isBetaEnabled
	 * @param User|null $user
	 * @param string $subPage
	 * @param string|MWException $expected
	 * @param string $msg
	 */
	public function testDisabledMsg(
		$isBetaEnabled,
		$user,
		$subPage,
		$expected,
		$msg
	) {
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS, [] );
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ENABLE_BETA_FEATURE, $isBetaEnabled );
		if ( $expected == UserNotLoggedIn::class ) {
			$this->expectException( $expected );
			list( $html, ) = $this->executeSpecialPage( $subPage, null, 'qqx', $user );
		} else {
			list( $html, ) = $this->executeSpecialPage( $subPage, null, 'qqx', $user );
			$this->assertStringContainsString( $expected, $html, $msg );
		}
	}

	public function testDisplayHint() {
		$user = new User();
		$user->setName( __METHOD__ );
		$user->addToDatabase();

		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx', null );
		$this->assertStringContainsString( 'achievementbadges-achievement-hint-sign-up', $html,
			'A user can see a hint for not earning achievement' );

		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx', $user );
		$this->assertStringContainsString( 'achievementbadges-achievement-description-sign-up', $html,
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
		$this->assertStringContainsString( 'achievementbadges-achievement-description-special-test-0', $html,
			'Achieved achievement should be shown on Special:Achievements' );

		$info['stats'] = 15;
		Achievement::sendStats( $info );
		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx', $user );
		$this->assertStringContainsString( 'achievementbadges-achievement-description-special-test-0', $html,
			'Achieved achievement should be shown on Special:Achievements' );
		$this->assertStringContainsString( 'achievementbadges-achievement-description-special-test-1', $html,
			'Achieved achievement should be shown on Special:Achievements' );
	}

}
