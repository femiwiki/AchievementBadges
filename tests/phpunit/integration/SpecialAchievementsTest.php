<?php

namespace MediaWiki\Extension\AchievementBadges\Tests\Integration;

use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWiki\Extension\AchievementBadges\SpecialAchievements;
use SpecialPageTestBase;
use User;
use UserNotLoggedIn;
use Wikimedia\TestingAccessWrapper;

/**
 * @group AchievementBadges
 *
 * @covers \MediaWiki\Extension\AchievementBadges\SpecialAchievements
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

	/**
	 * @todo Add more data
	 * @return array
	 */
	public static function provideIconPaths() {
		return [
			[ 'en', '/path/to/icon.svg', '/path/to/icon.svg' ],
			[
				'en',
				[
					'en' => '/path/to/icon.svg',
					'ko' => '/path/to/icon-ko.svg',
					'ru' => '/path/to/icon-ru.svg',
				],
				'/path/to/icon.svg'
			],
			[
				'ko',
				[
					'en' => '/path/to/icon.svg',
					'ko' => '/path/to/icon-ko.svg',
					'ru' => '/path/to/icon-ru.svg',
				],
				'/path/to/icon-ko.svg'
			],
			[
				'he',
				[
					'en' => '/path/to/icon.svg',
					'ko' => '/path/to/icon-ko.svg',
					'rtl' => '/path/to/icon-rtl.svg',
				],
				'/path/to/icon-rtl.svg'
			],
		];
	}

	/**
	 * @dataProvider provideIconPaths
	 * @covers \MediaWiki\Extension\AchievementBadges\SpecialAchievements::getAchievementIcon()
	 *
	 * @param string $lang
	 * @param string|array $path
	 * @param string $fallback
	 */
	public function testGetAchievementIcon( $lang, $path, $fallback ) {
		$this->setContentLang( $lang );
		$page = $this->newSpecialPage();
		$wrappedPage = TestingAccessWrapper::newFromObject( $page );

		$this->assertEquals( $wrappedPage->getAchievementIcon( $path ), $fallback,
			'Returns as it is passed' );
	}

}
