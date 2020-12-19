<?php

namespace MediaWiki\Extension\AchievementBadges;

use EchoEvent;
use EchoEventPresentationModel;
use Language;
use Message;
use SpecialPage;
use User;

class EarnEchoEventPresentationModel extends EchoEventPresentationModel {
	/**
	 * @var array
	 */
	private $achievement_key;
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
		$this->achievement_key = $this->event->getExtraParam( 'key' );
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
		return 'placeholder';
	}

	/**
	 * @inheritDoc
	 */
	public function getPrimaryLink() {
		$fragment = 'achievement-' . $this->achievement_key;
		$title = SpecialPage::getTitleFor( SpecialAchievements::PAGE_NAME, false, $fragment ?? '' );
		$link = $this->getPageLink( $title, '', true );
		return $link;
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderMessage() : Message {
		$achievementKey = $this->achievement_key;

		$msg = $this->getMessageWithAgent( 'notification-header-achievementbadges-earn' );
		$msg->params( $this->msg( 'achievement-name-' . $achievementKey ) );
		return $msg;
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyMessage() {
		$key = $this->achievement_key;
		$msg = $this->getMessageWithAgent( "achievement-description-$key" );

		return $msg;
	}
}
