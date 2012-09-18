/**
 * SlimboxThumbs extensions /rewritten/
 * License: GNU GPL 3.0 or later
 * Contributor(s): Vitaliy Filippov <vitalif@mail.ru>
 */

function makeSlimboxThumbs( $, pathRegexp, wgFullScriptPath ) {
	var re = new RegExp( pathRegexp );
	var canview = /\.(jpe?g|jpe|gif|png)$/i;
	var m;
	var names = [];
	var links = {};
	$( 'img' ).each( function( i, e ) {
		if ( e.parentNode.nodeName == 'A' && ( m = re.exec( e.parentNode.href ) ) ) {
			var n = unescape( m[1] );
			names.push( n );
			links[ n ] = e.parentNode;
		}
	} );
	if ( names.length ) {
		sajax_request_type = 'POST';
		sajax_do_call( 'efSBTGetImageSizes', [ names.join( ':' ) ], function( r ) {
			var nodes = [];
			var can;
			var ww = $( window ).width();
			var wh = $( window ).height() * 0.9;
			r = $.parseJSON( r.responseText );
			for ( var n in r ) {
				var h = r[n][2];
				can = canview.exec( n );
				if ( !can || r[n][0] > ww || r[n][1] > wh ) {
					var sc = ww;
					var sh = Math.round( r[n][0] * wh / r[n][1] );
					if ( sh < sc ) {
						sc = sh;
					}
					h = wgFullScriptPath + '/thumb.php?f=' + escape( n ) + '&w=' + sc;
				}
				links[n]._lightbox = [
					h, '<a href="'+links[n].href+'">'+
					n.replace( /_/g, ' ' )+'</a>'
				];
				nodes.push( links[n] );
			}
			$( nodes ).slimbox({ captionAnimationDuration: 0 }, function( e, i ) {
				return e._lightbox;
			}, function() { return true; });
		} );
	}
}
