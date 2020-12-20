<?php

namespace MediaWiki\Extension\AchievementBadges;

class LogFormatter extends \LogFormatter {

	/**
	 * @inheritDoc
	 */
	protected function getMessageParameters() {
		$params = parent::getMessageParameters();
		$key = $params[3];
		$msg = 'achievement-name-' . $key;
		if ( isset( $params['index'] ) && $params['index'] > 0 ) {
			$msg .= (string)( $params['index'] + 1 );
		}
		$msg = $this->msg( $msg );
		$params[3] = $msg->plain();
		return $params;
	}

	/** @inheritDoc */
	public function getMessageKey() {
		return 'logentry-achievementbadges';
	}
}
