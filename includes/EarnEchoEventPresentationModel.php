<?php

namespace MediaWiki\Extension\AchievementBadges;

use EchoEvent;
use EchoEventPresentationModel;
use Language;
use MediaWiki\Extension\AchievementBadges\Special\SpecialAchievements;
use MediaWiki\Extension\AchievementBadges\Special\SpecialShareAchievementBadge;
use Message;
use SpecialPage;
use User;

class EarnEchoEventPresentationModel extends EchoEventPresentationModel {
	/** @var string */
	private $achievementKey;

	/** @inheritDoc */
	protected function __construct(
		EchoEvent $event,
		Language $language,
		User $user,
		$distributionType
	) {
		parent::__construct( $event, $language, $user, $distributionType );
		$this->achievementKey = $event->getExtraParam( 'key' );
	}

	/** @inheritDoc */
	public function canRender() {
		return true;
	}

	/** @inheritDoc */
	public function getIconType() {
		return Constants::EVENT_KEY_EARN;
	}

	/** @inheritDoc */
	public function getPrimaryLink() {
		if ( $this->isBundled() ) {
			return $this->getSpecialAchievementsLink();
		} else {
			if ( !$this->achievementKey ) {
				return false;
			}
			$agent = $this->event->getAgent()->getName();
			$key = $this->achievementKey;
			$title = SpecialPage::getTitleFor( SpecialShareAchievementBadge::PAGE_NAME, "$agent/$key" );
			$link = $this->getPageLink( $title, '', true );
			return $link;
		}
	}

	/**
	 * @return array
	 */
	private function getSpecialAchievementsLink() {
		$title = SpecialPage::getTitleFor( SpecialAchievements::PAGE_NAME );
		return [
			'url' => $title->getFullURL(),
			'label' => $this->msg( 'notification-link-text-all-achievements' )->plain(),
			'tooltip' => $title->getPrefixedText(),
			'icon' => 'article',
		];
	}

	/** @inheritDoc */
	public function getSecondaryLinks() {
		if ( $this->isBundled() ) {
			return [];
		} else {
			return [ $this->getSpecialAchievementsLink() ];
		}
	}

	/** @inheritDoc */
	public function getHeaderMessage() : Message {
		if ( $this->isBundled() ) {
			$msg = $this->getMessageWithAgent( 'notification-bundle-header-achievementbadges-earn' );
			$count = $this->getNotificationCountForOutput();
			$msg->numParams( $count );
			return $msg;
		} else {
			$msg = $this->getMessageWithAgent( 'notification-header-achievementbadges-earn' );
			$key = $this->achievementKey;
			$agent = $this->event->getAgent();
			$msg->params( $this->msg( "achievement-name-$key", $agent->getName() ) );
			return $msg;
		}
	}

	/** @inheritDoc */
	public function getBodyMessage() {
		if ( $this->isBundled() ) {
			return false;
		} else {
			$key = $this->achievementKey;
			$agent = $this->event->getAgent();
			$msg = $this->msg( "achievement-description-$key", $agent->getName() );
			return $msg;
		}
	}

	/** @inheritDoc */
	public function getCompactHeaderMessage() {
		$key = $this->achievementKey;
		$agent = $this->event->getAgent();
		return $this->msg( "achievement-name-$key", $agent->getName() );
	}
}
