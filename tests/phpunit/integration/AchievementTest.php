<?php

namespace MediaWiki\Extension\AchievementBadges\Tests\Integration;

use MediaWiki\Extension\AchievementBadges\Achievement;
use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWiki\Extension\AchievementBadges\HookHandler\Main;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use MWException;
use User;

/**
 * @group AchievementBadges
 *
 * @covers \MediaWiki\Extension\AchievementBadges\Achievement
 */
class AchievementTest extends MediaWikiIntegrationTestCase {

	/** Config $config */
	private $config;

	/** @inheritDoc */
	protected function setUp(): void {
		parent::setUp();
		Main::initExtension();
		$this->config = MediaWikiServices::getInstance()->getMainConfig();
	}

	/**
	 * @covers \MediaWiki\Extension\AchievementBadges\Achievement::isAchievementBadgesAvailable
	 */
	public function testIsAchievementBadgesAvailable() {
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_DISABLED_ACHIEVEMENTS, [
			Constants::ACHV_KEY_SIGN_UP, Constants::ACHV_KEY_ENABLE_ACHIEVEMENT_BADGES ] );

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
		$user->saveSettings();
		$this->assertTrue( Achievement::isAchievementBadgesAvailable( $user ),
			'A user which enables AB can use AB where wiki uses AB as a beta feature' );
	}

	private function assertLogging( $user, $key, $maxIndex = null ) {
		$logs = [];
		for ( $i = 0; $i <= $index; $i++ ) {
			$params = [
				'4::key' => $key,
			];
			if ( $index !== null ) {
				$params['5::index'] = $i;
			}
			$logs[] = [
				Constants::LOG_TYPE,
				$key,
				serialize( $params )
			];
		}

		$this->assertSelect(
			'logging',
			[ 'log_type', 'log_action', 'log_params' ],
			[
				'log_type' => Constants::LOG_TYPE,
				'log_action' => $key,
				'log_actor' => $user->getActorId(),
			],
			$logs
		);
	}

	/**
	 * @covers \MediaWiki\Extension\AchievementBadges\Achievement::sendStats
	 */
	public function testSendStats() {
		$key = 'test-achievements';
		$key2 = 'test-achievements-2';
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS, [
			$key => [
				'type' => 'stats',
				'thresholds' => [ 1, 10, 100 ],
			],
			$key2 => [
				'type' => 'stats',
				'thresholds' => [ 1, 3 ],
			],
		] );
		$user = $this->getTestUser()->getUser();

		$this->expectException( MWException::class );
		Achievement::sendStats( [ 'user' => $user, 'stats' => 1 ],
			'Calls to sendStats without key should throw exception' );

		$info = [
			'key' => $key,
			'user' => $user,
		];
		$info['stats'] = 1;
		Achievement::sendStats( $info );
		$this->assertLogging( $user, $key, 0 );
		$info['stats'] = 2;
		Achievement::sendStats( $info );
		$this->assertLogging( $user, $key, 0 );
		$info['stats'] = 9;
		Achievement::sendStats( $info );
		$this->assertLogging( $user, $key, 0 );
		$info['stats'] = 10;
		Achievement::sendStats( $info );
		$this->assertLogging( $user, $key, 1 );
		$info['stats'] = 100;
		Achievement::sendStats( $info );
		$this->assertLogging( $user, $key, 2 );

		# Test 2
		$info['key'] = $key2;
		$info['stats'] = 0;
		Achievement::sendStats( $info );
		$this->assertLogging( $user, $key, 0 );
		$info['stats'] = 1;
		Achievement::sendStats( $info );
		$this->assertLogging( $user, $key, 1 );
		$info['stats'] = 2;
		Achievement::sendStats( $info );
		$this->assertLogging( $user, $key, 1 );
		$info['stats'] = 3;
		Achievement::sendStats( $info );
		$this->assertLogging( $user, $key, 2 );
	}
}
