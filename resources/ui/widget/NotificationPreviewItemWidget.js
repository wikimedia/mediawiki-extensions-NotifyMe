ext.notifyme.ui.widget.NotificationPreviewItemWidget = function ( data ) {
	ext.notifyme.ui.widget.NotificationPreviewItemWidget.parent.call( this, {} );
	mws.galaxyIntegration.ForeignWikiBadge.call( this, data._source_wiki || null ); // eslint-disable-line no-underscore-dangle
	const $unreadCircleWrapper = $( '<div>' ).addClass( 'notification-unread-circle-wrapper' );
	const $unreadCircle = $( '<div>' ).addClass( 'notification-unread-circle' );

	$unreadCircleWrapper.append( $unreadCircle );

	const hasAgent = !data.agent_is_bot && data.entity_type === 'single_notification';
	const agent = hasAgent ? data.agent.display_name : '';
	const $message = $( '<div>' ).addClass( 'notification-message' );
	$message.html( agent ? agent + ' ' + data.message.main : data.message.main );

	// Timestamp
	const itemMoment = moment.utc( data.timestamp );
	itemMoment.local();

	const $timestamp = $( '<div>' ).addClass( 'notification-timestamp' );
	$timestamp.text( itemMoment.fromNow( false ) );

	const $badge = this.getWikiBadge( 40 );
	if ( $badge ) {
		this.$element.append( $badge );
		this.$element.addClass( 'notification-preview-item-has-wiki-badge' );
		$unreadCircle.css( 'background-color', this.getWikiColor() );
	}
	this.$element.append( [
		$unreadCircleWrapper,
		$message,
		$timestamp
	] );

	this.$element.addClass( 'notification-item' );

};

OO.inheritClass( ext.notifyme.ui.widget.NotificationPreviewItemWidget, OO.ui.Widget );
OO.mixinClass( ext.notifyme.ui.widget.NotificationPreviewItemWidget, mws.galaxyIntegration.ForeignWikiBadge );
