<?php

namespace MediaWiki\Extension\AchievementBadges\Tests\Integration;

use EchoNotificationMapper;
use MediaWiki\Extension\AchievementBadges\Achievement;
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
	private function assertNotificationNumber( $num, $user, $type, $msg = null ) {
		$notifMapper = new EchoNotificationMapper();
		$limit = 50;
		$notifs = $notifMapper->fetchUnreadByUser( $user, $limit, '', [ $type ] );
		$texts = [];
		foreach ( $notifs as $noti ) {
			$event = $noti->getEvent();
			$texts[] = $event . '(' . implode( ', ', array_values( $event->getExtra() ) ) . ')';
		}
		$this->assertEquals( $num, count( $notifs ), "$msg (" . implode( ', ', $texts ) . ')' );
	}

	private function assertEarnedAchievement( $num, $user, $key ) {
		$dbr = wfGetDB( DB_REPLICA );
		$query = Achievement::getQueryInfo( $dbr );
		$query['conds'] = array_merge( $query['conds'], [
			'log_action' => $key,
			'log_actor' => $user->getActorId(),
		] );
		$actual = $dbr->selectRowCount(
			$query['tables'],
			'*',
			$query['conds']
		);
		$this->assertSame( $num, $actual );
	}

	public function testAchieveEditPages() {
		$user = new User();
		$user->setName( 'EditPageDummy' );
		$user->addToDatabase();
		$this->assertSame( $user->getEditCount(), 0 );

		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS, [
			Constants::ACHV_KEY_EDIT_PAGE => [
				'type' => 'stats',
				'thresholds' => [ 1, 2 ],
			],
		] );
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_DISABLED_ACHIEVEMENTS, [
			Constants::ACHV_KEY_CREATE_PAGE,
			Constants::ACHV_KEY_EDIT_SIZE,
		] );

		$ct = 1;
		// Edit a page
		$this->editPage( 'Edit Test', str_repeat( 'lorem', $ct++ ), '', NS_MAIN, $user );
		$this->assertSame( $user->getEditCount(), 1 );
		$this->assertNotificationNumber( 1, $user, Constants::EVENT_KEY_EARN,
			"edit-page-0 should be achieved (edit count: {$user->getEditCount()})" );
		$this->assertEarnedAchievement( 1, $user, Constants::ACHV_KEY_EDIT_PAGE );

		// Edit another page
		$this->editPage( 'Edit Test', str_repeat( 'lorem', $ct++ ), '', NS_MAIN, $user );
		$this->assertSame( $user->getEditCount(), 2 );
		$this->assertNotificationNumber( 2, $user, Constants::EVENT_KEY_EARN,
			"Only edit-page-0 should be achieved (edit count: {$user->getEditCount()})" );
		$this->assertEarnedAchievement( 2, $user, Constants::ACHV_KEY_EDIT_PAGE );
	}

	public function testAchieveCreatePages() {
		$user = new User();
		$user->setName( 'CreatePageDummy' );
		$user->addToDatabase();

		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS, [
			Constants::ACHV_KEY_CREATE_PAGE => [
				'type' => 'stats',
				'thresholds' => [ 1, 2 ],
			],
		] );
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_DISABLED_ACHIEVEMENTS, [
			Constants::ACHV_KEY_EDIT_PAGE,
			Constants::ACHV_KEY_EDIT_SIZE,
			] );

		$ct = 1;
		// Create a page
		$this->editPage( 'Creation test' . $ct++, 'ipsum', '', NS_MAIN, $user );
		$this->assertNotificationNumber( 1, $user, Constants::EVENT_KEY_EARN,
			"create-page-0 should be achieved" );
		$this->assertEarnedAchievement( 1, $user, Constants::ACHV_KEY_CREATE_PAGE );

		// Create another page
		$this->editPage( 'Creation test' . $ct++, 'ipsum', '', NS_MAIN, $user );
		$this->assertNotificationNumber( 2, $user, Constants::EVENT_KEY_EARN,
			"Only create-page-0 should be achieved" );
		$this->assertEarnedAchievement( 2, $user, Constants::ACHV_KEY_CREATE_PAGE );
	}

	public function testAchieveEditSize() {
		$user = new User();
		$user->setName( 'EditSizePageDummy' );
		$user->addToDatabase();

		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS, [
			Constants::ACHV_KEY_EDIT_SIZE => [
				'type' => 'stats',
				'thresholds' => [ 10, 100 ],
			],
		] );

		$this->editPage( 'Size test', str_repeat( 'ipsum', 10 ), '', NS_MAIN, $user );
		$this->assertNotificationNumber( 1, $user, Constants::EVENT_KEY_EARN,
			"edit-size-0 should be achieved" );
		$this->assertEarnedAchievement( 1, $user, Constants::ACHV_KEY_EDIT_SIZE );

		$this->editPage( 'Size test', str_repeat( 'ipsum', 30 ), '', NS_MAIN, $user );
		$this->assertNotificationNumber( 2, $user, Constants::EVENT_KEY_EARN,
			"Only edit-size-0 should be achieved" );
		$this->assertEarnedAchievement( 2, $user, Constants::ACHV_KEY_EDIT_SIZE );
	}

	public function testAchieveEditSizeExist() {
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS, [
			Constants::ACHV_KEY_EDIT_SIZE => [
				'type' => 'stats',
				'thresholds' => [ 10, 100 ],
			],
		] );

		$sysop = self::getTestSysop()->getUser();
		$titleText = 'Existing size test';
		$content = str_repeat( 'lorem ipsum', 10 );
		$this->editPage( $titleText, $content, '', NS_MAIN, $sysop );

		$user = new User();
		$user->setName( 'ExistingEditSizePageDummy' );
		$user->addToDatabase();

		$this->editPage( $titleText, $content . 'dolor', '', NS_MAIN, $user );
		$this->assertNotificationNumber( 0, $user, Constants::EVENT_KEY_EARN,
			"edit-size-0 should not be achieved" );
		$this->assertEarnedAchievement( 0, $user, Constants::ACHV_KEY_EDIT_SIZE );
	}

	public function testAchieveManyEditSize() {
		$user = new User();
		$user->setName( __METHOD__ );
		$user->addToDatabase();

		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS, [
			Constants::ACHV_KEY_EDIT_SIZE => [
				'type' => 'stats',
				'thresholds' => [ 10, 20, 30, 40, 50 ],
			],
		] );
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_DISABLED_ACHIEVEMENTS, [
			Constants::ACHV_KEY_EDIT_PAGE,
			Constants::ACHV_KEY_CREATE_PAGE,
			] );

		$this->editPage( __METHOD__, str_repeat( 'ipsum', 30 ), '', NS_MAIN, $user );
		$this->assertNotificationNumber( 5, $user, Constants::EVENT_KEY_EARN,
			"All edit-size should be achieved" );
		$this->assertEarnedAchievement( 5, $user, Constants::ACHV_KEY_EDIT_SIZE );
	}

	public function testLongUserPage() {
		global $wgAchievementBadgesAchievements;
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS, [
			Constants::ACHV_KEY_LONG_USER_PAGE => $wgAchievementBadgesAchievements[Constants::ACHV_KEY_LONG_USER_PAGE]
		] );
		$user = new User();
		$user->setName( 'UserPageDummy' );
		$user->addToDatabase();
		$longText = str_repeat( 'lorem ipsum dolor amat', 40 );

		$this->editPage( $user->getName(), $longText, '', NS_USER, $user );
		$this->assertNotificationNumber( 1, $user, Constants::EVENT_KEY_EARN,
			'Should be achieved long-user-page' );
		$this->assertEarnedAchievement( 1, $user, Constants::ACHV_KEY_LONG_USER_PAGE );
	}

	public function testAchieveSignIn() {
		$this->assertSame( 1, 1 );
		// @todo
	}
}
