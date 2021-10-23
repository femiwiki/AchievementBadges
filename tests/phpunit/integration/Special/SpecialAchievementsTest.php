<?php

namespace MediaWiki\Extension\AchievementBadges\Tests\Integration;

use MediaWiki\Extension\AchievementBadges\Achievement;
use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWiki\Extension\AchievementBadges\Hooks\HookRunner;
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
		return new SpecialAchievements( $this->createMock( HookRunner::class ) );
	}

	/** @return array */
	public function provideExecutionVariables() {
		$whenBeta = true;
		$whenNotBeta = false;

		$anon = null;
		$loggedInUser = false;
		$viewerEnablingTheFeature = true;
		$viewerNotEnablingTheFeature = $loggedInUser;

		$queriesThemselves = null;
		$queriesOtherUser = true;
		$queriesOtherDisablingUser = false;
		$queriesOtherEnablingUser = $queriesOtherUser;

		$isOk = 'achievements-summary';
		return [
			'An anonymous user should not see Special:Achievements' => [
				$whenNotBeta, $anon, $queriesThemselves, $isOk,
			],
			'An anonymous user should not see Special:Achievements for other' => [
				$whenNotBeta, $anon, $queriesOtherUser, $isOk,
			],
			'A registered user should see Special:Achievements' => [
				$whenNotBeta, $loggedInUser, $queriesThemselves, $isOk,
			],
			'A registered user should see Special:Achievements for other' => [
				$whenNotBeta, $loggedInUser, $queriesOtherUser, $isOk,
			],
			// Below tests are worth on the beta period of AchievementBadges extension itself.
			'An anonymous user should not see Special:Achievements during beta period' => [
				$whenBeta, $anon, $queriesThemselves, UserNotLoggedIn::class,
			],
			'If the target user does not enable AB, no one should see the list' => [
				$whenBeta, $anon, $queriesOtherDisablingUser, 'achievementbadges-target-not-disabled-ab',
			],
			'If the target user enables AB, everyone should see the list' => [
				$whenBeta, $anon, $queriesOtherEnablingUser, $isOk,
			],
			'A registered user should not see Special:Achievements during beta period if not enabled it' => [
				$whenBeta, $viewerNotEnablingTheFeature, $queriesThemselves, 'achievementbadges-disabled',
			],
			'If the target user does not enable AB, no one should see the list' => [
				$whenBeta, $viewerNotEnablingTheFeature, $queriesOtherUser, 'achievementbadges-target-not-disabled-ab',
			],
			'If the target user enables AB, everyone should see the list' => [
				$whenBeta, $viewerNotEnablingTheFeature, $queriesOtherUser, $isOk,
			],
			'A user who enables AB should see achievements on Special:Achievements during beta period' => [
				$whenBeta, $viewerEnablingTheFeature, $queriesThemselves, $isOk,
			],
			'If the target user does not enable AB, no one should see the list' => [
				$whenBeta, $viewerEnablingTheFeature, $queriesOtherUser, 'achievementbadges-target-not-disabled-ab',
			],
			'If the target user enables AB, everyone should see the list' => [
				$whenBeta, $viewerEnablingTheFeature, $queriesOtherUser, $isOk,
			],
		];
	}

	/**
	 * @dataProvider provideExecutionVariables
	 *
	 * @param bool $isBetaEnabled
	 * @param bool|null $viewerIsEnabledBeta
	 * @param bool|null $queriesAbout
	 * @param string|MWException $expected
	 */
	public function testDisabledMsg(
		$isBetaEnabled,
		$viewerIsEnabledBeta,
		$queriesAbout,
		$expected
	) {
		$this->setMwGlobals( [
			'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS => [],
			'wg' . Constants::CONFIG_KEY_ENABLE_BETA_FEATURE => $isBetaEnabled
		] );
		$optionManager = $this->getServiceContainer()->getUserOptionsManager();

		if ( $viewerIsEnabledBeta === null ) {
			$viewer = null;
		} else {
			$viewer = new User();
			$viewer->setName( 'viewer' );
			$viewer->addToDatabase();
			$optionManager->setOption( $viewer, Constants::PREF_KEY_ACHIEVEMENT_ENABLE,
				$viewerIsEnabledBeta ? '1' : '0' );
			$viewer->saveSettings();
		}

		if ( $queriesAbout === null ) {
			$subpage = '';
		} else {
			$otherUser = new User();
			$otherUser->setName( 'otherUser' );
			$otherUser->addToDatabase();
			$optionManager->setOption( $otherUser, Constants::PREF_KEY_ACHIEVEMENT_ENABLE, $queriesAbout ? '1' : '0' );
			$otherUser->saveSettings();
			$subpage = $otherUser->getName();
		}

		if ( $expected == UserNotLoggedIn::class ) {
			$this->expectException( $expected );
			list( $html, ) = $this->executeSpecialPage( $subpage, null, 'qqx', $viewer );
		} else {
			list( $html, ) = $this->executeSpecialPage( $subpage, null, 'qqx', $viewer );
			$this->assertStringContainsString( $expected, $html );
		}
	}

	public function testDisplayHint() {
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ENABLE_BETA_FEATURE, false );
		$user = new User();
		$user->setId( 1 );
		$user->setName( 'DisplayHintUser' );
		$optionManager = $this->getServiceContainer()->getUserOptionsManager();
		$optionManager->setOption( $user, Constants::PREF_KEY_ACHIEVEMENT_ENABLE, '1' );
		$user->addToDatabase();

		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx', $user );
		$this->assertStringContainsString( 'achievementbadges-achievement-hint-long-user-page', $html,
			'A user can see a hint for not earning achievement' );
	}

	public function testStatsAchievement() {
		$key = 'special-test';
		$this->setMwGlobals( [
			'wg' . Constants::CONFIG_KEY_ENABLE_BETA_FEATURE => false,
			'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS => [
				$key => [
					'type' => 'stats',
					'thresholds' => [ 1, 10, 100 ],
				],
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
