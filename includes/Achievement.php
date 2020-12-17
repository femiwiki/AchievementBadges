<?php

namespace MediaWiki\Extension\AchievementBadges;

use EchoEvent;
use MWException;

class Achievement {
	/**
	 * You should not call the constructor.
	 */
	protected function __construct() {
	}
	/**
	 * @param array $info arguments:
	 * key:
	 * user: The user who earned the achievement;
	 */
	public static function earn( $info ) {
		if ( empty( $info['key'] ) ) {
			throw new MWException( "'key' parameter is mandatory" );
		}

		wfDebug( '[AchievementBadges] ' . $info['user']->getName() . ' earned ' . $info['key'] );
		$result = EchoEvent::create( [
			'type' => Constants::EVENT_KEY_EARN,
			'extra' => [
				'key' => $info['key'],
			],
			'agent' => $info['user'],
		] );
	}
}
