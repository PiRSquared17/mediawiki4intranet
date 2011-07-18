<?php
/**
 * @file
 * @ingroup upload
 *
 * Implements uploading from a HTTP resource.
 *
 * @author Bryan Tong Minh
 * @author Michael Dale
 */
class UploadFromUrl extends UploadBase {
	protected $mTempDownloadPath;

	/**
	 * Checks if the user is allowed to use the upload-by-URL feature. If the
	 * user is allowed, pass on permissions checking to the parent.
	 */
	public static function isAllowed( $user ) {
		if( !$user->isAllowed( 'upload_by_url' ) )
			return 'upload_by_url';
		return parent::isAllowed( $user );
	}

	/**
	 * Checks if the upload from URL feature is enabled
	 */
	public static function isEnabled() {
		global $wgAllowCopyUploads;
		return $wgAllowCopyUploads && parent::isEnabled();
	}

	/**
	 * Entry point for API upload
	 */
	public function initialize( $name, $url, $na, $nb = false ) {
		global $wgTmpDirectory;

		$localFile = tempnam( $wgTmpDirectory, 'WEBUPLOAD' );
		$this->initializePathInfo( $name, $localFile, 0, true );

		$this->mUrl = trim( $url );
	}

	/**
	 * Entry point for SpecialUpload
	 * @param $request Object: WebRequest object
	 */
	public function initializeFromRequest( &$request ) {
		$desiredDestName = $request->getText( 'wpDestFile' );
		if( !$desiredDestName )
			$desiredDestName = $request->getText( 'wpUploadFileURL' );
		return $this->initialize(
			$desiredDestName,
			$request->getVal( 'wpUploadFileURL' ),
			false
		);
	}

	/**
	 * @param $request Object: WebRequest object
	 */
	public static function isValidRequest( $request ){
		if( !$request->getVal( 'wpUploadFileURL' ) )
			return false;
		// check that is a valid url:
		return self::isValidUrl( $request->getVal( 'wpUploadFileURL' ) );
	}

	public static function isValidUrl( $url ) {
		// Only allow HTTP or FTP for now
		return (bool)preg_match( '!^(https?://|ftp://)!', $url );
	}

	/**
	 * Do the real fetching stuff
	 */
	function fetchFile() {
		if( !self::isValidUrl( $this->mUrl ) ) {
			return Status::newFatal( 'upload-proto-error' );
		}
		return $this->httpCopy();
	}

	/**
	 * Safe copy from URL
	 * Returns true if there was an error, false otherwise
	 */
	private function httpCopy() {
		global $wgOut;

		# Open temporary file
		$this->mDestHandle = @fopen( $this->mTempPath, "wb" );
		if( $this->mDestHandle === false ) {
			# Could not open temporary file to write in
			return 'upload-file-error';
		}

		$request = HttpRequest::factory( $this->mUrl, array( 'timeout' => 10 ) );
		$request->setCallback( array( $this, 'uploadCallback' ) );
		$status = $request->execute();

		fclose( $this->mDestHandle );
		unset( $this->mDestHandle );

		return $status;
	}

	/**
	 * Callback function for web transfer
	 * Write data to file unless we've passed the length limit;
	 * if so, abort immediately.
	 * @access private
	 */
	function uploadCallback( $ch, $data ) {
		global $wgMaxUploadSize;
		$length = strlen( $data );
		$this->mFileSize += $length;
		if( $this->mFileSize > $wgMaxUploadSize ) {
			return 0;
		}
		fwrite( $this->mDestHandle, $data );
		return $length;
	}
}
