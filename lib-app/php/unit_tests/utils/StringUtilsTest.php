<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/utils/string_utils.php" ) ;

class StringUtilsTest extends PHPUnit_Framework_TestCase {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	function testPregConversion() {

		$pattern = "/php/san?y/**/profile.*" ;
		$stringThatShouldMatch    = "/php/sandy/folderA/folderB/folderC/profile.php" ;
		$stringThatShouldNotMatch = "/php/sandy/folderA/folderC/profile.php/zing" ;

		$this->assertTrue(  StringUtils::matchSimplePattern( 
			                $pattern, $stringThatShouldMatch ) ) ;

		$this->assertFalse( StringUtils::matchSimplePattern( 
							$pattern, $stringThatShouldNotMatch ) ) ;
	}	

	// https://ant.apache.org/manual/dirtasks.html
	function testAntFilterTestCases() {

		$pattern = "**/CVS/*" ;

		$this->assertTrue( StringUtils::matchSimplePattern( $pattern,
			               "org/apache/CVS/Entries" ) ) ;
		$this->assertTrue( StringUtils::matchSimplePattern( $pattern,
			               "CVS/Repository" ) ) ;
		$this->assertTrue( StringUtils::matchSimplePattern( $pattern,
			               "org/apache/jakarta/tools/ant/CVS/Entries" ) ) ;

		$this->assertFalse( StringUtils::matchSimplePattern( $pattern,
			                "org/apache/CVS/foo/bar/Entries" ) ) ;
	}

	function testAntFilterTestCases1() {

		$pattern = "org/apache/jakarta/**" ;

		$this->assertTrue( StringUtils::matchSimplePattern( $pattern,
			               "org/apache/jakarta/tools/ant/docs/index.html" ) ) ;
		$this->assertTrue( StringUtils::matchSimplePattern( $pattern,
			               "org/apache/jakarta/test.xml" ) ) ;

		$this->assertFalse( StringUtils::matchSimplePattern( $pattern,
			                "org/apache/xyz.java" ) ) ;
	}

	function testAntFilterTestCases2() {

		$pattern = "org/apache/**/CVS/*" ;

		$this->assertTrue( StringUtils::matchSimplePattern( $pattern,
			               "org/apache/CVS/Entries" ) ) ;
		$this->assertTrue( StringUtils::matchSimplePattern( $pattern,
			               "org/apache/jakarta/tools/ant/CVS/Entries" ) ) ;

		$this->assertFalse( StringUtils::matchSimplePattern( $pattern,
			                "org/apache/CVS/foo/bar/Entries" ) ) ;
	}

	function testAntFilterTestCases3() {

		$pattern = "**/test/**" ;

		$this->assertTrue( StringUtils::matchSimplePattern( $pattern,
			               "org/apache/test/abra/ca/dabra" ) ) ;
		$this->assertTrue( StringUtils::matchSimplePattern( $pattern,
			               "com/test/CVS/Entries" ) ) ;

		$this->assertFalse( StringUtils::matchSimplePattern( $pattern,
			                "org/quick/brown/fox/" ) ) ;
	}
}

?>