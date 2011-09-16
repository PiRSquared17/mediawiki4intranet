<?php
/**
 * Main classes used by the Semantic Internal Objects extension.
 *
 * @author Yaron Koren
 * @contributor Vitaliy Filippov
 */

/**
 * Class that holds information on a single internal object, including all
 * its properties.
 */
class SIOInternalObject {
	protected $mMainTitle;
	protected $mIndex;
	protected $mData;

	public function SIOInternalObject( $title, $index ) {
		$this->mMainTitle = $title;
		$this->mIndex = $index;
		if ( SMW_VERSION >= '1.6' ) {
			$wp = new SMWDIWikiPage( $title->getDBkey().'#'.$index, $title->getNamespace(), $title->getInterwiki() );
		} else {
			$wp = SMWWikiPageValue::makePage( $title->getDBkey().'#'.$index, $title->getNamespace(), $title->getDBkey(), $title->getInterwiki() );
		}
		$this->mData = new SMWSemanticData( $wp );
	}

	public function addPropertyAndValue( $propName, $value ) {
		if ( SMW_VERSION >= '1.6' ) {
			$property = SMWDIProperty::newFromUserLabel( $propName );
		} else {
			$property = SMWPropertyValue::makeUserProperty( $propName );
		}

		if ( is_object( $value ) ) {
			$dataValue = $value;
		} else {
			$dataValue = SMWDataValueFactory::newPropertyObjectValue( $property, $value );
			if ( SMW_VERSION >= '1.6' ) {
				if ( $dataValue->isValid() ) {
					$dataValue = $dataValue->getDataItem();
				} else {
					$dataValue = NULL;
				}
			} elseif ( !$dataValue->isValid() ) {
				$dataValue = NULL;
			}
		}

		if ( $dataValue !== NULL ) {
			$this->mData->addPropertyObjectValue( $property, $dataValue );
		} // else - show an error message?
	}

	public function getName() {
		return $this->mMainTitle->getDBkey() . '#' . $this->mIndex;
	}

	public function getNamespace() {
		return $this->mMainTitle->getNamespace();
	}

	public function getData() {
		return $this->mData;
	}
}

/**
 * Class for all database-related actions.
 * This class exists mostly because SMWSQLStore2's functions makeSMWPageID()
 * and makeSMWPropertyID(), which are needed for the DB access, are both
 * protected, and thus can't be accessed externally.
 */
class SIOSQLStore extends SMWSQLStore2 {
	
	// TODO rewrite to smwfGetStore()->deleteSemanticData(new SMWDIWikiPage($pageName.'#'., $namespace, $interwiki))
	static function deleteDataForPage( $subject ) {
		$pageName = $subject->getDBKey();
		$namespace = $subject->getNamespace();
		$idsForDeletion = array();

		// Get the set of IDs for internal objects to be deleted.
		$iw = '';
		$db = wfGetDB( DB_SLAVE );
		$res = $db->select(
			'smw_ids',
			array( 'smw_id' ),
			'smw_title LIKE ' . $db->addQuotes( $pageName . '#%' ) . ' AND ' . 'smw_namespace=' . $db->addQuotes( $namespace ) . ' AND smw_iw=' . $db->addQuotes( $iw ),
			'SIO::getSMWPageObjectIDs'
		);
		
		while ( $row = $db->fetchObject( $res ) ) {
			$idsForDeletion[] = $row->smw_id;
		}

		if ( count( $idsForDeletion ) == 0 ) {
			return;
		}

		// Now, do the deletion.
		$db = wfGetDB( DB_MASTER );
		$idsString = '(' . implode ( ', ', $idsForDeletion ) . ')';
		$db->delete( 'smw_rels2', array( "(s_id IN $idsString) OR (o_id IN $idsString)" ), 'SIO::deleteRels2Data' );
		$db->delete( 'smw_atts2', array( "s_id IN $idsString" ), 'SIO::deleteAtts2Data' );
		$db->delete( 'smw_text2', array( "s_id IN $idsString" ), 'SIO::deleteText2Data' );
		$db->delete( 'smw_inst2', array( "s_id IN $idsString" ), 'SIO::deleteInst2Data' );
		// Handle the sm_coords table only if the Semantic Maps
		// extension is installed and uses sm_coords.
		if ( defined( 'SM_VERSION' ) && version_compare( SM_VERSION, '0.6' ) >= 0 ) {
			$db->delete( 'sm_coords', array( "s_id IN $idsString" ), 'SIO::deleteCoordsData' );
		}
	}

