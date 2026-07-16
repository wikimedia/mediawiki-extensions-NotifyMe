ext.notifyme.ui.panel.IndividualSubscriptions = function ( cfg ) {
	cfg = cfg || {};
	cfg.expanded = false;
	cfg.padded = false;
	cfg.classes = [ 'ext-notifyme-subscription-section' ];

	ext.notifyme.ui.panel.IndividualSubscriptions.parent.call( this, cfg );
	this.setRegistry = ext.notifyme.subscriptionSetRegistry;
	this.buckets = cfg.buckets || [];
	this.events = cfg.events || [];
	this.channelLabels = cfg.channelLabels || {};

	this.$element.addClass( 'ext-notifyme-individual-subscriptions' );
	this.makeLayout();

	this.setValue( cfg.data || [] );
};

OO.inheritClass( ext.notifyme.ui.panel.IndividualSubscriptions, OO.ui.PanelLayout );

ext.notifyme.ui.panel.IndividualSubscriptions.prototype.makeLayout = function () {

	this.eventPanel = new OO.ui.PanelLayout( {
		padded: false,
		expanded: false
	} );
	this.buildEvents();

	this.deliveryPanel = new OO.ui.FieldsetLayout( {
		padded: true,
		expanded: false,
		label: mw.message( 'notifyme-ui-delivery-channels-label' ).text(),
		classes: [ 'ext-notifyme-subscription-section' ]
	} );
	this.buildDelivery();

	const mainLayout = new OO.ui.FieldsetLayout( {
		padded: false,
		items: [
			new OO.ui.LabelWidget( {
				label: mw.message( 'notifyme-ui-subscription-set-watchlist-label' ).text(),
				classes: [ 'ext-notifyme-subscription-set-label' ]
			} ),
			this.eventPanel,
			this.deliveryPanel
		]
	} );

	this.$element.append( mainLayout.$element );
};

ext.notifyme.ui.panel.IndividualSubscriptions.prototype.buildEvents = function () {
	this.eventControls = {};
	for ( const bucketKey in this.events ) {
		if ( !this.buckets.hasOwnProperty( bucketKey ) ) {
			continue;
		}
		if ( this.buckets[ bucketKey ].mandatory ) {
			continue;
		}
		const events = this.events[ bucketKey ];
		const layouts = [];
		for ( const eventKey in events ) {
			const eventControl = new OO.ui.CheckboxInputWidget( {
				data: bucketKey
			} );
			eventControl.connect( this, {
				change: function () {
					this.emit( 'change' );
				}
			} );
			layouts.push( new OO.ui.FieldLayout( eventControl, {
				align: 'inline',
				label: events[ eventKey ],
				classes: [ 'ext-notifyme-subscription-event-layout' ]
			} ) );

			this.eventControls[ eventKey ] = eventControl;
		}

		this.eventPanel.$element.append(
			new OO.ui.FieldsetLayout( {
				label: this.buckets[ bucketKey ].label,
				items: layouts
			} ).$element
		);

	}
};

ext.notifyme.ui.panel.IndividualSubscriptions.prototype.getValue = function () {
	const value = {
		subscriptions: {},
		delivery: this.deliveryControl.findSelectedItemsData()
	};
	for ( const eventKey in this.eventControls ) {
		value.subscriptions[ eventKey ] = this.eventControls[ eventKey ].isSelected();
	}

	return value;
};

ext.notifyme.ui.panel.IndividualSubscriptions.prototype.setValue = function ( value ) {
	const subscriptions = value.subscriptions || {};
	for ( const event in subscriptions ) {
		if ( !this.eventControls.hasOwnProperty( event ) ) {
			continue;
		}
		this.eventControls[ event ].setSelected( subscriptions[ event ] );
	}
	this.deliveryControl.selectItemsByData( value.delivery || [] );
};

ext.notifyme.ui.panel.IndividualSubscriptions.prototype.buildDelivery = function () {
	const options = [];
	for ( const channelKey in this.channelLabels ) {
		if ( channelKey === 'web' ) {
			// Web is unsubscribable
			continue;
		}
		options.push( new OO.ui.CheckboxMultioptionWidget( {
			data: channelKey,
			label: this.channelLabels[ channelKey ].label
		} ) );
	}
	this.deliveryControl = new OO.ui.CheckboxMultiselectWidget( {
		items: options
	} );
	this.deliveryControl.connect( this, {
		change: function () {
			this.emit( 'change' );
		}
	} );
	this.deliveryPanel.$element.append(
		new OO.ui.LabelWidget( {
			label: mw.message( 'notifyme-ui-subscription-delivery-help' ).text(),
			classes: [ 'ext-notifyme-subscription-set-label' ]
		} ).$element
	);
	this.deliveryPanel.$element.append( this.deliveryControl.$element );

};
