ext.notifyme.ui.widget.NotificationPreviewItemWidget = function ( data ) {
	ext.notifyme.ui.widget.NotificationPreviewItemWidget.parent.call( this, {} );
	this.$unreadCircleWrapper = $( '<div>' ).addClass( 'notification-unread-circle-wrapper' );
	this.$unreadCircle = $( '<div>' ).addClass( 'notification-unread-circle' );

	this.$unreadCircleWrapper.append( this.$unreadCircle );

	const hasAgent = !data.agent_is_bot && data.entity_type === 'single_notification';
	const agent = hasAgent ? data.agent.display_name : '';
	this.$message = $( '<div>' ).addClass( 'notification-message' );
	this.$message.html( agent ? agent + ' ' + data.message.main : data.message.main );

	// Timestamp
	const itemMoment = moment.utc( data.timestamp );
	itemMoment.local();

	this.$timestamp = $( '<div>' ).addClass( 'notification-timestamp' );
	this.$timestamp.text( itemMoment.fromNow( false ) );

	this.$element.append( [
		this.$unreadCircleWrapper,
		this.$message,
		this.$timestamp
	] );

	this.$element.addClass( 'notification-item' );
	mw.hook( 'notifyme.notification.preview.item' ).fire( this, data );
};

OO.inheritClass( ext.notifyme.ui.widget.NotificationPreviewItemWidget, OO.ui.Widget );
