<?php

namespace MediaWiki\Extension\AchievementBadges\Notifications;

use EchoEventPresentationModel;
use MediaWiki\Extension\AchievementBadges\SpecialAchievements;
use Message;
use SpecialPage;

class EarnPresentationModel extends EchoEventPresentationModel {

	public function canRender() {
		return true;
	}

	/**
	 * @return string
	 */
	public function getIconType() {
		return 'placeholder';
	}

	/**
	 * @return array
	 */
	public function getPrimaryLink() {
		$fragment = 'achievement-' . $this->event->getExtraParam( 'key' );
		$title = SpecialPage::getTitleFor( SpecialAchievements::PAGE_NAME, false, $fragment ?? '' );
		$link = $this->getPageLink( $title, '', true );
		return $link;
	}

	/**
	 * @return Message
	 */
	public function getHeaderMessage() : Message {
		$achievementKey = $this->event->getExtraParam( 'key' );

		$msg = $this->getMessageWithAgent( 'notification-header-achievementbadges-earn' );
		$msg->params( $this->msg( 'achievement-name-' . $achievementKey ) );
		return $msg;
	}
}
