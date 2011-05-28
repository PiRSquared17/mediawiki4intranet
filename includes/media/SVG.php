<?php
/**
 * @file
 * @ingroup Media
 */

class SvgThumbnailImage extends ThumbnailImage
{
	function SvgThumbnailImage( $file, $url, $svgurl, $width, $height, $path = false, $page = false, $later = false )
	{
		$this->svgurl = $svgurl;
		$this->later = $later;
		$this->ThumbnailImage( $file, $url, $width, $height, $path, $page );
	}
	function toHtml( $options = array() )
	{
		if ( count( func_get_args() ) == 2 ) {
			throw new MWException( __METHOD__ .' called in the old style' );
		}

		$alt = empty( $options['alt'] ) ? '' : $options['alt'];
		$query = empty( $options['desc-query'] )  ? '' : $options['desc-query'];

		if ( !empty( $options['custom-url-link'] ) ) {
			$linkAttribs = array( 'href' => $options['custom-url-link'] );
			if ( !empty( $options['title'] ) ) {
				$linkAttribs['title'] = $options['title'];
			}
		} elseif ( !empty( $options['custom-title-link'] ) ) {
			$title = $options['custom-title-link'];
			$linkAttribs = array(
				'href' => $title->getLinkUrl(),
				'title' => empty( $options['title'] ) ? $title->getFullText() : $options['title']
			);
		} elseif ( !empty( $options['desc-link'] ) ) {
			$linkAttribs = $this->getDescLinkAttribs( empty( $options['title'] ) ? null : $options['title'], $query );
		} elseif ( !empty( $options['file-link'] ) ) {
			$linkAttribs = array( 'href' => $this->file->getURL() );
		} else {
			$linkAttribs = false;
		}

		$attribs = array(
			'alt' => $alt,
			'src' => $this->url,
			'width' => $this->width,
			'height' => $this->height,
		);
		if ( !empty( $options['valign'] ) ) {
			$attribs['style'] = "vertical-align: {$options['valign']}";
		}
		if ( !empty( $options['img-class'] ) ) {
			$attribs['class'] = $options['img-class'];
		}

		// :-( The only cross-browser way to link from SVG
		// is to add an <a xlink:href> into SVG image itself
		global $wgServer;
		$href = $linkAttribs['href'];
		if ( $href{0} == '/' )
			$href = $wgServer . $href;
		$method = method_exists( $this->file, 'getPhys' ) ? 'getPhys' : 'getName';
		$hash = '/' . $this->file->$method() . '-linked-' . crc32( $href . "\0" . $linkAttribs['title'] ) . '.svg';
		$linkfn = $this->file->getThumbPath() . $hash;
		$linkurl = $this->file->getThumbUrl() . $hash;
		// Cache changed SVGs only when TRANSFORM_LATER is on
		if ( $this->later )
			$mtime = @filemtime( $linkfn );
		if ( !$mtime || $mtime < filemtime( $this->file->getPath() ) )
		{
			$svg = file_get_contents( $this->file->getPath() );
			preg_match( '/<svg[^<>]*>/', $svg, $m, PREG_OFFSET_CAPTURE );
			$open = $m[0][0];
			if ( !strpos( $open, 'xmlns:xlink' ) )
				$open = substr( $open, 0, -1 ) . ' xmlns:xlink="http://www.w3.org/1999/xlink">';
			$open .= '<a xlink:href="'.htmlspecialchars( $href ).
				'" target="_parent" xlink:title="'.htmlspecialchars( $linkAttribs['title'] ).'">';
			$svg = substr( $svg, 0, $m[0][1] ) . $open . substr( $svg, $m[0][1] + strlen( $m[0][0] ) );
			$svg = str_replace( '</svg>', '</a></svg>', $svg );
			file_put_contents( $linkfn, $svg );
		}

		$html = $this->linkWrap( $linkAttribs, Xml::element( 'img', $attribs ) );
		$html = Xml::tags( 'object', array(
			'type' => 'image/svg+xml',
			'data' => $linkurl,
			'style' => 'overflow: hidden',
			'width' => $this->width,
			'height' => $this->height,
		), $html );
		return $html;
	}
}

/**
 * @ingroup Media
 */
