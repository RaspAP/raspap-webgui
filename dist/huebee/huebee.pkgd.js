/*!
 * Huebee PACKAGED v2.1.0
 * 1-click color picker
 * MIT license
 * https://huebee.buzz
 * Copyright 2020 Metafizzy
 */

/**
 * EvEmitter v1.1.0
 * Lil' event emitter
 * MIT License
 */

/* jshint unused: true, undef: true, strict: true */

( function( global, factory ) {
  // universal module definition
  /* jshint strict: false */ /* globals define, module, window */
  if ( typeof define == 'function' && define.amd ) {
    // AMD - RequireJS
    define( 'ev-emitter/ev-emitter',factory );
  } else if ( typeof module == 'object' && module.exports ) {
    // CommonJS - Browserify, Webpack
    module.exports = factory();
  } else {
    // Browser globals
    global.EvEmitter = factory();
  }

}( typeof window != 'undefined' ? window : this, function() {

"use strict";

function EvEmitter() {}

var proto = EvEmitter.prototype;

proto.on = function( eventName, listener ) {
  if ( !eventName || !listener ) {
    return;
  }
  // set events hash
  var events = this._events = this._events || {};
  // set listeners array
  var listeners = events[ eventName ] = events[ eventName ] || [];
  // only add once
  if ( listeners.indexOf( listener ) == -1 ) {
    listeners.push( listener );
  }

  return this;
};

proto.once = function( eventName, listener ) {
  if ( !eventName || !listener ) {
    return;
  }
  // add event
  this.on( eventName, listener );
  // set once flag
  // set onceEvents hash
  var onceEvents = this._onceEvents = this._onceEvents || {};
  // set onceListeners object
  var onceListeners = onceEvents[ eventName ] = onceEvents[ eventName ] || {};
  // set flag
  onceListeners[ listener ] = true;

  return this;
};

proto.off = function( eventName, listener ) {
  var listeners = this._events && this._events[ eventName ];
  if ( !listeners || !listeners.length ) {
    return;
  }
  var index = listeners.indexOf( listener );
  if ( index != -1 ) {
    listeners.splice( index, 1 );
  }

  return this;
};

proto.emitEvent = function( eventName, args ) {
  var listeners = this._events && this._events[ eventName ];
  if ( !listeners || !listeners.length ) {
    return;
  }
  // copy over to avoid interference if .off() in listener
  listeners = listeners.slice(0);
  args = args || [];
  // once stuff
  var onceListeners = this._onceEvents && this._onceEvents[ eventName ];

  for ( var i=0; i < listeners.length; i++ ) {
    var listener = listeners[i]
    var isOnce = onceListeners && onceListeners[ listener ];
    if ( isOnce ) {
      // remove listener
      // remove before trigger to prevent recursion
      this.off( eventName, listener );
      // unset once flag
      delete onceListeners[ listener ];
    }
    // trigger listener
    listener.apply( this, args );
  }

  return this;
};

proto.allOff = function() {
  delete this._events;
  delete this._onceEvents;
};

return EvEmitter;

}));
/*!
 * Unipointer v2.3.0
 * base class for doing one thing with pointer event
 * MIT license
 */

/*jshint browser: true, undef: true, unused: true, strict: true */