	static function createRDF( $title, $rdfDataArray, $fullexport = true, $backlinks = false ) {
		// if it's not a full export, don't add internal object data
		if ( !$fullexport ) {
			return true;
		}

		$pageName = $title->getDBkey();
		$namespace = $title->getNamespace();

		// Go through all SIOs for the current page, create RDF for
		// each one, and add it to the general array.
		$iw = '';
		$db = wfGetDB( DB_SLAVE );
		$res = $db->select(
			'smw_ids',
			array( 'smw_id', 'smw_namespace', 'smw_title' ),
			'smw_title LIKE ' . $db->addQuotes( $pageName . '#%' ) . ' AND ' . 'smw_namespace=' . $db->addQuotes( $namespace ) . ' AND smw_iw=' . $db->addQuotes( $iw ),
			'SIO::getSMWPageObjectIDs'
		);
		
		while ( $row = $db->fetchObject( $res ) ) {
			$value = new SIOInternalObjectValue( $row->smw_title, intval( $row->smw_namespace ) );
			if ( SMW_VERSION >= '1.6' ) {
				$semdata = new SMWSqlStubSemanticData( $value, false );
			} else {
				$semdata = new SMWSemanticData( $value, false );
			}
			$propertyTables = SMWSQLStore2::getPropertyTables();
			foreach ( $propertyTables as $tableName => $propertyTable ) {
				$data = smwfGetStore()->fetchSemanticData( $row->smw_id, null, $propertyTable );
				foreach ( $data as $d ) {
					$semdata->addPropertyStubValue( reset( $d ), end( $d ) );
				}
			}
			
			$rdfDataArray[] = SMWExporter::makeExportData( $semdata, null );
		}
		
		return true;
	}
}

/**
 * Class for hook functions for creating and storing information
 */
class SIOHandler {

	static $mCurPageFullName = '';
	static $mInternalObjectIndex = 1;
	static $mInternalObjects = array();
	static $mHandledPages = array();

	/**
	 * Called with the 'ParserClearState' hook, when more than one page
	 * is parsed in a single action.
	 */
	public static function clearState( &$parser ) {
		// For some reason, the #set_internal calls on a page are
		// sometimes called twice (or more?). Ideally, there would
		// be a way to prevent that, but until then, we use the
		// $mHandledPages array to store pages whose internal objects
		// have already been created - if doSetInternal() is called
		// on a page whose name is already is in this array, the
		// page is ignored.
		if ( ! empty( self::$mCurPageFullName ) ) {
			self::$mHandledPages[] = self::$mCurPageFullName;
		}
		
		self::$mCurPageFullName = '';
		self::$mInternalObjectIndex = 1;
		
		return true;
	}

	/**
	 * Handle the #set_internal parser function.
	 */
	public static function doSetInternal( &$parser ) {
		$title = $parser->getTitle();
		$mainPageFullName = $title->getText();
		if ( ( $nsText = $title->getNsText() ) != '' ) {
			$mainPageFullName = $nsText . ':' . $mainPageFullName;
		}

		if ( in_array( $mainPageFullName, self::$mHandledPages ) ) {
			// The #set_internal calls for this page have already
			// been processed! Skip it.
			return;
		}

		if ( $mainPageFullName == self::$mCurPageFullName ) {
			self::$mInternalObjectIndex++;
		} else {
			self::$mCurPageFullName = $mainPageFullName;
			self::$mInternalObjectIndex = 1;
		}
		
		$curObjectNum = self::$mInternalObjectIndex;
		$params = func_get_args();
		array_shift( $params ); // we already know the $parser...
		$internalObject = new SIOInternalObject( $title, $curObjectNum );
		$objToPagePropName = array_shift( $params );
		$internalObject->addPropertyAndValue( $objToPagePropName, self::$mCurPageFullName );
		
		foreach ( $params as $param ) {
			$parts = explode( '=', trim( $param ), 2 );
			
			if ( count( $parts ) == 2 ) {
				$key = $parts[0];
				$value = $parts[1];
				// if the property name ends with '#list', it's
				// a comma-delimited group of values
				if ( substr( $key, - 5 ) == '#list' ) {
					$key = substr( $key, 0, strlen( $key ) - 5 );
					$listValues = explode( ',', $value );
					
					foreach ( $listValues as $listValue ) {
						$internalObject->addPropertyAndValue( $key, trim( $listValue ) );
					}
				} else {
					$internalObject->addPropertyAndValue( $key, $value );
				}
			}
		}
		
		self::$mInternalObjects[] = $internalObject;
	}

	/**
	 * Handle the #set_internal_recurring_event parser function.
	 */
	public static function doSetInternalRecurringEvent( &$parser ) {
		$params = func_get_args();
		array_shift( $params ); // We already know the $parser ...

		// First param should be a standalone property name.
		$objToPagePropName = array_shift( $params );

		// The location of this function changed in SMW 1.5.3
		if ( SMW_VERSION >= '1.6' ) {
			$results = SMWSetRecurringEvent::getDatesForRecurringEvent( $params );
		} else {
			$results = SMWParserExtensions::getDatesForRecurringEvent( $params );
		}
		
		if ( $results == null ) {
			return null;
		}

		list( $property, $all_date_strings, $unused_params ) = $results;

		// Mimic a call to #set_internal for each date.
		foreach ( $all_date_strings as $date_string ) {
			$first_params = array(
				$parser,
				$objToPagePropName,
				"$property=$date_string"
			);
			
			$cur_params = array_merge( $first_params, $unused_params );
			call_user_func_array( 'SIOHandler::doSetInternal', $cur_params );
		}
	}