class SvgHandler extends ImageHandler {
	function isEnabled() {
		global $wgSVGConverters, $wgSVGConverter;
		if ( !isset( $wgSVGConverters[$wgSVGConverter] ) ) {
			wfDebug( "\$wgSVGConverter is invalid, disabling SVG rendering.\n" );
			return false;
		} else {
			return true;
		}
	}

	function mustRender( $file ) {
		return true;
	}

	function normaliseParams( $image, &$params ) {
		global $wgSVGMaxSize;
		if ( !parent::normaliseParams( $image, $params ) ) {
			return false;
		}
		// Don't make an image bigger than wgMaxSVGSize
		$params['physicalWidth'] = $params['width'];
		$params['physicalHeight'] = $params['height'];
		if ( $params['physicalWidth'] > $wgSVGMaxSize ) {
			$srcWidth = $image->getWidth( $params['page'] );
			$srcHeight = $image->getHeight( $params['page'] );
			$params['physicalWidth'] = $wgSVGMaxSize;
			$params['physicalHeight'] = File::scaleHeight( $srcWidth, $srcHeight, $wgSVGMaxSize );
		}
		return true;
	}

	function doTransform( $image, $dstPath, $dstUrl, $params, $flags = 0 ) {
		if ( !$this->normaliseParams( $image, $params ) ) {
			return new TransformParameterError( $params );
		}
		$clientWidth = $params['width'];
		$clientHeight = $params['height'];
		$physicalWidth = $params['physicalWidth'];
		$physicalHeight = $params['physicalHeight'];
		$srcPath = $image->getPath();

		if ( $flags & self::TRANSFORM_LATER ) {
			return new SvgThumbnailImage( $image, $dstUrl, $image->getFullUrl(), $clientWidth, $clientHeight, $dstPath, false, true );
		}

		if ( !wfMkdirParents( dirname( $dstPath ) ) ) {
			return new MediaTransformError( 'thumbnail_error', $clientWidth, $clientHeight,
				wfMsg( 'thumbnail_dest_directory' ) );
		}
		
		$status = $this->rasterize( $srcPath, $dstPath, $physicalWidth, $physicalHeight );
		if( $status === true ) {
			return new SvgThumbnailImage( $image, $dstUrl, $image->getFullUrl(), $clientWidth, $clientHeight, $dstPath );
		} else {
			return $status; // MediaTransformError
		}
	}
	
	/*
	* Transform an SVG file to PNG
	* This function can be called outside of thumbnail contexts
	* @param string $srcPath
	* @param string $dstPath
	* @param string $width
	* @param string $height
	* @returns TRUE/MediaTransformError
	*/
	public function rasterize( $srcPath, $dstPath, $width, $height ) {
		global $wgSVGConverters, $wgSVGConverter, $wgSVGConverterPath;
		$err = false;
		if ( isset( $wgSVGConverters[$wgSVGConverter] ) ) {
			$cmd = str_replace(
				array( '$path/', '$width', '$height', '$input', '$output' ),
				array( $wgSVGConverterPath ? wfEscapeShellArg( "$wgSVGConverterPath/" ) : "",
					   intval( $width ),
					   intval( $height ),
					   wfEscapeShellArg( $srcPath ),
					   wfEscapeShellArg( $dstPath ) ),
				$wgSVGConverters[$wgSVGConverter]
			) . " 2>&1";
			wfProfileIn( 'rsvg' );
			wfDebug( __METHOD__.": $cmd\n" );
			$err = wfShellExec( $cmd, $retval );
			wfProfileOut( 'rsvg' );
		}
		$removed = $this->removeBadFile( $dstPath, $retval );
		if ( $retval != 0 || $removed ) {
			wfDebugLog( 'thumbnail', sprintf( 'thumbnail failed on %s: error %d "%s" from "%s"',
					wfHostname(), $retval, trim($err), $cmd ) );
			return new MediaTransformError( 'thumbnail_error', $width, $height, $err );
		}
		return true;
	}

	function getImageSize( $image, $path ) {
		return wfGetSVGsize( $path );
	}

	function getThumbType( $ext, $mime ) {
		return array( 'png', 'image/png' );
	}

	function getLongDesc( $file ) {
		global $wgLang;
		return wfMsgExt( 'svg-long-desc', 'parseinline',
			$wgLang->formatNum( $file->getWidth() ),
			$wgLang->formatNum( $file->getHeight() ),
			$wgLang->formatSize( $file->getSize() ) );
	}
}