( function( window, factory ) {
  // universal module definition
  /* jshint strict: false */ /*global define, module, require */
  if ( typeof define == 'function' && define.amd ) {
    // AMD
    define( 'unipointer/unipointer',[
      'ev-emitter/ev-emitter'
    ], function( EvEmitter ) {
      return factory( window, EvEmitter );
    });
  } else if ( typeof module == 'object' && module.exports ) {
    // CommonJS
    module.exports = factory(
      window,
      require('ev-emitter')
    );
  } else {
    // browser global
    window.Unipointer = factory(
      window,
      window.EvEmitter
    );
  }

}( window, function factory( window, EvEmitter ) {

'use strict';

function noop() {}

function Unipointer() {}

// inherit EvEmitter
var proto = Unipointer.prototype = Object.create( EvEmitter.prototype );

proto.bindStartEvent = function( elem ) {
  this._bindStartEvent( elem, true );
};

proto.unbindStartEvent = function( elem ) {
  this._bindStartEvent( elem, false );
};

/**
 * Add or remove start event
 * @param {Boolean} isAdd - remove if falsey
 */
proto._bindStartEvent = function( elem, isAdd ) {
  // munge isAdd, default to true
  isAdd = isAdd === undefined ? true : isAdd;
  var bindMethod = isAdd ? 'addEventListener' : 'removeEventListener';

  // default to mouse events
  var startEvent = 'mousedown';
  if ( window.PointerEvent ) {
    // Pointer Events
    startEvent = 'pointerdown';
  } else if ( 'ontouchstart' in window ) {
    // Touch Events. iOS Safari
    startEvent = 'touchstart';
  }
  elem[ bindMethod ]( startEvent, this );
};

// trigger handler methods for events
proto.handleEvent = function( event ) {
  var method = 'on' + event.type;
  if ( this[ method ] ) {
    this[ method ]( event );
  }
};

// returns the touch that we're keeping track of
proto.getTouch = function( touches ) {
  for ( var i=0; i < touches.length; i++ ) {
    var touch = touches[i];
    if ( touch.identifier == this.pointerIdentifier ) {
      return touch;
    }
  }
};

// ----- start event ----- //

proto.onmousedown = function( event ) {
  // dismiss clicks from right or middle buttons
  var button = event.button;
  if ( button && ( button !== 0 && button !== 1 ) ) {
    return;
  }
  this._pointerDown( event, event );
};

proto.ontouchstart = function( event ) {
  this._pointerDown( event, event.changedTouches[0] );
};

proto.onpointerdown = function( event ) {
  this._pointerDown( event, event );
};

/**
 * pointer start
 * @param {Event} event
 * @param {Event or Touch} pointer
 */
proto._pointerDown = function( event, pointer ) {
  // dismiss right click and other pointers
  // button = 0 is okay, 1-4 not
  if ( event.button || this.isPointerDown ) {
    return;
  }

  this.isPointerDown = true;
  // save pointer identifier to match up touch events
  this.pointerIdentifier = pointer.pointerId !== undefined ?
    // pointerId for pointer events, touch.indentifier for touch events
    pointer.pointerId : pointer.identifier;

  this.pointerDown( event, pointer );
};

proto.pointerDown = function( event, pointer ) {
  this._bindPostStartEvents( event );
  this.emitEvent( 'pointerDown', [ event, pointer ] );
};

// hash of events to be bound after start event
var postStartEvents = {
  mousedown: [ 'mousemove', 'mouseup' ],
  touchstart: [ 'touchmove', 'touchend', 'touchcancel' ],
  pointerdown: [ 'pointermove', 'pointerup', 'pointercancel' ],
};

proto._bindPostStartEvents = function( event ) {
  if ( !event ) {
    return;
  }
  // get proper events to match start event
  var events = postStartEvents[ event.type ];
  // bind events to node
  events.forEach( function( eventName ) {
    window.addEventListener( eventName, this );
  }, this );
  // save these arguments
  this._boundPointerEvents = events;
};

proto._unbindPostStartEvents = function() {
  // check for _boundEvents, in case dragEnd triggered twice (old IE8 bug)
  if ( !this._boundPointerEvents ) {
    return;
  }
  this._boundPointerEvents.forEach( function( eventName ) {
    window.removeEventListener( eventName, this );
  }, this );

  delete this._boundPointerEvents;
};

// ----- move event ----- //

proto.onmousemove = function( event ) {
  this._pointerMove( event, event );
};

proto.onpointermove = function( event ) {
  if ( event.pointerId == this.pointerIdentifier ) {
    this._pointerMove( event, event );
  }
};

proto.ontouchmove = function( event ) {
  var touch = this.getTouch( event.changedTouches );
  if ( touch ) {
    this._pointerMove( event, touch );
  }
};

/**
 * pointer move
 * @param {Event} event
 * @param {Event or Touch} pointer
 * @private
 */
proto._pointerMove = function( event, pointer ) {
  this.pointerMove( event, pointer );
};

// public
proto.pointerMove = function( event, pointer ) {
  this.emitEvent( 'pointerMove', [ event, pointer ] );
};

// ----- end event ----- //


proto.onmouseup = function( event ) {
  this._pointerUp( event, event );
};

proto.onpointerup = function( event ) {
  if ( event.pointerId == this.pointerIdentifier ) {
    this._pointerUp( event, event );
  }
};

proto.ontouchend = function( event ) {
  var touch = this.getTouch( event.changedTouches );
  if ( touch ) {
    this._pointerUp( event, touch );
  }
};

/**
 * pointer up
 * @param {Event} event
 * @param {Event or Touch} pointer
 * @private
 */
proto._pointerUp = function( event, pointer ) {
  this._pointerDone();
  this.pointerUp( event, pointer );
};

// public
proto.pointerUp = function( event, pointer ) {
  this.emitEvent( 'pointerUp', [ event, pointer ] );
};

// ----- pointer done ----- //

// triggered on pointer up & pointer cancel
proto._pointerDone = function() {
  this._pointerReset();
  this._unbindPostStartEvents();
  this.pointerDone();
};

proto._pointerReset = function() {
  // reset properties
  this.isPointerDown = false;
  delete this.pointerIdentifier;
};

proto.pointerDone = noop;

// ----- pointer cancel ----- //

proto.onpointercancel = function( event ) {
  if ( event.pointerId == this.pointerIdentifier ) {
    this._pointerCancel( event, event );
  }
};

proto.ontouchcancel = function( event ) {
  var touch = this.getTouch( event.changedTouches );
  if ( touch ) {
    this._pointerCancel( event, touch );
  }
};

/**
 * pointer cancel
 * @param {Event} event
 * @param {Event or Touch} pointer
 * @private
 */
proto._pointerCancel = function( event, pointer ) {
  this._pointerDone();
  this.pointerCancel( event, pointer );
};

// public
proto.pointerCancel = function( event, pointer ) {
  this.emitEvent( 'pointerCancel', [ event, pointer ] );
};

// -----  ----- //

// utility function for getting x/y coords from event
Unipointer.getPointerPoint = function( pointer ) {
  return {
    x: pointer.pageX,
    y: pointer.pageY
  };
};

// -----  ----- //

return Unipointer;

}));
/*!
 * Huebee v2.1.0
 * 1-click color picker
 * MIT license
 * https://huebee.buzz
 * Copyright 2020 Metafizzy
 */

