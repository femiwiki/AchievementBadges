<?php

namespace MediaWiki\Extension\AchievementBadges;

class LogFormatter extends \LogFormatter {

	/**
	 * @inheritDoc
	 */
	protected function getMessageParameters() {
		$params = parent::getMessageParameters();
		$achievementKey = $params[3];
		$achievementNameMsgKey = 'achievement-name-' . $achievementKey;
		if ( isset( $params[4] ) ) {
			$achievementNameMsgKey .= (string)( $params[4] + 1 );
		}
		$achievementNameMsg = $this->msg( $achievementNameMsgKey );
		$params[3] = $achievementNameMsg->plain();
		return $params;
	}

	/** @inheritDoc */
	public function getMessageKey() {
		return 'logentry-achievementbadges';
	}
}
