<?php

namespace MediaWiki\Extension\AchievementBadges;

use EchoEvent;
use ManualLogEntry;
use MWException;
use SpecialPage;

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

		$user = $info['user'];
		if ( !Utils::isAchievementBadgesAvailable( $user ) ) {
			return;
		}
		$key = $info['key'];

		$dbr = wfGetDB( DB_REPLICA );
		$query = [];
		$count = $dbr->selectRowCount(
			[ 'logging', 'actor' ],
			'*',
			[
				'log_type' => Constants::LOG_TYPE,
				'log_action' => $key,
				'actor_user' => $user->getId(),
			],
			__METHOD__,
			[],
			[
				'actor' => [ 'JOIN', 'actor_id = log_actor' ],
			]
		);
		if ( $count ) {
			// The achievement was earned already.
			return;
		}

		wfDebug( '[AchievementBadges] ' . $user->getName() . ' earned ' . $key );

		$logEntry = new ManualLogEntry( Constants::LOG_TYPE, $key );
		$logEntry->setPerformer( $user );
		$logEntry->setTarget( SpecialPage::getTitleFor( SpecialAchievements::PAGE_NAME ) );
		$logEntry->setParameters( [
			'4::key' => $key,
		] );
		$logEntry->insert();

		$result = EchoEvent::create( [
			'type' => Constants::EVENT_KEY_EARN,
			'extra' => [
				'key' => $key,
			],
			'agent' => $user,
		] );
	}
}
