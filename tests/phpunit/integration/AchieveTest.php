<?php

namespace MediaWiki\Extension\AchievementBadges\Tests\Integration;

use EchoNotificationMapper;
use MediaWiki\Extension\AchievementBadges\Constants;
use MediaWikiIntegrationTestCase;
use User;

/**
 * @group AchievementBadges
 * @group Database
 *
 * @covers \MediaWiki\Extension\AchievementBadges\Achievement
 * @covers \MediaWiki\Extension\AchievementBadges\Achievement
 */
class AchieveTest extends MediaWikiIntegrationTestCase {

	/** @inheritDoc */
	protected function setUp(): void {
		parent::setUp();
	}

	/**
	 * @param int $num
	 * @return int
	 */
	private function assertNotificationNumber( $user, $type, $num, $msg = null ) {
		$notifMapper = new EchoNotificationMapper();
		$limit = 50;
		$notifs = $notifMapper->fetchUnreadByUser( $user, $limit, '', [ $type ] );
		$texts = [];
		foreach ( $notifs as $noti ) {
			$event = $noti->getEvent();
			$texts[] = $event . '(' . implode( ', ', array_values( $event->getExtra() ) ) . ')';
		}
		$this->assertEquals( count( $notifs ), $num, "$msg (" . implode( ', ', $texts ) . ')' );
	}

	private function assertEarnedAchievement( $user, $key, $num = 0 ) {
		$logs = [];
		for ( $i = 0; $i < $num; $i++ ) {
			$logs[] = [
				Constants::LOG_TYPE,
				$key,
				serialize( [
					'4::key' => $key,
					'5::index' => $i,
				] )
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

	public function testAchieveEditPages() {
		$user = new User();
		$user->setName( 'EditPageDummy' );
		$user->addToDatabase();
		$this->assertSame( $user->getEditCount(), 0 );

		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS, [
			Constants::ACHV_KEY_EDIT_PAGE => [
				'type' => 'stats',
				'thresholds' => [ 1, 3 ],
			],
		] );
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_DISABLED_ACHIEVEMENTS, [
			Constants::ACHV_KEY_CREATE_PAGE
		] );

		$ct = 1;
		// Edit a page
		$this->editPage( 'Edit Test', str_repeat( 'lorem', $ct++ ), '', NS_MAIN, $user );
		$this->assertSame( $user->getEditCount(), 1 );
		$this->assertNotificationNumber( $user, Constants::EVENT_KEY_EARN, 1,
			"edit-page-0 should be achieved (edit count: {$user->getEditCount()})" );
		$this->assertEarnedAchievement( $user, Constants::ACHV_KEY_EDIT_PAGE, 1 );

		// Edit another page
		$this->editPage( 'Edit Test', str_repeat( 'lorem', $ct++ ), '', NS_MAIN, $user );
		$this->assertSame( $user->getEditCount(), 2 );
		$this->assertNotificationNumber( $user, Constants::EVENT_KEY_EARN, 1,
			"Only edit-page-0 should be achieved (edit count: {$user->getEditCount()})" );
		$this->assertEarnedAchievement( $user, Constants::ACHV_KEY_EDIT_PAGE, 1 );

		// More edit
		$this->editPage( 'Edit Test', str_repeat( 'lorem', $ct++ ), '', NS_MAIN, $user );
		$this->assertNotificationNumber( $user, Constants::EVENT_KEY_EARN, 2,
			"edit-page-1 should be achieved (edit count: {$user->getEditCount()})" );
		$this->assertEarnedAchievement( $user, Constants::ACHV_KEY_EDIT_PAGE, 2 );
	}

	public function testAchieveCreatePages() {
		$user = new User();
		$user->setName( 'CreatePageDummy' );
		$user->addToDatabase();

		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS, [
			Constants::ACHV_KEY_CREATE_PAGE => [
				'type' => 'stats',
				'thresholds' => [ 1, 3 ],
			],
		] );
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_DISABLED_ACHIEVEMENTS, [
			Constants::ACHV_KEY_EDIT_PAGE
		] );

		$ct = 1;
		// create a page
		$this->editPage( 'Creation test' . $ct++, 'ipsum', '', NS_MAIN, $user );
		$this->assertNotificationNumber( $user, Constants::EVENT_KEY_EARN, 1,
			"create-page-0 should be achieved" );
		$this->assertEarnedAchievement( $user, Constants::ACHV_KEY_CREATE_PAGE, 1 );

		// create another page
		$this->editPage( 'Creation test' . $ct++, 'ipsum', '', NS_MAIN, $user );
		$this->assertNotificationNumber( $user, Constants::EVENT_KEY_EARN, 1,
			"Only create-page-0 should be achieved" );
		$this->assertEarnedAchievement( $user, Constants::ACHV_KEY_CREATE_PAGE, 1 );

		// More creation
		$this->editPage( 'Creation test' . $ct++, 'ipsum-', '', NS_MAIN, $user );
		$this->assertNotificationNumber( $user, Constants::EVENT_KEY_EARN, 2,
			"create-page-1 should be achieved" );
		$this->assertEarnedAchievement( $user, Constants::ACHV_KEY_CREATE_PAGE, 2 );
	}

	public function testLongUserPage() {
		$user = new User();
		$user->setName( 'UserPageDummy' );
		$user->addToDatabase();
		$longText = str_repeat( 'lorem ipsum dolor amat', 40 );

		$this->editPage( $user->getName(), $longText, '', NS_USER, $user );
		$this->assertNotificationNumber( $user, Constants::EVENT_KEY_EARN, 3,
			'Should be achieved long-user-page, create-page1, edit-page1' );
		$this->assertEarnedAchievement( $user, Constants::ACHV_KEY_EDIT_PAGE, 1 );
	}

	public function testAchieveSignIn() {
		$this->assertSame( 1, 1 );
		// @todo
	}
}
