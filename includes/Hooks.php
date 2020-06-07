<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file Hooks.php
 * @author Daniel Beard
 */

namespace MediaWiki\Extension\UniqueLink;

/**
 * A class which contains a number of static functions, which are called by
 * MediaWiki core when the relevant events happen.
 */
class Hooks {

  /**
   * Hook called when parser is first initialised. Initialises the link store
   * object that records all pages uniquely linked to, and registers the parser
   * functions.
   * 
   * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
   * @param \Parser $parser the parser object that is being initialised
   */
  public static function onParserFirstCallInit( \Parser $parser ) {
    global $egUniqueLinkDisabled;
    
    // Do nothing if the extension has been deliberately disabled
    if ( $egUniqueLinkDisabled ) {
      return;
    }
    
    // Otherwise, initialise the unique link store...
    LinkStore::resetLinkStore( $parser );

    // ... and register the parser functions.
    self::registerParserFuncNorm( $parser, 'uniquelink' );
    self::registerParserFuncNorm( $parser, 'uniquelinkifexists' );
    self::registerParserFuncObj( $parser, 'alreadylinkeduniquely' );
  }

  /**
   * Registers a parser function with the parser, using the normal function
   * parameters (i.e. no SFH_OBJECT_ARGS flag).
   * 
   * @param \Parser $parser the parser to register the parser function with
   * @param string $name the name of the parser function to register
   */
  private static function registerParserFuncNorm(
    \Parser $parser, string $name
  ) : void {
    global $egUniqueLinkDisabledFunctions;

    // Do nothing if the parser function is disabled
    if ( in_array( $name, $egUniqueLinkDisabledFunctions ) ) {
      return;
    }

    // Otherwise, register the function
    $parser->setFunctionHook(
      $name,
      [ LinkStore::class, "parserFunc_$name" ]
    );
  }

  /**
   * Registers a parser function with the parser, using the SFH object function
   * parameters (i.e. with the SFH_OBJECT_ARGS flag enabled).
   * 
   * @param \Parser $parser the parser to register the parser function with
   * @param string $name the name of the parser function to register
   */
  private static function registerParserFuncObj(
    \Parser $parser, string $name
  ) : void {
    global $egUniqueLinkDisabledFunctions;

    // Do nothing if the parser function is disabled
    if ( in_array( $name, $egUniqueLinkDisabledFunctions ) ) {
      return;
    }

    // Otherwise, register the function
    $parser->setFunctionHook(
      $name,
      [ LinkStore::class, "parserFuncObj_$name" ],
      \Parser::SFH_OBJECT_ARGS
    );
  }

  /**
   * Hook called when parser is having its state cleared, so it can parse
   * another page. Resets the link store, so that the list of pages already
   * linked to by the uniquelink parser functions is not carried from page to
   * page
   * 
   * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserClearState
   * @param \Parser $parser the parser object that is having its state cleared
   */
  public static function onParserClearState( \Parser $parser ) {
    LinkStore::resetLinkStore( $parser );
  }
}

?>