<?php

namespace MediaWiki\Extension\AchievementBadges\Tests\Integration;

use MediaWiki\Extension\AchievementBadges\Achievement;
use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWiki\Extension\AchievementBadges\HookHandler\Main;
use MediaWikiIntegrationTestCase;
use MWException;
use User;

/**
 * @group AchievementBadges
 *
 * @covers MediaWiki\Extension\AchievementBadges\Achievement
 */
class AchievementTest extends MediaWikiIntegrationTestCase {

	/** @inheritDoc */
	protected function setUp(): void {
		parent::setUp();
		Main::initExtension();
	}

	/**
	 * @covers MediaWiki\Extension\AchievementBadges\Achievement::isAchievementBadgesAvailable
	 */
	public function testIsAchievementBadgesAvailable() {
		$systemUser = User::newSystemUser( __METHOD__ );
		$anon = new User;
		$user = $this->getTestUser()->getUser();
		$this->assertFalse( Achievement::isAchievementBadgesAvailable( $systemUser ),
			'A system user cannot use AB' );

		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ENABLE_BETA_FEATURE, false );
		$this->assertTrue( Achievement::isAchievementBadgesAvailable( $user ),
			'Every user can use AB where wiki uses AB as a default' );
		$this->assertTrue( Achievement::isAchievementBadgesAvailable( $anon ),
			'A anonymous user can use AB where wiki uses AB as a default' );

		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ENABLE_BETA_FEATURE, true );
		$this->assertFalse( Achievement::isAchievementBadgesAvailable( $user ),
			'A user which do not enable AB cannot use AB where wiki uses AB as a beta feature' );
		$this->assertFalse( Achievement::isAchievementBadgesAvailable( $anon ),
			'A anonymous user cannot use AB where wiki uses AB as a beta feature' );

		$user = $this->getMutableTestUser()->getUser();
		$user->setOption( Constants::PREF_KEY_ACHIEVEMENT_ENABLE, '1' );
		$this->expectException( MWException::class,
			'Saving preferences during the test throws exception because the unregistered sign-up achievement' );
		$user->saveSettings();
		$this->assertTrue( Achievement::isAchievementBadgesAvailable( $user ),
			'A user which enables AB can use AB where wiki uses AB as a beta feature' );
	}
}