/* jshint browser: true, unused: true, undef: true */

( function( window, factory ) {
  // universal module definition
  if ( typeof define == 'function' && define.amd ) {
    /* globals define */ // AMD
    define( [
      'ev-emitter/ev-emitter',
      'unipointer/unipointer',
    ], function( EvEmitter, Unipointer ) {
      return factory( window, EvEmitter, Unipointer );
    } );
  } else if ( typeof module == 'object' && module.exports ) {
    // CommonJS
    module.exports = factory(
        window,
        require('ev-emitter'),
        require('unipointer')
    );
  } else {
    // browser global
    window.Huebee = factory(
        window,
        window.EvEmitter,
        window.Unipointer
    );
  }

}( window, function factory( window, EvEmitter, Unipointer ) {

function Huebee( anchor, options ) {
  // anchor
  anchor = getQueryElement( anchor );
  if ( !anchor ) {
    throw new Error( 'Bad element for Huebee: ' + anchor );
  }
  this.anchor = anchor;
  // options
  this.options = {};
  this.option( Huebee.defaults );
  this.option( options );
  // kick things off
  this.create();
}

Huebee.defaults = {
  hues: 12,
  hue0: 0,
  shades: 5,
  saturations: 3,
  notation: 'shortHex',
  setText: true,
  setBGColor: true,
};

var proto = Huebee.prototype = Object.create( EvEmitter.prototype );

proto.option = function( options ) {
  this.options = extend( this.options, options );
};

// globally unique identifiers
var GUID = 0;
// internal store of all Colcade intances
var instances = {};

proto.create = function() {
  // add guid for Colcade.data
  var guid = this.guid = ++GUID;
  this.anchor.huebeeGUID = guid;
  instances[ guid ] = this; // associate via id
  // properties
  this.setBGElems = this.getSetElems( this.options.setBGColor );
  this.setTextElems = this.getSetElems( this.options.setText );
  // events
  // HACK: this is getting ugly
  this.outsideCloseIt = this.outsideClose.bind( this );
  this.onDocKeydown = this.docKeydown.bind( this );
  this.closeIt = this.close.bind( this );
  this.openIt = this.open.bind( this );
  this.onElemTransitionend = this.elemTransitionend.bind( this );
  // open events
  this.isInputAnchor = this.anchor.nodeName == 'INPUT';
  if ( !this.options.staticOpen ) {
    this.anchor.addEventListener( 'click', this.openIt );
    this.anchor.addEventListener( 'focus', this.openIt );
  }
  // change event
  if ( this.isInputAnchor ) {
    this.anchor.addEventListener( 'input', this.inputInput.bind( this ) );
  }
  // create element
  var element = this.element = document.createElement('div');
  element.className = 'huebee ';
  element.className += this.options.staticOpen ? 'is-static-open ' :
    'is-hidden ';
  element.className += this.options.className || '';
  // create container
  var container = this.container = document.createElement('div');
  container.className = 'huebee__container';
  // do not blur if padding clicked
  function onContainerPointerStart( event ) {
    if ( event.target == container ) {
      event.preventDefault();
    }
  }
  container.addEventListener( 'mousedown', onContainerPointerStart );
  container.addEventListener( 'touchstart', onContainerPointerStart );
  // create canvas
  this.createCanvas();
  // create cursor
  this.cursor = document.createElement('div');
  this.cursor.className = 'huebee__cursor is-hidden';
  container.appendChild( this.cursor );
  // create close button
  this.createCloseButton();

  element.appendChild( container );
  // set relative position on parent
  if ( !this.options.staticOpen ) {
    var parentStyle = getComputedStyle( this.anchor.parentNode );
    if ( parentStyle.position != 'relative' && parentStyle.position != 'absolute' ) {
      this.anchor.parentNode.style.position = 'relative';
    }
  }

  // satY, y position where saturation grid starts
  var customLength = this.getCustomLength();
  this.satY = customLength ? Math.ceil( customLength / this.options.hues ) + 1 : 0;
  // colors
  this.updateColors();
  this.setAnchorColor();
  if ( this.options.staticOpen ) {
    this.open();
  }
};

proto.getSetElems = function( option ) {
  if ( option === true ) {
    return [ this.anchor ];
  } else if ( typeof option == 'string' ) {
    return document.querySelectorAll( option );
  }
};

proto.getCustomLength = function() {
  var customColors = this.options.customColors;
  return customColors && customColors.length || 0;
};

proto.createCanvas = function() {
  var canvas = this.canvas = document.createElement('canvas');
  canvas.className = 'huebee__canvas';
  this.ctx = canvas.getContext('2d');
  // canvas pointer events
  var canvasPointer = this.canvasPointer = new Unipointer();
  canvasPointer._bindStartEvent( canvas );
  canvasPointer.on( 'pointerDown', this.canvasPointerDown.bind( this ) );
  canvasPointer.on( 'pointerMove', this.canvasPointerMove.bind( this ) );
  this.container.appendChild( canvas );
};

var svgURI = 'http://www.w3.org/2000/svg';

proto.createCloseButton = function() {
  if ( this.options.staticOpen ) {
    return;
  }
  var svg = document.createElementNS( svgURI, 'svg' );
  svg.setAttribute( 'class', 'huebee__close-button' );
  svg.setAttribute( 'viewBox', '0 0 24 24' );
  svg.setAttribute( 'width', '24' );
  svg.setAttribute( 'height', '24' );
  var path = document.createElementNS( svgURI, 'path' );
  path.setAttribute( 'd', 'M 7,7 L 17,17 M 17,7 L 7,17' );
  path.setAttribute( 'class', 'huebee__close-button__x' );
  svg.appendChild( path );
  svg.addEventListener( 'click', this.closeIt );
  this.container.appendChild( svg );
};

proto.updateColors = function() {
  // hash of color, h, s, l according to x,y grid position
  // [x,y] = { color, h, s, l }
  this.swatches = {};
  // hash of gridX,gridY position according to color
  // [#09F] = { x, y }
  this.colorGrid = {};
  this.updateColorModer();

  var shades = this.options.shades;
  var sats = this.options.saturations;
  var hues = this.options.hues;

  // render custom colors
  if ( this.getCustomLength() ) {
    var customI = 0;
    this.options.customColors.forEach( function( color ) {
      var x = customI % hues;
      var y = Math.floor( customI/hues );
      var swatch = getSwatch( color );
      if ( swatch ) {
        this.addSwatch( swatch, x, y );
        customI++;
      }
    }.bind( this ) );
  }

  // render saturation grids
  var i;
  for ( i = 0; i < sats; i++ ) {
    var sat = 1 - i/sats;
    var yOffset = shades * i + this.satY;
    this.updateSaturationGrid( i, sat, yOffset );
  }

  // render grays
  var grayCount = this.getGrayCount();
  for ( i = 0; i < grayCount; i++ ) {
    var lum = 1 - i / ( shades + 1 );
    var color = this.colorModer( 0, 0, lum );
    var swatch = getSwatch( color );
    this.addSwatch( swatch, hues + 1, i );
  }
};

// get shades + black & white; else 0
proto.getGrayCount = function() {
  return this.options.shades ? this.options.shades + 2 : 0;
};

proto.updateSaturationGrid = function( i, sat, yOffset ) {
  var shades = this.options.shades;
  var hues = this.options.hues;
  var hue0 = this.options.hue0;
  for ( var row = 0; row < shades; row++ ) {
    for ( var col = 0; col < hues; col++ ) {
      var hue = Math.round( col * 360/hues + hue0 ) % 360;
      var lum = 1 - ( row + 1 ) / ( shades + 1 );
      var color = this.colorModer( hue, sat, lum );
      var swatch = getSwatch( color );
      var gridY = row + yOffset;
      this.addSwatch( swatch, col, gridY );
    }
  }
};

proto.addSwatch = function( swatch, gridX, gridY ) {
  // add swatch color to hash
  this.swatches[ gridX + ',' + gridY ] = swatch;
  // add color to colorGrid
  this.colorGrid[ swatch.color.toUpperCase() ] = {
    x: gridX,
    y: gridY,
  };
};

var colorModers = {
  hsl: function( h, s, l ) {
    s = Math.round( s * 100 );
    l = Math.round( l * 100 );
    return 'hsl(' + h + ', ' + s + '%, ' + l + '%)';
  },
  hex: hsl2hex,
  shortHex: function( h, s, l ) {
    var hex = hsl2hex( h, s, l );
    return roundHex( hex );
  },
};

proto.updateColorModer = function() {
  this.colorModer = colorModers[ this.options.notation ] || colorModers.shortHex;
};

proto.renderColors = function() {
  var gridSize = this.gridSize * 2;
  for ( var position in this.swatches ) {
    var swatch = this.swatches[ position ];
    var duple = position.split(',');
    var gridX = duple[0];
    var gridY = duple[1];
    this.ctx.fillStyle = swatch.color;
    this.ctx.fillRect( gridX * gridSize, gridY * gridSize, gridSize, gridSize );
  }
};

proto.setAnchorColor = function() {
  if ( this.isInputAnchor ) {
    this.setColor( this.anchor.value );
  }
};

// ----- events ----- //

var docElem = document.documentElement;

proto.open = function() {
  /* jshint unused: false */
  if ( this.isOpen ) {
    return;
  }
  var anchor = this.anchor;
  var elem = this.element;
  if ( !this.options.staticOpen ) {
    elem.style.left = anchor.offsetLeft + 'px';
    elem.style.top = anchor.offsetTop + anchor.offsetHeight + 'px';
  }
  this.bindOpenEvents( true );
  elem.removeEventListener( 'transitionend', this.onElemTransitionend );
  // add huebee to DOM
  anchor.parentNode.insertBefore( elem, anchor.nextSibling );
  // measurements
  var duration = getComputedStyle( elem ).transitionDuration;
  this.hasTransition = duration && duration != 'none' && parseFloat( duration );

  this.isOpen = true;
  this.updateSizes();
  this.renderColors();
  this.setAnchorColor();

  // trigger reflow for transition
  /* eslint-disable-next-line no-unused-vars */
  var h = elem.offsetHeight;
  elem.classList.remove('is-hidden');
};

proto.bindOpenEvents = function( isAdd ) {
  if ( this.options.staticOpen ) {
    return;
  }
  var method = ( isAdd ? 'add' : 'remove' ) + 'EventListener';
  docElem[ method ]( 'mousedown', this.outsideCloseIt );
  docElem[ method ]( 'touchstart', this.outsideCloseIt );
  document[ method ]( 'focusin', this.outsideCloseIt );
  document[ method ]( 'keydown', this.onDocKeydown );
  this.anchor[ method ]( 'blur', this.closeIt );
};

proto.updateSizes = function() {
  var hues = this.options.hues;
  var shades = this.options.shades;
  var sats = this.options.saturations;
  var grayCount = this.getGrayCount();
  var customLength = this.getCustomLength();

  this.cursorBorder = parseInt( getComputedStyle( this.cursor ).borderTopWidth, 10 );
  this.gridSize = Math.round( this.cursor.offsetWidth - this.cursorBorder * 2 );
  this.canvasOffset = {
    x: this.canvas.offsetLeft,
    y: this.canvas.offsetTop,
  };
  var cols, rows;
  if ( customLength && !grayCount ) {
    // custom colors only
    cols = Math.min( customLength, hues );
    rows = Math.ceil( customLength/hues );
  } else {
    cols = hues + 2;
    rows = Math.max( shades * sats + this.satY, grayCount );
  }
  var width = this.canvas.width = cols * this.gridSize * 2;
  this.canvas.height = rows * this.gridSize * 2;
  this.canvas.style.width = width/2 + 'px';
};

// close if target is not anchor or element
proto.outsideClose = function( event ) {
  var isAnchor = this.anchor.contains( event.target );
  var isElement = this.element.contains( event.target );
  if ( !isAnchor && !isElement ) {
    this.close();
  }
};

var closeKeydowns = {
  13: true, // enter
  27: true, // esc
};

proto.docKeydown = function( event ) {
  if ( closeKeydowns[ event.keyCode ] ) {
    this.close();
  }
};

var supportsTransitions = typeof docElem.style.transform == 'string';

proto.close = function() {
  if ( !this.isOpen ) {
    return;
  }

  if ( supportsTransitions && this.hasTransition ) {
    this.element.addEventListener( 'transitionend', this.onElemTransitionend );
  } else {
    this.remove();
  }
  this.element.classList.add('is-hidden');

  this.bindOpenEvents( false );
  this.isOpen = false;
};

proto.remove = function() {
  var parent = this.element.parentNode;
  if ( parent.contains( this.element ) ) {
    parent.removeChild( this.element );
  }
};

proto.elemTransitionend = function( event ) {
  if ( event.target != this.element ) {
    return;
  }
  this.element.removeEventListener( 'transitionend', this.onElemTransitionend );
  this.remove();
};

proto.inputInput = function() {
  this.setColor( this.anchor.value );
};

// ----- canvas pointer ----- //

proto.canvasPointerDown = function( event, pointer ) {
  event.preventDefault();
  this.updateOffset();
  this.canvasPointerChange( pointer );
};

proto.updateOffset = function() {
  var boundingRect = this.canvas.getBoundingClientRect();
  this.offset = {
    x: boundingRect.left + window.pageXOffset,
    y: boundingRect.top + window.pageYOffset,
  };
};

proto.canvasPointerMove = function( event, pointer ) {
  this.canvasPointerChange( pointer );
};

proto.canvasPointerChange = function( pointer ) {
  var x = Math.round( pointer.pageX - this.offset.x );
  var y = Math.round( pointer.pageY - this.offset.y );
  var gridSize = this.gridSize;
  var sx = Math.floor( x/gridSize );
  var sy = Math.floor( y/gridSize );

  var swatch = this.swatches[ sx + ',' + sy ];
  this.setSwatch( swatch );
};

// ----- select ----- //

proto.setColor = function( color ) {
  var swatch = getSwatch( color );
  this.setSwatch( swatch );
};

proto.setSwatch = function( swatch ) {
  var color = swatch && swatch.color;
  if ( !swatch ) {
    return;
  }
  var wasSameColor = color == this.color;
  // color properties
  this.color = color;
  this.hue = swatch.hue;
  this.sat = swatch.sat;
  this.lum = swatch.lum;
  // estimate if color can have dark or white text
  var lightness = this.lum - Math.cos( ( this.hue + 70 )/180 * Math.PI ) * 0.15;
  this.isLight = lightness > 0.5;
  // cursor
  var gridPosition = this.colorGrid[ color.toUpperCase() ];
  this.updateCursor( gridPosition );
  // set texts & backgrounds
  this.setTexts();
  this.setBackgrounds();
  // event
  if ( !wasSameColor ) {
    this.emitEvent( 'change', [ color, swatch.hue, swatch.sat, swatch.lum ] );
  }
};

proto.setTexts = function() {
  if ( !this.setTextElems ) {
    return;
  }
  for ( var i = 0; i < this.setTextElems.length; i++ ) {
    var elem = this.setTextElems[i];
    var property = elem.nodeName == 'INPUT' ? 'value' : 'textContent';
    elem[ property ] = this.color;
  }
};

proto.setBackgrounds = function() {
  if ( !this.setBGElems ) {
    return;
  }
  var textColor = this.isLight ? '#222' : 'white';
  for ( var i = 0; i < this.setBGElems.length; i++ ) {
    var elem = this.setBGElems[i];
    elem.style.backgroundColor = this.color;
    elem.style.color = textColor;
  }
};

proto.updateCursor = function( position ) {
  if ( !this.isOpen ) {
    return;
  }
  // show cursor if color is on the grid
  var classMethod = position ? 'remove' : 'add';
  this.cursor.classList[ classMethod ]('is-hidden');

  if ( !position ) {
    return;
  }
  var gridSize = this.gridSize;
  var offset = this.canvasOffset;
  var border = this.cursorBorder;
  this.cursor.style.left = position.x * gridSize + offset.x - border + 'px';
  this.cursor.style.top = position.y * gridSize + offset.y - border + 'px';
};

// -------------------------- htmlInit -------------------------- //

var console = window.console;

function htmlInit() {
  var elems = document.querySelectorAll('[data-huebee]');
  for ( var i = 0; i < elems.length; i++ ) {
    var elem = elems[i];
    var attr = elem.getAttribute('data-huebee');
    var options;
    try {
      options = attr && JSON.parse( attr );
    } catch ( error ) {
      // log error, do not initialize
      if ( console ) {
        console.error( 'Error parsing data-huebee on ' + elem.className +
          ': ' + error );
      }
      continue;
    }
    // initialize
    new Huebee( elem, options );
  }
}

var readyState = document.readyState;
if ( readyState == 'complete' || readyState == 'interactive' ) {
  htmlInit();
} else {
  document.addEventListener( 'DOMContentLoaded', htmlInit );
}

// -------------------------- Huebee.data -------------------------- //

Huebee.data = function( elem ) {
  elem = getQueryElement( elem );
  var id = elem && elem.huebeeGUID;
  return id && instances[ id ];
};

// -------------------------- getSwatch -------------------------- //

// proxy canvas used to check colors
var proxyCanvas = document.createElement('canvas');
proxyCanvas.width = proxyCanvas.height = 1;
var proxyCtx = proxyCanvas.getContext('2d');

function getSwatch( color ) {
  // check that color value is valid
  proxyCtx.clearRect( 0, 0, 1, 1 );
  proxyCtx.fillStyle = '#010203'; // reset value
  proxyCtx.fillStyle = color;
  proxyCtx.fillRect( 0, 0, 1, 1 );
  var data = proxyCtx.getImageData( 0, 0, 1, 1 ).data;
  // convert to array, imageData not array, #10
  data = [ data[0], data[1], data[2], data[3] ];
  if ( data.join(',') == '1,2,3,255' ) {
    // invalid color
    return;
  }
  // convert rgb to hsl
  var hsl = rgb2hsl.apply( this, data );
  return {
    color: color.trim(),
    hue: hsl[0],
    sat: hsl[1],
    lum: hsl[2],
  };
}

// -------------------------- utils -------------------------- //

function extend( a, b ) {
  for ( var prop in b ) {
    a[ prop ] = b[ prop ];
  }
  return a;
}

function getQueryElement( elem ) {
  if ( typeof elem == 'string' ) {
    elem = document.querySelector( elem );
  }
  return elem;
}

function hsl2hex( h, s, l ) {
  var rgb = hsl2rgb( h, s, l );
  return rgb2hex( rgb );
}

// thx jfsiii
// https://github.com/jfsiii/chromath/blob/master/src/static.js#L312
/* eslint-disable max-statements-per-line */
function hsl2rgb( h, s, l ) {

  var C = ( 1 - Math.abs( 2 * l - 1 ) ) * s;
  var hp = h/60;
  var X = C * ( 1 - Math.abs( hp % 2 - 1 ) );
  var rgb, m;

  switch ( Math.floor( hp ) ) {
  case 0: rgb = [ C, X, 0 ]; break;
  case 1: rgb = [ X, C, 0 ]; break;
  case 2: rgb = [ 0, C, X ]; break;
  case 3: rgb = [ 0, X, C ]; break;
  case 4: rgb = [ X, 0, C ]; break;
  case 5: rgb = [ C, 0, X ]; break;
  default: rgb = [ 0, 0, 0 ];
  }

  m = l - ( C/2 );
  rgb = rgb.map( function( v ) {
    return v + m;
  } );

  return rgb;
}

function rgb2hsl( r, g, b ) {
  r /= 255; g /= 255; b /= 255;

  var M = Math.max( r, g, b );
  var m = Math.min( r, g, b );
  var C = M - m;
  var L = 0.5 * ( M + m );
  var S = C === 0 ? 0 : C / ( 1 - Math.abs( 2 * L - 1 ) );

  var h;
  if ( C === 0 ) {
    h = 0; // spec'd as undefined, but usually set to 0
  } else if ( M === r ) {
    h = ( ( g - b )/C ) % 6;
  } else if ( M === g ) {
    h = ( ( b - r )/C ) + 2;
  } else if ( M === b ) {
    h = ( ( r - g )/C ) + 4;
  }

  var H = 60 * h;

  return [ H, parseFloat( S ), parseFloat( L ) ];
}
/* eslint-enable max-statements-per-line */

function rgb2hex( rgb ) {
  var hex = rgb.map( function( value ) {
    value = Math.round( value * 255 );
    var hexNum = value.toString( 16 ).toUpperCase();
    // left pad 0
    hexNum = hexNum.length < 2 ? '0' + hexNum : hexNum;
    return hexNum;
  } );

  return '#' + hex.join('');
}

// #123456 -> #135
// grab first digit from hex
// not mathematically accurate, but makes for better palette
function roundHex( hex ) {
  return '#' + hex[1] + hex[3] + hex[5];
}

// --------------------------  -------------------------- //

return Huebee;

} ) );
