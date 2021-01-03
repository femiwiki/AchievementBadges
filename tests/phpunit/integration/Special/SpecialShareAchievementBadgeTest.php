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

	/**
	 * @param string $key
	 * @return int
	 */
	private function getAchievedUserId( $key ) {
		$this->setMwGlobals( 'wg' . Constants::CONFIG_KEY_ACHIEVEMENTS, [
			$key => [
				'type' => 'instant'
			]
		] );
		$user = new User();
		$name = 'ShareAchievementTester';
		$user->setName( $name );
		$user->addToDatabase();
		$user->setOption( 'language', 'qqx' );
		$user->saveSettings();
		Achievement::achieve( [ 'user' => $user, 'key' => $key ] );
		return $user->getId();
	}

	public function testExecute() {
		$key = 'share-badge1';
		$id = $this->getAchievedUserId( $key );

		list( $html, ) = $this->executeSpecialPage( base64_encode( "$id/$key" ), null, 'qqx' );
		$this->assertStringContainsString( 'special-shareachievementsbadge-message', $html );
	}

	public function testMetaTags() {
		$key = 'share-badge2';
		$id = $this->getAchievedUserId( $key );

		$page = $this->newSpecialPage();
		$output = $page->getOutput();
		$output->setTitle( $page->getPageTitle() );

		$page->execute( base64_encode( "$id/$key" ) );
		$head = $output->headElement( $output->getContext()->getSkin() );
		$this->assertStringContainsString( '<meta name="title"', $head );
		$this->assertStringContainsString( '<meta property="og:title"', $head );
		$this->assertStringContainsString( '<meta property="og:description"', $head );
		$this->assertStringContainsString( 'special-shareachievementsbadge-external-description', $head );
	}

}