	/**
	 * Called when a page is deleted.
	 */
	public static function deleteData( $sqlStore, $subject ) {
		SIOSQLStore::deleteDataForPage( $subject );
		return true;
	}

	/**
	 * Called when a page's semantic data is updated - either it's been
	 * modified, or one of its templates has been modified, or an SMW
	 * "refresh data" action has been called.
	 */
	public static function updateData( $sqlStore, $data ) {
		// Don't try to update internal objects of internal objects
		$subject = $data->getSubject();
		if ( preg_match( '/#\d+$/', $subject->getDBkey() ) ) {
			return true;
		}
		// Copy subject categories to all internal objects
		$values = $data->getPropertyValues(
			SMW_VERSION >= '1.6'
			? new SMWDIProperty( '_INST' )
			: SMWPropertyValue::makeProperty( SMW_SP_INSTANCE_OF )
		);
		foreach ( self::$mInternalObjects as $object ) {
			foreach ( $values as $v ) {
				$object->addPropertyAndValue( '_INST', $v );
			}
		}
		// Update data
		foreach ( self::$mInternalObjects as $object ) {
			smwfGetStore()->updateData( $object->getData() );
		}
		self::$mInternalObjects = array();
		return true;
	}

	/**
	 * Called after a page is moved - renames all the internal objects
	 * named "Old page#x" to "New page#x".
	 */
	static public function handlePageMove( &$old_title, &$new_title, &$user, $page_id, $redir_id ) {
		$oldPageName = $old_title->getDBkey();
		$oldNamespace = $old_title->getNamespace();
		$newPageName = $new_title->getDBkey();
		$newNamespace = $new_title->getNamespace();
		$iw = '';
		$sioNames = array();
		$db = wfGetDB( DB_SLAVE );
		// Unfortunately, there's no foolproof way to do the replacement
		// with a single SQL call, using regexps and wildcards -
		// instead, we first get the set of all matching entries in
		// the 'smw_ids' table, then call an explicit update on each
		// one.
		$res = $db->select(
			'smw_ids',
			array( 'smw_title' ),
			'smw_title LIKE ' . $db->addQuotes( $oldPageName . '#%' ) . ' AND ' . 'smw_namespace=' . $db->addQuotes( $oldNamespace ) . ' AND smw_iw=' . $db->addQuotes( $iw ),
			'SIO::getTitlesForPageMove'
		);
		
		while ( $row = $db->fetchObject( $res ) ) {
			$sioNames[] = $row->smw_title;
		}
		
		foreach ( $sioNames as $sioName ) {
			// update the name, and possibly the namespace as well
			$newSIOName = str_replace( $oldPageName, $newPageName, $sioName );
			$db->update(
				'smw_ids',
				array( 'smw_title' => $newSIOName, 'smw_namespace' => $newNamespace ),
				array( 'smw_title' => $sioName, 'smw_namespace' => $oldNamespace ),
				'SIO::updateTitlesForPageMove'
			);
		}
		
		return true;
	}

	/**
	 * Takes a set of SMW "update jobs", and keeps only the unique, actual
	 * titles among them - this is useful if there are any internal objects
	 * among the group; a set of names like "Page name#1", "Page name#2"
	 * etc. should be turned into just "Page name".
	 */
	static function handleUpdatingOfInternalObjects( &$jobs ) {
		$uniqueTitles = array();
		
		foreach ( $jobs as $i => $job ) {
			$title = Title::makeTitleSafe( $job->title->getNamespace(), $job->title->getText() );
			$id = $title->getArticleID();
			$uniqueTitles[$id] = $title;
		}
		
		$jobs = array();
		
		foreach ( $uniqueTitles as $id => $title ) {
			$jobs[] = new SMWUpdateJob( $title );
		}
		
		return true;
	}

	/**
	 * Takes a set of SMW "update jobs" generated by refresh data and removes
	 * any job with a fragment (in other words a job trying to update a SIO object)
	 * We aren't guaranteed that all the jobs related to a single page using SIO
	 * will be in a single one of these batches so we remove everything updating
	 * a SIO object instead of filtering them down to unique titles.
	 */
	 static function handleRefreshingOfInternalObjects( &$jobs ) {
	 	$allJobs = $jobs;
	 	$jobs = array();
	 	
	 	foreach ( $allJobs as $job ) {
	 		if ( strpos( $job->title->getText(), '#' ) === false ) {
	 			$jobs[] = $job;
	 		}
	 	}
	 	
		return true;
	}
	
}
