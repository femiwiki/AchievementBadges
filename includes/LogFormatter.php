<?php

namespace MediaWiki\Extension\AchievementBadges;

class LogFormatter extends \LogFormatter {

	/**
	 * @inheritDoc
	 */
	protected function getMessageParameters() {
		$params = parent::getMessageParameters();

		if ( isset( $params[3] ) ) {
			$msg = $this->msg( 'achievement-name-' . $params[3] );
			$params[3] = $msg->plain();
		}
		return $params;
	}

	/** @inheritDoc */
	public function getMessageKey() {
		return 'logentry-achievementbadges';
	}
}
