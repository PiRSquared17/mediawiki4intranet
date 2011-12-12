<?php
/**
 * @file
 * @ingroup Media
 */

class SvgThumbnailImage extends ThumbnailImage
{
	function SvgThumbnailImage( $file, $url, $svgurl, $width, $height, $path = false, $page = false, $later = false ) {
		$this->svgurl = $svgurl;
		$this->later = $later;
		$this->ThumbnailImage( $file, $url, $width, $height, $path, $page );
	}
	static function scaleParam( $name, $value, $sw, $sh ) {
		if ( $name == 'viewBox' ) {
			$value = preg_split( '/\s+/', $value );
			$value[0] *= $sw; $value[1] *= $sh;
			$value[2] *= $sw; $value[3] *= $sh;
			$value = implode( ' ', $value );
		} elseif ( $name == 'width' ) {
			$value *= $sw;
		} else {
			$value *= $sh;
		}
		return "$name=\"$value\"";
	}
	function toHtml( $options = array() ) {
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
			$linkAttribs = array( 'href' => '' );
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

		$linkurl = $this->file->getUrl();

		if ( !empty( $linkAttribs['href'] ) ||
			$this->width != $this->file->getWidth() ||
			$this->height != $this->file->getHeight() ) {
			if ( empty( $linkAttribs['href'] ) ) {
				$linkAttribs['href'] = '';
			}
			if ( empty( $linkAttribs['title'] ) ) {
				$linkAttribs['title'] = '';
			}
			// :-( The only cross-browser way to link from SVG
			// is to add an <a xlink:href> into SVG image itself
			global $wgServer;
			$href = $linkAttribs['href'];
			if ( $href{0} == '/' ) {
				$href = $wgServer . $href;
			}
			$method = method_exists( $this->file, 'getPhys' ) ? 'getPhys' : 'getName';
			$hash = '/' . $this->file->$method() . '-linked-' . crc32( $href . "\0" .
				$linkAttribs['title'] . "\0" . $this->width . "\0" . $this->height ) . '.svg';
			$linkfn = $this->file->getThumbPath() . $hash;
			$linkurl = $this->file->getThumbUrl() . $hash;

			// Cache changed SVGs only when TRANSFORM_LATER is on
			$mtime = false;
			if ( $this->later ) {
				$mtime = @filemtime( $linkfn );
			}
			if ( !$mtime || $mtime < filemtime( $this->file->getPath() ) ) {
				// Load original SVG or SVGZ and extract opening element
				$svg = file_get_contents( 'compress.zlib://'.$this->file->getPath() );
				preg_match( '#<svg[^<>]*>#is', $svg, $m, PREG_OFFSET_CAPTURE );
				$closepos = strrpos( $svg, '</svg' );
				if ( $m && $closepos !== false ) {
					$open = $m[0][0];
					$openpos = $m[0][1];
					$openlen = strlen( $m[0][0] );
					$sw = $this->width / $this->file->getWidth();
					$sh = $this->height / $this->file->getHeight();
					$close = '';
					// Scale width, height and viewBox
					$open = preg_replace_callback( '/(viewBox|width|height)=[\'\"]([^\'\"]+)[\'\"]/',
						create_function( '$m', "return SvgThumbnailImage::scaleParam( \$m[1], \$m[2], $sw, $sh );" ), $open );
					// Add xlink namespace, if not yet
					if ( !strpos( $open, 'xmlns:xlink' ) ) {
						$open = substr( $open, 0, -1 ) . ' xmlns:xlink="http://www.w3.org/1999/xlink">';
					}
					if ( $sw < 0.99 || $sw > 1.01 || $sh < 0.99 || $sh > 1.01 ) {
						// Wrap contents into a scaled layer
						$open .= "<g transform='scale($sw $sh)'>";
						$close = "</g>$close";
					}
					// Wrap contents into a hyperlink
					if ( $href ) {
						$open .= '<a xlink:href="'.htmlspecialchars( $href ).
							'" target="_parent" xlink:title="'.htmlspecialchars( $linkAttribs['title'] ).'">';
						$close = "</a>$close";
					}
					// Write modified SVG
					$svg = substr( $svg, 0, $openpos ) . $open .
						substr( $svg, $openpos+$openlen, $closepos-$openpos-$openlen ) . $close .
						ltrim( substr( $svg, $closepos ), ">\t\r\n" );
					file_put_contents( $linkfn, $svg );
				}
				else {
					$linkurl = $this->file->getUrl();
				}
			}
		}

		// Output PNG <img> wrapped into SVG <object>
		$html = $this->linkWrap( $linkAttribs, Xml::element( 'img', $attribs ) );
		$html = Xml::tags( 'object', array(
			'type' => 'image/svg+xml',
			'data' => $linkurl,
			'style' => 'overflow: hidden; vertical-align: middle',
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
