<?php

namespace MediaWiki\Extension\AchievementBadges;

use EchoEvent;
use EchoEventPresentationModel;
use Language;
use MediaWiki\Extension\AchievementBadges\Special\SpecialAchievements;
use Message;
use SpecialPage;
use User;

class EarnEchoEventPresentationModel extends EchoEventPresentationModel {
	/**
	 * @var string
	 */
	private $achievementKey;

	/**
	 * @inheritDoc
	 */
	protected function __construct(
		EchoEvent $event,
		Language $language,
		User $user,
		$distributionType
	) {
		parent::__construct( $event, $language, $user, $distributionType );
		$this->achievementKey = $event->getExtraParam( 'key' );
	}

	/**
	 * @inheritDoc
	 */
	public function canRender() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconType() {
		return Constants::EVENT_KEY_EARN;
	}

	/**
	 * @inheritDoc
	 */
	public function getPrimaryLink() {
		if ( !$this->achievementKey ) {
			return false;
		}
		$key = 'achievement-' . $this->achievementKey;
		$title = SpecialPage::getTitleFor( SpecialAchievements::PAGE_NAME, false, $key );
		$link = $this->getPageLink( $title, '', true );
		return $link;
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderMessage() : Message {
		$key = $this->achievementKey;

		$msg = $this->getMessageWithAgent( 'notification-header-achievementbadges-earn' );
		$msg->params( $this->msg( 'achievement-name-' . $key ) );
		return $msg;
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyMessage() {
		$key = $this->achievementKey;
		$msg = $this->getMessageWithAgent( "achievement-description-$key" );

		return $msg;
	}
}
